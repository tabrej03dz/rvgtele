<?php

namespace App\Http\Controllers;

use App\Models\CallHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DialedCallHistoryController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $companyId = (int) $currentUser->company_id;
        $isSuperAdmin = $currentUser->hasRole('super_admin');
        $isCompanyAdmin = $currentUser->hasAnyRole(['owner', 'admin']);
        $canFilterEmployees = $isSuperAdmin || $isCompanyAdmin;

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', Rule::in(['incoming', 'outgoing'])],
            'call_type' => ['nullable', Rule::in(['incoming', 'outgoing', 'missed', 'rejected'])],
            'call_status' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('from_date'), ['after_or_equal:from_date']),
            ],
            'per_page' => ['nullable', 'integer', Rule::in([20, 50, 100])],
            'without_lead' => ['nullable', 'boolean'],
        ]);

        $search = trim($filters['search'] ?? '');
        $callStatus = trim($filters['call_status'] ?? '');
        $direction = $filters['direction'] ?? null;
        $callType = $filters['call_type'] ?? null;
        $onlyWithoutLead = (bool) ($filters['without_lead'] ?? false);

        // Android dialer records with a mobile number and a recognized direction.
        $baseQuery = CallHistory::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->whereIn('direction', ['incoming', 'outgoing']);

        if (!$isSuperAdmin) {
            $baseQuery->whereHas('user', function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId);
            });

            if (!$isCompanyAdmin) {
                $baseQuery->where('user_id', $currentUser->id);
            }
        }

        $employees = collect();

        if ($canFilterEmployees) {
            $employeeIds = (clone $baseQuery)
                ->whereNotNull('user_id')
                ->select('user_id');

            $employees = User::query()
                ->whereIn('id', $employeeIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // These filters apply both to the overview and to the records table.
        $overviewQuery = clone $baseQuery;

        if ($search !== '') {
            $overviewQuery->where(function (Builder $query) use ($search) {
                $query->where('mobile', 'like', "%{$search}%")
                    ->orWhereHas('lead', function (Builder $leadQuery) use ($search) {
                        $leadQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($canFilterEmployees && !empty($filters['user_id'])) {
            $overviewQuery->where('user_id', $filters['user_id']);
        }

        if ($callStatus !== '') {
            $overviewQuery->where('call_status', $callStatus);
        }

        if (!empty($filters['from_date'])) {
            $overviewQuery->where('call_at', '>=', $filters['from_date'] . ' 00:00:00');
        }

        if (!empty($filters['to_date'])) {
            $endExclusive = CarbonImmutable::createFromFormat('!Y-m-d', $filters['to_date'])
                ->addDay()
                ->toDateTimeString();

            $overviewQuery->where('call_at', '<', $endExclusive);
        }

        // Compute all card counts in one query.
        $counts = (clone $overviewQuery)
            ->toBase()
            ->selectRaw(
                'COUNT(*) AS total_calls,
                 COUNT(CASE WHEN direction = ? THEN 1 END) AS incoming_calls,
                 COUNT(CASE WHEN direction = ? THEN 1 END) AS outgoing_calls,
                 COUNT(CASE WHEN call_type = ? THEN 1 END) AS missed_calls,
                 COUNT(CASE WHEN lead_id IS NULL THEN 1 END) AS without_lead',
                ['incoming', 'outgoing', 'missed']
            )
            ->first();

        $query = clone $overviewQuery;

        if ($direction !== null && $direction !== '') {
            $query->where('direction', $direction);
        }

        if ($callType !== null && $callType !== '') {
            $query->where('call_type', $callType);
        }

        if ($onlyWithoutLead) {
            $query->whereNull('lead_id');
        }

        $histories = $query
            ->with([
                'lead' => function ($query) {
                    $query->withTrashed()->select('id', 'name', 'mobile');
                },
                'user:id,name',
            ])
            ->orderByDesc('call_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        $filterUrlParams = array_filter(
            $filters,
            static fn ($value) => $value !== null && $value !== ''
        );

        if (!$canFilterEmployees) {
            unset($filterUrlParams['user_id']);
        }

        // Each card replaces the direction, call type, and lead-link filters.
        $cardFilters = Arr::except($filterUrlParams, ['direction', 'call_type', 'without_lead']);

        $refreshParams = $filterUrlParams;

        if ($histories->currentPage() > 1) {
            $refreshParams['page'] = $histories->currentPage();
        }

        return view('calling-crm.dialed-call-history', [
            'histories' => $histories,
            'employees' => $employees,
            'filters' => $filters,
            'search' => $search,
            'callStatus' => $callStatus,
            'direction' => $direction,
            'callType' => $callType,
            'onlyWithoutLead' => $onlyWithoutLead,
            'totalCalls' => (int) ($counts->total_calls ?? 0),
            'incomingCalls' => (int) ($counts->incoming_calls ?? 0),
            'outgoingCalls' => (int) ($counts->outgoing_calls ?? 0),
            'missedCalls' => (int) ($counts->missed_calls ?? 0),
            'withoutLead' => (int) ($counts->without_lead ?? 0),
            'canFilterEmployees' => $canFilterEmployees,
            'cardFilters' => $cardFilters,
            'filterUrlParams' => $filterUrlParams,
            'refreshParams' => $refreshParams,
        ]);
    }
}