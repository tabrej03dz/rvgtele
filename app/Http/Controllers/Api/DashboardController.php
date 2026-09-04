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
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Admin access
        |--------------------------------------------------------------------------
        |
        | Permission मिलने पर सभी employees का data देख सकेगा।
        | Super Admin को हमेशा full access मिलेगा।
        |
        */

        $canViewAll = $authUser->hasRole('super-admin')
            || $authUser->hasRole('super_admin')
            || $authUser->hasRole('admin')
            || $authUser->hasRole('owner')
            || $authUser->can('dashboard.view-all');

        $validated = $request->validate([
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Employee filter
        |--------------------------------------------------------------------------
        */

        $selectedEmployeeId = null;

        if ($canViewAll && !empty($validated['employee_id'])) {
            $selectedEmployeeId = (int) $validated['employee_id'];

            $employeeExists = User::query()
                ->whereKey($selectedEmployeeId)
                ->where('company_id', $authUser->company_id)
                ->exists();

            if (!$employeeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected employee does not belong to your company.',
                ], 422);
            }
        } elseif (!$canViewAll) {
            $selectedEmployeeId = (int) $authUser->id;
        }

        $companyId = (int) $authUser->company_id;

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Lead query
        |--------------------------------------------------------------------------
        */

        $leadQuery = Lead::query()
            ->where('company_id', $companyId)
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where(
                    'assigned_to',
                    $selectedEmployeeId
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Call query
        |--------------------------------------------------------------------------
        */

        $callQuery = CallLog::query()
            ->whereHas('lead', function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where(
                    'user_id',
                    $selectedEmployeeId
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Follow-up query
        |--------------------------------------------------------------------------
        */

        $followUpQuery = FollowUp::query()
            ->where('company_id', $companyId)
            ->when(
                $selectedEmployeeId,
                fn (Builder $query) => $query->where(
                    'assigned_to',
                    $selectedEmployeeId
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Lead metrics
        |--------------------------------------------------------------------------
        */

      $totalLeads = (clone $leadQuery)->count();

$newToday = (clone $leadQuery)
    ->whereBetween('leads.created_at', [
        $todayStart,
        $todayEnd,
    ])
    ->count();

/*
 * ऐसी leads जिन पर अभी तक एक भी call नहीं की गई।
 * इसके लिए Lead model में callLogs relationship आवश्यक नहीं है।
 */
$uncalledLeads = (clone $leadQuery)
    ->whereNotExists(function ($query) {
        $query->selectRaw('1')
            ->from('call_logs')
            ->whereColumn('call_logs.lead_id', 'leads.id');
    })
    ->count();

/*
 * Converted status वाली leads।
 */
$converted = (clone $leadQuery)
    ->whereHas('status', function (Builder $query) {
        $query->whereRaw('LOWER(name) = ?', ['converted']);
    })
    ->count();

$convertedToday = (clone $leadQuery)
    ->whereHas('status', function (Builder $query) {
        $query->whereRaw('LOWER(name) = ?', ['converted']);
    })
    ->whereBetween('leads.updated_at', [
        $todayStart,
        $todayEnd,
    ])
    ->count();

        /*
        |--------------------------------------------------------------------------
        | Demo metrics
        |--------------------------------------------------------------------------
        */

        $totalDemoSent = (clone $leadQuery)
            ->where('demo_send', true)
            ->count();

        $demoSentToday = (clone $leadQuery)
            ->where('demo_send', true)
            ->whereBetween('demo_sent_at', [
                $todayStart,
                $todayEnd,
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Call metrics
        |--------------------------------------------------------------------------
        */

        $totalCalls = (clone $callQuery)->count();

        $callsToday = (clone $callQuery)
            ->whereBetween('created_at', [
                $todayStart,
                $todayEnd,
            ])
            ->count();

        /*
         * Duration 0 से ज्यादा है तो call connected मानी जाएगी।
         */
        $totalConnectedCalls = (clone $callQuery)
            ->where('duration_seconds', '>', 0)
            ->count();

        $connectedCallsToday = (clone $callQuery)
            ->where('duration_seconds', '>', 0)
            ->whereBetween('created_at', [
                $todayStart,
                $todayEnd,
            ])
            ->count();

        /*
         * एक number से कई बार बात हुई हो तो भी एक ही connected number count होगा।
         */
        $uniqueConnectedNumbers = (clone $callQuery)
            ->where('duration_seconds', '>', 0)
            ->distinct('lead_id')
            ->count('lead_id');

        /*
        |--------------------------------------------------------------------------
        | Follow-up metrics
        |--------------------------------------------------------------------------
        */

        $totalFollowUps = (clone $followUpQuery)->count();

        $pendingFollowUps = (clone $followUpQuery)
            ->where('status', 'pending')
            ->count();

        $followUpsToday = (clone $followUpQuery)
            ->whereBetween('scheduled_at', [
                $todayStart,
                $todayEnd,
            ])
            ->count();

        $overdueFollowUps = (clone $followUpQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Selected employee details
        |--------------------------------------------------------------------------
        */

        $selectedEmployee = null;

        if ($selectedEmployeeId) {
            $employee = User::query()
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

            'scope' => [
                'type' => $selectedEmployeeId
                    ? 'employee'
                    : 'company',

                'can_view_all' => $canViewAll,

                'employee' => $selectedEmployee,
            ],

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
}
