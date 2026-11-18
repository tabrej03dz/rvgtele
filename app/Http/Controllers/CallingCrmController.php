<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class CallingCrmController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $filters = $request->validate([
            'search'   => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status'   => 'nullable|string|max:255',
            'user_id'  => 'nullable|integer|min:1',
        ]);

        $search = trim($filters['search'] ?? '');
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;

        $selectedUserId = $isSuperAdmin
            ? ($filters['user_id'] ?? null)
            : $user->id;

        // Base query: exclude pending leads.
        $baseQuery = Lead::query()
            ->whereNotNull('call_status')
            ->where('call_status', '!=', 'pending');

        if (!$isSuperAdmin) {
            $baseQuery->where('assigned_to', $user->id);
        }

        // Category dropdown.
        $categoryQuery = clone $baseQuery;

        if ($selectedUserId !== null) {
            $categoryQuery->where('assigned_to', $selectedUserId);
        }

        $categories = $categoryQuery
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Assigned user dropdown.
        $users = collect();

        if ($isSuperAdmin) {
            $assignedUserIds = (clone $baseQuery)
                ->whereNotNull('assigned_to')
                ->distinct()
                ->pluck('assigned_to');

            $users = User::query()
                ->whereIn('id', $assignedUserIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Status dropdown uses the selected user and category.
        $statusQuery = clone $baseQuery;

        if ($selectedUserId !== null) {
            $statusQuery->where('assigned_to', $selectedUserId);
        }

        if ($category !== null && $category !== '') {
            $statusQuery->where('category', $category);
        }

        $statuses = (clone $statusQuery)
            ->distinct()
            ->orderBy('call_status')
            ->pluck('call_status');

        // Start the lead list with the same user and category filters.
        $contactsQuery = clone $statusQuery;

        if ($search !== '') {
            $contactsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        // Count leads across all pages before applying the status dropdown.
        $statusCounts = (clone $contactsQuery)
            ->select('call_status')
            ->selectRaw('COUNT(*) AS lead_count')
            ->groupBy('call_status')
            ->pluck('lead_count', 'call_status');

        if ($status !== null && $status !== '') {
            $contactsQuery->where('call_status', $status);
        }

        $contacts = $contactsQuery
            ->with([
                'assignedUser:id,name',

                'callHistories' => function ($query) {
                    $query->with('user:id,name')
                        ->orderByDesc('call_at')
                        ->orderByDesc('id');
                },
            ])
            ->withCount('callHistories')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('calling-crm.index', [
            'title'          => 'Calling CRM',
            'contacts'       => $contacts,
            'categories'     => $categories,
            'statuses'       => $statuses,
            'statusCounts'   => $statusCounts,
            'users'          => $users,
            'isSuperAdmin'   => $isSuperAdmin,
            'selectedUserId' => $selectedUserId,
        ]);
    }
}