<?php

namespace App\Http\Controllers;

use App\Models\{
    Lead,
    CallLog,
    User,
    Order
};

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        try {
            $from = $request->filled('from')
                ? Carbon::parse($request->from)->startOfDay()
                : now()->subDays(30)->startOfDay();

            $to = $request->filled('to')
                ? Carbon::parse($request->to)->endOfDay()
                : now()->endOfDay();
        } catch (\Exception $e) {
            $from = now()->subDays(30)->startOfDay();
            $to   = now()->endOfDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Employees / Assigned Leads
        |--------------------------------------------------------------------------
        */

        $users = User::query()
            ->where('company_id', $companyId)
            ->withCount([
                'assignedLeads' => function ($query) use ($from, $to) {
                    $query->whereBetween('created_at', [$from, $to]);
                }
            ])
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Leads By Status
        |--------------------------------------------------------------------------
        */

        $status = Lead::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('lead_status_id, COUNT(*) as total')
            ->groupBy('lead_status_id')
            ->with('status')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Calling Performance
        |--------------------------------------------------------------------------
        */

        $calls = CallLog::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                user_id,
                COUNT(*) as calls,
                COALESCE(SUM(duration_seconds), 0) as duration
            ')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('calls')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        */

        $sales = Order::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalLeads = Lead::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $totalCalls = (int) $calls->sum('calls');

        $totalDuration = (int) $calls->sum('duration');

        $totalEmployees = $users->count();

        return view('reports.index', compact(
            'users',
            'status',
            'calls',
            'sales',
            'from',
            'to',
            'totalLeads',
            'totalCalls',
            'totalDuration',
            'totalEmployees'
        ));
    }
}
