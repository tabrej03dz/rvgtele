<?php

namespace App\Http\Controllers;

use App\Models\CallDisposition;
use App\Models\CallLog;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $companyId = (int) $user->company_id;
        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Dashboard Period Filter
        |--------------------------------------------------------------------------
        |
        | today = Today
        | month = Current Month
        | all   = All Time
        |
        | Default = today
        |
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
            'all' => 'All Time',
            default => 'Today',
        };

        $dispositionPeriod = $request->get('disposition_period', $period);

        if (!in_array($dispositionPeriod, [
            'today',
            'month',
            'all',
        ], true)) {
            $dispositionPeriod = $period;
        }

        $dispositionPeriodLabel = match ($dispositionPeriod) {
            'month' => 'This Month',
            'all' => 'All Time',
            default => 'Today',
        };

        /*
        |--------------------------------------------------------------------------
        | Period Filter Helper
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

            /*
             * all = No date restriction
             */

            return $query;
        };

        $applyDispositionPeriod = function (
            Builder $query,
            string $column = 'created_at'
        ) use ($dispositionPeriod): Builder {

            if ($dispositionPeriod === 'today') {
                $query->whereBetween($column, [
                    now()->startOfDay(),
                    now(),
                ]);
            }

            if ($dispositionPeriod === 'month') {
                $query->whereBetween($column, [
                    now()->startOfMonth(),
                    now(),
                ]);
            }

            return $query;
        };

        /*
        |--------------------------------------------------------------------------
        | Full Access Roles
        |--------------------------------------------------------------------------
        */

        $hasFullAccess = $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);

        $leaderTeamIds = $hasFullAccess
            ? []
            : Team::query()
                ->where('company_id', $companyId)
                ->where('leader_id', $userId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $isTeamLeader = !$hasFullAccess && !empty($leaderTeamIds);

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
        |--------------------------------------------------------------------------
        | Base Lead Query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where('company_id', $companyId);

        if (!$hasFullAccess) {
            $leadQuery->whereIn('assigned_to', $visibleUserIds);
        }

        /*
        |--------------------------------------------------------------------------
        | Visible Lead IDs
        |--------------------------------------------------------------------------
        */

        $visibleLeadIdsQuery = Lead::query()
            ->select('id')
            ->where('company_id', $companyId);

        if (!$hasFullAccess) {
            $visibleLeadIdsQuery->whereIn('assigned_to', $visibleUserIds);
        }

        /*
        |--------------------------------------------------------------------------
        | Total Leads
        |--------------------------------------------------------------------------
        */

        $totalLeads = (clone $leadQuery)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | New Leads - Selected Period
        |--------------------------------------------------------------------------
        */

        $newLeadQuery = clone $leadQuery;

        $applyPeriod(
            $newLeadQuery,
            'created_at'
        );

        $newToday = $newLeadQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Total Demo Sent
        |--------------------------------------------------------------------------
        */

        $totalLeadSend = (clone $leadQuery)
            ->where(
                'demo_send',
                true
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Demo Sent - Selected Period
        |--------------------------------------------------------------------------
        */

        $demoPeriodQuery = (clone $leadQuery)
            ->where(
                'demo_send',
                true
            )
            ->whereNotNull(
                'demo_sent_at'
            );

        $applyPeriod(
            $demoPeriodQuery,
            'demo_sent_at'
        );

        $todayLeadSend = $demoPeriodQuery
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Hot Leads
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
         * Employee / Telecaller:
         * Sirf apni assigned leads ki calls dekhe.
         */

        if (!$hasFullAccess) {
            $callsBaseQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Total Calls - Selected Period
        |--------------------------------------------------------------------------
        */

        $callsPeriodQuery = clone $callsBaseQuery;

        $applyPeriod(
            $callsPeriodQuery,
            'created_at'
        );

        $callsToday = $callsPeriodQuery
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Connected Calls - Selected Period
        |--------------------------------------------------------------------------
        */

        $connectedTodayQuery = clone $callsBaseQuery;

        $applyPeriod(
            $connectedTodayQuery,
            'created_at'
        );

        $connectedTodayQuery->whereHas(
            'disposition',
            function (Builder $query) {
                $query->where(
                    'type',
                    'connected'
                );
            }
        );

        $connectedToday = $connectedTodayQuery
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Disposition Call Counts
        |--------------------------------------------------------------------------
        |
        | CallLog table column:
        |
        | call_disposition_id
        |
        | Yahan selected period ke calls ko group karenge.
        |
        */

        $dispositionCountQuery = clone $callsBaseQuery;

        $applyDispositionPeriod(
            $dispositionCountQuery,
            'created_at'
        );

        $dispositionTotalCallsQuery = clone $callsBaseQuery;

        $applyDispositionPeriod(
            $dispositionTotalCallsQuery,
            'created_at'
        );

        $dispositionTotalCalls = $dispositionTotalCallsQuery->count();

        $dispositionCounts = $dispositionCountQuery
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
        | ALL DYNAMIC DISPOSITIONS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Hum CallLog se disposition list nahi bana rahe.
        |
        | Hum CallDisposition master table se SAARE dispositions
        | la rahe hain.
        |
        | Isliye:
        |
        | - Count 0 ho tab bhi disposition dikhega
        | - New disposition add hote hi dikhega
        | - Name hard-coded nahi hai
        | - Type hard-coded nahi hai
        |
        */

        $allDispositionsQuery = CallDisposition::query();

        /*
         * Agar CallDisposition company-wise hai to company filter.
         *
         * Agar aapki call_dispositions table me company_id column hai
         * to ye required hai.
         */

        $allDispositionsQuery->where(
            'company_id',
            $companyId
        );

        /*
         * Saare dispositions la rahe hain.
         *
         * Active aur inactive dono ka data available rahega.
         *
         * Agar sirf active chahiye to:
         *
         * ->where('is_active', true)
         *
         * laga sakte ho.
         */

        $allDispositions = $allDispositionsQuery
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Final Dynamic Disposition Stats
        |--------------------------------------------------------------------------
        */

        $dispositionStats = $allDispositions
            ->map(function ($disposition) use (
                $dispositionCounts
            ) {

                return [

                    'id' => (int) $disposition->id,

                    'name' => $disposition->name,

                    'type' => $disposition->type,

                    /*
                     * Count nahi mila to 0
                     */
                    'total' => (int) (
                        $dispositionCounts[
                            $disposition->id
                        ] ?? 0
                    ),

                    /*
                     * Dashboard par active/inactive
                     * dikhane ke kaam aa sakta hai.
                     */
                    'is_active' => (bool) (
                        $disposition->is_active ?? true
                    ),

                    /*
                     * Extra fields bhi Blade me use kar sakte hain.
                     */
                    'requires_follow_up' => (bool) (
                        $disposition->requires_follow_up ?? false
                    ),

                    'requires_remarks' => (bool) (
                        $disposition->requires_remarks ?? false
                    ),

                    'auto_remarks' => $disposition->auto_remarks
                        ?? null,

                    'next_followup' => $disposition->next_followup
                        ?? null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Calls Without Disposition
        |--------------------------------------------------------------------------
        */

        $withoutDispositionQuery = clone $callsBaseQuery;

        $applyDispositionPeriod(
            $withoutDispositionQuery,
            'created_at'
        );

        $withoutDisposition = $withoutDispositionQuery
            ->whereNull(
                'call_disposition_id'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Follow-ups - Selected Period
        |--------------------------------------------------------------------------
        */

        $followUpsDueQuery = FollowUp::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending');

        if (!$hasFullAccess) {
            $followUpsDueQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        if ($period === 'today') {
            $followUpsDueQuery->whereBetween('scheduled_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ]);
        } elseif ($period === 'month') {
            $followUpsDueQuery->whereBetween('scheduled_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
        }

        $followUpsDue = $followUpsDueQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Overdue Follow-ups
        |--------------------------------------------------------------------------
        */

        $overdueQuery = FollowUp::query()
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

        $overdue = $overdueQuery
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Sales - Selected Period
        |--------------------------------------------------------------------------
        */

        $salesQuery = Order::query()
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
        | Payments - Selected Period
        |--------------------------------------------------------------------------
        */

        $receivedQuery = Payment::query()
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
        | Active Users
        |--------------------------------------------------------------------------
        */

        if ($hasFullAccess) {
            $activeUsers = User::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        } elseif ($isTeamLeader) {
            $activeUsers = User::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $visibleUserIds)
                ->where('is_active', true)
                ->count();
        } else {
            $activeUsers = 1;
        }


    /*
    |--------------------------------------------------------------------------
    | Dashboard Snapshot Statistics
    |--------------------------------------------------------------------------
    |
    | Screenshot-style dashboard ke liye "Today" aur "All Time" cards
    | ek saath dikhane hain. Isliye ye values main period filter se
    | independent rakhi gayi hain.
    |
    */

    $todayStart = now()->startOfDay();
    $todayEnd = now()->endOfDay();

    // Today Leads
    $todayNewLeads = (clone $leadQuery)
        ->whereBetween('created_at', [$todayStart, $todayEnd])
        ->count();

    // Today Calls
    $todayCalls = (clone $callsBaseQuery)
        ->whereBetween('created_at', [$todayStart, $todayEnd])
        ->count();

    // Today Connected
    $todayConnected = (clone $callsBaseQuery)
        ->whereBetween('created_at', [$todayStart, $todayEnd])
        ->whereHas('disposition', function (Builder $query) {
            $query->where('type', 'connected');
        })
        ->count();

    // Today Demo Sent
    $todayDemos = (clone $leadQuery)
        ->where('demo_send', true)
        ->whereNotNull('demo_sent_at')
        ->whereBetween('demo_sent_at', [$todayStart, $todayEnd])
        ->count();

    // Follow-up base query with same visibility rules as the dashboard
    $snapshotFollowUpBase = FollowUp::query()
        ->where('company_id', $companyId);

    if (!$hasFullAccess) {
        $snapshotFollowUpBase->whereIn(
            'lead_id',
            clone $visibleLeadIdsQuery
        );
    }

    // Today Pending Follow-ups
    $todayPendingFollowUps = (clone $snapshotFollowUpBase)
        ->where('status', 'pending')
        ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
        ->count();

    // Today Overdue
    $todayOverdue = (clone $snapshotFollowUpBase)
        ->where('status', 'pending')
        ->whereBetween('scheduled_at', [$todayStart, now()])
        ->count();

    // All Time Calls
    $allCalls = (clone $callsBaseQuery)->count();

    // All Time Connected
    $allConnected = (clone $callsBaseQuery)
        ->whereHas('disposition', function (Builder $query) {
            $query->where('type', 'connected');
        })
        ->count();

    // All Time Pending Follow-ups
    $allPendingFollowUps = (clone $snapshotFollowUpBase)
        ->where('status', 'pending')
        ->count();

    // All Time Overdue Follow-ups
    $allOverdue = (clone $snapshotFollowUpBase)
        ->where('status', 'pending')
        ->where('scheduled_at', '<', now())
        ->count();

    // All Time Sales
    $allSalesQuery = Order::query()
        ->where('company_id', $companyId);

    if (!$hasFullAccess) {
        $allSalesQuery->whereIn(
            'lead_id',
            clone $visibleLeadIdsQuery
        );
    }

    $allSales = (float) $allSalesQuery->sum('total_amount');

    // All Time Payments Received
    $allReceivedQuery = Payment::query()
        ->where('company_id', $companyId);

    if (!$hasFullAccess) {
        $allReceivedQuery->whereHas(
            'order',
            function (Builder $query) use ($visibleLeadIdsQuery) {
                $query->whereIn(
                    'lead_id',
                    clone $visibleLeadIdsQuery
                );
            }
        );
    }

    $allReceived = (float) $allReceivedQuery->sum('amount');




    /*
    |--------------------------------------------------------------------------
    | Employee Performance
    |--------------------------------------------------------------------------
    |
    | Only Admin / Super Admin
    |
    */

    $employeePerformance = collect();

    if ($hasFullAccess) {

        /*
        |--------------------------------------------------------------------------
        | Employee Tracking Period
        |--------------------------------------------------------------------------
        */

        $employeePeriod = $request->get(
            'employee_period',
            'today'
        );

        if (!in_array(
            $employeePeriod,
            [
                'today',
                'month',
                'all',
                'custom',
            ],
            true
        )) {
            $employeePeriod = 'today';
        }

        $employeeFrom = $request->get('employee_from');
        $employeeTo = $request->get('employee_to');

        if ($employeePeriod === 'today') {

            $employeeDateFrom = now()->startOfDay();
            $employeeDateTo = now()->endOfDay();

            $employeePeriodLabel = 'Today';

        } elseif ($employeePeriod === 'month') {

            $employeeDateFrom = now()->startOfMonth();
            $employeeDateTo = now()->endOfDay();

            $employeePeriodLabel = 'This Month';

        } elseif (
            $employeePeriod === 'custom'
            && $employeeFrom
            && $employeeTo
        ) {

            $employeeDateFrom = \Carbon\Carbon::parse(
                $employeeFrom
            )->startOfDay();

            $employeeDateTo = \Carbon\Carbon::parse(
                $employeeTo
            )->endOfDay();

            $employeePeriodLabel =
                $employeeDateFrom->format('d M Y')
                . ' - '
                . $employeeDateTo->format('d M Y');

        } else {

            $employeePeriod = 'all';

            $employeeDateFrom = null;
            $employeeDateTo = null;

            $employeePeriodLabel = 'All Time';
        }

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
                'team_id',
                'is_active',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Build Performance
        |--------------------------------------------------------------------------
        */

        $employeePerformance = $employees
            ->map(function ($employee) use (
                $companyId,
                $employeeDateFrom,
                $employeeDateTo
            ) {

                /*
                * Employee Leads
                */

                $leadIds = Lead::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'assigned_to',
                        $employee->id
                    )
                    ->pluck('id');

                $totalLeads = $leadIds->count();

                /*
                * Calls
                */

                $callsQuery = CallLog::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->whereIn(
                        'lead_id',
                        $leadIds
                    );

                if (
                    $employeeDateFrom
                    && $employeeDateTo
                ) {

                    $callsQuery->whereBetween(
                        'created_at',
                        [
                            $employeeDateFrom,
                            $employeeDateTo,
                        ]
                    );
                }

                $calls = (clone $callsQuery)
                    ->count();

                /*
                * Connected Calls
                */

                $connected = (clone $callsQuery)
                    ->whereHas(
                        'disposition',
                        function (Builder $query) {

                            $query->where(
                                'type',
                                'connected'
                            );
                        }
                    )
                    ->count();

                /*
                * Demo Sent
                */

                $demoQuery = Lead::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'assigned_to',
                        $employee->id
                    )
                    ->where(
                        'demo_send',
                        true
                    )
                    ->whereNotNull(
                        'demo_sent_at'
                    );

                if (
                    $employeeDateFrom
                    && $employeeDateTo
                ) {

                    $demoQuery->whereBetween(
                        'demo_sent_at',
                        [
                            $employeeDateFrom,
                            $employeeDateTo,
                        ]
                    );
                }

                $demos = $demoQuery->count();

                /*
                * Follow Ups
                */

                $followQuery = FollowUp::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->whereIn(
                        'lead_id',
                        $leadIds
                    );

                if (
                    $employeeDateFrom
                    && $employeeDateTo
                ) {

                    $followQuery->whereBetween(
                        'scheduled_at',
                        [
                            $employeeDateFrom,
                            $employeeDateTo,
                        ]
                    );
                }

                $followUps = (clone $followQuery)
                    ->count();

                $pendingFollowUps = (clone $followQuery)
                    ->where(
                        'status',
                        'pending'
                    )
                    ->count();

                /*
                * Connected Percentage
                */

                $connectedPercentage = $calls > 0
                    ? round(
                        ($connected / $calls) * 100,
                        1
                    )
                    : 0;

                return [

                    'user' => $employee,

                    'total_leads' => $totalLeads,

                    'calls' => $calls,

                    'connected' => $connected,

                    'connected_percentage' =>
                        $connectedPercentage,

                    'demos' => $demos,

                    'followups' => $followUps,

                    'pending_followups' =>
                        $pendingFollowUps,
                ];
            });
    } else {

        $employeePeriod = 'today';

        $employeePeriodLabel = 'Today';

        $employeeFrom = null;

        $employeeTo = null;
    }





    /*
    |--------------------------------------------------------------------------
    | Recent Leads
    |--------------------------------------------------------------------------
    */

    $recentLeads = (clone $leadQuery)
        ->with([
            'assignedUser:id,name,employee_code',
            'status:id,name,color',
            'source:id,name',
        ])
        ->latest('id')
        ->limit(8)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Dashboard Mode
    |--------------------------------------------------------------------------
    */

    $dashboardMode = $hasFullAccess
        ? 'admin'
        : ($isTeamLeader ? 'team_leader' : 'employee');

    /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

    return view('dashboard', [

        /*
        * Leads
        */

        'totalLeads' => $totalLeads,

        'newToday' => $newToday,

        'hotLeads' => $hotLeads,

        /*
        * Demo
        */

        'todayLeadSend' => $todayLeadSend,

        'totalLeadSend' => $totalLeadSend,

        /*
        * Calls
        */

        'callsToday' => $callsToday,

        'connectedToday' => $connectedToday,

        /*
        * Dynamic Dispositions
        */

        'dispositionStats' => $dispositionStats,

        'withoutDisposition' => $withoutDisposition,

        /*
        * Follow Ups
        */

        'followUpsDue' => $followUpsDue,

        'overdue' => $overdue,

        /*
        * Sales
        */

        'sales' => $sales,

        'received' => $received,

        /*
        * Employees
        */

        'activeUsers' => $activeUsers,


        /*
         * Screenshot Dashboard Snapshot
         */

        'todayNewLeads' => $todayNewLeads,
        'todayCalls' => $todayCalls,
        'todayConnected' => $todayConnected,
        'todayDemos' => $todayDemos,
        'todayPendingFollowUps' => $todayPendingFollowUps,
        'todayOverdue' => $todayOverdue,

        'allCalls' => $allCalls,
        'allConnected' => $allConnected,
        'allPendingFollowUps' => $allPendingFollowUps,
        'allOverdue' => $allOverdue,
        'allSales' => $allSales,
        'allReceived' => $allReceived,


        'employeePerformance' => $employeePerformance,

        'employeePeriod' => $employeePeriod,

        'employeePeriodLabel' => $employeePeriodLabel,

        'employeeFrom' => $employeeFrom,

        'employeeTo' => $employeeTo,

        /*
            * Leads
            */

        'recentLeads' => $recentLeads,

        /*
            * Dashboard
            */

        'dashboardMode' => $dashboardMode,

        'hasFullAccess' => $hasFullAccess,

        'isTeamLeader' => $isTeamLeader,

        'visibleUserIds' => $visibleUserIds,

        /*
            * Disposition Filter
            */

        'dispositionPeriod' => $dispositionPeriod,

        'dispositionPeriodLabel' => $dispositionPeriodLabel,

        'dispositionTotalCalls' => $dispositionTotalCalls,

        /*
            * Filter
            */

        'period' => $period,

        'periodLabel' => $periodLabel,
    ]);
}




    /**
 * Super Admin / Admin Employee Performance Detail
 */
public function showEmployee(Request $request, User $employee): View
{
    $authUser = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

    abort_unless(
        $authUser->hasAnyRole(['super_admin', 'admin']),
        403
    );

    /*
    |--------------------------------------------------------------------------
    | Company Security
    |--------------------------------------------------------------------------
    |
    | Dusri company ka employee URL change karke open na ho.
    |
    */

    abort_unless(
        (int) $employee->company_id === (int) $authUser->company_id,
        404
    );

    $companyId = (int) $authUser->company_id;
    $employeeId = (int) $employee->id;

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([
        'period' => [
            'nullable',
            'in:today,month,all,custom',
        ],

        'from' => [
            'nullable',
            'date',
        ],

        'to' => [
            'nullable',
            'date',
            'after_or_equal:from',
        ],

        'search' => [
            'nullable',
            'string',
            'max:255',
        ],
    ]);

    $period = $validated['period'] ?? 'today';

    $from = $validated['from'] ?? null;
    $to = $validated['to'] ?? null;
    $search = trim($validated['search'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Date Range
    |--------------------------------------------------------------------------
    */

    if ($period === 'today') {

        $dateFrom = now()->startOfDay();
        $dateTo = now()->endOfDay();

        $periodLabel = 'Today';

    } elseif ($period === 'month') {

        $dateFrom = now()->startOfMonth();
        $dateTo = now()->endOfDay();

        $periodLabel = 'This Month';

    } elseif ($period === 'custom' && $from && $to) {

        $dateFrom = \Carbon\Carbon::parse($from)->startOfDay();
        $dateTo = \Carbon\Carbon::parse($to)->endOfDay();

        $periodLabel =
            $dateFrom->format('d M Y')
            . ' - '
            . $dateTo->format('d M Y');

    } else {

        $period = 'all';

        $dateFrom = null;
        $dateTo = null;

        $periodLabel = 'All Time';
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Lead IDs
    |--------------------------------------------------------------------------
    */

    $employeeLeadIds = Lead::query()
        ->where('company_id', $companyId)
        ->where('assigned_to', $employeeId)
        ->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | Total Assigned Leads
    |--------------------------------------------------------------------------
    */

    $totalAssignedLeads = $employeeLeadIds->count();

    /*
    |--------------------------------------------------------------------------
    | Calls
    |--------------------------------------------------------------------------
    */

    $callsQuery = CallLog::query()
        ->where('company_id', $companyId)
        ->whereIn('lead_id', $employeeLeadIds);

    if ($dateFrom && $dateTo) {
        $callsQuery->whereBetween(
            'created_at',
            [$dateFrom, $dateTo]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | Search Lead Name / Mobile
    |
    */

    if ($search !== '') {

        $matchingLeadIds = Lead::query()
            ->where('company_id', $companyId)
            ->where('assigned_to', $employeeId)
            ->where(function ($query) use ($search) {

                $query
                    ->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'mobile',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'company_name',
                        'like',
                        '%' . $search . '%'
                    );
            })
            ->pluck('id');

        $callsQuery->whereIn(
            'lead_id',
            $matchingLeadIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Total Calls
    |--------------------------------------------------------------------------
    */

    $totalCalls = (clone $callsQuery)->count();

    /*
    |--------------------------------------------------------------------------
    | Connected Calls
    |--------------------------------------------------------------------------
    */

    $connectedCalls = (clone $callsQuery)
        ->whereHas(
            'disposition',
            function (Builder $query) {

                $query->where(
                    'type',
                    'connected'
                );
            }
        )
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Calls Without Disposition
    |--------------------------------------------------------------------------
    */

    $withoutDisposition = (clone $callsQuery)
        ->whereNull('call_disposition_id')
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Demo Sent
    |--------------------------------------------------------------------------
    */

    $demoQuery = Lead::query()
        ->where('company_id', $companyId)
        ->where('assigned_to', $employeeId)
        ->where('demo_send', true)
        ->whereNotNull('demo_sent_at');

    if ($dateFrom && $dateTo) {
        $demoQuery->whereBetween(
            'demo_sent_at',
            [$dateFrom, $dateTo]
        );
    }

    if ($search !== '') {

        $demoQuery->where(function ($query) use ($search) {

            $query
                ->where(
                    'name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'mobile',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'company_name',
                    'like',
                    '%' . $search . '%'
                );
        });
    }

    $demoSent = $demoQuery->count();

    /*
    |--------------------------------------------------------------------------
    | Follow-ups
    |--------------------------------------------------------------------------
    */

    $followUpQuery = FollowUp::query()
        ->where('company_id', $companyId)
        ->whereIn('lead_id', $employeeLeadIds);

    if ($dateFrom && $dateTo) {
        $followUpQuery->whereBetween(
            'scheduled_at',
            [$dateFrom, $dateTo]
        );
    }

    $totalFollowUps = (clone $followUpQuery)->count();

    $pendingFollowUps = (clone $followUpQuery)
        ->where('status', 'pending')
        ->count();

    $completedFollowUps = (clone $followUpQuery)
        ->where('status', 'completed')
        ->count();

    $overdueFollowUps = (clone $followUpQuery)
        ->where('status', 'pending')
        ->where('scheduled_at', '<', now())
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Disposition Statistics
    |--------------------------------------------------------------------------
    */

    $dispositionCounts = (clone $callsQuery)
        ->whereNotNull('call_disposition_id')
        ->select('call_disposition_id')
        ->selectRaw('COUNT(*) as total')
        ->groupBy('call_disposition_id')
        ->pluck(
            'total',
            'call_disposition_id'
        );

    $dispositions = CallDisposition::query()
        ->where('company_id', $companyId)
        ->orderBy('id')
        ->get()
        ->map(function ($disposition) use (
            $dispositionCounts
        ) {

            return [
                'id' => $disposition->id,

                'name' => $disposition->name,

                'type' => $disposition->type,

                'total' => (int) (
                    $dispositionCounts[
                        $disposition->id
                    ] ?? 0
                ),
            ];
        });

    /*
    |--------------------------------------------------------------------------
    | Call Logs
    |--------------------------------------------------------------------------
    */

    $callLogs = (clone $callsQuery)
        ->with([
            'disposition',
        ])
        ->latest('created_at')
        ->paginate(25)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Lead Information For Call Table
    |--------------------------------------------------------------------------
    */

    $callLeadIds = $callLogs
        ->getCollection()
        ->pluck('lead_id')
        ->filter()
        ->unique();

    $callLeads = Lead::query()
        ->where('company_id', $companyId)
        ->whereIn('id', $callLeadIds)
        ->get([
            'id',
            'name',
            'mobile',
            'company_name',
        ])
        ->keyBy('id');

    /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

    return view(
        'employee',
        compact(
            'employee',
            'period',
            'periodLabel',
            'from',
            'to',
            'search',
            'totalAssignedLeads',
            'totalCalls',
            'connectedCalls',
            'withoutDisposition',
            'demoSent',
            'totalFollowUps',
            'pendingFollowUps',
            'completedFollowUps',
            'overdueFollowUps',
            'dispositions',
            'callLogs',
            'callLeads'
        )
    );
}
}
