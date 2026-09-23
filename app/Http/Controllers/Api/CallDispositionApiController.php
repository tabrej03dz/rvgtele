<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallDisposition;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallDispositionApiController extends Controller
{
    private array $fullAccessRoles = [
        'super_admin',
        'admin',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'active' => [
                'nullable',
                Rule::in(['all', '1', '0', 1, 0]),
            ],
            'type' => ['nullable', 'string', 'max:100'],
            'with_counts' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;

        abort_if(
            $companyId < 1,
            403,
            'No company is assigned to this user.'
        );

        $query = CallDisposition::query()
            ->where(function (Builder $builder) use ($companyId) {
                $builder
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });

        $active = (string) ($validated['active'] ?? '1');

        if ($active !== 'all') {
            $query->where('is_active', $active === '1');
        }

        if (!empty($validated['type'])) {
            $type = strtolower(trim((string) $validated['type']));
            $query->whereRaw('LOWER(type) = ?', [$type]);
        }

        if (!empty($validated['search'])) {
            $search = trim((string) $validated['search']);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('auto_remarks', 'like', "%{$search}%");
            });
        }

        $withCounts = !array_key_exists('with_counts', $validated)
            || (bool) $validated['with_counts'];

        $dispositions = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(function (CallDisposition $disposition) use (
                $request,
                $companyId,
                $withCounts
            ) {
                return $this->formatDisposition(
                    $disposition,
                    $request,
                    $companyId,
                    $withCounts
                );
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Call dispositions fetched successfully.',
            'count' => $dispositions->count(),
            'data' => $dispositions,
        ]);
    }

    public function show(
        Request $request,
        CallDisposition $callDisposition
    ): JsonResponse {
        $companyId = (int) $request->user()->company_id;

        abort_unless(
            $callDisposition->company_id === null
            || (int) $callDisposition->company_id === $companyId,
            403,
            'You are not allowed to access this call disposition.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Call disposition fetched successfully.',
            'data' => $this->formatDisposition(
                $callDisposition,
                $request,
                $companyId,
                true
            ),
        ]);
    }

    private function formatDisposition(
        CallDisposition $disposition,
        Request $request,
        int $companyId,
        bool $withCounts
    ): array {
        $nextFollowUpMinutes = $disposition->next_followup !== null
            ? (int) $disposition->next_followup
            : null;

        $data = [
            'id' => (int) $disposition->id,
            'company_id' => $disposition->company_id !== null
                ? (int) $disposition->company_id
                : null,
            'is_global' => $disposition->company_id === null,
            'name' => $disposition->name,
            'type' => $disposition->type,
            'requires_remarks' => (bool) $disposition->requires_remarks,
            'requires_follow_up' => (bool) $disposition->requires_follow_up,
            'auto_remarks' => $disposition->auto_remarks,
            'next_followup' => $nextFollowUpMinutes,
            'next_followup_minutes' => $nextFollowUpMinutes,
            'next_followup_unit' => $nextFollowUpMinutes !== null
                ? 'minutes'
                : null,
            'suggested_follow_up_at' => $nextFollowUpMinutes !== null
                ? now()->copy()
                    ->addMinutes($nextFollowUpMinutes)
                    ->toIso8601String()
                : null,
            'is_active' => (bool) $disposition->is_active,
            'created_at' => $disposition->created_at?->toIso8601String(),
            'updated_at' => $disposition->updated_at?->toIso8601String(),
            'leads_filter' => [
                'parameter' => 'call_disposition_id',
                'value' => (int) $disposition->id,
                'url' => url('/api/leads')
                    . '?call_disposition_id='
                    . (int) $disposition->id,
            ],
        ];

        if (!$withCounts) {
            return $data;
        }

        $leadQuery = $this->visibleLeadQuery($request, $companyId);

        $latestLeadQuery = $this->applyLatestDispositionFilter(
            clone $leadQuery,
            (int) $disposition->id,
            false
        );

        $todayLatestLeadQuery = $this->applyLatestDispositionFilter(
            clone $leadQuery,
            (int) $disposition->id,
            true
        );

        $visibleLeadIds = (clone $leadQuery)->select('leads.id');

        $data['counts'] = [
            'latest_unique_leads' => $latestLeadQuery
                ->distinct()
                ->count('leads.id'),
            'today_latest_unique_leads' => $todayLatestLeadQuery
                ->distinct()
                ->count('leads.id'),
            'total_call_logs' => CallLog::query()
                ->where('company_id', $companyId)
                ->whereIn('lead_id', clone $visibleLeadIds)
                ->where('call_disposition_id', $disposition->id)
                ->count(),
            'today_call_logs' => CallLog::query()
                ->where('company_id', $companyId)
                ->whereIn('lead_id', clone $visibleLeadIds)
                ->where('call_disposition_id', $disposition->id)
                ->whereBetween('created_at', [
                    now()->startOfDay(),
                    now(),
                ])
                ->count(),
        ];

        return $data;
    }

    private function visibleLeadQuery(
        Request $request,
        int $companyId
    ): Builder {
        $user = $request->user();

        $query = Lead::query()
            ->where('leads.company_id', $companyId);

        if ($user->hasAnyRole($this->fullAccessRoles)) {
            return $query;
        }

        $leaderTeamIds = Team::query()
            ->where('company_id', $companyId)
            ->where('leader_id', $user->id)
            ->pluck('id')
            ->all();

        $query->where(function (Builder $scope) use (
            $user,
            $companyId,
            $leaderTeamIds
        ) {
            $scope->where('leads.assigned_to', $user->id);

            if ($leaderTeamIds !== []) {
                $scope->orWhereIn(
                    'leads.assigned_to',
                    User::query()
                        ->select('id')
                        ->where('company_id', $companyId)
                        ->whereIn('team_id', $leaderTeamIds)
                );
            }
        });

        return $query;
    }

    private function applyLatestDispositionFilter(
        Builder $leadQuery,
        int $dispositionId,
        bool $todayOnly
    ): Builder {
        return $leadQuery->whereExists(function ($callQuery) use (
            $dispositionId,
            $todayOnly
        ) {
            $callQuery
                ->selectRaw('1')
                ->from('call_logs as matched_calls')
                ->whereColumn('matched_calls.lead_id', 'leads.id')
                ->where(
                    'matched_calls.call_disposition_id',
                    $dispositionId
                )
                ->whereRaw(
                    "
                    matched_calls.id = (
                        SELECT MAX(latest_calls.id)
                        FROM call_logs AS latest_calls
                        WHERE latest_calls.lead_id = leads.id
                        AND (
                            (
                                leads.assigned_to IS NOT NULL
                                AND latest_calls.user_id = leads.assigned_to
                                AND latest_calls.created_at >= COALESCE(
                                    (
                                        SELECT MAX(assignments.assigned_at)
                                        FROM lead_assignments AS assignments
                                        WHERE assignments.lead_id = leads.id
                                        AND assignments.new_user_id = leads.assigned_to
                                    ),
                                    leads.created_at
                                )
                            )
                            OR leads.assigned_to IS NULL
                        )
                    )
                    "
                );

            if ($todayOnly) {
                $callQuery->whereBetween(
                    'matched_calls.created_at',
                    [now()->startOfDay(), now()]
                );
            }
        });
    }
}
