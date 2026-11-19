<?php

namespace App\Http\Controllers;

use App\Models\CallHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DialedCallHistoryController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $companyId = (int) $currentUser->company_id;

        $isSuperAdmin = $currentUser->hasRole('super_admin');

        $isCompanyAdmin = $currentUser->hasAnyRole([
            'owner',
            'admin',
        ]);

        // Filters validate करें
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'direction' => [
                'nullable',
                Rule::in(['incoming', 'outgoing']),
            ],
            'call_type' => [
                'nullable',
                Rule::in([
                    'incoming',
                    'outgoing',
                    'missed',
                    'rejected',
                ]),
            ],
            'call_status' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|min:1',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'per_page' => 'nullable|integer|in:20,50,100',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        |
        | सिर्फ Android dialer से sync की गई history.
        | जिन records में mobile और direction है।
        |
        */

        $baseQuery = CallHistory::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->whereIn('direction', [
                'incoming',
                'outgoing',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Company और employee visibility
        |--------------------------------------------------------------------------
        */

        if (!$isSuperAdmin) {

            $baseQuery->whereHas(
                'user',
                function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                }
            );

            // Employee केवल अपनी calls देखेगा
            if (!$isCompanyAdmin) {
                $baseQuery->where(
                    'user_id',
                    $currentUser->id
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Employee dropdown
        |--------------------------------------------------------------------------
        */

        $employeeIds = (clone $baseQuery)
            ->distinct()
            ->pluck('user_id');

        $employees = User::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
        |--------------------------------------------------------------------------
        | Filtered Query
        |--------------------------------------------------------------------------
        */

        $query = clone $baseQuery;

        // Search by mobile / lead name
        if (!empty($filters['search'])) {

            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->where('mobile', 'like', "%{$search}%")

                  ->orWhereHas(
                      'lead',
                      function ($leadQuery) use ($search) {

                          $leadQuery->where(
                              'name',
                              'like',
                              "%{$search}%"
                          );
                      }
                  );
            });
        }

        if (!empty($filters['direction'])) {
            $query->where(
                'direction',
                $filters['direction']
            );
        }

        if (!empty($filters['call_type'])) {
            $query->where(
                'call_type',
                $filters['call_type']
            );
        }

        if (!empty($filters['call_status'])) {
            $query->where(
                'call_status',
                $filters['call_status']
            );
        }

        if (!empty($filters['user_id'])) {
            $query->where(
                'user_id',
                $filters['user_id']
            );
        }

        if (!empty($filters['from_date'])) {
            $query->where(
                'call_at',
                '>=',
                $filters['from_date'] . ' 00:00:00'
            );
        }

        if (!empty($filters['to_date'])) {
            $query->where(
                'call_at',
                '<=',
                $filters['to_date'] . ' 23:59:59'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Counts
        |--------------------------------------------------------------------------
        */

        $totalCalls = (clone $query)->count();

        $incomingCalls = (clone $query)
            ->where('direction', 'incoming')
            ->count();

        $outgoingCalls = (clone $query)
            ->where('direction', 'outgoing')
            ->count();

        $missedCalls = (clone $query)
            ->where('call_type', 'missed')
            ->count();

        $withoutLead = (clone $query)
            ->whereNull('lead_id')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | History
        |--------------------------------------------------------------------------
        */

        $histories = $query
            ->with([
                'lead' => function ($q) {
                    $q->withTrashed()
                        ->select('id', 'name', 'mobile');
                },
                'user:id,name',
            ])
            ->orderByDesc('call_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return view('calling-crm.dialed-call-history', [
            'histories' => $histories,
            'employees' => $employees,
            'filters' => $filters,
            'totalCalls' => $totalCalls,
            'incomingCalls' => $incomingCalls,
            'outgoingCalls' => $outgoingCalls,
            'missedCalls' => $missedCalls,
            'withoutLead' => $withoutLead,
            'canFilterEmployees' =>
                $isSuperAdmin || $isCompanyAdmin,
        ]);
    }
}
