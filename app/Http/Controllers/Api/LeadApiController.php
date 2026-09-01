<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallDisposition;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadLabel;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Note;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\MobileCallService;
use Throwable;
use App\Models\CallLog;
use App\Models\FollowUp;


class LeadApiController extends Controller
{



public function callOnMobile(
    Request $request,
    Lead $lead,
    MobileCallService $mobileCallService
): JsonResponse {

    $user = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Company Security
    |--------------------------------------------------------------------------
    */

    if (
        isset($user->company_id) &&
        isset($lead->company_id) &&
        (int) $user->company_id !== (int) $lead->company_id
    ) {
        return response()->json([
            'status' => false,
            'message' => 'You are not allowed to access this lead.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Lead Security
    |--------------------------------------------------------------------------
    |
    | Normal employee ko doosre employee ki lead par call nahi karne dena.
    |
    | Agar aapke LeadApiController me already guard/access method hai,
    | to ye block hata kar wahi guard use karna better hai.
    |
    */

    $hasFullAccess = $user->hasAnyRole([
        'super_admin',
        'admin',
    ]);

    if (
        !$hasFullAccess &&
        (int) $lead->assigned_to !== (int) $user->id
    ) {
        return response()->json([
            'status' => false,
            'message' => 'This lead is not assigned to you.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile Number
    |--------------------------------------------------------------------------
    */

    if (blank($lead->mobile)) {
        return response()->json([
            'status' => false,
            'message' => 'Lead mobile number is missing.',
        ], 422);
    }

    try {

        $result = $mobileCallService->send(
            $user,
            $lead
        );

        return response()->json([
            'status' => true,

            'message' =>
                'Call command sent to your mobile app.',

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

    private array $fullAccessRoles = ['super_admin', 'admin'];

    public function indexOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer'],
            'source' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string'],
            'team_id' => ['nullable', 'integer'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent', 'hot'])],
            'temperature' => ['nullable', Rule::in(['cold', 'warm', 'hot'])],
            'call_disposition' => ['nullable', 'string'],
            'label_id' => ['nullable', 'integer'],
            'demo_send' => ['nullable', 'boolean'],
            'lead_send' => ['nullable', Rule::in(['today', 'all'])],
            'created_filter' => ['nullable', Rule::in(['today'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100, 200])],
        ]);

        $leads = $this->filteredLeadQuery($request)
            ->with([
                'assignedUser:id,name,email,employee_code,team_id',
                'source:id,name',
                'status:id,name,color',
                'team:id,name',
                'stage:id,name,color',
                'labels:id,company_id,name,color',
            ])
            ->addSelect([
                'latest_note_body' => Note::query()
                    ->select('body')->whereColumn('notes.lead_id', 'leads.id')
                    ->latest('notes.id')->limit(1),
                'latest_note_created_at' => Note::query()
                    ->select('created_at')->whereColumn('notes.lead_id', 'leads.id')
                    ->latest('notes.id')->limit(1),
            ])
            ->latest('leads.id')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return $this->success($leads, 'Leads fetched successfully.');
    }



    public function index(Request $request): JsonResponse
{
    /*
    |--------------------------------------------------------------------------
    | Default Call Filter
    |--------------------------------------------------------------------------
    |
    | call_disposition नहीं भेजने पर केवल ऐसी leads आएंगी जिन पर अभी
    | तक कोई call log नहीं बना है।
    |
    | call_disposition=all भेजने पर सभी accessible leads आएंगी।
    |
    */

    if (!$request->has('call_disposition')) {
        $request->merge([
            'call_disposition' => 'no_call',
        ]);
    }

    $validated = $request->validate([
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

        'category' => [
            'nullable',
            'string',
            'max:255',
        ],

        'assigned_to' => [
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

        /*
         * Allowed values:
         * no_call
         * all
         * disposition ID जैसे 1, 2, 3
         */
        'call_disposition' => [
            'nullable',
            'string',
            'max:50',
        ],

        'label_id' => [
            'nullable',
            'integer',
        ],

        'demo_send' => [
            'nullable',
            'boolean',
        ],

        'lead_send' => [
            'nullable',
            Rule::in([
                'today',
                'all',
            ]),
        ],

        'created_filter' => [
            'nullable',
            Rule::in([
                'today',
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

        'per_page' => [
            'nullable',
            'integer',
            Rule::in([
                10,
                25,
                50,
                100,
                200,
            ]),
        ],
    ]);

    $callDisposition = (string) (
        $validated['call_disposition']
        ?? 'no_call'
    );

    /*
    |--------------------------------------------------------------------------
    | Filter Request
    |--------------------------------------------------------------------------
    |
    | filteredLeadQuery() के अंदर मौजूद call disposition filter हटाकर
    | नीचे नया complete filter लगाया जाएगा।
    |
    */

    $filterRequest = clone $request;

    $filterRequest->query->remove(
        'call_disposition'
    );

    $filterRequest->request->remove(
        'call_disposition'
    );

    /*
    |--------------------------------------------------------------------------
    | Base Lead Query
    |--------------------------------------------------------------------------
    */

    $query = $this->filteredLeadQuery(
        $filterRequest
    )
        ->with([
            'assignedUser:id,name,email,employee_code,team_id',
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

    /*
    |--------------------------------------------------------------------------
    | Call Filter
    |--------------------------------------------------------------------------
    */

    if ($callDisposition === 'no_call') {
        /*
         * जिन leads पर एक भी call log नहीं है।
         */
        $query->whereDoesntHave('calls');
    } elseif ($callDisposition === 'all') {
        /*
         * सभी accessible leads.
         * कोई call filter नहीं लगेगा।
         */
    } elseif (ctype_digit($callDisposition)) {
        /*
         * केवल वही leads जिनकी latest call का disposition
         * selected disposition है।
         */

        $dispositionId = (int) $callDisposition;

        $query->whereHas(
            'calls',
            function (
                Builder $callQuery
            ) use ($dispositionId) {
                $callQuery
                    ->where(
                        'call_disposition_id',
                        $dispositionId
                    )
                    ->whereRaw(
                        'call_logs.id = (
                            SELECT MAX(cl2.id)
                            FROM call_logs AS cl2
                            WHERE cl2.lead_id = call_logs.lead_id
                        )'
                    );
            }
        );
    } else {
        /*
         * गलत call_disposition value पर empty result.
         */
        $query->whereRaw('1 = 0');
    }

    $perPage = (int) (
        $validated['per_page'] ?? 25
    );

    $leads = $query
        ->latest('leads.id')
        ->paginate($perPage);

    /*
     * Response में applied filter भी मिलेगा।
     */
    $leads->setCollection(
        $leads->getCollection()->map(
            function ($lead) use ($callDisposition) {
                $lead->applied_call_filter =
                    $callDisposition;

                return $lead;
            }
        )
    );

    return $this->success(
        $leads,
        'Leads fetched successfully.'
    );
}

    public function options(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $hasFullAccess = $this->hasFullAccess($request);
        $leaderTeamIds = $this->leaderTeamIds($request);

        $users = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->when(!$hasFullAccess, function (Builder $query) use ($request, $leaderTeamIds) {
                $query->where(function (Builder $q) use ($request, $leaderTeamIds) {
                    $q->whereKey($request->user()->id);
                    if ($leaderTeamIds !== []) {
                        $q->orWhereIn('team_id', $leaderTeamIds);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'employee_code', 'team_id']);

        $teams = Team::query()
            ->where('company_id', $companyId)
            ->when(!$hasFullAccess && $leaderTeamIds !== [], fn(Builder $q) => $q->whereIn('id', $leaderTeamIds))
            ->when(!$hasFullAccess && $leaderTeamIds === [], fn(Builder $q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')->get(['id', 'name']);

        $companyOrGlobal = fn(Builder $q) => $q->where(fn(Builder $x) => $x->whereNull('company_id')->orWhere('company_id', $companyId));

        return $this->success([
            'sources' => LeadSource::query()->where($companyOrGlobal)->where('is_active', true)->orderBy('name')->get(),
            'statuses' => LeadStatus::query()->where($companyOrGlobal)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'dispositions' => CallDisposition::query()->where($companyOrGlobal)->where('is_active', true)->orderBy('name')->get(),
            'users' => $users,
            'teams' => $teams,
            'stages' => PipelineStage::query()->whereHas('pipeline', fn(Builder $q) => $q->where('company_id', $companyId))->orderBy('sort_order')->get(),
            'labels' => LeadLabel::query()->where('company_id', $companyId)->withCount('leads')->orderBy('name')->get(),
            'categories' => Lead::query()->where('company_id', $companyId)->whereNotNull('category')->where('category', '<>', '')->distinct()->orderBy('category')->pluck('category'),
            'access' => [
                'full_access' => $hasFullAccess,
                'team_leader' => !$hasFullAccess && $leaderTeamIds !== [],
                'can_assign' => $hasFullAccess,
            ],
        ], 'Lead form data fetched successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $validated = $this->validateData($request);

        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] = (int) $request->user()->id;
        }

        $validated['pipeline_stage_id'] ??= $this->defaultPipelineStageId($companyId);
        $validated['company_id'] = $companyId;
        $validated['created_by'] = (int) $request->user()->id;
        $validated['priority'] ??= 'normal';
        $validated['temperature'] ??= 'cold';

        $lead = DB::transaction(function () use ($validated, $request, $companyId) {
            $lead = Lead::create($validated);
            if (!empty($validated['assigned_to'])) {
                $this->createAssignmentHistory($lead, null, (int) $validated['assigned_to'], (int) $request->user()->id, 'Lead assigned during creation', $companyId);
            }
            return $lead;
        });

        return $this->success($this->loadLead($lead), 'Lead created successfully.', 201);
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $this->guard($request, $lead);
        return $this->success($this->loadLead($lead), 'Lead fetched successfully.');
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $this->guard($request, $lead);

        if ($request->boolean('demo_send_only')) {
            $data = $request->validate(['demo_send' => ['required', 'boolean']]);
            $enabled = (bool) $data['demo_send'];
            $lead->update([
                'demo_send' => $enabled,
                'demo_sent_at' => $enabled ? ($lead->demo_sent_at ?? now()) : null,
            ]);
            return $this->success($this->loadLead($lead->fresh()), $enabled ? 'Lead marked as Demo Send.' : 'Demo Send mark removed.');
        }

        $validated = $this->validateData($request, $lead);
        if (!$this->hasFullAccess($request)) {
            $validated['assigned_to'] = $lead->assigned_to;
        }

        $oldUserId = $lead->assigned_to ? (int) $lead->assigned_to : null;
        $newUserId = !empty($validated['assigned_to']) ? (int) $validated['assigned_to'] : null;

        DB::transaction(function () use ($lead, $validated, $oldUserId, $newUserId, $request) {
            $lead->update($validated);
            if ($this->hasFullAccess($request) && $oldUserId !== $newUserId && $newUserId !== null) {
                $this->createAssignmentHistory($lead, $oldUserId, $newUserId, (int) $request->user()->id, 'Lead owner changed from API', $this->companyId($request));
            }
        });

        return $this->success($this->loadLead($lead->fresh()), 'Lead updated successfully.');
    }

    public function destroy(Request $request, Lead $lead): JsonResponse
    {
        $this->ensureFullAccess($request);
        $this->guardCompany($request, $lead);
        $lead->delete();
        return $this->success(null, 'Lead moved to trash.');
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $this->ensureFullAccess($request);
        $this->guardCompany($request, $lead);
        $companyId = $this->companyId($request);
        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId)->where('is_active', true))],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $old = $lead->assigned_to ? (int) $lead->assigned_to : null;
        $new = (int) $validated['assigned_to'];
        if ($old === $new) {
            return $this->error('Lead is already assigned to this employee.', 422);
        }

        DB::transaction(function () use ($lead, $old, $new, $validated, $request, $companyId) {
            $lead->update(['assigned_to' => $new]);
            $this->createAssignmentHistory($lead, $old, $new, (int) $request->user()->id, $validated['reason'], $companyId);
        });

        return $this->success($this->loadLead($lead->fresh()), 'Lead assigned successfully.');
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $this->ensureFullAccess($request);
        $companyId = $this->companyId($request);
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', Rule::exists('leads', 'id')->where(fn($q) => $q->where('company_id', $companyId)->whereNull('deleted_at'))],
            'action' => ['required', Rule::in(['assign', 'unassign'])],
            'assigned_to' => ['nullable', 'required_if:action,assign', 'integer', Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId)->where('is_active', true))],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $leads = Lead::query()->where('company_id', $companyId)->whereIn('id', $validated['lead_ids'])->get();
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($leads, $validated, $request, $companyId, &$updated, &$skipped) {
            foreach ($leads as $lead) {
                $old = $lead->assigned_to ? (int) $lead->assigned_to : null;
                if ($validated['action'] === 'unassign') {
                    if ($old === null) {
                        $skipped++;
                        continue;
                    }
                    $lead->update(['assigned_to' => null]);
                    $updated++;
                    continue;
                }
                $new = (int) $validated['assigned_to'];
                if ($old === $new) {
                    $skipped++;
                    continue;
                }
                $lead->update(['assigned_to' => $new]);
                $this->createAssignmentHistory($lead, $old, $new, (int) $request->user()->id, $validated['reason'], $companyId);
                $updated++;
            }
        });

        return $this->success(['updated_count' => $updated, 'skipped_count' => $skipped], 'Bulk action completed successfully.');
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $this->guard($request, $lead);
        $validated = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $note = Note::create(['lead_id' => $lead->id, 'user_id' => $request->user()->id, 'body' => trim($validated['body'])]);
        $note->load('user:id,name,email');
        return $this->success($note, 'Note added successfully.', 201);
    }

    public function storeLabel(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('lead_labels', 'name')->where(fn($q) => $q->where('company_id', $companyId))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $label = LeadLabel::create(['company_id' => $companyId, 'name' => trim($validated['name']), 'color' => strtoupper($validated['color']), 'created_by' => $request->user()->id]);
        return $this->success($label, 'Label created successfully.', 201);
    }

    public function addLabel(Request $request, Lead $lead): JsonResponse
    {
        $this->guard($request, $lead);
        $companyId = $this->companyId($request);
        $validated = $request->validate(['label_id' => ['required', 'integer', Rule::exists('lead_labels', 'id')->where(fn($q) => $q->where('company_id', $companyId))]]);
        $lead->labels()->syncWithoutDetaching([(int) $validated['label_id']]);
        return $this->success($lead->labels()->get(), 'Label added to lead successfully.');
    }

    public function removeLabel(Request $request, Lead $lead, LeadLabel $label): JsonResponse
    {
        $this->guard($request, $lead);
        abort_unless((int) $label->company_id === $this->companyId($request), 403);
        $lead->labels()->detach($label->id);
        return $this->success(null, 'Label removed from lead.');
    }

    public function destroyLabel(Request $request, LeadLabel $label): JsonResponse
    {
        $this->ensureFullAccess($request);
        abort_unless((int) $label->company_id === $this->companyId($request), 403);
        $label->delete();
        return $this->success(null, 'Label deleted successfully.');
    }

    private function filteredLeadQuery(Request $request): Builder
    {
        $companyId = $this->companyId($request);
        $user = $request->user();
        $full = $this->hasFullAccess($request);
        $leaderTeamIds = $full ? [] : $this->leaderTeamIds($request);
        $query = Lead::query()->where('company_id', $companyId);

        if (!$full) {
            $query->where(function (Builder $q) use ($user, $companyId, $leaderTeamIds) {
                $q->where('assigned_to', $user->id);
                if ($leaderTeamIds !== []) {
                    $q->orWhereIn('assigned_to', User::query()->select('id')->where('company_id', $companyId)->whereIn('team_id', $leaderTeamIds));
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(fn(Builder $q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")->orWhere('alternate_mobile', 'like', "%{$search}%")
                ->orWhere('whatsapp_number', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%"));
        }

        $query->when($request->filled('status'), fn(Builder $q) => $q->where('lead_status_id', $request->status));
        $query->when($request->filled('source'), fn(Builder $q) => $q->where('lead_source_id', $request->source));
        $query->when($request->filled('category'), fn(Builder $q) => $q->where('category', trim((string) $request->category)));

        if (($full || $leaderTeamIds !== []) && $request->filled('assigned_to')) {
            $assigned = (string) $request->assigned_to;
            if ($assigned === 'unassigned' && $full) $query->whereNull('assigned_to');
            elseif (ctype_digit($assigned)) $query->where('assigned_to', (int) $assigned);
            else $query->whereRaw('1 = 0');
        }

        if ($request->filled('team_id')) {
            $teamId = (int) $request->team_id;
            $query->where(fn(Builder $q) => $q->where('team_id', $teamId)->orWhereIn('assigned_to', User::query()->select('id')->where('company_id', $companyId)->where('team_id', $teamId)));
        }

        $query->when($request->filled('priority'), fn(Builder $q) => $q->where('priority', $request->priority));
        $query->when($request->filled('temperature'), fn(Builder $q) => $q->where('temperature', $request->temperature));
        $query->when($request->filled('label_id'), fn(Builder $q) => $q->whereHas('labels', fn(Builder $x) => $x->where('lead_labels.id', (int) $request->label_id)));

        if ($request->filled('call_disposition')) {
            $value = (string) $request->call_disposition;
            if ($value === 'no_call') $query->whereDoesntHave('calls');
            elseif (ctype_digit($value)) {
                $id = (int) $value;
                $query->whereHas('calls', fn(Builder $q) => $q->where('call_disposition_id', $id)->whereRaw('call_logs.id = (SELECT MAX(c.id) FROM call_logs c WHERE c.lead_id = leads.id)'));
            } else $query->whereRaw('1 = 0');
        }

        if ($request->has('demo_send')) $query->where('demo_send', $request->boolean('demo_send'));
        if ($request->lead_send === 'today') $query->where('demo_send', true)->whereDate('demo_sent_at', today());
        elseif ($request->lead_send === 'all') $query->where('demo_send', true);
        if ($request->created_filter === 'today') $query->whereDate('created_at', today());
        $query->when($request->filled('date_from'), fn(Builder $q) => $q->whereDate('created_at', '>=', $request->date_from));
        $query->when($request->filled('date_to'), fn(Builder $q) => $q->whereDate('created_at', '<=', $request->date_to));
        return $query;
    }

    private function validateData(Request $request, ?Lead $lead = null): array
    {
        $companyId = $this->companyId($request);
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', Rule::unique('leads', 'mobile')->where(fn($q) => $q->where('company_id', $companyId))->ignore($lead?->id)],
            'alternate_mobile' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['required', 'integer', Rule::exists('lead_sources', 'id')->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))],
            'lead_status_id' => ['required', 'integer', Rule::exists('lead_statuses', 'id')->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId)->where('is_active', true))],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')->where(fn($q) => $q->where('company_id', $companyId))],
            'pipeline_stage_id' => ['nullable', 'integer', Rule::exists('pipeline_stages', 'id')->where(fn($q) => $q->whereIn('pipeline_id', Pipeline::query()->select('id')->where('company_id', $companyId)))],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent', 'hot'])],
            'temperature' => ['sometimes', Rule::in(['cold', 'warm', 'hot'])],
            'preferred_language' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'required_product' => ['nullable', 'string', 'max:255'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0'],
            'expected_deal_value' => ['nullable', 'numeric', 'min:0'],
            'expected_closing_date' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);
    }

    private function loadLead(Lead $lead): Lead
    {
        return $lead->load(['assignedUser', 'source', 'status', 'stage', 'team', 'labels', 'calls.user', 'calls.disposition', 'followUps.assignedUser', 'notes.user', 'assignments']);
    }

    private function defaultPipelineStageId(int $companyId): ?int
    {
        $pipeline = Pipeline::query()->where('company_id', $companyId)->orderByDesc('is_default')->orderBy('id')->first();
        return $pipeline ? PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('sort_order')->orderBy('id')->value('id') : null;
    }

    private function createAssignmentHistory(Lead $lead, ?int $previousUserId, int $newUserId, int $assignedBy, string $reason, int $companyId): void
    {
        LeadAssignment::create(['company_id' => $companyId, 'lead_id' => $lead->id, 'previous_user_id' => $previousUserId, 'new_user_id' => $newUserId, 'assigned_by' => $assignedBy, 'reason' => $reason, 'assigned_at' => now()]);
    }

    private function companyId(Request $request): int
    {
        $companyId = (int) $request->user()->company_id;
        abort_if($companyId < 1, 403, 'No company is assigned to this user.');
        return $companyId;
    }

    private function leaderTeamIds(Request $request): array
    {
        return Team::query()->where('company_id', $this->companyId($request))->where('leader_id', $request->user()->id)->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    private function hasFullAccess(Request $request): bool
    {
        return $request->user()->hasAnyRole($this->fullAccessRoles);
    }

    private function ensureFullAccess(Request $request): void
    {
        abort_unless($this->hasFullAccess($request), 403, 'You do not have permission to manage leads.');
    }

    private function guardCompany(Request $request, Lead $lead): void
    {
        abort_unless((int) $lead->company_id === $this->companyId($request), 403, 'Unauthorized company lead access.');
    }

    private function guard(Request $request, Lead $lead): void
    {
        $this->guardCompany($request, $lead);
        if ($this->hasFullAccess($request) || (int) $lead->assigned_to === (int) $request->user()->id) return;
        $allowed = User::query()->where('company_id', $this->companyId($request))->whereKey($lead->assigned_to)->whereIn('team_id', $this->leaderTeamIds($request))->exists();
        abort_unless($allowed, 403, 'This lead is not assigned to you or your team.');
    }

    private function success(mixed $data, string $message, int $code = 200): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data], $code);
    }

    private function error(string $message, int $code): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'data' => null], $code);
    }




    /*
|--------------------------------------------------------------------------
| Lead Complete Communication History
|--------------------------------------------------------------------------
|
| Is API se lead ke:
| - Saare notes
| - Saare call logs
| - Call remarks
| - Call dispositions
| - Kis user ne baat/note add kiya
| - Combined timeline
|
| mil jayegi.
|
*/

public function communicationHistory(
    Request $request,
    Lead $lead
): JsonResponse {
    /*
    |--------------------------------------------------------------------------
    | Lead Access Security
    |--------------------------------------------------------------------------
    |
    | Admin/Super Admin    : company ki lead
    | Team Leader          : apni team ki lead
    | Employee             : khud assigned lead
    |
    */

    $this->guard($request, $lead);

    /*
    |--------------------------------------------------------------------------
    | Request Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([
        'per_page' => [
            'nullable',
            'integer',
            'min:1',
            'max:100',
        ],

        'type' => [
            'nullable',
            Rule::in([
                'all',
                'calls',
                'notes',
            ]),
        ],
    ]);

    $perPage = (int) ($validated['per_page'] ?? 50);
    $historyType = $validated['type'] ?? 'all';

    /*
    |--------------------------------------------------------------------------
    | Basic Lead Details
    |--------------------------------------------------------------------------
    */

    $lead->load([
        'assignedUser:id,name,email,employee_code',
        'source:id,name',
        'status:id,name,color',
        'team:id,name',
        'stage:id,name,color',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Notes
    |--------------------------------------------------------------------------
    */

    $notes = collect();

    if (
        $historyType === 'all'
        || $historyType === 'notes'
    ) {
        $notes = Note::query()
            ->where('lead_id', $lead->id)
            ->with([
                'user:id,name,email,employee_code',
            ])
            ->latest('id')
            ->get()
            ->map(function (Note $note) {
                return [
                    'id' => $note->id,

                    'type' => 'note',

                    'lead_id' => $note->lead_id,

                    'body' => $note->body,

                    'user_id' => $note->user_id,

                    'user' => $note->user
                        ? [
                            'id' => $note->user->id,
                            'name' => $note->user->name,
                            'email' => $note->user->email,
                            'employee_code' =>
                                $note->user->employee_code,
                        ]
                        : null,

                    'created_at' => $note->created_at,

                    'updated_at' => $note->updated_at,

                    /*
                     * Combined timeline ko sort karne ke liye.
                     */
                    'activity_at' => $note->created_at,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Call Logs and Remarks
    |--------------------------------------------------------------------------
    */

    $calls = collect();

    if (
        $historyType === 'all'
        || $historyType === 'calls'
    ) {
        $calls = $lead->calls()
            ->with([
                'user:id,name,email,employee_code',
                'disposition:id,name',
            ])
            ->latest('id')
            ->get()
            ->map(function ($call) {
                /*
                |--------------------------------------------------------------------------
                | Remark Column Compatibility
                |--------------------------------------------------------------------------
                |
                | Alag projects me column ka naam:
                |
                | remarks
                | remark
                | notes
                | call_remark
                |
                | ho sakta hai. Jo available hoga wahi response me jayega.
                |
                */

                $remark =
                    $call->remarks
                    ?? $call->remark
                    ?? $call->call_remark
                    ?? $call->notes
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | Call Date Compatibility
                |--------------------------------------------------------------------------
                */

                $calledAt =
                    $call->called_at
                    ?? $call->started_at
                    ?? $call->call_started_at
                    ?? $call->created_at;

                /*
                |--------------------------------------------------------------------------
                | Call Duration Compatibility
                |--------------------------------------------------------------------------
                */

                $duration =
                    $call->duration_seconds
                    ?? $call->duration
                    ?? $call->call_duration
                    ?? 0;

                /*
                |--------------------------------------------------------------------------
                | Recording Compatibility
                |--------------------------------------------------------------------------
                */

                $recording =
                    $call->recording_url
                    ?? $call->recording_path
                    ?? $call->audio_url
                    ?? null;

                return [
                    'id' => $call->id,

                    'type' => 'call',

                    'lead_id' => $call->lead_id,

                    'user_id' => $call->user_id,

                    'call_disposition_id' =>
                        $call->call_disposition_id,

                    'disposition' => $call->disposition
                        ? [
                            'id' => $call->disposition->id,
                            'name' => $call->disposition->name,
                        ]
                        : null,

                    'call_type' =>
                        $call->call_type
                        ?? $call->direction
                        ?? null,

                    'phone_number' =>
                        $call->phone_number
                        ?? $call->mobile
                        ?? null,

                    'remark' => $remark,

                    'duration_seconds' => (int) $duration,

                    'called_at' => $calledAt,

                    'recording_url' => $recording,

                    'user' => $call->user
                        ? [
                            'id' => $call->user->id,
                            'name' => $call->user->name,
                            'email' => $call->user->email,
                            'employee_code' =>
                                $call->user->employee_code,
                        ]
                        : null,

                    'created_at' => $call->created_at,

                    'updated_at' => $call->updated_at,

                    /*
                     * Combined timeline ko sort karne ke liye.
                     */
                    'activity_at' => $calledAt,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Combined Timeline
    |--------------------------------------------------------------------------
    |
    | Notes aur calls ko ek saath date/time ke hisaab se latest first.
    |
    */

    $timeline = $notes
        ->concat($calls)
        ->sortByDesc(function (array $history) {
            return optional(
                \Illuminate\Support\Carbon::parse(
                    $history['activity_at']
                )
            )->timestamp;
        })
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Manual Pagination
    |--------------------------------------------------------------------------
    */

    $currentPage = max(
        1,
        (int) $request->input('page', 1)
    );

    $total = $timeline->count();

    $paginatedTimeline = $timeline
        ->forPage(
            $currentPage,
            $perPage
        )
        ->values();

    $lastPage = max(
        1,
        (int) ceil($total / $perPage)
    );

    /*
    |--------------------------------------------------------------------------
    | Final Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'status' => true,

        'message' =>
            'Lead communication history fetched successfully.',

        'data' => [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'mobile' => $lead->mobile,
                'alternate_mobile' =>
                    $lead->alternate_mobile,
                'whatsapp_number' =>
                    $lead->whatsapp_number,
                'email' => $lead->email,

                'assigned_user' =>
                    $lead->assignedUser,

                'source' => $lead->source,

                'status' => $lead->status,

                'team' => $lead->team,

                'stage' => $lead->stage,
            ],

            'summary' => [
                'total_calls' =>
                    $lead->calls()->count(),

                'total_notes' =>
                    $lead->notes()->count(),

                'total_communications' =>
                    $lead->calls()->count()
                    + $lead->notes()->count(),

                'last_call_at' =>
                    $lead->calls()
                        ->latest('id')
                        ->value('created_at'),

                'last_note_at' =>
                    $lead->notes()
                        ->latest('id')
                        ->value('created_at'),
            ],

            'timeline' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'data' => $paginatedTimeline,
            ],
        ],
    ]);
}





public function saveCallResult(
    Request $request,
    Lead $lead
): JsonResponse {
    $this->guard($request, $lead);

    $companyId = $this->companyId($request);
    $userId = (int) $request->user()->id;

    /*
     * Flutter compatibility:
     * दोनों field names स्वीकार किए जाएंगे।
     */
    if (
        !$request->filled('call_disposition_id')
        && $request->filled('disposition_id')
    ) {
        $request->merge([
            'call_disposition_id' =>
                $request->input('disposition_id'),
        ]);
    }

    if (
        !$request->filled('follow_up_at')
        && $request->filled('next_follow_up_at')
    ) {
        $request->merge([
            'follow_up_at' =>
                $request->input('next_follow_up_at'),
        ]);
    }

    $request->validate([
        'call_disposition_id' => [
            'required',
            'integer',
        ],
    ]);

    /*
     * केवल global या current company disposition allow करें।
     */
    $disposition = CallDisposition::query()
        ->whereKey(
            (int) $request->input(
                'call_disposition_id'
            )
        )
        ->where('is_active', true)
        ->where(function (Builder $query) use ($companyId) {
            $query
                ->whereNull('company_id')
                ->orWhere('company_id', $companyId);
        })
        ->firstOrFail();

    $validated = $request->validate([
        'call_disposition_id' => [
            'required',
            'integer',
        ],

        'remarks' => [
            $disposition->requires_remarks
                ? 'required'
                : 'nullable',
            'string',
            'max:3000',
        ],

        'follow_up_at' => [
            $disposition->requires_follow_up
                ? 'required'
                : 'nullable',
            'nullable',
            'date',
            'after:now',
        ],

        'duration_seconds' => [
            'nullable',
            'integer',
            'min:0',
        ],
    ], [
        'remarks.required' =>
            "Remarks are required for {$disposition->name}.",

        'follow_up_at.required' =>
            "Follow-up date and time are required for {$disposition->name}.",

        'follow_up_at.after' =>
            'Follow-up date and time must be in the future.',
    ]);

    $remarks = trim(
        (string) ($validated['remarks'] ?? '')
    );

    $followUpAt =
        $validated['follow_up_at'] ?? null;

    $durationSeconds = (int) (
        $validated['duration_seconds'] ?? 0
    );

    $result = DB::transaction(function () use (
        $lead,
        $companyId,
        $userId,
        $disposition,
        $remarks,
        $followUpAt,
        $durationSeconds
    ) {
        /*
         * Call log save करें।
         */
        $callLog = CallLog::create([
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'user_id' => $userId,

            'call_disposition_id' =>
                $disposition->id,

            'direction' => 'outgoing',

            'started_at' => now()->subSeconds(
                $durationSeconds
            ),

            'ended_at' => now(),

            'duration_seconds' =>
                $durationSeconds,

            'remarks' =>
                $remarks !== '' ? $remarks : null,
        ]);

        $followUp = null;

        /*
         * Date/time आया है तो follow_ups table में record बनाएँ।
         */
        if (!empty($followUpAt)) {
            $assignedTo = $lead->assigned_to
                ? (int) $lead->assigned_to
                : $userId;

            $followUp = FollowUp::create([
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'assigned_to' => $assignedTo,
                'created_by' => $userId,

                'type' => 'phone',

                'scheduled_at' => $followUpAt,

                'reminder_notified_at' => null,

                'priority' => 'normal',
                'status' => 'pending',

                'notes' => $remarks !== ''
                    ? $remarks
                    : "Follow-up created from {$disposition->name} call disposition.",
            ]);

            /*
             * Lead पर next follow-up भी update करें।
             */
            $lead->update([
                'last_contact_at' => now(),
                'next_follow_up_at' => $followUpAt,
            ]);
        } else {
            $lead->update([
                'last_contact_at' => now(),
            ]);
        }

        return [
            'call_log' => $callLog->fresh([
                'disposition',
                'user',
            ]),

            'follow_up' => $followUp?->fresh([
                'assignedUser',
                'lead',
            ]),

            'lead' => $lead->fresh(),
        ];
    });

    return $this->success(
        $result,
        $result['follow_up']
            ? 'Call result and follow-up saved successfully.'
            : 'Call result saved successfully.',
        201
    );
}


}
