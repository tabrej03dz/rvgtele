<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Note;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FULL ACCESS ROLES
    |--------------------------------------------------------------------------
    |
    | In roles ko company ki saari leads dikhengi.
    |
    */

    private array $fullAccessRoles = [
        'super_admin',
        'admin',
    ];

    /*
    |--------------------------------------------------------------------------
    | Lead Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);

        $hasFullAccess = $this->hasFullAccess($request);

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | filteredLeadQuery() ke andar hi role based restriction lagi hui hai.
        |
        | Admin:
        | company ki saari leads
        |
        | Employee:
        | assigned_to = login user id
        |
        */

        $query = $this->filteredLeadQuery($request)
            ->with([
                'assignedUser:id,name,employee_code',
                'source:id,name',
                'status:id,name,color',
                'team:id,name',
                'stage:id,name,color',
            ]);

        $leads = $query
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statuses
        |--------------------------------------------------------------------------
        */

        $statuses = LeadStatus::query()
            ->where(function (Builder $query) use ($companyId) {
                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $companyId
                    );
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Sources
        |--------------------------------------------------------------------------
        */

        $sources = LeadSource::query()
            ->where(function (Builder $query) use ($companyId) {
                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $companyId
                    );
            })
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employees
        |--------------------------------------------------------------------------
        |
        | Employee ko doosre employees ka dropdown dene ki zarurat nahi.
        |
        */

        $users = $hasFullAccess
            ? User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ])
            : collect();

        /*
        |--------------------------------------------------------------------------
        | Teams
        |--------------------------------------------------------------------------
        */

        $teams = Team::query()
            ->where(
                'company_id',
                $companyId
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return view('leads.index', [
            'leads' => $leads,
            'statuses' => $statuses,
            'sources' => $sources,
            'users' => $users,
            'teams' => $teams,

            /*
            | Blade role control
            */

            'hasFullAccess' => $hasFullAccess,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Lead Form
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): View
    {
        return view(
            'leads.form',
            $this->formData($request)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Lead
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {
        $companyId = $this->companyId($request);

        $validated = $this->validateData($request);

        /*
        |--------------------------------------------------------------------------
        | Normal Employee
        |--------------------------------------------------------------------------
        |
        | Employee agar Lead create karta hai to Lead automatically usi ko
        | assign hogi.
        |
        | Wo manually kisi aur employee ko assign nahi kar sakta.
        |
        */

        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] =
                (int) $request->user()->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Default Pipeline Stage
        |--------------------------------------------------------------------------
        */

        if (empty($validated['pipeline_stage_id'])) {
            $validated['pipeline_stage_id'] =
                $this->defaultPipelineStageId(
                    $companyId
                );
        }

        $validated['company_id'] = $companyId;

        $validated['created_by'] =
            (int) $request->user()->id;

        $validated['priority'] =
            $validated['priority'] ?? 'normal';

        $validated['temperature'] =
            $validated['temperature'] ?? 'cold';

        $lead = DB::transaction(
            function () use (
                $validated,
                $request,
                $companyId
            ) {
                $lead = Lead::create(
                    $validated
                );

                /*
                |--------------------------------------------------------------------------
                | Assignment History
                |--------------------------------------------------------------------------
                */

                if (!empty($validated['assigned_to'])) {
                    $this->createAssignmentHistory(
                        lead: $lead,
                        previousUserId: null,
                        newUserId:
                            (int) $validated['assigned_to'],
                        assignedBy:
                            (int) $request->user()->id,
                        reason:
                            $this->hasFullAccess($request)
                                ? 'Lead assigned during creation'
                                : 'Lead created by employee and automatically assigned to self',
                        companyId: $companyId
                    );
                }

                return $lead;
            }
        );

        return redirect()
            ->route(
                'leads.show',
                $lead
            )
            ->with(
                'success',
                'Lead created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Lead Detail
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Lead $lead
    ): View {
        /*
        | Employee doosre employee ki lead URL se open nahi kar sakta.
        */

        $this->guard(
            $request,
            $lead
        );

        $lead->load([
            'assignedUser',
            'source',
            'status',
            'stage',
            'team',

            'calls.user',
            'calls.disposition',

            'followUps.assignedUser',

            'notes.user',

            'assignments',
        ]);

        $companyId =
            $this->companyId($request);

        /*
        |--------------------------------------------------------------------------
        | Assignment Dropdown
        |--------------------------------------------------------------------------
        |
        | Sirf Admin/Super Admin ko employee list denge.
        |
        */

        $users = $this->hasFullAccess($request)
            ? User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ])
            : collect();

        /*
        |--------------------------------------------------------------------------
        | Dispositions
        |--------------------------------------------------------------------------
        */

        $dispositions =
            \App\Models\CallDisposition::query()
                ->where(
                    function (
                        Builder $query
                    ) use ($companyId) {
                        $query
                            ->whereNull(
                                'company_id'
                            )
                            ->orWhere(
                                'company_id',
                                $companyId
                            );
                    }
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        return view('leads.show', [
            'lead' => $lead,

            'users' => $users,

            'dispositions' =>
                $dispositions,

            'hasFullAccess' =>
                $this->hasFullAccess($request),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Lead
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        Lead $lead
    ): View {
        $this->guard(
            $request,
            $lead
        );

        return view(
            'leads.form',
            array_merge(
                $this->formData($request),
                compact('lead')
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Lead
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard(
            $request,
            $lead
        );

        $validated = $this->validateData(
            $request,
            $lead
        );

        /*
        |--------------------------------------------------------------------------
        | Employee cannot change owner
        |--------------------------------------------------------------------------
        */

        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] =
                $lead->assigned_to;
        }

        $oldAssignedUserId =
            $lead->assigned_to;

        $newAssignedUserId =
            $validated['assigned_to'] ?? null;

        DB::transaction(
            function () use (
                $lead,
                $validated,
                $oldAssignedUserId,
                $newAssignedUserId,
                $request
            ) {
                $lead->update(
                    $validated
                );

                /*
                |--------------------------------------------------------------------------
                | Admin changed assignment from edit form
                |--------------------------------------------------------------------------
                */

                if (
                    $this->hasFullAccess($request)
                    &&
                    (int) $oldAssignedUserId !==
                    (int) $newAssignedUserId
                    &&
                    !empty($newAssignedUserId)
                ) {
                    $this->createAssignmentHistory(
                        lead: $lead,

                        previousUserId:
                            $oldAssignedUserId
                                ? (int) $oldAssignedUserId
                                : null,

                        newUserId:
                            (int) $newAssignedUserId,

                        assignedBy:
                            (int) $request->user()->id,

                        reason:
                            'Lead owner changed from edit form',

                        companyId:
                            $this->companyId($request)
                    );
                }
            }
        );

        return redirect()
            ->route(
                'leads.show',
                $lead
            )
            ->with(
                'success',
                'Lead updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Lead
    |--------------------------------------------------------------------------
    |
    | Sirf Admin/Super Admin.
    |
    */

    public function destroy(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->ensureFullAccess(
            $request
        );

        $this->guardCompany(
            $request,
            $lead
        );

        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with(
                'success',
                'Lead moved to trash.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Single Assignment
    |--------------------------------------------------------------------------
    |
    | Sirf Admin/Super Admin.
    |
    */

    public function assign(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->ensureFullAccess(
            $request
        );

        $this->guardCompany(
            $request,
            $lead
        );

        $companyId =
            $this->companyId($request);

        $validated =
            $request->validate([
                'assigned_to' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'users',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],
            ]);

        $oldAssignedUserId =
            $lead->assigned_to;

        /*
        |--------------------------------------------------------------------------
        | Already same employee
        |--------------------------------------------------------------------------
        */

        if (
            (int) $oldAssignedUserId ===
            (int) $validated['assigned_to']
        ) {
            return back()->with(
                'error',
                'Lead is already assigned to this employee.'
            );
        }

        DB::transaction(
            function () use (
                $lead,
                $validated,
                $oldAssignedUserId,
                $request,
                $companyId
            ) {
                $lead->update([
                    'assigned_to' =>
                        $validated['assigned_to'],
                ]);

                $this->createAssignmentHistory(
                    lead: $lead,

                    previousUserId:
                        $oldAssignedUserId
                            ? (int) $oldAssignedUserId
                            : null,

                    newUserId:
                        (int) $validated['assigned_to'],

                    assignedBy:
                        (int) $request->user()->id,

                    reason:
                        $validated['reason'],

                    companyId:
                        $companyId
                );
            }
        );

        return back()->with(
            'success',
            'Lead assigned successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Assignment
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Employee direct POST request bhejkar bhi bulk assignment nahi kar sakta.
    |
    */

    public function bulkAssign(
        Request $request
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | ROLE SECURITY
        |--------------------------------------------------------------------------
        */

        $this->ensureFullAccess(
            $request
        );

        $companyId =
            $this->companyId($request);

        $validated =
            $request->validate([
                'assignment_scope' => [
                    'required',

                    Rule::in([
                        'selected',
                        'filtered',
                    ]),
                ],

                'lead_ids' => [
                    'nullable',
                    'array',
                    'required_if:assignment_scope,selected',
                ],

                'lead_ids.*' => [
                    'integer',

                    Rule::exists(
                        'leads',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->whereNull(
                                    'deleted_at'
                                );
                        }
                    ),
                ],

                'assigned_to' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'users',
                        'id'
                    )->where(
                        function ($query) use (
                            $companyId
                        ) {
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],

                /*
                |--------------------------------------------------------------------------
                | Current Filters
                |--------------------------------------------------------------------------
                */

                'search' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'status' => [
                    'nullable',
                    'integer',
                ],

                'source' => [
                    'nullable',
                    'integer',
                ],

                'filter_assigned_to' => [
                    'nullable',
                    'string',
                ],

                'team_id' => [
                    'nullable',
                    'integer',
                ],

                'priority' => [
                    'nullable',

                    Rule::in([
                        'low',
                        'normal',
                        'high',
                        'urgent',
                        'hot',
                    ]),
                ],

                'temperature' => [
                    'nullable',

                    Rule::in([
                        'cold',
                        'warm',
                        'hot',
                    ]),
                ],

                'date_from' => [
                    'nullable',
                    'date',
                ],

                'date_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:date_from',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Selected Leads
        |--------------------------------------------------------------------------
        */

        if (
            $validated['assignment_scope'] ===
            'selected'
        ) {
            $targetQuery =
                Lead::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->whereIn(
                        'id',
                        $validated['lead_ids']
                            ?? []
                    );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Leads
        |--------------------------------------------------------------------------
        */

        else {
            $filterRequest =
                new Request([
                    'search' =>
                        $validated['search']
                            ?? null,

                    'status' =>
                        $validated['status']
                            ?? null,

                    'source' =>
                        $validated['source']
                            ?? null,

                    'assigned_to' =>
                        $validated[
                            'filter_assigned_to'
                        ] ?? null,

                    'team_id' =>
                        $validated['team_id']
                            ?? null,

                    'priority' =>
                        $validated['priority']
                            ?? null,

                    'temperature' =>
                        $validated['temperature']
                            ?? null,

                    'date_from' =>
                        $validated['date_from']
                            ?? null,

                    'date_to' =>
                        $validated['date_to']
                            ?? null,
                ]);

            $filterRequest->setUserResolver(
                fn () =>
                    $request->user()
            );

            $targetQuery =
                $this->filteredLeadQuery(
                    $filterRequest
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Count
        |--------------------------------------------------------------------------
        */

        $totalLeads =
            (clone $targetQuery)->count();

        if ($totalLeads < 1) {
            return back()->with(
                'error',
                'No leads found for bulk assignment.'
            );
        }

        $newUserId =
            (int) $validated['assigned_to'];

        $assignedBy =
            (int) $request->user()->id;

        $reason =
            $validated['reason'];

        $updatedCount = 0;

        $skippedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Chunk Processing
        |--------------------------------------------------------------------------
        */

        $targetQuery
            ->select([
                'id',
                'company_id',
                'assigned_to',
            ])
            ->orderBy('id')
            ->chunkById(
                200,

                function ($leads) use (
                    $newUserId,
                    $assignedBy,
                    $reason,
                    $companyId,
                    &$updatedCount,
                    &$skippedCount
                ) {
                    DB::transaction(
                        function () use (
                            $leads,
                            $newUserId,
                            $assignedBy,
                            $reason,
                            $companyId,
                            &$updatedCount,
                            &$skippedCount
                        ) {
                            foreach (
                                $leads as $lead
                            ) {
                                $previousUserId =
                                    $lead->assigned_to;

                                /*
                                | Already same employee
                                */

                                if (
                                    (int) $previousUserId ===
                                    $newUserId
                                ) {
                                    $skippedCount++;

                                    continue;
                                }

                                $lead->update([
                                    'assigned_to' =>
                                        $newUserId,
                                ]);

                                $this->createAssignmentHistory(
                                    lead: $lead,

                                    previousUserId:
                                        $previousUserId
                                            ? (int) $previousUserId
                                            : null,

                                    newUserId:
                                        $newUserId,

                                    assignedBy:
                                        $assignedBy,

                                    reason:
                                        $reason,

                                    companyId:
                                        $companyId
                                );

                                $updatedCount++;
                            }
                        }
                    );
                }
            );

        return back()->with(
            'success',
            "{$updatedCount} leads assigned successfully. {$skippedCount} already assigned leads skipped."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Note
    |--------------------------------------------------------------------------
    |
    | Employee apni assigned Lead par note add kar sakta hai.
    |
    */

    public function note(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard(
            $request,
            $lead
        );

        $validated =
            $request->validate([
                'body' => [
                    'required',
                    'string',
                    'max:3000',
                ],
            ]);

        Note::create([
            'lead_id' =>
                $lead->id,

            'user_id' =>
                $request->user()->id,

            'body' =>
                $validated['body'],
        ]);

        return back()->with(
            'success',
            'Note added successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filtered Lead Query
    |--------------------------------------------------------------------------
    |
    | YE SABSE IMPORTANT METHOD HAI.
    |
    | Har Lead listing/query sabse pehle role access lagayegi.
    |
    */

    private function filteredLeadQuery(
        Request $request
    ): Builder {
        $companyId =
            $this->companyId($request);

        $user =
            $request->user();

        $hasFullAccess =
            $this->hasFullAccess($request);

        $query =
            Lead::query()
                ->where(
                    'company_id',
                    $companyId
                );

        /*
        |--------------------------------------------------------------------------
        | ROLE BASED LEAD ACCESS
        |--------------------------------------------------------------------------
        |
        | Admin:
        | company ki all leads.
        |
        | Employee:
        | only assigned leads.
        |
        */

        if (!$hasFullAccess) {
            $query->where(
                'assigned_to',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                trim(
                    (string)
                    $request->search
                );

            $query->where(
                function (
                    Builder $subQuery
                ) use ($search) {
                    $subQuery
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'mobile',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'alternate_mobile',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'whatsapp_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'company_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'city',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'lead_status_id',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */

        if ($request->filled('source')) {
            $query->where(
                'lead_source_id',
                $request->source
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Employee Filter
        |--------------------------------------------------------------------------
        |
        | Sirf Admin/Super Admin ke liye.
        |
        */

        if (
            $hasFullAccess
            &&
            $request->filled(
                'assigned_to'
            )
        ) {
            if (
                $request->assigned_to ===
                'unassigned'
            ) {
                $query->whereNull(
                    'assigned_to'
                );
            } else {
                $query->where(
                    'assigned_to',
                    $request->assigned_to
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Team
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'team_id'
            )
        ) {
            $query->where(
                'team_id',
                $request->team_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Priority
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'priority'
            )
        ) {
            $query->where(
                'priority',
                $request->priority
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Temperature
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'temperature'
            )
        ) {
            $query->where(
                'temperature',
                $request->temperature
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'date_from'
            )
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'date_to'
            )
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Form Data
    |--------------------------------------------------------------------------
    */

    private function formData(
        Request $request
    ): array {
        $companyId =
            $this->companyId($request);

        $hasFullAccess =
            $this->hasFullAccess($request);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        |
        | Normal employee ko doosra employee choose nahi karna hai.
        |
        */

        $users = $hasFullAccess
            ? User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ])
            : collect([
                $request->user(),
            ]);

        return [
            'sources' =>
                LeadSource::query()
                    ->where(
                        function (
                            Builder $query
                        ) use ($companyId) {
                            $query
                                ->whereNull(
                                    'company_id'
                                )
                                ->orWhere(
                                    'company_id',
                                    $companyId
                                );
                        }
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('name')
                    ->get(),

            'statuses' =>
                LeadStatus::query()
                    ->where(
                        function (
                            Builder $query
                        ) use ($companyId) {
                            $query
                                ->whereNull(
                                    'company_id'
                                )
                                ->orWhere(
                                    'company_id',
                                    $companyId
                                );
                        }
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy('name')
                    ->get(),

            'users' =>
                $users,

            'teams' =>
                Team::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

            'stages' =>
                PipelineStage::query()
                    ->whereHas(
                        'pipeline',
                        fn (
                            Builder $query
                        ) =>
                            $query->where(
                                'company_id',
                                $companyId
                            )
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->get(),

            'hasFullAccess' =>
                $hasFullAccess,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateData(
        Request $request,
        ?Lead $lead = null
    ): array {
        $companyId =
            $this->companyId($request);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',

                Rule::unique(
                    'leads',
                    'mobile'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $companyId
                            )
                    )
                    ->ignore(
                        $lead?->id
                    ),
            ],

            'alternate_mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lead_source_id' => [
                'required',
                'integer',

                Rule::exists(
                    'lead_sources',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query->where(
                            function (
                                $subQuery
                            ) use ($companyId) {
                                $subQuery
                                    ->whereNull(
                                        'company_id'
                                    )
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }
                ),
            ],

            'lead_status_id' => [
                'required',
                'integer',

                Rule::exists(
                    'lead_statuses',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query->where(
                            function (
                                $subQuery
                            ) use ($companyId) {
                                $subQuery
                                    ->whereNull(
                                        'company_id'
                                    )
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }
                ),
            ],

            'assigned_to' => [
                'nullable',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    function ($query) use (
                        $companyId
                    ) {
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'is_active',
                                true
                            );
                    }
                ),
            ],

            'team_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'teams',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                ),
            ],

            'pipeline_stage_id' => [
                'nullable',
                'integer',
                'exists:pipeline_stages,id',
            ],

            'priority' => [
                'required',

                Rule::in([
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ]),
            ],

            'temperature' => [
                'required',

                Rule::in([
                    'cold',
                    'warm',
                    'hot',
                ]),
            ],

            'preferred_language' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10',
            ],

            'required_product' => [
                'nullable',
                'string',
                'max:255',
            ],

            'estimated_budget' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'expected_deal_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'expected_closing_date' => [
                'nullable',
                'date',
            ],

            'next_follow_up_at' => [
                'nullable',
                'date',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Default Pipeline Stage
    |--------------------------------------------------------------------------
    */

    private function defaultPipelineStageId(
        int $companyId
    ): ?int {
        $pipeline =
            Pipeline::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderByDesc(
                    'is_default'
                )
                ->orderBy('id')
                ->first();

        if (!$pipeline) {
            return null;
        }

        return PipelineStage::query()
            ->where(
                'pipeline_id',
                $pipeline->id
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy('id')
            ->value('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment History
    |--------------------------------------------------------------------------
    */

    private function createAssignmentHistory(
        Lead $lead,
        ?int $previousUserId,
        int $newUserId,
        int $assignedBy,
        string $reason,
        int $companyId
    ): void {
        LeadAssignment::create([
            'company_id' =>
                $companyId,

            'lead_id' =>
                $lead->id,

            'previous_user_id' =>
                $previousUserId,

            'new_user_id' =>
                $newUserId,

            'assigned_by' =>
                $assignedBy,

            'reason' =>
                $reason,

            'assigned_at' =>
                now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Company ID
    |--------------------------------------------------------------------------
    */

    private function companyId(
        Request $request
    ): int {
        return (int)
            $request->user()->company_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Full Access Check
    |--------------------------------------------------------------------------
    */

    private function hasFullAccess(
        Request $request
    ): bool {
        return $request
            ->user()
            ->hasAnyRole(
                $this->fullAccessRoles
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Admin Access
    |--------------------------------------------------------------------------
    */

    private function ensureFullAccess(
        Request $request
    ): void {
        abort_unless(
            $this->hasFullAccess($request),
            403,
            'You do not have permission to manage lead assignments.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Guard
    |--------------------------------------------------------------------------
    */

    private function guardCompany(
        Request $request,
        Lead $lead
    ): void {
        abort_unless(
            (int) $lead->company_id ===
            $this->companyId($request),
            403,
            'Unauthorized company lead access.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lead Access Guard
    |--------------------------------------------------------------------------
    |
    | Super Admin/Admin:
    | company ki lead open kar sakte hain.
    |
    | Employee:
    | sirf assigned lead open kar sakta hai.
    |
    */

    private function guard(
        Request $request,
        Lead $lead
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Company Check
        |--------------------------------------------------------------------------
        */

        $this->guardCompany(
            $request,
            $lead
        );

        /*
        |--------------------------------------------------------------------------
        | Full Access
        |--------------------------------------------------------------------------
        */

        if (
            $this->hasFullAccess(
                $request
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Employee Access
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $lead->assigned_to ===
            (int) $request->user()->id,
            403,
            'This lead is not assigned to you.'
        );
    }
}