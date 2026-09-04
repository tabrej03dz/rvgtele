<?php

namespace App\Http\Controllers;

use App\Models\CallDisposition;
use App\Models\CallLog;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
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

        /*
        |--------------------------------------------------------------------------
        | Full Access Roles
        |--------------------------------------------------------------------------
        */

        $hasFullAccess = $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Base Lead Query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where(
                'company_id',
                $companyId
            );

        if (!$hasFullAccess) {
            $leadQuery->where(
                'assigned_to',
                $userId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Visible Lead IDs
        |--------------------------------------------------------------------------
        */

        $visibleLeadIdsQuery = Lead::query()
            ->select('id')
            ->where(
                'company_id',
                $companyId
            );

        if (!$hasFullAccess) {
            $visibleLeadIdsQuery->where(
                'assigned_to',
                $userId
            );
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

        $applyPeriod(
            $dispositionCountQuery,
            'created_at'
        );

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

        $applyPeriod(
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

        $applyPeriod(
            $followUpsDueQuery,
            'scheduled_at'
        );

        $followUpsDue = $followUpsDueQuery
            ->count();

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

        $activeUsers = $hasFullAccess
            ? User::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->count()
            : 1;

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
            : 'employee';

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
             * Leads
             */

            'recentLeads' => $recentLeads,

            /*
             * Dashboard
             */

            'dashboardMode' => $dashboardMode,

            'hasFullAccess' => $hasFullAccess,

            /*
             * Filter
             */

            'period' => $period,

            'periodLabel' => $periodLabel,
        ]);
    }
}