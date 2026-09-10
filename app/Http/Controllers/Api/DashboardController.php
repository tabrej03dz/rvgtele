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
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Permission
        |--------------------------------------------------------------------------
        */

        $canViewAll =
            $authUser->hasRole('Super Admin') ||
            $authUser->hasRole('super-admin') ||
            $authUser->hasRole('super_admin') ||
            $authUser->hasRole('Admin') ||
            $authUser->hasRole('admin') ||
            $authUser->hasRole('Owner') ||
            $authUser->hasRole('owner') ||
            $authUser->can('dashboard.view-all');

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],

            'period' => [
                'nullable',
                Rule::in([
                    'today',
                    'yesterday',

                    // Flutter old values
                    'week',
                    'month',
                    'all',

                    // New aliases
                    'this_week',
                    'this_month',
                    'all_time',

                    'custom',
                ]),
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $companyId = (int) $authUser->company_id;

        /*
        |--------------------------------------------------------------------------
        | Employee Scope
        |--------------------------------------------------------------------------
        */

        $selectedEmployeeId = null;

        if ($canViewAll && !empty($validated['employee_id'])) {

            $selectedEmployeeId = (int) $validated['employee_id'];

            $employeeExists = User::query()
                ->whereKey($selectedEmployeeId)
                ->where('company_id', $companyId)
                ->exists();

            if (!$employeeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected employee does not belong to your company.',
                ], 422);
            }

        } elseif (!$canViewAll) {

            // Normal employee sirf apna dashboard dekhega
            $selectedEmployeeId = (int) $authUser->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $requestedPeriod = $validated['period'] ?? 'today';

        // Old / new values normalize
        $period = match ($requestedPeriod) {
            'this_week'  => 'week',
            'this_month' => 'month',
            'all_time'   => 'all',
            default      => $requestedPeriod,
        };

        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        */

        [$from, $to] = $this->resolvePeriod(
            $period,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        if ($period === 'custom' && (!$from || !$to)) {
            return response()->json([
                'success' => false,
                'message' => 'from_date and to_date are required for custom date filter.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Actual Today
        |--------------------------------------------------------------------------
        */

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd   = Carbon::today()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Base Lead Query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where('company_id', $companyId)
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) =>
                    $query->where('assigned_to', $selectedEmployeeId)
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
                fn (Builder $query) =>
                    $query->where('user_id', $selectedEmployeeId)
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
                fn (Builder $query) =>
                    $query->where('assigned_to', $selectedEmployeeId)
            );

        /*
        |--------------------------------------------------------------------------
        | Period Lead Query
        |--------------------------------------------------------------------------
        */

        $periodLeadQuery = clone $leadQuery;

        if ($from && $to) {
            $periodLeadQuery->whereBetween(
                'leads.created_at',
                [$from, $to]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Period Call Query
        |--------------------------------------------------------------------------
        */

        $periodCallQuery = clone $callQuery;

        if ($from && $to) {
            $this->applyCallDateRange(
                $periodCallQuery,
                $from,
                $to
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Period Follow-up Query
        |--------------------------------------------------------------------------
        */

        $periodFollowUpQuery = clone $followUpQuery;

        if ($from && $to) {
            $periodFollowUpQuery->whereBetween(
                'scheduled_at',
                [$from, $to]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Total Leads
        |--------------------------------------------------------------------------
        */

        $totalLeads = (clone $periodLeadQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | New Today
        |--------------------------------------------------------------------------
        */

        $newToday = (clone $leadQuery)
            ->whereBetween(
                'leads.created_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Uncalled Leads
        |--------------------------------------------------------------------------
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
        | Total Calls
        |--------------------------------------------------------------------------
        */

        $totalCalls = (clone $periodCallQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | Calls Today
        |--------------------------------------------------------------------------
        */

        $callsTodayQuery = clone $callQuery;

        $this->applyCallDateRange(
            $callsTodayQuery,
            $todayStart,
            $todayEnd
        );

        $callsToday = $callsTodayQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Connected Calls
        |--------------------------------------------------------------------------
        */

        $totalConnectedCalls = (clone $periodCallQuery)
            ->where('duration_seconds', '>', 0)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Connected Today
        |--------------------------------------------------------------------------
        */

        $connectedTodayQuery = (clone $callQuery)
            ->where('duration_seconds', '>', 0);

        $this->applyCallDateRange(
            $connectedTodayQuery,
            $todayStart,
            $todayEnd
        );

        $connectedCallsToday = $connectedTodayQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Unique Connected
        |--------------------------------------------------------------------------
        */

        $uniqueConnectedNumbers = (clone $periodCallQuery)
            ->where('duration_seconds', '>', 0)
            ->whereNotNull('lead_id')
            ->distinct()
            ->count('lead_id');

        /*
        |--------------------------------------------------------------------------
        | Today Demo Send
        |--------------------------------------------------------------------------
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
        | Demo Send - Selected Period
        |--------------------------------------------------------------------------
        */

        $demoQuery = (clone $leadQuery)
            ->where('demo_send', true);

        if ($from && $to) {
            $demoQuery
                ->whereNotNull('demo_sent_at')
                ->whereBetween(
                    'demo_sent_at',
                    [$from, $to]
                );
        }

        $totalDemoSent = $demoQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Total Follow-ups
        |--------------------------------------------------------------------------
        */

        $totalFollowUps = (clone $periodFollowUpQuery)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending Follow-ups
        |--------------------------------------------------------------------------
        */

        $pendingFollowUps = (clone $periodFollowUpQuery)
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Follow-ups Today
        |--------------------------------------------------------------------------
        */

        $followUpsToday = (clone $followUpQuery)
            ->whereBetween(
                'scheduled_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Overdue Follow-ups
        |--------------------------------------------------------------------------
        */

        $overdueQuery = (clone $followUpQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now());

        /*
         * Period selected hai to usi period ke followups me
         * overdue calculate hoga.
         */
        if ($from && $to) {
            $overdueQuery->whereBetween(
                'scheduled_at',
                [$from, $to]
            );
        }

        $overdueFollowUps = $overdueQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Converted Total / Selected Period
        |--------------------------------------------------------------------------
        */

        $convertedQuery = (clone $leadQuery)
            ->whereHas('status', function (Builder $query) {
                $query->whereRaw(
                    'LOWER(name) = ?',
                    ['converted']
                );
            });

        if ($from && $to) {
            $convertedQuery->whereBetween(
                'leads.updated_at',
                [$from, $to]
            );
        }

        $converted = $convertedQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Converted Today
        |--------------------------------------------------------------------------
        */

        $convertedToday = (clone $leadQuery)
            ->whereHas('status', function (Builder $query) {
                $query->whereRaw(
                    'LOWER(name) = ?',
                    ['converted']
                );
            })
            ->whereBetween(
                'leads.updated_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Employee
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
        | Period Label
        |--------------------------------------------------------------------------
        */

        $periodLabel = match ($period) {
            'today'     => 'Today',
            'yesterday' => 'Yesterday',
            'week'      => 'This Week',
            'month'     => 'This Month',
            'all'       => 'All Time',

            'custom' => $from && $to
                ? $from->format('d M Y') . ' - ' . $to->format('d M Y')
                : 'Custom Date',

            default => 'Today',
        };

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'Dashboard overview fetched successfully.',

            'filter' => [
                'period' => $period,
                'requested_period' => $requestedPeriod,
                'label' => $periodLabel,

                'from_date' => $from
                    ? $from->format('Y-m-d')
                    : null,

                'to_date' => $to
                    ? $to->format('Y-m-d')
                    : null,
            ],

            'scope' => [
                'type' => $selectedEmployeeId
                    ? 'employee'
                    : 'company',

                'can_view_all' => $canViewAll,

                'employee' => $selectedEmployee,
            ],

            /*
            |--------------------------------------------------------------------------
            | Screenshot Cards
            |--------------------------------------------------------------------------
            */

            'dashboard' => [

                'total_leads' => [
                    'label' => 'Total Leads',
                    'value' => $totalLeads,
                ],

                'new_today' => [
                    'label' => 'New Today',
                    'value' => $newToday,
                ],

                'uncalled_leads' => [
                    'label' => 'Uncalled Leads',
                    'value' => $uncalledLeads,
                ],

                'total_calls' => [
                    'label' => 'Total Calls',
                    'value' => $totalCalls,
                ],

                'calls_today' => [
                    'label' => 'Calls Today',
                    'value' => $callsToday,
                ],

                'connected_calls' => [
                    'label' => 'Connected Calls',
                    'value' => $totalConnectedCalls,
                ],

                'connected_today' => [
                    'label' => 'Connected Today',
                    'value' => $connectedCallsToday,
                ],

                'unique_connected' => [
                    'label' => 'Unique Connected',
                    'value' => $uniqueConnectedNumbers,
                ],

                'today_demo_send' => [
                    'label' => 'Today Demo Send',
                    'value' => $demoSentToday,
                ],

                'total_demo_send' => [
                    'label' => 'Total Demo Send',
                    'value' => $totalDemoSent,
                ],

                'total_followups' => [
                    'label' => 'Total Follow-ups',
                    'value' => $totalFollowUps,
                ],

                'pending_followups' => [
                    'label' => 'Pending Follow-ups',
                    'value' => $pendingFollowUps,
                ],

                'overdue_followups' => [
                    'label' => 'Overdue Follow-ups',
                    'value' => $overdueFollowUps,
                ],

                'converted_total' => [
                    'label' => 'Converted (Total)',
                    'value' => $converted,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Existing Flutter Compatible Metrics
            |--------------------------------------------------------------------------
            */

            'metrics' => [
                'total_leads' => $totalLeads,
                'new_today' => $newToday,
                'uncalled_leads' => $uncalledLeads,

                'total_calls' => $totalCalls,
                'calls_today' => $callsToday,

                'total_connected_calls' => $totalConnectedCalls,
                'connected_calls_today' => $connectedCallsToday,
                'unique_connected_numbers' => $uniqueConnectedNumbers,

                'demo_sent_today' => $demoSentToday,
                'total_demo_sent' => $totalDemoSent,

                'total_followups' => $totalFollowUps,
                'followups_today' => $followUpsToday,
                'pending_followups' => $pendingFollowUps,
                'overdue_followups' => $overdueFollowUps,

                'converted' => $converted,
                'converted_today' => $convertedToday,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Period
    |--------------------------------------------------------------------------
    */

    private function resolvePeriod(
        string $period,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {

        return match ($period) {

            'today' => [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ],

            'yesterday' => [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ],

            'week' => [
                Carbon::now()->startOfWeek()->startOfDay(),
                Carbon::now()->endOfDay(),
            ],

            'month' => [
                Carbon::now()->startOfMonth()->startOfDay(),
                Carbon::now()->endOfDay(),
            ],

            'custom' => [
                $fromDate
                    ? Carbon::parse($fromDate)->startOfDay()
                    : null,

                $toDate
                    ? Carbon::parse($toDate)->endOfDay()
                    : null,
            ],

            'all' => [
                null,
                null,
            ],

            default => [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Call Date Range
    |--------------------------------------------------------------------------
    |
    | New call log:
    | started_at use karega.
    |
    | Old record:
    | started_at NULL hua to created_at use karega.
    |
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