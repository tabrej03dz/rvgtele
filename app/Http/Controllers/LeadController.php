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
    /**
     * Display lead listing with advanced filters.
     */
    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);

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

        return view('leads.index', [
            'leads' => $leads,

            'statuses' => LeadStatus::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'sources' => LeadSource::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->orderBy('name')
                ->get(),

            'users' => User::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ]),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),
        ]);
    }

    /**
     * Show create form.
     */
    public function create(Request $request): View
    {
        return view('leads.form', $this->formData($request));
    }

    /**
     * Store new lead.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $this->validateData($request);

        /*
        |--------------------------------------------------------------------------
        | Default pipeline stage
        |--------------------------------------------------------------------------
        |
        | leads table me pipeline_id column nahi hai.
        | Lead ko pipeline_stage_id ke through pipeline me rakha jayega.
        |
        */

        if (empty($validated['pipeline_stage_id'])) {
            $validated['pipeline_stage_id'] =
                $this->defaultPipelineStageId($companyId);
        }

        $validated['company_id'] = $companyId;
        $validated['created_by'] = $request->user()->id;
        $validated['priority'] = $validated['priority'] ?? 'normal';
        $validated['temperature'] = $validated['temperature'] ?? 'cold';

        $lead = DB::transaction(function () use (
            $validated,
            $request,
            $companyId
        ) {
            $lead = Lead::create($validated);

            if (!empty($validated['assigned_to'])) {
                $this->createAssignmentHistory(
                    lead: $lead,
                    previousUserId: null,
                    newUserId: (int) $validated['assigned_to'],
                    assignedBy: (int) $request->user()->id,
                    reason: 'Lead assigned during creation',
                    companyId: $companyId
                );
            }

            return $lead;
        });

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead created successfully.');
    }

    /**
     * Display lead detail.
     */
    public function show(
        Request $request,
        Lead $lead
    ): View {
        $this->guard($request, $lead);

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

        $companyId = $this->companyId($request);

        return view('leads.show', [
            'lead' => $lead,

            'users' => User::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ]),

            'dispositions' => \App\Models\CallDisposition::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show lead edit form.
     */
    public function edit(
        Request $request,
        Lead $lead
    ): View {
        $this->guard($request, $lead);

        return view(
            'leads.form',
            array_merge(
                $this->formData($request),
                compact('lead')
            )
        );
    }

    /**
     * Update lead.
     */
    public function update(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard($request, $lead);

        $validated = $this->validateData(
            $request,
            $lead
        );

        /*
        |--------------------------------------------------------------------------
        | Assignment changed from edit form
        |--------------------------------------------------------------------------
        */

        $oldAssignedUserId = $lead->assigned_to;
        $newAssignedUserId = $validated['assigned_to'] ?? null;

        DB::transaction(function () use (
            $lead,
            $validated,
            $oldAssignedUserId,
            $newAssignedUserId,
            $request
        ) {
            $lead->update($validated);

            if (
                (int) $oldAssignedUserId !==
                (int) $newAssignedUserId
                && !empty($newAssignedUserId)
            ) {
                $this->createAssignmentHistory(
                    lead: $lead,
                    previousUserId: $oldAssignedUserId
                        ? (int) $oldAssignedUserId
                        : null,
                    newUserId: (int) $newAssignedUserId,
                    assignedBy: (int) $request->user()->id,
                    reason: 'Lead owner changed from edit form',
                    companyId: $this->companyId($request)
                );
            }
        });

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    /**
     * Soft delete lead.
     */
    public function destroy(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard($request, $lead);

        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead moved to trash.');
    }

    /**
     * Assign one lead.
     */
    public function assign(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard($request, $lead);

        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true);
                    }),
            ],

            'reason' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $oldAssignedUserId = $lead->assigned_to;

        DB::transaction(function () use (
            $lead,
            $validated,
            $oldAssignedUserId,
            $request,
            $companyId
        ) {
            $lead->update([
                'assigned_to' => $validated['assigned_to'],
            ]);

            $this->createAssignmentHistory(
                lead: $lead,
                previousUserId: $oldAssignedUserId
                    ? (int) $oldAssignedUserId
                    : null,
                newUserId: (int) $validated['assigned_to'],
                assignedBy: (int) $request->user()->id,
                reason: $validated['reason'],
                companyId: $companyId
            );
        });

        return back()->with(
            'success',
            'Lead assigned successfully.'
        );
    }

    /**
     * Assign selected or filtered leads in bulk.
     */
    public function bulkAssign(
        Request $request
    ): RedirectResponse {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
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

                Rule::exists('leads', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'assigned_to' => [
                'required',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true);
                    }),
            ],

            'reason' => [
                'required',
                'string',
                'max:500',
            ],

            /*
            |--------------------------------------------------------------------------
            | Filter values
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
        | Build target lead query
        |--------------------------------------------------------------------------
        */

        if ($validated['assignment_scope'] === 'selected') {
            $targetQuery = Lead::query()
                ->where('company_id', $companyId)
                ->whereIn(
                    'id',
                    $validated['lead_ids'] ?? []
                );
        } else {
            /*
            | Create a separate request containing filter values because
            | bulk form uses filter_assigned_to to avoid conflict with the
            | new employee assigned_to field.
            */

            $filterRequest = new Request([
                'search' => $validated['search'] ?? null,
                'status' => $validated['status'] ?? null,
                'source' => $validated['source'] ?? null,

                'assigned_to' =>
                    $validated['filter_assigned_to'] ?? null,

                'team_id' => $validated['team_id'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ]);

            $filterRequest->setUserResolver(
                fn () => $request->user()
            );

            $targetQuery = $this->filteredLeadQuery(
                $filterRequest
            );
        }

        $totalLeads = (clone $targetQuery)->count();

        if ($totalLeads < 1) {
            return back()->with(
                'error',
                'No leads found for bulk assignment.'
            );
        }

        $newUserId = (int) $validated['assigned_to'];
        $assignedBy = (int) $request->user()->id;
        $reason = $validated['reason'];

        $updatedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Chunk assignment
        |--------------------------------------------------------------------------
        |
        | Large number of filtered leads ko ek saath memory me load nahi karega.
        |
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
                    &$updatedCount
                ) {
                    DB::transaction(function () use (
                        $leads,
                        $newUserId,
                        $assignedBy,
                        $reason,
                        $companyId,
                        &$updatedCount
                    ) {
                        foreach ($leads as $lead) {
                            $previousUserId = $lead->assigned_to;

                            /*
                            | Already same employee ko assigned lead ko skip.
                            */

                            if (
                                (int) $previousUserId ===
                                $newUserId
                            ) {
                                continue;
                            }

                            $lead->update([
                                'assigned_to' => $newUserId,
                            ]);

                            $this->createAssignmentHistory(
                                lead: $lead,
                                previousUserId: $previousUserId
                                    ? (int) $previousUserId
                                    : null,
                                newUserId: $newUserId,
                                assignedBy: $assignedBy,
                                reason: $reason,
                                companyId: $companyId
                            );

                            $updatedCount++;
                        }
                    });
                }
            );

        return back()->with(
            'success',
            "{$updatedCount} leads assigned successfully."
        );
    }

    /**
     * Add lead note.
     */
    public function note(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->guard($request, $lead);

        $validated = $request->validate([
            'body' => [
                'required',
                'string',
                'max:3000',
            ],
        ]);

        Note::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with(
            'success',
            'Note added successfully.'
        );
    }

    /**
     * Reusable filtered lead query.
     */
    private function filteredLeadQuery(
        Request $request
    ): Builder {
        $companyId = $this->companyId($request);

        return Lead::query()
            ->where('company_id', $companyId)

            ->when(
                $request->filled('search'),
                function (Builder $query) use ($request) {
                    $search = trim(
                        (string) $request->search
                    );

                    $query->where(
                        function (Builder $subQuery) use ($search) {
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
            )

            ->when(
                $request->filled('status'),
                fn (Builder $query) =>
                    $query->where(
                        'lead_status_id',
                        $request->status
                    )
            )

            ->when(
                $request->filled('source'),
                fn (Builder $query) =>
                    $query->where(
                        'lead_source_id',
                        $request->source
                    )
            )

            ->when(
                $request->filled('assigned_to'),
                function (Builder $query) use ($request) {
                    if ($request->assigned_to === 'unassigned') {
                        $query->whereNull('assigned_to');
                    } else {
                        $query->where(
                            'assigned_to',
                            $request->assigned_to
                        );
                    }
                }
            )

            ->when(
                $request->filled('team_id'),
                fn (Builder $query) =>
                    $query->where(
                        'team_id',
                        $request->team_id
                    )
            )

            ->when(
                $request->filled('priority'),
                fn (Builder $query) =>
                    $query->where(
                        'priority',
                        $request->priority
                    )
            )

            ->when(
                $request->filled('temperature'),
                fn (Builder $query) =>
                    $query->where(
                        'temperature',
                        $request->temperature
                    )
            )

            ->when(
                $request->filled('date_from'),
                fn (Builder $query) =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $request->date_from
                    )
            )

            ->when(
                $request->filled('date_to'),
                fn (Builder $query) =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $request->date_to
                    )
            );
    }

    /**
     * Form dropdown data.
     */
    private function formData(
        Request $request
    ): array {
        $companyId = $this->companyId($request);

        return [
            'sources' => LeadSource::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'statuses' => LeadStatus::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'users' => User::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                ]),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),

            'stages' => PipelineStage::query()
                ->whereHas(
                    'pipeline',
                    fn (Builder $query) =>
                        $query->where(
                            'company_id',
                            $companyId
                        )
                )
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    /**
     * Lead validation.
     */
    private function validateData(
        Request $request,
        ?Lead $lead = null
    ): array {
        $companyId = $this->companyId($request);

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

                Rule::unique('leads', 'mobile')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $companyId
                            )
                    )
                    ->ignore($lead?->id),
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

                Rule::exists('lead_sources', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query->where(
                            function ($subQuery) use ($companyId) {
                                $subQuery
                                    ->whereNull('company_id')
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }),
            ],

            'lead_status_id' => [
                'required',
                'integer',

                Rule::exists('lead_statuses', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query->where(
                            function ($subQuery) use ($companyId) {
                                $subQuery
                                    ->whereNull('company_id')
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }),
            ],

            'assigned_to' => [
                'nullable',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true);
                    }),
            ],

            'team_id' => [
                'nullable',
                'integer',

                Rule::exists('teams', 'id')
                    ->where(
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

    /**
     * Get default first pipeline stage.
     */
    private function defaultPipelineStageId(
        int $companyId
    ): ?int {
        /*
        | Aapke pipelines table me is_active column nahi hai,
        | isliye uski condition nahi lagayi gayi.
        */

        $pipeline = Pipeline::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$pipeline) {
            return null;
        }

        return PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Save assignment history.
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
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'previous_user_id' => $previousUserId,
            'new_user_id' => $newUserId,
            'assigned_by' => $assignedBy,
            'reason' => $reason,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Current company ID.
     */
    private function companyId(
        Request $request
    ): int {
        return (int) $request->user()->company_id;
    }

    /**
     * Company data access guard.
     */
    private function guard(
        Request $request,
        Lead $lead
    ): void {
        abort_unless(
            (int) $lead->company_id ===
            $this->companyId($request),
            403,
            'Unauthorized lead access.'
        );
    }
}