<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NoAnswerLeadController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['super_admin', 'admin'])
                && $request->user()->can('leads.assign'),
            403,
            'You do not have permission to reassign leads.'
        );
    }

    private function eligibleQuery(): Builder
    {
        return Lead::query()
            ->whereRaw(
                'LOWER(TRIM(call_status)) IN (?, ?)',
                ['not answer', 'no answer']
            )
            ->has('callHistories', '=', 1);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        // Super admin can view all companies.
        // Admin can view only their own company.
        $query = $this->eligibleQuery();

        if (!$isSuperAdmin) {
            abort_unless($actor->company_id, 403);

            $query->where('company_id', $actor->company_id);
        }

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('assigned_to', $filters['user_id']);
        }

        $leads = $query
            ->with([
                'assignedUser:id,name',
                'callHistories' => function ($query) {
                    $query->with('user:id,name');
                },
            ])
            ->withCount('callHistories')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Existing assignees for filtering.
        $filterUsersQuery = User::query();

        // Active employees for reassignment.
        $employeesQuery = User::query()
            ->where('is_active', true)
            ->whereNotNull('company_id');

        if (!$isSuperAdmin) {
            $filterUsersQuery->where('company_id', $actor->company_id);
            $employeesQuery->where('company_id', $actor->company_id);
        }

        return view('calling-crm.no-answer', [
            'title' => 'No Answer — 1 Call',
            'leads' => $leads,
            'filterUsers' => $filterUsersQuery
                ->orderBy('name')
                ->get(['id', 'name']),
            'employees' => $employeesQuery
                ->orderBy('name')
                ->get(['id', 'name', 'company_id']),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function reassign(Request $request)
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:100'],
            'lead_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('is_active', true),
            ],
        ]);

        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        $count = DB::transaction(function () use (
            $validated,
            $actor,
            $isSuperAdmin
        ) {
            $employee = User::query()
                ->whereKey($validated['assigned_to'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$employee || !$employee->company_id) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Please select an active company employee.',
                ]);
            }

            if (
                !$isSuperAdmin
                && (int) $employee->company_id !== (int) $actor->company_id
            ) {
                abort(403, 'You cannot assign another company employee.');
            }

            /*
             * Lock selected leads before checking their latest status
             * and history count.
             */
            $leads = Lead::query()
                ->whereIn('id', $validated['lead_ids'])
                ->where('company_id', $employee->company_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($leads->count() !== count($validated['lead_ids'])) {
                throw ValidationException::withMessages([
                    'lead_ids' => 'Select leads from the same company as the employee. Some selected leads may no longer be available.',
                ]);
            }

            // Check eligibility again when the form is submitted.
            $eligibleIds = $this->eligibleQuery()
                ->whereIn('id', $leads->modelKeys())
                ->pluck('id');

            if ($eligibleIds->count() !== $leads->count()) {
                throw ValidationException::withMessages([
                    'lead_ids' => 'Some selected leads no longer have No Answer status with exactly one history record. Refresh the page and select again.',
                ]);
            }

            foreach ($leads as $lead) {
                $previousUserId = $lead->assigned_to;

                $lead->update([
                    'assigned_to' => $employee->id,
                    'call_status' => 'pending',
                ]);

                // Assignment history is separate from call history.
                LeadAssignment::create([
                    'company_id' => $lead->company_id,
                    'lead_id' => $lead->id,
                    'previous_user_id' => $previousUserId,
                    'new_user_id' => $employee->id,
                    'assigned_by' => $actor->id,
                    'reason' => 'No Answer lead with exactly one call history record reassigned for retry.',
                    'assigned_at' => now(),
                ]);
            }

            return $leads->count();
        }, 3);

        return back()->with(
            'success',
            "{$count} leads reassigned successfully. Call status changed to pending."
        );
    }
}