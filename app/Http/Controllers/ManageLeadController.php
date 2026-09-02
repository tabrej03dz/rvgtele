<?php

namespace App\Http\Controllers;
use App\Models\CallDisposition;
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
use App\Models\LeadLabel;
use App\Services\MobileCallService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ManageLeadController extends Controller
{

public function callOnMobile(
    Request $request,
    Lead $lead,
    MobileCallService $mobileCallService
): JsonResponse {

    $this->guard(
        $request,
        $lead
    );

    if (blank($lead->mobile)) {
        return response()->json([
            'status' => false,
            'message' => 'Lead mobile number is missing.',
        ], 422);
    }

    try {

        $result = $mobileCallService->send(
            $request->user(),
            $lead
        );

        return response()->json([
            'status' => true,

            'message' =>
                'Call sent to mobile successfully.',

            'data' => [
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'mobile' => $lead->mobile,
                'device' => $result,
            ],
        ]);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}

    private array $fullAccessRoles = [
        'super_admin',
        'admin',
    ];

public function index(Request $request): View
{

    if (!$request->has('call_disposition')) {
        $request->merge([
            'call_disposition' => 'no_call',
        ]);
    }

    $companyId = $this->companyId($request);
    $hasFullAccess = $this->hasFullAccess($request);

    $leaderTeamIds = $this->leaderTeamIds($request);

    $isTeamLeader =
        !$hasFullAccess &&
        !empty($leaderTeamIds);

    $canFilterByEmployee =
        $hasFullAccess || $isTeamLeader;

    $canFilterByTeam =
        $hasFullAccess || $isTeamLeader;

    $callDisposition = (string) $request->input(
        'call_disposition',
        'no_call'
    );

    $filterRequest = clone $request;

    $filterRequest->query->remove('call_disposition');
    $filterRequest->request->remove('call_disposition');

    $query = $this->filteredLeadQuery($filterRequest)
        ->with([
            'assignedUser:id,name,employee_code,team_id',
            'source:id,name',
            'status:id,name,color',
            'team:id,name',
            'stage:id,name,color',
            'labels:id,company_id,name,color',
        ])
        ->addSelect([

            'latest_note_body' => Note::query()
                ->select('body')
                ->whereColumn(
                    'notes.lead_id',
                    'leads.id'
                )
                ->latest('notes.id')
                ->limit(1),

            'latest_note_created_at' => Note::query()
                ->select('created_at')
                ->whereColumn(
                    'notes.lead_id',
                    'leads.id'
                )
                ->latest('notes.id')
                ->limit(1),

            'latest_note_user_name' => Note::query()
                ->leftJoin(
                    'users',
                    'users.id',
                    '=',
                    'notes.user_id'
                )
                ->select('users.name')
                ->whereColumn(
                    'notes.lead_id',
                    'leads.id'
                )
                ->latest('notes.id')
                ->limit(1),
        ]);

    if ($callDisposition === 'no_call') {

        $query->whereDoesntHave('calls');

    } elseif (
        $callDisposition !== '' &&
        $callDisposition !== 'all'
    ) {

        $dispositionId = (int) $callDisposition;

        $query->whereHas('calls', function (Builder $callQuery) use ($dispositionId) {

            $callQuery
                ->where(
                    'call_disposition_id',
                    $dispositionId
                )
                ->where(
                    'call_logs.id',
                    '=',
                    function ($subQuery) {
                        $subQuery
                            ->selectRaw('MAX(cl2.id)')
                            ->from('call_logs as cl2')
                            ->whereColumn(
                                'cl2.lead_id',
                                'call_logs.lead_id'
                            );
                    }
                );
        });
    }

    $allowedPerPage = [
        25,
        50,
        100,
        200,
    ];

    $perPage = (int) $request->input(
        'per_page',
        25
    );

    if (!in_array(
        $perPage,
        $allowedPerPage,
        true
    )) {
        $perPage = 25;
    }

    $leads = $query
        ->latest('leads.id')
        ->paginate($perPage)
        ->withQueryString();

    $statuses = LeadStatus::query()
        ->where(function (Builder $query) use ($companyId) {
            $query
                ->whereNull('company_id')
                ->orWhere(
                    'company_id',
                    $companyId
                );
        })
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $dispositions = CallDisposition::query()
        ->where(function (Builder $query) use ($companyId) {
            $query
                ->whereNull('company_id')
                ->orWhere(
                    'company_id',
                    $companyId
                );
        })
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

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

    $categories = Lead::query()
        ->where(
            'company_id',
            $companyId
        )
        ->whereNotNull('category')
        ->where(
            'category',
            '<>',
            ''
        )
        ->select('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category');

    $cities = Lead::query()
        ->where('company_id', $companyId)
        ->whereNotNull('city')
        ->where('city', '<>', '')
        ->select('city')
        ->distinct()
        ->orderBy('city')
        ->pluck('city');

    if ($hasFullAccess) {

        $users = User::query()
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
                'team_id',
            ]);

    } elseif ($isTeamLeader) {

        $users = User::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                true
            )
            ->where(function (Builder $query) use (
                $request,
                $leaderTeamIds
            ) {
                $query
                    ->whereKey(
                        $request->user()->id
                    )
                    ->orWhereIn(
                        'team_id',
                        $leaderTeamIds
                    );
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
                'team_id',
            ]);

    } else {

        $users = collect();
    }

    if ($hasFullAccess) {

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

    } elseif ($isTeamLeader) {

        $teams = Team::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereIn(
                'id',
                $leaderTeamIds
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

    } else {

        $teams = collect();
    }

    $labels = LeadLabel::query()
        ->where(
            'company_id',
            $companyId
        )
        ->withCount('leads')
        ->orderBy('name')
        ->get();

    return view('manage-leads.index', [

        'leads' => $leads,

        'statuses' => $statuses,
        'dispositions' => $dispositions,
        'sources' => $sources,
        'categories' => $categories,
        'cities' => $cities,

        'perPage' => $perPage,

        'users' => $users,
        'teams' => $teams,

        'labels' => $labels,

        'hasFullAccess' => $hasFullAccess,
        'isTeamLeader' => $isTeamLeader,
        'canFilterByEmployee' => $canFilterByEmployee,
        'canFilterByTeam' => $canFilterByTeam,
    ]);
}

    public function create(Request $request): View
    {
        return view(
            'leads.form',
            $this->formData($request)
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {

        $companyId = $this->companyId($request);

        $validated = $this->validateData($request);

        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] =
                (int) $request->user()->id;
        }

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

                if (!empty($validated['assigned_to'])) {
                    $this->createAssignmentHistory(
                        lead: $lead,
                        previousUserId: null,
                        newUserId: (int) $validated['assigned_to'],
                        assignedBy: (int) $request->user()->id,
                        reason: $this->hasFullAccess($request)
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

    public function show(
        Request $request,
        Lead $lead
    ):
     View {

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
            'labels:id,company_id,name,color',
        ]);

        $companyId =
            $this->companyId($request);

        $navigationParams = $request->except([
            'page',
        ]);

        $navigationQuery =
            $this->filteredLeadQuery($request);

        $previousLead =
            (clone $navigationQuery)
            ->where('id', '>', $lead->id)
            ->orderBy('id')
            ->first([
                'id',
                'name',
            ]);

        $nextLead =
            (clone $navigationQuery)
            ->where('id', '<', $lead->id)
            ->orderByDesc('id')
            ->first([
                'id',
                'name',
            ]);

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

        $labels = LeadLabel::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'color']);

        return view('leads.show', [
            'lead' => $lead,

            'users' => $users,

            'dispositions' =>
            $dispositions,

            'hasFullAccess' =>
            $this->hasFullAccess($request),

            'previousLead' =>
            $previousLead,

            'nextLead' =>
            $nextLead,

            'navigationParams' =>
            $navigationParams,

            'labels' => $labels,
        ]);
    }

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

    public function update(
    Request $request,
    Lead $lead
): RedirectResponse {
    $this->guard(
        $request,
        $lead
    );

    if ($request->boolean('demo_send_only')) {

        $demoValidated = $request->validate([
            'demo_send' => [
                'required',
                'boolean',
            ],
        ]);

        $isDemoSend = (bool) $demoValidated['demo_send'];

        if ($isDemoSend) {

            $lead->demo_send = true;

            if (empty($lead->demo_sent_at)) {
                $lead->demo_sent_at = now();
            }

        } else {

            $lead->demo_send = false;
            $lead->demo_sent_at = null;
        }

        $lead->save();

        return back()->with(
            'success',
            $lead->demo_send
                ? 'Lead marked as Demo Send.'
                : 'Demo Send mark removed.'
        );
    }

    $validated = $this->validateData(
        $request,
        $lead
    );

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

    public function storeLabel(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_labels', 'name')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        LeadLabel::create([
            'company_id' => $companyId,
            'name' => trim($validated['name']),
            'color' => strtoupper($validated['color']),
            'created_by' => (int) $request->user()->id,
        ]);

        return back()->with('success', 'Label created successfully.');
    }

    public function addLabel(Request $request, Lead $lead): RedirectResponse
    {
        $this->guard($request, $lead);
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'label_id' => [
                'required',
                'integer',
                Rule::exists('lead_labels', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
        ]);

        $lead->labels()->syncWithoutDetaching([(int) $validated['label_id']]);

        return back()->with('success', 'Lead added to label successfully.');
    }

    public function removeLabel(
        Request $request,
        Lead $lead,
        LeadLabel $label
    ): RedirectResponse {
        $this->guard($request, $lead);

        abort_unless(
            (int) $label->company_id === $this->companyId($request),
            403
        );

        $lead->labels()->detach($label->id);

        return back()->with('success', 'Label removed from lead.');
    }

    public function bulkLabel(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['required', 'integer'],
            'label_id' => [
                'required',
                'integer',
                Rule::exists('lead_labels', 'id')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'label_action' => ['required', Rule::in(['add', 'remove'])],
        ]);

        $accessRequest = new Request();
        $accessRequest->setUserResolver(fn () => $request->user());

        $leads = $this->filteredLeadQuery($accessRequest)
            ->whereIn('leads.id', $validated['lead_ids'])
            ->get(['leads.id']);

        if ($leads->isEmpty()) {
            return back()->with('error', 'No accessible leads selected.');
        }

        $labelId = (int) $validated['label_id'];
        $action = $validated['label_action'];

        DB::transaction(function () use ($leads, $labelId, $action) {
            foreach ($leads as $lead) {
                if ($action === 'add') {
                    $lead->labels()->syncWithoutDetaching([$labelId]);
                } else {
                    $lead->labels()->detach($labelId);
                }
            }
        });

        $count = $leads->count();

        return back()->with(
            'success',
            $action === 'add'
                ? "{$count} lead(s) added to label successfully."
                : "{$count} lead(s) removed from label successfully."
        );
    }

    public function destroyLabel(
        Request $request,
        LeadLabel $label
    ): RedirectResponse {
        $this->ensureFullAccess($request);

        abort_unless(
            (int) $label->company_id === $this->companyId($request),
            403
        );

        $label->delete();

        return back()->with('success', 'Label deleted successfully.');
    }

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

    public function assign(
        Request $request,
        Lead $lead
    ): RedirectResponse {
        $this->ensureFullAccess($request);
        $this->guardCompany($request, $lead);

        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)
                ),
            ],
            'reason' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $oldAssignedUserId = $lead->assigned_to;
        $newAssignedUserId = (int) $validated['assigned_to'];
        $automaticStatusId = $this->automaticAssignedStatusId($companyId);

        if (
            (int) $oldAssignedUserId === $newAssignedUserId
            && (int) $lead->lead_status_id === $automaticStatusId
        ) {
            return back()->with(
                'error',
                'Lead already has this employee and automatic assigned status.'
            );
        }

        DB::transaction(function () use (
            $lead,
            $oldAssignedUserId,
            $newAssignedUserId,
            $automaticStatusId,
            $validated,
            $request,
            $companyId
        ) {
            $lead->forceFill([
                'assigned_to' => $newAssignedUserId,
                'lead_status_id' => $automaticStatusId,
            ])->save();

            if ((int) $oldAssignedUserId !== $newAssignedUserId) {
                $this->createAssignmentHistory(
                    lead: $lead,
                    previousUserId: $oldAssignedUserId
                        ? (int) $oldAssignedUserId
                        : null,
                    newUserId: $newAssignedUserId,
                    assignedBy: (int) $request->user()->id,
                    reason: $validated['reason'],
                    companyId: $companyId
                );
            }
        });

        return back()->with(
            'success',
            'Lead assigned successfully and status changed automatically.'
        );
    }

    public function bulkAssign(
        Request $request
    ): RedirectResponse {
        $this->ensureFullAccess($request);

        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'bulk_action' => [
                'required',
                Rule::in(['assign', 'unassign']),
            ],
            'assignment_scope' => [
                'required',
                Rule::in(['selected', 'filtered']),
            ],
            'lead_ids' => [
                'nullable',
                'array',
                'min:1',
                'required_if:assignment_scope,selected',
            ],
            'lead_ids.*' => [
                'integer',
                Rule::exists('leads', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                ),
            ],
            'assigned_to' => [
                'nullable',
                'required_if:bulk_action,assign',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)
                ),
            ],
            'reason' => [
                'required',
                'string',
                'max:500',
            ],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'integer',
                Rule::exists('lead_statuses', 'id')->where(
                    fn ($query) => $query
                        ->where(function ($subQuery) use ($companyId) {
                            $subQuery
                                ->whereNull('company_id')
                                ->orWhere('company_id', $companyId);
                        })
                        ->where('is_active', true)
                ),
            ],
            'source' => ['nullable', 'integer'],
            'city' => ['nullable', 'string', 'max:255'],
            'filter_assigned_to' => ['nullable', 'string'],
            'team_id' => ['nullable', 'integer'],
            'priority' => [
                'nullable',
                Rule::in(['low', 'normal', 'high', 'urgent', 'hot']),
            ],
            'temperature' => [
                'nullable',
                Rule::in(['cold', 'warm', 'hot']),
            ],
            'call_disposition' => [
                'nullable',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ) use ($companyId) {
                    if (in_array((string) $value, ['no_call', 'all'], true)) {
                        return;
                    }

                    if (
                        !ctype_digit((string) $value)
                        || !CallDisposition::query()
                            ->whereKey((int) $value)
                            ->where(function (Builder $query) use ($companyId) {
                                $query
                                    ->whereNull('company_id')
                                    ->orWhere('company_id', $companyId);
                            })
                            ->where('is_active', true)
                            ->exists()
                    ) {
                        $fail('The selected call disposition is invalid.');
                    }
                },
            ],
            'demo_send' => ['nullable', 'boolean'],
            'per_page' => [
                'nullable',
                'integer',
                Rule::in([25, 50, 100, 200]),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'label_id_filter' => ['nullable', 'integer'],
        ]);

        if ($validated['assignment_scope'] === 'selected') {
            $targetQuery = Lead::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $validated['lead_ids'] ?? []);
        } else {
            $filterRequest = new Request([
                'search' => $validated['search'] ?? null,
                'status' => $validated['status'] ?? null,
                'source' => $validated['source'] ?? null,
                'city' => $validated['city'] ?? null,
                'assigned_to' => $validated['filter_assigned_to'] ?? null,
                'team_id' => $validated['team_id'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'call_disposition' => $validated['call_disposition'] ?? null,
                'demo_send' => $validated['demo_send'] ?? null,
                'per_page' => $validated['per_page'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'label_id' => $validated['label_id_filter'] ?? null,
            ]);

            $filterRequest->setUserResolver(
                fn () => $request->user()
            );

            $targetQuery = $this->filteredLeadQuery($filterRequest);
        }

        if ((clone $targetQuery)->count() < 1) {
            return back()->with(
                'error',
                $validated['bulk_action'] === 'unassign'
                    ? 'No leads found for bulk unassign.'
                    : 'No leads found for bulk assignment.'
            );
        }

        $assignedBy = (int) $request->user()->id;
        $reason = $validated['reason'];
        $updatedCount = 0;
        $skippedCount = 0;

        if ($validated['bulk_action'] === 'unassign') {
            $targetQuery
                ->select(['id', 'company_id', 'assigned_to'])
                ->orderBy('id')
                ->chunkById(
                    200,
                    function ($leads) use (
                        &$updatedCount,
                        &$skippedCount
                    ) {
                        DB::transaction(function () use (
                            $leads,
                            &$updatedCount,
                            &$skippedCount
                        ) {
                            foreach ($leads as $lead) {
                                if (empty($lead->assigned_to)) {
                                    $skippedCount++;
                                    continue;
                                }

                                $lead->forceFill([
                                    'assigned_to' => null,
                                ])->save();

                                $updatedCount++;
                            }
                        });
                    }
                );

            return back()->with(
                'success',
                "{$updatedCount} leads unassigned successfully. {$skippedCount} already unassigned leads skipped."
            );
        }

        $newUserId = (int) $validated['assigned_to'];
        $automaticStatusId = $this->automaticAssignedStatusId($companyId);
        $statusUpdatedCount = 0;

        $targetQuery
            ->select([
                'id',
                'company_id',
                'assigned_to',
                'lead_status_id',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($leads) use (
                    $newUserId,
                    $automaticStatusId,
                    $assignedBy,
                    $reason,
                    $companyId,
                    &$updatedCount,
                    &$statusUpdatedCount,
                    &$skippedCount
                ) {
                    DB::transaction(function () use (
                        $leads,
                        $newUserId,
                        $automaticStatusId,
                        $assignedBy,
                        $reason,
                        $companyId,
                        &$updatedCount,
                        &$statusUpdatedCount,
                        &$skippedCount
                    ) {
                        foreach ($leads as $lead) {
                            $previousUserId = $lead->assigned_to;
                            $previousStatusId = $lead->lead_status_id;

                            $ownerChanged =
                                (int) $previousUserId !== $newUserId;

                            $statusChanged =
                                (int) $previousStatusId !== $automaticStatusId;

                            if (!$ownerChanged && !$statusChanged) {
                                $skippedCount++;
                                continue;
                            }

                            $lead->forceFill([
                                'assigned_to' => $newUserId,
                                'lead_status_id' => $automaticStatusId,
                            ])->save();

                            if ($ownerChanged) {
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

                            if ($statusChanged) {
                                $statusUpdatedCount++;
                            }
                        }
                    });
                }
            );

        return back()->with(
            'success',
            "{$updatedCount} lead owner(s) updated and {$statusUpdatedCount} lead status(es) changed automatically. {$skippedCount} unchanged lead(s) skipped."
        );
    }

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
            trim($validated['body']),
        ]);

        return back()->with(
            'success',
            'Note added successfully.'
        );
    }

    private function filteredLeadQuery(
    Request $request
): Builder {
    $companyId = $this->companyId($request);
    $user = $request->user();
    $hasFullAccess = $this->hasFullAccess($request);

    $query = Lead::query()
        ->where(
            'company_id',
            $companyId
        );

    $leaderTeamIds = [];

    if (!$hasFullAccess) {

        $leaderTeamIds =
            $this->leaderTeamIds($request);

        if (empty($leaderTeamIds)) {

            $query->where(
                'assigned_to',
                $user->id
            );

        } else {

            $query->where(
                function (Builder $accessQuery) use (
                    $user,
                    $companyId,
                    $leaderTeamIds
                ) {
                    $accessQuery
                        ->where(
                            'assigned_to',
                            $user->id
                        )
                        ->orWhereIn(
                            'assigned_to',
                            User::query()
                                ->select('id')
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->whereIn(
                                    'team_id',
                                    $leaderTeamIds
                                )
                        );
                }
            );
        }
    }

    if ($request->filled('search')) {

        $search = trim(
            (string) $request->input('search')
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
                    )
                    ->orWhere(
                        'category',
                        'like',
                        "%{$search}%"
                    );
            }
        );
    }

    if ($request->filled('status')) {
        $statusId = (int) $request->input('status');

        $validStatus = LeadStatus::query()
            ->whereKey($statusId)
            ->where(function (Builder $statusQuery) use ($companyId) {
                $statusQuery
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('is_active', true)
            ->exists();

        if ($validStatus) {
            $query->where('lead_status_id', $statusId);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    if ($request->filled('source')) {
        $query->where(
            'lead_source_id',
            $request->input('source')
        );
    }

    if ($request->filled('category')) {

        $category = trim(
            (string) $request->input('category')
        );

        $query->where(
            'category',
            $category
        );
    }

    if ($request->filled('city')) {
        $city = trim((string) $request->input('city'));

        $query->where('city', $city);
    }

    $isTeamLeader =
        !$hasFullAccess &&
        !empty($leaderTeamIds);

    if (
        ($hasFullAccess || $isTeamLeader)
        &&
        $request->filled('assigned_to')
    ) {

        $assignedTo =
            (string) $request->input('assigned_to');

        if ($assignedTo === 'unassigned') {

            if ($hasFullAccess) {

                $query->whereNull(
                    'assigned_to'
                );

            } else {

                $query->whereRaw('1 = 0');
            }

        } elseif (ctype_digit($assignedTo)) {

            $query->where(
                'assigned_to',
                (int) $assignedTo
            );

        } else {

            $query->whereRaw('1 = 0');
        }
    }

    if ($request->filled('team_id')) {

        $teamId =
            (int) $request->input('team_id');

        $query->where(
            function (Builder $teamQuery) use (
                $companyId,
                $teamId
            ) {

                $teamQuery
                    ->where(
                        'team_id',
                        $teamId
                    )
                    ->orWhereIn(
                        'assigned_to',
                        User::query()
                            ->select('id')
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'team_id',
                                $teamId
                            )
                    );
            }
        );
    }

    if ($request->filled('priority')) {

        $priority =
            (string) $request->input('priority');

        if (in_array(
            $priority,
            [
                'low',
                'normal',
                'high',
                'urgent',
                'hot',
            ],
            true
        )) {
            $query->where(
                'priority',
                $priority
            );
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    if ($request->filled('temperature')) {

        $temperature =
            (string) $request->input('temperature');

        if (in_array(
            $temperature,
            [
                'cold',
                'warm',
                'hot',
            ],
            true
        )) {
            $query->where(
                'temperature',
                $temperature
            );
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    if ($request->filled('call_disposition')) {

        $callDisposition =
            (string) $request->input(
                'call_disposition'
            );

        if ($callDisposition === 'no_call') {

            $query->whereDoesntHave(
                'calls'
            );

        } elseif (ctype_digit($callDisposition)) {

            $dispositionId =
                (int) $callDisposition;

            $validDisposition =
                CallDisposition::query()
                    ->whereKey(
                        $dispositionId
                    )
                    ->where(
                        function (
                            Builder $dispositionQuery
                        ) use ($companyId) {

                            $dispositionQuery
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
                    ->exists();

            if ($validDisposition) {

                $query->whereHas(
                    'calls',
                    function (
                        Builder $callQuery
                    ) use (
                        $dispositionId
                    ) {

                        $callQuery
                            ->where(
                                'call_disposition_id',
                                $dispositionId
                            )
                            ->whereRaw(
                                'call_logs.id = (
                                    SELECT MAX(latest_call.id)
                                    FROM call_logs AS latest_call
                                    WHERE latest_call.lead_id = leads.id
                                )'
                            );
                    }
                );

            } else {

                $query->whereRaw(
                    '1 = 0'
                );
            }

        } else {

            $query->whereRaw(
                '1 = 0'
            );
        }
    }

    if ($request->filled('label_id')) {

        $labelId =
            (int) $request->input(
                'label_id'
            );

        $validLabel =
            LeadLabel::query()
                ->whereKey(
                    $labelId
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->exists();

        if ($validLabel) {

            $query->whereHas(
                'labels',
                function (
                    Builder $labelQuery
                ) use (
                    $labelId
                ) {

                    $labelQuery->where(
                        'lead_labels.id',
                        $labelId
                    );
                }
            );

        } else {

            $query->whereRaw(
                '1 = 0'
            );
        }
    }

    if ($request->boolean('demo_send')) {

        $query->where(
            'demo_send',
            true
        );
    }

    if ($request->filled('lead_send')) {

        $leadSend =
            (string) $request->input(
                'lead_send'
            );

        if ($leadSend === 'today') {

            $query
                ->where(
                    'demo_send',
                    true
                )
                ->whereNotNull(
                    'demo_sent_at'
                )
                ->whereDate(
                    'demo_sent_at',
                    today()
                );

        } elseif ($leadSend === 'all') {

            $query->where(
                'demo_send',
                true
            );

        } else {

            $query->whereRaw(
                '1 = 0'
            );
        }
    }

    if (
        $request->input(
            'created_filter'
        ) === 'today'
    ) {
        $query->whereDate(
            'created_at',
            today()
        );
    }

    if ($request->filled('date_from')) {

        $query->whereDate(
            'created_at',
            '>=',
            $request->input(
                'date_from'
            )
        );
    }

    if ($request->filled('date_to')) {

        $query->whereDate(
            'created_at',
            '<=',
            $request->input(
                'date_to'
            )
        );
    }

    return $query;
}

    private function formData(
        Request $request
    ): array {
        $companyId =
            $this->companyId($request);

        $hasFullAccess =
            $this->hasFullAccess($request);

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
                    fn(
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
                        fn($query) =>
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
                    fn($query) =>
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

    private function automaticAssignedStatusId(int $companyId): int
    {
        $baseQuery = LeadStatus::query()
            ->where(function (Builder $query) use ($companyId) {
                $query
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('is_active', true);

        $preferredNames = [
            'assigned',
            'in progress',
            'working',
            'open',
        ];

        foreach ($preferredNames as $name) {
            $statusId = (clone $baseQuery)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->value('id');

            if ($statusId) {
                return (int) $statusId;
            }
        }

        $statusId = (clone $baseQuery)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        abort_if(
            !$statusId,
            422,
            'No active lead status is available for automatic assignment.'
        );

        return (int) $statusId;
    }

    private function companyId(
        Request $request
    ): int {
        return (int)
        $request->user()->company_id;
    }

    private function leaderTeamIds(
        Request $request
    ): array {
        $companyId =
            $this->companyId($request);

        return Team::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'leader_id',
                $request->user()->id
            )
            ->pluck('id')
            ->map(
                fn($id) => (int) $id
            )
            ->values()
            ->all();
    }

    private function isTeamLeader(
        Request $request
    ): bool {
        return !empty($this->leaderTeamIds($request));
    }

    private function hasFullAccess(
        Request $request
    ): bool {
        return $request
            ->user()
            ->hasAnyRole(
                $this->fullAccessRoles
            );
    }

    private function ensureFullAccess(
        Request $request
    ): void {
        abort_unless(
            $this->hasFullAccess($request),
            403,
            'You do not have permission to manage lead assignments.'
        );
    }

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

    private function guard(
        Request $request,
        Lead $lead
    ): void {

        $this->guardCompany(
            $request,
            $lead
        );

        if ($this->hasFullAccess($request)) {
            return;
        }

        $user = $request->user();
        $companyId =
            $this->companyId($request);

        if (
            (int) $lead->assigned_to ===
            (int) $user->id
        ) {
            return;
        }

        $leaderTeamIds =
            $this->leaderTeamIds($request);

        if (!empty($leaderTeamIds)) {
            $assignedEmployeeIsInLeaderTeam =
                User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->whereKey(
                    $lead->assigned_to
                )
                ->whereIn(
                    'team_id',
                    $leaderTeamIds
                )
                ->exists();

            if ($assignedEmployeeIsInLeaderTeam) {
                return;
            }
        }

        abort(
            403,
            'This lead is not assigned to you or your team.'
        );
    }
}