<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query()
            ->with('causer')
            ->where('log_name', 'user-activity');

        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        /*
        |--------------------------------------------------------------------------
        | HTTP Method
        |--------------------------------------------------------------------------
        */

        if ($request->filled('method')) {
            $query->where(
                'properties->method',
                strtoupper($request->method)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Route
        |--------------------------------------------------------------------------
        */

        if ($request->filled('route')) {
            $query->where(
                'properties->route',
                'like',
                '%'.$request->route.'%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere(
                        'properties->user_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'properties->user_email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'properties->url',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'properties->route',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'properties->ip_address',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        $activities = $query
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Users dropdown
        |--------------------------------------------------------------------------
        */

        $users = \App\Models\User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $todayCount = Activity::where(
                'log_name',
                'user-activity'
            )
            ->whereDate('created_at', today())
            ->count();

        $totalCount = Activity::where(
            'log_name',
            'user-activity'
        )->count();

        $todayUsers = Activity::where(
                'log_name',
                'user-activity'
            )
            ->whereDate('created_at', today())
            ->whereNotNull('causer_id')
            ->distinct('causer_id')
            ->count('causer_id');

        return view(
            'activitylogs.index',
            compact(
                'activities',
                'users',
                'todayCount',
                'totalCount',
                'todayUsers'
            )
        );
    }

    public function show(Activity $activity)
    {
        $activity->load('causer');

        return view(
            'activitylogs.show',
            compact('activity')
        );
    }
}
