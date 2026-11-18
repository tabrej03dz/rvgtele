<?php

namespace App\Http\Controllers;

use App\Imports\LeadsImport;
use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SimpleLeadController extends Controller
{
    private function companyId(Request $request): int
    {
        abort_unless(
            $request->user()->hasAnyRole([
                'super_admin',
                'admin',
            ]),
            403
        );

        $companyId = (int) $request->user()->company_id;

        abort_unless(
            $companyId > 0,
            403,
            'Please select a company workspace first.'
        );

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId($request);

        $filters = $request->validate([
            'search' => [
                'nullable', 'string', 'max:255'
            ],
            'category_id' => [
                'nullable', 'integer', 'min:1'
            ],
            'employee' => [
                'nullable',
                'string',
                'regex:/^(unassigned|[1-9][0-9]*)$/',
            ],
            'call_status' => [
                'nullable', 'string', 'max:255'
            ],
            'state' => [
                'nullable', 'string', 'max:255'
            ],
            'city' => [
                'nullable', 'string', 'max:255'
            ],
            'per_page' => [
                'nullable', 'integer', 'min:1', 'max:500'
            ],
            'page' => [
                'nullable', 'integer', 'min:1'
            ],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 50);

        $selectedState = trim(
            (string) ($filters['state'] ?? '')
        );

        $selectedCity = trim(
            (string) ($filters['city'] ?? '')
        );

        /*
         |-----------------------------------------
         | MAIN LEADS QUERY
         |-----------------------------------------
         */

        $query = Lead::query()
            ->where('company_id', $companyId);

        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where(
                'category_id',
                $filters['category_id']
            );
        }

        /*
         |-----------------------------------------
         | EMPLOYEE FILTER
         |-----------------------------------------
         */

        $employee = $filters['employee'] ?? '';

        if ($employee === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($employee !== '') {
            $query->where(
                'assigned_to',
                (int) $employee
            );
        }

        /*
         |-----------------------------------------
         | CALL STATUS FILTER
         |-----------------------------------------
         */

        if (!empty($filters['call_status'])) {
            $query->where(
                'call_status',
                $filters['call_status']
            );
        }

        /*
         |-----------------------------------------
         | STATE FILTER
         |-----------------------------------------
         */

        if ($selectedState !== '') {
            $query->whereRaw(
                'TRIM(state) = ?',
                [$selectedState]
            );
        }

        /*
         |-----------------------------------------
         | CITY FILTER
         |-----------------------------------------
         */

        if ($selectedCity !== '') {
            $query->whereRaw(
                'TRIM(city) = ?',
                [$selectedCity]
            );
        }

        /*
         |-----------------------------------------
         | PAGINATION
         |-----------------------------------------
         */

        $leads = $query
            ->with([
                'assignedUser:id,name',
                'leadCategory:id,name',
            ])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        /*
         | If requested page exceeds last page,
         | redirect to the last valid page.
         */

        if (
            $leads->currentPage() >
            $leads->lastPage()
        ) {
            return redirect()->route(
                'simple-leads.index',
                array_merge(
                    $request->only([
                        'search',
                        'category_id',
                        'employee',
                        'call_status',
                        'state',
                        'city',
                    ]),
                    [
                        'per_page' => $perPage,
                        'page' => $leads->lastPage(),
                    ]
                )
            );
        }

        /*
         |-----------------------------------------
         | EMPLOYEES
         |-----------------------------------------
         */

        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
         |-----------------------------------------
         | CATEGORIES
         |-----------------------------------------
         */

        $categories = Category::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
         |-----------------------------------------
         | CALL STATUSES
         |-----------------------------------------
         */

        $statuses = Lead::query()
            ->where('company_id', $companyId)
            ->whereNotNull('call_status')
            ->where('call_status', '!=', '')
            ->distinct()
            ->orderBy('call_status')
            ->pluck('call_status');

        /*
         |-----------------------------------------
         | STATES
         | Only states existing in company leads.
         |-----------------------------------------
         */

        $states = Lead::query()
            ->where('company_id', $companyId)
            ->whereNotNull('state')
            ->whereRaw("TRIM(state) != ''")
            ->selectRaw('TRIM(state) AS state_name')
            ->distinct()
            ->orderBy('state_name')
            ->pluck('state_name');

        /*
         |-----------------------------------------
         | CITIES WITH STATE MAPPING
         |
         | No new API required.
         | Only company-specific lead data.
         |-----------------------------------------
         */

        $cityRows = Lead::query()
            ->where('company_id', $companyId)
            ->whereNotNull('city')
            ->whereRaw("TRIM(city) != ''")
            ->selectRaw('TRIM(city) AS city_name')
            ->selectRaw(
                "COALESCE(TRIM(state), '') AS state_name"
            )
            ->distinct()
            ->orderBy('state_name')
            ->orderBy('city_name')
            ->get();

        /*
         |-----------------------------------------
         | VIEW
         |-----------------------------------------
         */

        return view(
            'calling-crm.simple-leads',
            compact(
                'leads',
                'employees',
                'categories',
                'statuses',
                'states',
                'cityRows',
                'selectedState',
                'selectedCity',
                'perPage'
            )
        );
    }

    /*
     |=============================================
     | UPLOAD LEADS
     | Existing import flow preserved.
     |=============================================
     */

    public function upload(Request $request)
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240',
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(function ($q) use ($companyId) {
                        $q->where(
                            'company_id',
                            $companyId
                        )->orWhereNull('company_id');
                    }),
            ],
        ]);

        $sourceId = LeadSource::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        $statusId = LeadStatus::query()
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if (!$sourceId || !$statusId) {
            return back()->with(
                'error',
                'Upload se pehle CRM Settings mein active Lead Source aur Lead Status banayein.'
            );
        }

        $import = new LeadsImport(
            companyId: $companyId,
            importedBy: (int) $request->user()->id,
            defaultSourceId: (int) $sourceId,
            defaultStatusId: (int) $statusId,
            defaultCategoryId: (int) $validated['category_id'],
            duplicateAction: 'skip',
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        /*
         | Existing Smart Lead Distribution
         | remains unchanged.
         */

        
        $result = $import->result();

        return redirect()
            ->route('simple-leads.index')
            ->with(
                'success',
                "Uploaded: {$result['imported']}, Duplicate skipped: {$result['duplicates']}, Failed: {$result['failed']}."
            )
            ->with(
                'import_errors',
                $result['errors']
            );
    }

    /*
     |=============================================
     | ASSIGN LEADS
     |=============================================
     */

    public function assign(Request $request)
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'lead_ids' => [
                'required',
                'array',
                'min:1',
                'max:1000',
            ],
            'lead_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('leads', 'id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
        ]);

        $count = DB::transaction(
            function () use (
                $validated,
                $companyId,
                $request
            ) {
                $leads = Lead::query()
                    ->where('company_id', $companyId)
                    ->whereIn(
                        'id',
                        $validated['lead_ids']
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($leads as $lead) {
                    $previousUserId = $lead->assigned_to;

                    $lead->update([
                        'assigned_to' =>
                            $validated['assigned_to'],
                        'call_status' => 'pending',
                    ]);

                    LeadAssignment::create([
                        'company_id' => $companyId,
                        'lead_id' => $lead->id,
                        'previous_user_id' =>
                            $previousUserId,
                        'new_user_id' =>
                            $validated['assigned_to'],
                        'assigned_by' =>
                            $request->user()->id,
                        'reason' =>
                            'Assigned from Simple Leads.',
                        'assigned_at' => now(),
                    ]);
                }

                return $leads->count();
            }
        );

        return back()->with(
            'success',
            "{$count} leads assigned. Call status pending kar diya gaya."
        );
    }

    /*
     |=============================================
     | DELETE LEADS (SOFT DELETE)
     |=============================================
     */

    public function delete(Request $request)
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'lead_ids' => [
                'required',
                'array',
                'min:1',
                'max:1000',
            ],
            'lead_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('leads', 'id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $count = DB::transaction(
            function () use ($companyId, $validated) {
                $leads = Lead::query()
                    ->where('company_id', $companyId)
                    ->whereIn(
                        'id',
                        $validated['lead_ids']
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($leads as $lead) {
                    $lead->delete();
                }

                return $leads->count();
            }
        );

        return back()->with(
            'success',
            "{$count} leads Recycle Bin mein move kar di gayi."
        );
    }
}
