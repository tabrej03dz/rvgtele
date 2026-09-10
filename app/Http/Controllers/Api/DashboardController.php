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
     * Dashboard overview API.
     *
     * Normal user:
     * केवल अपना dashboard देखेगा।
     *
     * Admin / Super Admin:
     * पूरी company का dashboard देखेगा।
     * employee_id भेजकर किसी एक employee का dashboard भी देख सकता है।
     */
    // public function index(Request $request): JsonResponse
    // {
    //     $authUser = $request->user();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Admin access
    //     |--------------------------------------------------------------------------
    //     |
    //     | Permission मिलने पर सभी employees का data देख सकेगा।
    //     | Super Admin को हमेशा full access मिलेगा।
    //     |
    //     */

    //     $canViewAll = $authUser->hasRole('super-admin')
    //         || $authUser->hasRole('super_admin')
    //         || $authUser->hasRole('admin')
    //         || $authUser->hasRole('owner')
    //         || $authUser->can('dashboard.view-all');

    //     $validated = $request->validate([
    //         'employee_id' => [
    //             'nullable',
    //             'integer',
    //             Rule::exists('users', 'id'),
    //         ],
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Employee filter
    //     |--------------------------------------------------------------------------
    //     */

    //     $selectedEmployeeId = null;

    //     if ($canViewAll && !empty($validated['employee_id'])) {
    //         $selectedEmployeeId = (int) $validated['employee_id'];

    //         $employeeExists = User::query()
    //             ->whereKey($selectedEmployeeId)
    //             ->where('company_id', $authUser->company_id)
    //             ->exists();

    //         if (!$employeeExists) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Selected employee does not belong to your company.',
    //             ], 422);
    //         }
    //     } elseif (!$canViewAll) {
    //         $selectedEmployeeId = (int) $authUser->id;
    //     }

    //     $companyId = (int) $authUser->company_id;

    //     $todayStart = Carbon::today();
    //     $todayEnd = Carbon::today()->endOfDay();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Lead query
    //     |--------------------------------------------------------------------------
    //     */

    //     $leadQuery = Lead::query()
    //         ->where('company_id', $companyId)
    //         ->when(
    //             $selectedEmployeeId,
    //             fn (Builder $query) => $query->where(
    //                 'assigned_to',
    //                 $selectedEmployeeId
    //             )
    //         );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Call query
    //     |--------------------------------------------------------------------------
    //     */

    //     $callQuery = CallLog::query()
    //         ->whereHas('lead', function (Builder $query) use ($companyId) {
    //             $query->where('company_id', $companyId);
    //         })
    //         ->when(
    //             $selectedEmployeeId,
    //             fn (Builder $query) => $query->where(
    //                 'user_id',
    //                 $selectedEmployeeId
    //             )
    //         );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Follow-up query
    //     |--------------------------------------------------------------------------
    //     */

    //     $followUpQuery = FollowUp::query()
    //         ->where('company_id', $companyId)
    //         ->when(
    //             $selectedEmployeeId,
    //             fn (Builder $query) => $query->where(
    //                 'assigned_to',
    //                 $selectedEmployeeId
    //             )
    //         );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Lead metrics
    //     |--------------------------------------------------------------------------
    //     */

    //   $totalLeads = (clone $leadQuery)->count();

    //     $newToday = (clone $leadQuery)
    //         ->whereBetween('leads.created_at', [
    //             $todayStart,
    //             $todayEnd,
    //         ])
    //         ->count();

    //     /*
    //     * ऐसी leads जिन पर अभी तक एक भी call नहीं की गई।
    //     * इसके लिए Lead model में callLogs relationship आवश्यक नहीं है।
    //     */
    //     $uncalledLeads = (clone $leadQuery)
    //         ->whereNotExists(function ($query) {
    //             $query->selectRaw('1')
    //                 ->from('call_logs')
    //                 ->whereColumn('call_logs.lead_id', 'leads.id');
    //         })
    //         ->count();

    //     /*
    //     * Converted status वाली leads।
    //     */
    //     $converted = (clone $leadQuery)
    //         ->whereHas('status', function (Builder $query) {
    //             $query->whereRaw('LOWER(name) = ?', ['converted']);
    //         })
    //         ->count();

    //     $convertedToday = (clone $leadQuery)
    //     ->whereHas('status', function (Builder $query) {
    //         $query->whereRaw('LOWER(name) = ?', ['converted']);
    //     })
    //     ->whereBetween('leads.updated_at', [
    //         $todayStart,
    //         $todayEnd,
    //     ])
    //     ->count();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Demo metrics
    //     |--------------------------------------------------------------------------
    //     */

    //     $totalDemoSent = (clone $leadQuery)
    //         ->where('demo_send', true)
    //         ->count();

    //     $demoSentToday = (clone $leadQuery)
    //         ->where('demo_send', true)
    //         ->whereBetween('demo_sent_at', [
    //             $todayStart,
    //             $todayEnd,
    //         ])
    //         ->count();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Call metrics
    //     |--------------------------------------------------------------------------
    //     */

    //     $totalCalls = (clone $callQuery)->count();

    //     $callsToday = (clone $callQuery)
    //         ->whereBetween('created_at', [
    //             $todayStart,
    //             $todayEnd,
    //         ])
    //         ->count();

    //     /*
    //     * Duration 0 से ज्यादा है तो call connected मानी जाएगी।
    //     */
    //     $totalConnectedCalls = (clone $callQuery)
    //         ->where('duration_seconds', '>', 0)
    //         ->count();

    //     $connectedCallsToday = (clone $callQuery)
    //         ->where('duration_seconds', '>', 0)
    //         ->whereBetween('created_at', [
    //             $todayStart,
    //             $todayEnd,
    //         ])
    //         ->count();

    //     /*
    //     * एक number से कई बार बात हुई हो तो भी एक ही connected number count होगा।
    //     */
    //     $uniqueConnectedNumbers = (clone $callQuery)
    //         ->where('duration_seconds', '>', 0)
    //         ->distinct('lead_id')
    //         ->count('lead_id');

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Follow-up metrics
    //     |--------------------------------------------------------------------------
    //     */

    //     $totalFollowUps = (clone $followUpQuery)->count();

    //     $pendingFollowUps = (clone $followUpQuery)
    //         ->where('status', 'pending')
    //         ->count();

    //     $followUpsToday = (clone $followUpQuery)
    //         ->whereBetween('scheduled_at', [
    //             $todayStart,
    //             $todayEnd,
    //         ])
    //         ->count();

    //     $overdueFollowUps = (clone $followUpQuery)
    //         ->where('status', 'pending')
    //         ->whereNotNull('scheduled_at')
    //         ->where('scheduled_at', '<', now())
    //         ->count();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Selected employee details
    //     |--------------------------------------------------------------------------
    //     */

    //     $selectedEmployee = null;

    //     if ($selectedEmployeeId) {
    //         $employee = User::query()
    //             ->find($selectedEmployeeId);

    //         if ($employee) {
    //             $selectedEmployee = [
    //                 'id' => $employee->id,
    //                 'name' => $employee->name,
    //                 'employee_code' => $employee->employee_code,
    //             ];
    //         }
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Response
    //     |--------------------------------------------------------------------------
    //     */

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Dashboard overview fetched successfully.',

    //         'scope' => [
    //             'type' => $selectedEmployeeId
    //                 ? 'employee'
    //                 : 'company',

    //             'can_view_all' => $canViewAll,

    //             'employee' => $selectedEmployee,
    //         ],

    //         'metrics' => [
    //             'total_leads' => $totalLeads,
    //             'new_today' => $newToday,
    //             'uncalled_leads' => $uncalledLeads,

    //             'total_calls' => $totalCalls,
    //             'calls_today' => $callsToday,

    //             'total_connected_calls' => $totalConnectedCalls,
    //             'connected_calls_today' => $connectedCallsToday,
    //             'unique_connected_numbers' => $uniqueConnectedNumbers,

    //             'demo_sent_today' => $demoSentToday,
    //             'total_demo_sent' => $totalDemoSent,

    //             'total_followups' => $totalFollowUps,
    //             'followups_today' => $followUpsToday,
    //             'pending_followups' => $pendingFollowUps,
    //             'overdue_followups' => $overdueFollowUps,

    //             'converted' => $converted,
    //             'converted_today' => $convertedToday,
    //         ],
    //     ]);
    // }



    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Permission / Scope
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
        |
        | period:
        | today
        | yesterday
        | this_week
        | this_month
        | all_time
        | custom
        |
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
        | Employee Filter
        |--------------------------------------------------------------------------
        */

        $selectedEmployeeId = null;

        if ($canViewAll && !empty($validated['employee_id'])) {

            $selectedEmployeeId = (int) $validated['employee_id'];

            $employeeExists = User::query()
                ->where('id', $selectedEmployeeId)
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
        | Period / Date Filter
        |--------------------------------------------------------------------------
        */

        $period = $validated['period'] ?? 'today';

        $from = null;
        $to   = null;

        switch ($period) {

            case 'yesterday':

                $from = Carbon::yesterday()->startOfDay();
                $to   = Carbon::yesterday()->endOfDay();

                break;

            case 'this_week':

                $from = Carbon::now()->startOfWeek()->startOfDay();
                $to   = Carbon::now()->endOfWeek()->endOfDay();

                break;

            case 'this_month':

                $from = Carbon::now()->startOfMonth()->startOfDay();
                $to   = Carbon::now()->endOfMonth()->endOfDay();

                break;

            case 'all_time':

                $from = null;
                $to   = null;

                break;

            case 'custom':

                if (empty($validated['from_date']) || empty($validated['to_date'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'from_date and to_date are required for custom date filter.',
                    ], 422);
                }

                $from = Carbon::parse($validated['from_date'])->startOfDay();
                $to   = Carbon::parse($validated['to_date'])->endOfDay();

                break;

            case 'today':
            default:

                $period = 'today';

                $from = Carbon::today()->startOfDay();
                $to   = Carbon::today()->endOfDay();

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Actual Today Range
        |--------------------------------------------------------------------------
        |
        | Screenshot me kuch cards specifically "Today" ke hain.
        | Isliye unke liye actual today range alag rakhi gayi hai.
        |
        */

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd   = Carbon::today()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Helper: Date Filter
        |--------------------------------------------------------------------------
        */

        $applyDateFilter = function (Builder $query, string $column = 'created_at') use ($from, $to) {

            if ($from && $to) {
                $query->whereBetween($column, [$from, $to]);
            }

            return $query;
        };

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
            ->whereHas('lead', function (Builder $query) use ($companyId, $selectedEmployeeId) {

                $query->where('company_id', $companyId);

                /*
                * Employee dashboard par usi employee ki assigned leads.
                */
                if ($selectedEmployeeId) {
                    $query->where('assigned_to', $selectedEmployeeId);
                }
            })
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) =>
                    $query->where('user_id', $selectedEmployeeId)
            );

        /*
        |--------------------------------------------------------------------------
        | Base Follow-Up Query
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
        | 1. Total Leads
        |--------------------------------------------------------------------------
        |
        | Screenshot:
        | Total Leads
        |
        | Selected filter ke according count.
        |
        */

        $totalLeadsQuery = clone $leadQuery;

        $applyDateFilter(
            $totalLeadsQuery,
            'leads.created_at'
        );

        $totalLeads = $totalLeadsQuery->count();

        /*
        |--------------------------------------------------------------------------
        | 2. New Today
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
        | 3. Uncalled Leads
        |--------------------------------------------------------------------------
        |
        | Assigned leads jinke against abhi tak koi call log nahi hai.
        |
        */

        $uncalledLeadQuery = clone $leadQuery;

        $applyDateFilter(
            $uncalledLeadQuery,
            'leads.created_at'
        );

        $uncalledLeads = $uncalledLeadQuery
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
        | 4. Total Calls
        |--------------------------------------------------------------------------
        */

        $totalCallsQuery = clone $callQuery;

        $applyDateFilter(
            $totalCallsQuery,
            'call_logs.created_at'
        );

        $totalCalls = $totalCallsQuery->count();

        /*
        |--------------------------------------------------------------------------
        | 5. Calls Today
        |--------------------------------------------------------------------------
        */

        $callsToday = (clone $callQuery)
            ->whereBetween(
                'call_logs.created_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 6. Connected Calls
        |--------------------------------------------------------------------------
        |
        | duration_seconds > 0 = connected
        |
        */

        $connectedQuery = clone $callQuery;

        $applyDateFilter(
            $connectedQuery,
            'call_logs.created_at'
        );

        $totalConnectedCalls = $connectedQuery
            ->where('duration_seconds', '>', 0)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 7. Connected Today
        |--------------------------------------------------------------------------
        */

        $connectedCallsToday = (clone $callQuery)
            ->where('duration_seconds', '>', 0)
            ->whereBetween(
                'call_logs.created_at',
                [$todayStart, $todayEnd]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 8. Unique Connected
        |--------------------------------------------------------------------------
        |
        | Same lead ko multiple connected calls hue ho,
        | tab bhi lead ek hi baar count hogi.
        |
        */

        $uniqueConnectedQuery = clone $callQuery;

        $applyDateFilter(
            $uniqueConnectedQuery,
            'call_logs.created_at'
        );

        $uniqueConnectedNumbers = $uniqueConnectedQuery
            ->where('duration_seconds', '>', 0)
            ->whereNotNull('lead_id')
            ->distinct()
            ->count('lead_id');

        /*
        |--------------------------------------------------------------------------
        | 9. Today Demo Send
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
        | 10. Total Demo Send
        |--------------------------------------------------------------------------
        |
        | Selected period ke according.
        |
        */

        $totalDemoQuery = (clone $leadQuery)
            ->where('demo_send', true);

        if ($from && $to) {

            $totalDemoQuery->whereNotNull('demo_sent_at')
                ->whereBetween(
                    'demo_sent_at',
                    [$from, $to]
                );
        }

        $totalDemoSent = $totalDemoQuery->count();

        /*
        |--------------------------------------------------------------------------
        | 11. Total Follow-ups
        |--------------------------------------------------------------------------
        */

        $totalFollowUpQuery = clone $followUpQuery;

        if ($from && $to) {

            $totalFollowUpQuery->whereBetween(
                'scheduled_at',
                [$from, $to]
            );
        }

        $totalFollowUps = $totalFollowUpQuery->count();

        /*
        |--------------------------------------------------------------------------
        | 12. Pending Follow-ups
        |--------------------------------------------------------------------------
        */

        $pendingFollowUpQuery = (clone $followUpQuery)
            ->where('status', 'pending');

        if ($from && $to) {

            $pendingFollowUpQuery->whereBetween(
                'scheduled_at',
                [$from, $to]
            );
        }

        $pendingFollowUps = $pendingFollowUpQuery->count();

        /*
        |--------------------------------------------------------------------------
        | 13. Overdue Follow-ups
        |--------------------------------------------------------------------------
        |
        | Pending + scheduled time current time se pehle.
        |
        */

        $overdueFollowUps = (clone $followUpQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 14. Converted Total
        |--------------------------------------------------------------------------
        */

        $convertedQuery = (clone $leadQuery)
            ->whereHas('status', function (Builder $query) {

                $query->whereRaw(
                    'LOWER(name) = ?',
                    ['converted']
                );
            });

        $converted = $convertedQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Converted In Selected Period
        |--------------------------------------------------------------------------
        */

        $convertedPeriodQuery = (clone $leadQuery)
            ->whereHas('status', function (Builder $query) {

                $query->whereRaw(
                    'LOWER(name) = ?',
                    ['converted']
                );
            });

        if ($from && $to) {

            $convertedPeriodQuery->whereBetween(
                'leads.updated_at',
                [$from, $to]
            );
        }

        $convertedInPeriod = $convertedPeriodQuery->count();

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
        | Period Label
        |--------------------------------------------------------------------------
        */

        $periodLabel = match ($period) {

            'today' => 'Today',

            'yesterday' => 'Yesterday',

            'this_week' => 'This Week',

            'this_month' => 'This Month',

            'all_time' => 'All Time',

            'custom' => ($from && $to)
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

            /*
            |--------------------------------------------------------------------------
            | Current Filter
            |--------------------------------------------------------------------------
            */

            'filter' => [

                'period' => $period,

                'label' => $periodLabel,

                'from_date' => $from
                    ? $from->format('Y-m-d')
                    : null,

                'to_date' => $to
                    ? $to->format('Y-m-d')
                    : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | User Scope
            |--------------------------------------------------------------------------
            */

            'scope' => [

                'type' => $selectedEmployeeId
                    ? 'employee'
                    : 'company',

                'can_view_all' => $canViewAll,

                'employee' => $selectedEmployee,
            ],

            /*
            |--------------------------------------------------------------------------
            | Direct Dashboard Cards
            |--------------------------------------------------------------------------
            |
            | Flutter isi object se screenshot wale cards directly show kar sakta hai.
            |
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
            | Raw Metrics
            |--------------------------------------------------------------------------
            |
            | Purane Flutter code ko break na kare,
            | isliye metrics object bhi rakha hai.
            |
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

                'converted_in_period' => $convertedInPeriod,
            ],
        ]);
    }
}
