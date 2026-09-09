<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * Dashboard Overview API
     *
     * Supported periods:
     * - today
     * - yesterday
     * - week
     * - month
     * - all
     *
     * Normal Employee:
     * - sirf apna data
     *
     * Admin / Super Admin:
     * - company ka data
     * - employee_id ke through kisi employee ka data
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'period' => [
                'nullable',
                Rule::in([
                    'today',
                    'yesterday',
                    'week',
                    'month',
                    'all',
                ]),
            ],

            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Permission / Scope
        |--------------------------------------------------------------------------
        */

        $canViewAll =
            $authUser->hasRole('super-admin')
            || $authUser->hasRole('super_admin')
            || $authUser->hasRole('admin')
            || $authUser->hasRole('owner')
            || $authUser->can('dashboard.view-all');

        $companyId = (int) $authUser->company_id;

        $selectedEmployeeId = null;

        if ($canViewAll && ! empty($validated['employee_id'])) {

            $selectedEmployeeId = (int) $validated['employee_id'];

            $employeeExists = User::query()
                ->whereKey($selectedEmployeeId)
                ->where('company_id', $companyId)
                ->exists();

            if (! $employeeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected employee does not belong to your company.',
                ], 422);
            }

        } elseif (! $canViewAll) {

            $selectedEmployeeId = (int) $authUser->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $period = $validated['period'] ?? 'all';

        [$from, $to] = $this->resolvePeriod($period);

        /*
        |--------------------------------------------------------------------------
        | Today Range
        |
        | Backward compatibility ke liye old "today" keys bhi rahengi.
        |--------------------------------------------------------------------------
        */

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Base Lead Query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where('company_id', $companyId)
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where('assigned_to', $selectedEmployeeId)
            );

        /*
        |--------------------------------------------------------------------------
        | Base Call Query
        |--------------------------------------------------------------------------
        */

        $callQuery = CallLog::query()
            ->whereHas('lead', function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where('user_id', $selectedEmployeeId)
            );

        /*
        |--------------------------------------------------------------------------
        | Base Follow-up Query
        |--------------------------------------------------------------------------
        */

        $followUpQuery = FollowUp::query()
            ->where('company_id', $companyId)
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where('assigned_to', $selectedEmployeeId)
            );

        /*
        |--------------------------------------------------------------------------
        | Period-filtered Queries
        |--------------------------------------------------------------------------
        */

        $periodLeadQuery = clone $leadQuery;

        if ($from && $to) {
            $periodLeadQuery->whereBetween(
                'leads.created_at',
                [$from, $to]
            );
        }

        $periodCallQuery = clone $callQuery;

        if ($from && $to) {
            $this->applyCallDateRange(
                $periodCallQuery,
                $from,
                $to
            );
        }

        $periodFollowUpQuery = clone $followUpQuery;

        if ($from && $to) {
            $periodFollowUpQuery->whereBetween(
                'scheduled_at',
                [$from, $to]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LEAD METRICS
        |--------------------------------------------------------------------------
        */

        $totalLeads = (clone $periodLeadQuery)
            ->count();

        /*
         * Leads created today.
         * Legacy key ke liye hamesha actual today.
         */
        $newToday = (clone $leadQuery)
            ->whereBetween(
                'leads.created_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
         * Selected period me created leads
         * jin par abhi tak koi call nahi hua.
         */
        $uncalledLeads = (clone $periodLeadQuery)
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
        | CONVERSION METRICS
        |--------------------------------------------------------------------------
        */

        $convertedQuery = clone $leadQuery;

        $convertedQuery->whereHas(
            'status',
            function (Builder $query) {
                $query->whereRaw(
                    'LOWER(name) = ?',
                    ['converted']
                );
            }
        );

        if ($from && $to) {
            /*
             * Agar aapke leads table me converted_at hai,
             * to updated_at ki jagah converted_at use karein.
             */
            $convertedQuery->whereBetween(
                'leads.updated_at',
                [$from, $to]
            );
        }

        $converted = $convertedQuery->count();

        /*
         * Legacy today conversion.
         */
        $convertedToday = (clone $leadQuery)
            ->whereHas(
                'status',
                function (Builder $query) {
                    $query->whereRaw(
                        'LOWER(name) = ?',
                        ['converted']
                    );
                }
            )
            ->whereBetween(
                'leads.updated_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | DEMO METRICS
        |--------------------------------------------------------------------------
        |
        | Important:
        |
        | demo_send      = demo kabhi bheja gaya hai
        | demo_sent_at   = latest demo bhejne ka datetime
        |
        |--------------------------------------------------------------------------
        */

        $demoQuery = clone $leadQuery;

        $demoQuery->where('demo_send', true)
            ->whereNotNull('demo_sent_at');

        if ($from && $to) {
            $demoQuery->whereBetween(
                'demo_sent_at',
                [$from, $to]
            );
        }

        $totalDemoSent = $demoQuery->count();

        /*
         * Legacy Today Demo
         */
        $demoSentToday = (clone $leadQuery)
            ->where('demo_send', true)
            ->whereNotNull('demo_sent_at')
            ->whereBetween(
                'demo_sent_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | CALL METRICS
        |--------------------------------------------------------------------------
        */

        $totalCalls = (clone $periodCallQuery)
            ->count();

        /*
         * Legacy Calls Today
         *
         * started_at preferred.
         * started_at null ho to created_at fallback.
         */
        $callsTodayQuery = clone $callQuery;

        $this->applyCallDateRange(
            $callsTodayQuery,
            $todayStart,
            $todayEnd
        );

        $callsToday = $callsTodayQuery->count();

        /*
         * Connected = duration_seconds > 0
         */
        $totalConnectedCalls = (clone $periodCallQuery)
            ->where('duration_seconds', '>', 0)
            ->count();

        $connectedTodayQuery = clone $callQuery;

        $connectedTodayQuery
            ->where('duration_seconds', '>', 0);

        $this->applyCallDateRange(
            $connectedTodayQuery,
            $todayStart,
            $todayEnd
        );

        $connectedCallsToday =
            $connectedTodayQuery->count();

        /*
         * Unique connected lead count
         */
        $uniqueConnectedNumbers =
            (clone $periodCallQuery)
                ->where('duration_seconds', '>', 0)
                ->whereNotNull('lead_id')
                ->distinct()
                ->count('lead_id');

        /*
        |--------------------------------------------------------------------------
        | FOLLOW-UP METRICS
        |--------------------------------------------------------------------------
        */

        $totalFollowUps =
            (clone $periodFollowUpQuery)->count();

        $pendingFollowUps =
            (clone $periodFollowUpQuery)
                ->where('status', 'pending')
                ->count();

        /*
         * Legacy today followups
         */
        $followUpsToday =
            (clone $followUpQuery)
                ->whereBetween(
                    'scheduled_at',
                    [$todayStart, $todayEnd]
                )
                ->count();

        /*
         * Overdue normally current pending overdue hota hai.
         *
         * Agar period != all hai to selected period ke scheduled
         * followups me se overdue count hoga.
         */
        $overdueFollowUps =
            (clone $periodFollowUpQuery)
                ->where('status', 'pending')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', now())
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Selected Employee
        |--------------------------------------------------------------------------
        */

        $selectedEmployee = null;

        if ($selectedEmployeeId) {

            $employee = User::query()
                ->where('company_id', $companyId)
                ->find($selectedEmployeeId);

            if ($employee) {
                $selectedEmployee = [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'Dashboard overview fetched successfully.',

            /*
             * Flutter ko exact selected period bhi milega.
             */
            'filter' => [
                'period' => $period,

                'from' => $from
                    ? $from->format('Y-m-d H:i:s')
                    : null,

                'to' => $to
                    ? $to->format('Y-m-d H:i:s')
                    : null,
            ],

            'scope' => [
                'type' => $selectedEmployeeId
                    ? 'employee'
                    : 'company',

                'can_view_all' => $canViewAll,

                'employee' => $selectedEmployee,
            ],

            'metrics' => [

                /*
                 * Period filtered metrics
                 */
                'total_leads' => $totalLeads,

                'uncalled_leads' => $uncalledLeads,

                'total_calls' => $totalCalls,

                'total_connected_calls' => $totalConnectedCalls,

                'unique_connected_numbers' => $uniqueConnectedNumbers,

                'total_demo_sent' => $totalDemoSent,

                'total_followups' => $totalFollowUps,

                'pending_followups' => $pendingFollowUps,

                'overdue_followups' => $overdueFollowUps,

                'converted' => $converted,

                /*
                 * Existing legacy keys.
                 * Flutter ka old dashboard break nahi hoga.
                 */
                'new_today' => $newToday,

                'calls_today' => $callsToday,

                'connected_calls_today' => $connectedCallsToday,

                'demo_sent_today' => $demoSentToday,

                'followups_today' => $followUpsToday,

                'converted_today' => $convertedToday,
            ],
        ]);
    }

    /**
     * Resolve dashboard period.
     */
    private function resolvePeriod(
        string $period
    ): array {

        return match ($period) {

            'today' => [
                Carbon::today(),
                Carbon::today()->endOfDay(),
            ],

            'yesterday' => [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ],

            'week' => [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfDay(),
            ],

            'month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfDay(),
            ],

            /*
             * All = no date filter
             */
            default => [
                null,
                null,
            ],
        };
    }

    /**
     * Apply call datetime filter.
     *
     * started_at available ho to usko use karega.
     * Purane call logs me started_at NULL ho to
     * created_at fallback use hoga.
     */
    private function applyCallDateRange(
        Builder $query,
        Carbon $from,
        Carbon $to
    ): Builder {

        return $query->where(
            function (Builder $q) use ($from, $to) {

                $q->whereBetween(
                    'started_at',
                    [$from, $to]
                )
                    ->orWhere(
                        function (Builder $fallback) use ($from, $to) {

                            $fallback
                                ->whereNull('started_at')
                                ->whereBetween(
                                    'created_at',
                                    [$from, $to]
                                );
                        }
                    );
            }
        );
    }
}
