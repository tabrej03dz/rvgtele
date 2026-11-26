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
    private const REPORT_TIMEZONE = 'Asia/Kolkata';
    private const DEFAULT_PER_PAGE = 100;
    private const MAX_PER_PAGE = 500;

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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'without_lead' => ['nullable', 'boolean'],
        ]);

        $search = trim($filters['search'] ?? '');
        $callStatus = trim($filters['call_status'] ?? '');
        $direction = $filters['direction'] ?? null;
        $callType = $filters['call_type'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $onlyWithoutLead = (bool) ($filters['without_lead'] ?? false);
        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE);

        $today = CarbonImmutable::now(self::REPORT_TIMEZONE)->startOfDay();
        $storageTimezone = config('app.timezone', 'UTC');

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

        // Search, employee and call status are shared by both overviews and the table.
        $scope = clone $baseQuery;

        if ($search !== '') {
            $scope->where(function (Builder $query) use ($search) {
                $query->where('mobile', 'like', "%{$search}%")
                    ->orWhereHas('lead', function (Builder $leadQuery) use ($search) {
                        $leadQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($canFilterEmployees && !empty($filters['user_id'])) {
            $scope->where('user_id', $filters['user_id']);
        }

        if ($callStatus !== '') {
            $scope->where('call_status', $callStatus);
        }

        $todayQuery = clone $scope;
        $todayQuery
            ->where('call_at', '>=', $today->setTimezone($storageTimezone)->toDateTimeString())
            ->where('call_at', '<', $today->addDay()->setTimezone($storageTimezone)->toDateTimeString());

        $todayCounts = $this->countCalls($todayQuery);
        $totalCounts = $this->countCalls(clone $scope);

        $query = clone $scope;

        if ($direction !== null && $direction !== '') {
            $query->where('direction', $direction);
        }

        if ($callType !== null && $callType !== '') {
            $query->where('call_type', $callType);
        }

        if ($onlyWithoutLead) {
            $query->whereNull('lead_id');
        }

        if ($fromDate !== null && $fromDate !== '') {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $fromDate, self::REPORT_TIMEZONE)
                ->setTimezone($storageTimezone)
                ->toDateTimeString();

            $query->where('call_at', '>=', $start);
        }

        if ($toDate !== null && $toDate !== '') {
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $toDate, self::REPORT_TIMEZONE)
                ->addDay()
                ->setTimezone($storageTimezone)
                ->toDateTimeString();

            $query->where('call_at', '<', $end);
        }

        $filterUrlParams = array_filter(
            $filters,
            static fn ($value) => $value !== null && $value !== ''
        );

        if (!$canFilterEmployees) {
            unset($filterUrlParams['user_id']);
        }

        $filterUrlParams['per_page'] = $perPage;

        $histories = $query
            ->with([
                'lead' => function ($leadQuery) {
                    $leadQuery->withTrashed()->select('id', 'name', 'mobile');
                },
                'user:id,name',
            ])
            ->orderByDesc('call_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($filterUrlParams);

        $cardFilters = Arr::except($filterUrlParams, [
            'direction',
            'call_type',
            'without_lead',
            'from_date',
            'to_date',
        ]);

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
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'perPage' => $perPage,
            'maxPerPage' => self::MAX_PER_PAGE,
            'onlyWithoutLead' => $onlyWithoutLead,
            'canFilterEmployees' => $canFilterEmployees,
            'today' => $today,
            'reportTimezone' => self::REPORT_TIMEZONE,
            'todayCounts' => $todayCounts,
            'totalCounts' => $totalCounts,
            'cardFilters' => $cardFilters,
            'refreshParams' => $refreshParams,
            'clearWithoutLeadParams' => Arr::except($filterUrlParams, ['without_lead']),
        ]);
    }

    private function countCalls(Builder $query): array
    {
        $counts = $query->toBase()
            ->selectRaw(
                'COUNT(*) AS total_calls,
                 COUNT(CASE WHEN direction = ? THEN 1 END) AS incoming_calls,
                 COUNT(CASE WHEN direction = ? THEN 1 END) AS outgoing_calls,
                 COUNT(CASE WHEN call_type = ? THEN 1 END) AS missed_calls,
                 COUNT(CASE WHEN lead_id IS NULL THEN 1 END) AS without_lead',
                ['incoming', 'outgoing', 'missed']
            )
            ->first();

        return [
            'total' => (int) ($counts->total_calls ?? 0),
            'incoming' => (int) ($counts->incoming_calls ?? 0),
            'outgoing' => (int) ($counts->outgoing_calls ?? 0),
            'missed' => (int) ($counts->missed_calls ?? 0),
            'without_lead' => (int) ($counts->without_lead ?? 0),
        ];
    }
}