<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallDisposition;
use App\Models\CallLog;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Mobile App Dashboard API
     *
     * Access rules exactly same as Web Dashboard:
     *
     * super_admin / admin
     *      => poori company
     *
     * Team Leader
     *      => apna + apni team ke employees
     *
     * Normal Employee
     *      => sirf apni assigned leads
     *
     * Supported:
     *
     * period:
     * today
     * month
     * all
     *
     * disposition_period:
     * today
     * month
     * all
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $period = $request->get('period', 'today');

        if (!in_array($period, [
            'today',
            'month',
            'all',
        ], true)) {
            $period = 'today';
        }

        $periodLabel = match ($period) {
            'month' => 'This Month',
            'all'   => 'All Time',
            default => 'Today',
        };

        /*
        |--------------------------------------------------------------------------
        | Disposition Period
        |--------------------------------------------------------------------------
        */

        $dispositionPeriod = $request->get(
            'disposition_period',
            $period
        );

        if (!in_array($dispositionPeriod, [
            'today',
            'month',
            'all',
        ], true)) {
            $dispositionPeriod = $period;
        }

        $dispositionPeriodLabel = match ($dispositionPeriod) {
            'month' => 'This Month',
            'all'   => 'All Time',
            default => 'Today',
        };

        /*
        |--------------------------------------------------------------------------
        | Period Helper
        |--------------------------------------------------------------------------
        */

        $applyPeriod = function (
            Builder $query,
            string $column = 'created_at'
        ) use ($period): Builder {

            if ($period === 'today') {
                $query->whereBetween(
                    $column,
                    [
                        now()->startOfDay(),
                        now(),
                    ]
                );
            }

            if ($period === 'month') {
                $query->whereBetween(
                    $column,
                    [
                        now()->startOfMonth(),
                        now(),
                    ]
                );
            }

            // all = koi date restriction nahi

            return $query;
        };

        /*
        |--------------------------------------------------------------------------
        | Disposition Period Helper
        |--------------------------------------------------------------------------
        */

        $applyDispositionPeriod = function (
            Builder $query,
            string $column = 'created_at'
        ) use ($dispositionPeriod): Builder {

            if ($dispositionPeriod === 'today') {
                $query->whereBetween(
                    $column,
                    [
                        now()->startOfDay(),
                        now(),
                    ]
                );
            }

            if ($dispositionPeriod === 'month') {
                $query->whereBetween(
                    $column,
                    [
                        now()->startOfMonth(),
                        now(),
                    ]
                );
            }

            return $query;
        };

        /*
        |--------------------------------------------------------------------------
        | Access
        |--------------------------------------------------------------------------
        |
        | Ye bilkul Web Dashboard jaisa hai.
        |
        */

        /*
         * IMPORTANT:
         * Ye role list Lead API ke full-company scope ke exactly same honi
         * chahiye. Manager ko /api/leads me poori company ki leads milti hain,
         * isliye dashboard me bhi manager par assigned_to filter nahi lagega.
         *
         * Project me role name underscore ya space dono format me ho sakta hai,
         * isliye dono variants rakhe gaye hain.
         */
        $hasFullAccess = $user->hasAnyRole([
            'super_admin',
            'super admin',
            'admin',
            'manager',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check Team Leader
        |--------------------------------------------------------------------------
        */

        $leaderTeamIds = $hasFullAccess
            ? []
            : Team::query()
                ->where('company_id', $companyId)
                ->where('leader_id', $userId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $isTeamLeader =
            !$hasFullAccess &&
            !empty($leaderTeamIds);

        /*
        |--------------------------------------------------------------------------
        | Visible User IDs
        |--------------------------------------------------------------------------
        |
        | Employee:
        | [own id]
        |
        | Team Leader:
        | [own id + team users]
        |
        | Admin:
        | is list ki zarurat nahi
        |
        */

        $visibleUserIds = collect([$userId]);

        if ($isTeamLeader) {
            $visibleUserIds = $visibleUserIds->merge(
                User::query()
                    ->where('company_id', $companyId)
                    ->whereIn('team_id', $leaderTeamIds)
                    ->pluck('id')
            );
        }

        $visibleUserIds = $visibleUserIds
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        /*
         * Full-access users ke liye response/debug information me company ke
         * saare users dikhaye jayenge. Lead scope par iska koi extra filter nahi
         * lagega, isliye assigned aur unassigned dono leads count hongi.
         */
        if ($hasFullAccess) {
            $visibleUserIds = User::query()
                ->where('company_id', $companyId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Mode
        |--------------------------------------------------------------------------
        */

        $dashboardMode = $hasFullAccess
            ? 'admin'
            : (
                $isTeamLeader
                    ? 'team_leader'
                    : 'employee'
            );

        /*
        |--------------------------------------------------------------------------
        | Base Lead Query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where('company_id', $companyId);

        /*
         * Admin ko saari company leads.
         *
         * Employee / Team Leader ko sirf visible users
         * ki assigned leads.
         */

        if (!$hasFullAccess) {
            $leadQuery->whereIn(
                'assigned_to',
                $visibleUserIds
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Visible Lead IDs
        |--------------------------------------------------------------------------
        |
        | Calls, Followups, Orders, Payments sab isi visibility
        | ke according filter honge.
        |
        */

        $visibleLeadIdsQuery = Lead::query()
            ->select('id')
            ->where('company_id', $companyId);

        if (!$hasFullAccess) {
            $visibleLeadIdsQuery->whereIn(
                'assigned_to',
                $visibleUserIds
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL LEADS
        |--------------------------------------------------------------------------
        |
        | Web Dashboard me Total Leads ALWAYS all time hai.
        |
        */

        /*
         * Total Leads hamesha unique, accessible, all-time leads ka count hai.
         * Connected lead ko Dialed me dobara add karke total inflate nahi karna.
         */

        $newBucketCount = (clone $leadQuery)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('call_logs')
                    ->whereColumn('call_logs.lead_id', 'leads.id');
            })
            ->count();

        $calledBucketCount = (clone $leadQuery)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('call_logs')
                    ->whereColumn('call_logs.lead_id', 'leads.id');
            })
            ->count();

        $connectedBucketCount = (clone $leadQuery)
            ->whereExists(function ($query) use ($companyId) {
                $query->selectRaw('1')
                    ->from('call_logs')
                    ->join(
                        'call_dispositions',
                        'call_dispositions.id',
                        '=',
                        'call_logs.call_disposition_id'
                    )
                    ->whereColumn('call_logs.lead_id', 'leads.id')
                    ->where(function ($builder) use ($companyId) {
                        $builder
                            ->whereNull('call_dispositions.company_id')
                            ->orWhere(
                                'call_dispositions.company_id',
                                $companyId
                            );
                    })
                    ->whereIn('call_dispositions.type', [
                        'connected',
                        'demo',
                    ]);
            })
            ->count();

        $dialedBucketCount = max(
            0,
            $calledBucketCount - $connectedBucketCount
        );

        $totalLeads = (clone $leadQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | NEW LEADS - Selected Period
        |--------------------------------------------------------------------------
        */

        $newLeadQuery = clone $leadQuery;

        $applyPeriod(
            $newLeadQuery,
            'created_at'
        );

        $newLeads = $newLeadQuery->count();

        /*
        |--------------------------------------------------------------------------
        | UNCALLED LEADS
        |--------------------------------------------------------------------------
        |
        | App ke liye useful.
        |
        | Visible leads jinpar ek bhi call log nahi hai.
        |
        */

        $uncalledLeads = (clone $leadQuery)
            ->whereNotExists(function ($query) {

                $query->selectRaw('1')
                    ->from('call_logs')
                    ->whereColumn(
                        'call_logs.lead_id',
                        'leads.id'
                    );
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | HOT LEADS
        |--------------------------------------------------------------------------
        */

        $hotLeads = (clone $leadQuery)
            ->where(
                'temperature',
                'hot'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Base Call Query
        |--------------------------------------------------------------------------
        */

        $callsBaseQuery = CallLog::query()
            ->where(
                'company_id',
                $companyId
            );

        /*
         * IMPORTANT:
         *
         * Web dashboard user_id ke according calls filter nahi karta.
         *
         * Visible lead IDs ke according calls filter karta hai.
         */

        if (!$hasFullAccess) {
            $callsBaseQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DEMO SENT COUNTS
        |--------------------------------------------------------------------------
        |
        | Demo leads.demo_send se nahi aata. CRM me Demo ek call disposition hai
        | jiska type = demo hai. Isliye demo count call_logs ke disposition se
        | calculate hoga aur calls ke same user/lead access scope ko follow karega.
        |
        */

        $demoDispositionIds = CallDisposition::query()
            ->where(function (Builder $builder) use ($companyId) {
                $builder
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('type', 'demo')
            ->pluck('id');

        /*
         * Demo call rows nahi, unique leads count hongi.
         * Sirf current assignment ki latest call ka disposition Demo hona chahiye.
         */
        $makeLatestDemoLeadQuery = function (
            bool $withPeriod
        ) use (
            $leadQuery,
            $demoDispositionIds,
            $period
        ): Builder {
            $query = clone $leadQuery;

            $query->whereExists(function ($callQuery) use (
                $demoDispositionIds,
                $withPeriod,
                $period
            ) {
                $callQuery
                    ->selectRaw('1')
                    ->from('call_logs as demo_calls')
                    ->whereColumn(
                        'demo_calls.lead_id',
                        'leads.id'
                    )
                    ->whereIn(
                        'demo_calls.call_disposition_id',
                        $demoDispositionIds
                    )
                    ->whereRaw(
                        "
                        demo_calls.id = (
                            SELECT MAX(latest_demo_scope.id)
                            FROM call_logs AS latest_demo_scope
                            WHERE latest_demo_scope.lead_id = leads.id
                            AND (
                                (
                                    leads.assigned_to IS NOT NULL
                                    AND latest_demo_scope.user_id = leads.assigned_to
                                    AND latest_demo_scope.created_at >= COALESCE(
                                        (
                                            SELECT MAX(latest_assignment.assigned_at)
                                            FROM lead_assignments AS latest_assignment
                                            WHERE latest_assignment.lead_id = leads.id
                                            AND latest_assignment.new_user_id = leads.assigned_to
                                        ),
                                        leads.created_at
                                    )
                                )
                                OR leads.assigned_to IS NULL
                            )
                        )
                        "
                    );

                if ($withPeriod && $period === 'today') {
                    $callQuery->whereBetween(
                        'demo_calls.created_at',
                        [now()->startOfDay(), now()]
                    );
                }

                if ($withPeriod && $period === 'month') {
                    $callQuery->whereBetween(
                        'demo_calls.created_at',
                        [now()->startOfMonth(), now()]
                    );
                }
            });

            return $query;
        };

        $totalDemoSent = $makeLatestDemoLeadQuery(false)
            ->distinct()
            ->count('leads.id');

        $demoSent = $makeLatestDemoLeadQuery(true)
            ->distinct()
            ->count('leads.id');

        /*
        |--------------------------------------------------------------------------
        | TOTAL CALLS - Selected Period
        |--------------------------------------------------------------------------
        */

        $callsPeriodQuery = clone $callsBaseQuery;

        $applyPeriod(
            $callsPeriodQuery,
            'created_at'
        );

        $totalCalls = $callsPeriodQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Accessible Connected Disposition IDs
        |--------------------------------------------------------------------------
        |
        | CallDispositionApiController ki tarah global + current company dono.
        | Relation par depend karne ke bajay call_logs.call_disposition_id ko
        | master disposition IDs se directly match kiya jayega.
        |
        */

        $connectedDispositionIds = CallDisposition::query()
            ->where(function (Builder $builder) use ($companyId) {
                $builder
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('type', 'connected')
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | CONNECTED CALLS - Selected Period
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | duration_seconds se connected nahi maan rahe.
        |
        | Web Dashboard ki tarah:
        |
        | disposition.type = connected
        |
        */

        $connectedCallsQuery = clone $callsBaseQuery;

        $applyPeriod(
            $connectedCallsQuery,
            'created_at'
        );

        $connectedCallsQuery->whereIn(
            'call_disposition_id',
            $connectedDispositionIds
        );

        $connectedCalls = $connectedCallsQuery
            ->count();

        /*
        |--------------------------------------------------------------------------
        | UNIQUE CONNECTED LEADS
        |--------------------------------------------------------------------------
        |
        | Same lead se 5 connected calls hue,
        | tab bhi unique connected = 1.
        |
        */

        $uniqueConnectedQuery = clone $callsBaseQuery;

        $applyPeriod(
            $uniqueConnectedQuery,
            'created_at'
        );

        $uniqueConnectedQuery->whereIn(
            'call_disposition_id',
            $connectedDispositionIds
        );

        $uniqueConnected = $uniqueConnectedQuery
            ->whereNotNull('lead_id')
            ->distinct()
            ->count('lead_id');

        /*
        |--------------------------------------------------------------------------
        | Dynamic Disposition Counts
        |--------------------------------------------------------------------------
        */

        $dispositionCountQuery = clone $callsBaseQuery;

        $applyDispositionPeriod(
            $dispositionCountQuery,
            'created_at'
        );

        /*
        |--------------------------------------------------------------------------
        | Total Calls for Disposition
        |--------------------------------------------------------------------------
        */

        $dispositionTotalCallsQuery =
            clone $callsBaseQuery;

        $applyDispositionPeriod(
            $dispositionTotalCallsQuery,
            'created_at'
        );

        $dispositionTotalCalls =
            $dispositionTotalCallsQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Group Disposition Counts
        |--------------------------------------------------------------------------
        */

        $dispositionCounts =
            $dispositionCountQuery
                ->whereNotNull(
                    'call_disposition_id'
                )
                ->select(
                    'call_disposition_id'
                )
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->groupBy(
                    'call_disposition_id'
                )
                ->pluck(
                    'total',
                    'call_disposition_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Master Dispositions
        |--------------------------------------------------------------------------
        |
        | Call log se list nahi bana rahe.
        |
        | Master table ke sare dispositions API me jayenge,
        | chahe count 0 hi kyu na ho.
        |
        */

        $allDispositions =
            CallDisposition::query()
                ->where(function (Builder $builder) use ($companyId) {
                    $builder
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->orderBy('id')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Disposition Stats
        |--------------------------------------------------------------------------
        */

        $dispositionStats =
            $allDispositions
                ->map(function ($disposition) use (
                    $dispositionCounts,
                    $dispositionTotalCalls
                ) {

                    $total = (int) (
                        $dispositionCounts[
                            $disposition->id
                        ] ?? 0
                    );

                    $percentage =
                        $dispositionTotalCalls > 0
                            ? round(
                                (
                                    $total /
                                    $dispositionTotalCalls
                                ) * 100,
                                1
                            )
                            : 0;

                    return [
                        'id' =>
                            (int) $disposition->id,

                        'company_id' =>
                            $disposition->company_id !== null
                                ? (int) $disposition->company_id
                                : null,

                        'is_global' =>
                            $disposition->company_id === null,

                        'name' =>
                            $disposition->name,

                        'type' =>
                            $disposition->type,

                        'total' =>
                            $total,

                        'percentage' =>
                            $percentage,

                        'is_active' =>
                            (bool) (
                                $disposition->is_active
                                ?? true
                            ),

                        'requires_follow_up' =>
                            (bool) (
                                $disposition->requires_follow_up
                                ?? false
                            ),

                        'requires_remarks' =>
                            (bool) (
                                $disposition->requires_remarks
                                ?? false
                            ),

                        'auto_remarks' =>
                            $disposition->auto_remarks
                            ?? null,

                        'next_followup' =>
                            $disposition->next_followup !== null
                                ? (int) $disposition->next_followup
                                : null,

                        'next_followup_minutes' =>
                            $disposition->next_followup !== null
                                ? (int) $disposition->next_followup
                                : null,

                        'next_followup_unit' =>
                            $disposition->next_followup !== null
                                ? 'minutes'
                                : null,

                        'suggested_follow_up_at' =>
                            $disposition->next_followup !== null
                                ? now()->copy()
                                    ->addMinutes((int) $disposition->next_followup)
                                    ->toIso8601String()
                                : null,
                    ];
                })
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Calls Without Disposition
        |--------------------------------------------------------------------------
        */

        $withoutDispositionQuery =
            clone $callsBaseQuery;

        $applyDispositionPeriod(
            $withoutDispositionQuery,
            'created_at'
        );

        $withoutDisposition =
            $withoutDispositionQuery
                ->whereNull(
                    'call_disposition_id'
                )
                ->count();

        $withoutDispositionPercentage =
            $dispositionTotalCalls > 0
                ? round(
                    (
                        $withoutDisposition /
                        $dispositionTotalCalls
                    ) * 100,
                    1
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | FOLLOW UPS
        |--------------------------------------------------------------------------
        */

        $followUpsDueQuery =
            FollowUp::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'status',
                    'pending'
                );

        if (!$hasFullAccess) {
            $followUpsDueQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        /*
         * Web dashboard exact same logic.
         */

        if ($period === 'today') {

            $followUpsDueQuery
                ->whereBetween(
                    'scheduled_at',
                    [
                        now()->startOfDay(),
                        now()->endOfDay(),
                    ]
                );

        } elseif ($period === 'month') {

            $followUpsDueQuery
                ->whereBetween(
                    'scheduled_at',
                    [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]
                );
        }

        $followUpsDue =
            $followUpsDueQuery->count();

        /*
        |--------------------------------------------------------------------------
        | TOTAL FOLLOWUPS
        |--------------------------------------------------------------------------
        |
        | App screenshot ke Total Follow-ups card ke liye.
        |
        */

        $totalFollowUpsQuery =
            FollowUp::query()
                ->where(
                    'company_id',
                    $companyId
                );

        if (!$hasFullAccess) {
            $totalFollowUpsQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        $totalFollowUps =
            $totalFollowUpsQuery->count();

        /*
        |--------------------------------------------------------------------------
        | OVERDUE FOLLOW UPS
        |--------------------------------------------------------------------------
        */

        $overdueQuery =
            FollowUp::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'status',
                    'pending'
                )
                ->where(
                    'scheduled_at',
                    '<',
                    now()
                );

        if (!$hasFullAccess) {
            $overdueQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        if ($period !== 'all') {
            $applyPeriod(
                $overdueQuery,
                'scheduled_at'
            );
        }

        $overdue =
            $overdueQuery->count();

        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        */

        $salesQuery =
            Order::query()
                ->where(
                    'company_id',
                    $companyId
                );

        if (!$hasFullAccess) {
            $salesQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        $applyPeriod(
            $salesQuery,
            'created_at'
        );

        $sales = (float) $salesQuery
            ->sum(
                'total_amount'
            );

        /*
        |--------------------------------------------------------------------------
        | PAYMENT RECEIVED
        |--------------------------------------------------------------------------
        */

        $receivedQuery =
            Payment::query()
                ->where(
                    'company_id',
                    $companyId
                );

        if (!$hasFullAccess) {

            $receivedQuery->whereHas(
                'order',
                function (Builder $query) use (
                    $visibleLeadIdsQuery
                ) {

                    $query->whereIn(
                        'lead_id',
                        clone $visibleLeadIdsQuery
                    );
                }
            );
        }

        $applyPeriod(
            $receivedQuery,
            'created_at'
        );

        $received = (float) $receivedQuery
            ->sum(
                'amount'
            );

        /*
        |--------------------------------------------------------------------------
        | ACTIVE EMPLOYEES
        |--------------------------------------------------------------------------
        */

        if ($hasFullAccess) {

            $activeUsers =
                User::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count();

        } elseif ($isTeamLeader) {

            $activeUsers =
                User::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->whereIn(
                        'id',
                        $visibleUserIds
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count();

        } else {

            /*
             * Employee dashboard
             */
            $activeUsers =
                $user->is_active
                    ? 1
                    : 0;
        }

        /*
        |--------------------------------------------------------------------------
        | RECENT LEADS
        |--------------------------------------------------------------------------
        */

        $recentLeads =
            (clone $leadQuery)
                ->with([
                    'assignedUser:id,name,employee_code',
                    'status:id,name,color',
                    'source:id,name',
                ])
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(function ($lead) {

                    return [
                        'id' =>
                            (int) $lead->id,

                        'name' =>
                            $lead->name,

                        'mobile' =>
                            $lead->mobile,

                        'company_name' =>
                            $lead->company_name,

                        'city' =>
                            $lead->city,

                        'temperature' =>
                            $lead->temperature,

                        'demo_send' =>
                            (bool) $lead->demo_send,

                        'status' => $lead->status
                            ? [
                                'id' =>
                                    (int) $lead->status->id,

                                'name' =>
                                    $lead->status->name,

                                'color' =>
                                    $lead->status->color,
                            ]
                            : null,

                        'source' => $lead->source
                            ? [
                                'id' =>
                                    (int) $lead->source->id,

                                'name' =>
                                    $lead->source->name,
                            ]
                            : null,

                        'assigned_user' =>
                            $lead->assignedUser
                                ? [
                                    'id' =>
                                        (int) $lead->assignedUser->id,

                                    'name' =>
                                        $lead->assignedUser->name,

                                    'employee_code' =>
                                        $lead->assignedUser
                                            ->employee_code,
                                ]
                                : null,

                        'created_at' =>
                            optional(
                                $lead->created_at
                            )->toDateTimeString(),
                    ];
                })
                ->values();

        /*
        |--------------------------------------------------------------------------
        | App Cards
        |--------------------------------------------------------------------------
        |
        | Flutter loop laga kar bhi directly cards show kar sakta hai.
        |
        */

        $cards = [

            [
                'key' => 'total_leads',
                'label' => 'Total Leads',
                'sub_label' => 'All Time',
                'value' => $totalLeads,
            ],

            [
                'key' => 'new_leads',
                'label' => 'New Leads',
                'sub_label' => $periodLabel,
                'value' => $newLeads,
            ],

            [
                'key' => 'uncalled_leads',
                'label' => 'Uncalled Leads',
                'sub_label' => 'Current',
                'value' => $uncalledLeads,
            ],

            [
                'key' => 'total_calls',
                'label' => 'Total Calls',
                'sub_label' => $periodLabel,
                'value' => $totalCalls,
            ],

            [
                'key' => 'connected_calls',
                'label' => 'Connected Calls',
                'sub_label' => $periodLabel,
                'value' => $connectedCalls,
            ],

            [
                'key' => 'unique_connected',
                'label' => 'Unique Connected',
                'sub_label' => $periodLabel,
                'value' => $uniqueConnected,
            ],

            [
                'key' => 'demo_sent',
                'label' => 'Demo Sent',
                'sub_label' => $periodLabel,
                'value' => $demoSent,
            ],

            [
                'key' => 'total_demo_sent',
                'label' => 'Total Demo Sent',
                'sub_label' => 'All Time',
                'value' => $totalDemoSent,
            ],

            [
                'key' => 'total_followups',
                'label' => 'Total Follow-ups',
                'sub_label' => 'All Time',
                'value' => $totalFollowUps,
            ],

            [
                'key' => 'pending_followups',
                'label' => 'Pending Follow-ups',
                'sub_label' => $periodLabel,
                'value' => $followUpsDue,
            ],

            [
                'key' => 'overdue_followups',
                'label' => 'Overdue Follow-ups',
                'sub_label' => $periodLabel,
                'value' => $overdue,
            ],

            [
                'key' => 'hot_leads',
                'label' => 'Hot Leads',
                'sub_label' => 'Current',
                'value' => $hotLeads,
            ],

            [
                'key' => 'active_employees',
                'label' => 'Active Employees',
                'sub_label' => 'Current',
                'value' => $activeUsers,
            ],

            [
                'key' => 'sales',
                'label' => 'Sales Value',
                'sub_label' => $periodLabel,
                'value' => $sales,
            ],

            [
                'key' => 'received',
                'label' => 'Payment Received',
                'sub_label' => $periodLabel,
                'value' => $received,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | FINAL JSON RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Dashboard fetched successfully.',

            /*
            |--------------------------------------------------------------------------
            | Access information
            |--------------------------------------------------------------------------
            */

            'access' => [

                'mode' =>
                    $dashboardMode,

                'has_full_access' =>
                    $hasFullAccess,

                'is_team_leader' =>
                    $isTeamLeader,

                'visible_user_ids' =>
                    $visibleUserIds,

                'company_id' =>
                    $companyId,

                'user_id' =>
                    $userId,
            ],

            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            */

            'filter' => [

                'period' =>
                    $period,

                'period_label' =>
                    $periodLabel,

                'disposition_period' =>
                    $dispositionPeriod,

                'disposition_period_label' =>
                    $dispositionPeriodLabel,
            ],

            /*
            |--------------------------------------------------------------------------
            | App direct cards
            |--------------------------------------------------------------------------
            */

            'cards' =>
                $cards,

            /*
            |--------------------------------------------------------------------------
            | Raw Stats
            |--------------------------------------------------------------------------
            |
            | Flutter individual keys se direct value show kar sakta hai.
            |
            */

            'stats' => [

                /*
                 * Leads
                 */

                'total_leads' =>
                    $totalLeads,

                /*
                 * /api/leads counts verification
                 */

                'lead_buckets' => [
                    'new' =>
                        $newBucketCount,

                    'dialed' =>
                        $dialedBucketCount,

                    'connected' =>
                        $connectedBucketCount,

                    'total' =>
                        $totalLeads,
                ],

                'new_leads' =>
                    $newLeads,

                'uncalled_leads' =>
                    $uncalledLeads,

                'hot_leads' =>
                    $hotLeads,

                /*
                 * Calls
                 */

                'total_calls' =>
                    $totalCalls,

                'connected_calls' =>
                    $connectedCalls,

                'unique_connected' =>
                    $uniqueConnected,

                /*
                 * Demo
                 */

                'demo_sent' =>
                    $demoSent,

                'total_demo_sent' =>
                    $totalDemoSent,

                /*
                 * Followups
                 */

                'total_followups' =>
                    $totalFollowUps,

                'pending_followups' =>
                    $followUpsDue,

                'overdue_followups' =>
                    $overdue,

                /*
                 * Employees
                 */

                'active_employees' =>
                    $activeUsers,

                /*
                 * Sales
                 */

                'sales' =>
                    $sales,

                'payment_received' =>
                    $received,
            ],

            /*
            |--------------------------------------------------------------------------
            | Disposition Statistics
            |--------------------------------------------------------------------------
            */

            'dispositions' => [

                'period' =>
                    $dispositionPeriod,

                'period_label' =>
                    $dispositionPeriodLabel,

                'total_calls' =>
                    $dispositionTotalCalls,

                'without_disposition' => [

                    'total' =>
                        $withoutDisposition,

                    'percentage' =>
                        $withoutDispositionPercentage,
                ],

                'items' =>
                    $dispositionStats,
            ],

            /*
            |--------------------------------------------------------------------------
            | Recent Leads
            |--------------------------------------------------------------------------
            */

            'recent_leads' =>
                $recentLeads,
        ]);
    }
}
