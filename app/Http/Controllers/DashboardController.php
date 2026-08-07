<?php

namespace App\Http\Controllers;

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
        | Full Access Roles
        |--------------------------------------------------------------------------
        |
        | Super Admin aur Admin company ki saari leads/data dekh sakte hain.
        |
        | Baaki roles:
        | - telecaller
        | - employee
        | - sales executive
        | - etc.
        |
        | Sirf apni assigned leads dekhenge.
        |
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
            ->where('company_id', $companyId);

        if (!$hasFullAccess) {
            $leadQuery->where(
                'assigned_to',
                $userId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lead IDs
        |--------------------------------------------------------------------------
        |
        | Calls, follow-ups, orders etc. ko employee ke assigned leads ke
        | according filter karne ke liye.
        |
        */

        $visibleLeadIdsQuery = Lead::query()
            ->select('id')
            ->where('company_id', $companyId);

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

        $totalLeads = (clone $leadQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | New Leads Today
        |--------------------------------------------------------------------------
        */

        $newToday = (clone $leadQuery)
            ->whereDate(
                'created_at',
                today()
            )
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
        | Calls Today
        |--------------------------------------------------------------------------
        */

        $callsTodayQuery = CallLog::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereDate(
                'created_at',
                today()
            );

        if (!$hasFullAccess) {
            $callsTodayQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        $callsToday = $callsTodayQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Connected Calls Today
        |--------------------------------------------------------------------------
        */

        $connectedTodayQuery = CallLog::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereDate(
                'created_at',
                today()
            )
            ->whereHas(
                'disposition',
                function (Builder $query) {
                    $query->where(
                        'type',
                        'connected'
                    );
                }
            );

        if (!$hasFullAccess) {
            $connectedTodayQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        $connectedToday =
            $connectedTodayQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Follow-ups Due Today
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
            )
            ->whereDate(
                'scheduled_at',
                today()
            );

        if (!$hasFullAccess) {
            $followUpsDueQuery->whereIn(
                'lead_id',
                clone $visibleLeadIdsQuery
            );
        }

        $followUpsDue =
            $followUpsDueQuery->count();

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

        $overdue = $overdueQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        |
        | Admin / Super Admin:
        | Company ki total sales.
        |
        | Employee:
        | Sirf uski assigned leads se related orders.
        |
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

        $sales = (float) $salesQuery
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | Payments Received
        |--------------------------------------------------------------------------
        |
        | Payment -> Order -> Lead relation ke through filter.
        |
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

        $received = (float) $receivedQuery
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Active Users
        |--------------------------------------------------------------------------
        |
        | Sirf Admin/Super Admin ke liye company employee count useful hai.
        | Employee ke dashboard par 1 show karna meaningful nahi hai.
        |
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
        | User Dashboard Type
        |--------------------------------------------------------------------------
        |
        | Blade me bhi role-wise cards control kar sakte hain.
        |
        */

        $dashboardMode = $hasFullAccess
            ? 'admin'
            : 'employee';

        return view('dashboard', [
            'totalLeads' => $totalLeads,
            'newToday' => $newToday,
            'hotLeads' => $hotLeads,

            'callsToday' => $callsToday,
            'connectedToday' => $connectedToday,

            'followUpsDue' => $followUpsDue,
            'overdue' => $overdue,

            'sales' => $sales,
            'received' => $received,

            'activeUsers' => $activeUsers,

            'recentLeads' => $recentLeads,

            'dashboardMode' => $dashboardMode,
            'hasFullAccess' => $hasFullAccess,
        ]);
    }
}