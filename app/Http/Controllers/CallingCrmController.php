<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class CallingCrmController extends Controller
{
    private const NOT_ANSWERED_STATUSES = ['not_answered', 'not_answer'];

    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $filters = $request->validate([
            'search'        => 'nullable|string|max:255',
            'category'      => 'nullable|integer|min:1',
            'status'        => 'nullable|string|max:255',
            'user_id'       => 'nullable|integer|min:1',
            'date_range'    => 'nullable|in:all,today,yesterday,last_7_days,last_30_days',
            'status_source' => 'nullable|in:lead,history',
        ]);

        $search = trim($filters['search'] ?? '');
        $categoryId = $filters['category'] ?? null;
        $selectedStatus = $this->normalizeStatus($filters['status'] ?? null);
        $dateRange = $filters['date_range'] ?? 'all';
        $filterByHistory = ($filters['status_source'] ?? 'lead') === 'history';

        $selectedUserId = $isSuperAdmin
            ? ($filters['user_id'] ?? null)
            : $user->id;

        $reportTimezone = 'Asia/Kolkata';
        $storageTimezone = config('app.timezone', 'UTC');

        $today = CarbonImmutable::now($reportTimezone)->startOfDay();
        $tomorrow = $today->addDay();

        $baseQuery = Lead::query()
            ->whereNotNull('call_status')
            ->where('call_status', '!=', 'pending');

        if (!$isSuperAdmin) {
            $baseQuery->where('assigned_to', $user->id);
        }

        $users = collect();

        if ($isSuperAdmin) {
            $assignedUserIds = (clone $baseQuery)
                ->whereNotNull('assigned_to')
                ->select('assigned_to');

            $users = User::query()
                ->whereIn('id', $assignedUserIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $categoryQuery = Lead::query()->whereHas('leadCategory');
        $leadScope = clone $baseQuery;

        if ($selectedUserId !== null) {
            $categoryQuery->where('assigned_to', $selectedUserId);

            if ($isSuperAdmin) {
                $leadScope->where('assigned_to', $selectedUserId);
            }
        }

        $categories = Category::query()
            ->whereIn('id', $categoryQuery->select('category_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($categoryId !== null) {
            $leadScope->where('category_id', $categoryId);
        }

        $lead = $leadScope->getModel();
        $historyRelation = Relation::noConstraints(fn () => $lead->callHistories());

        $eligibleLeadIds = (clone $leadScope)
            ->select($lead->qualifyColumn($historyRelation->getLocalKeyName()));

        $historyQuery = $historyRelation->getQuery()
            ->whereIn($historyRelation->getQualifiedForeignKeyName(), $eligibleLeadIds);

        $todayHistoryQuery = clone $historyQuery;

        $this->withinCallDates(
            $todayHistoryQuery,
            $today->setTimezone($storageTimezone),
            $tomorrow->setTimezone($storageTimezone)
        );

        $todayCounts = $this->countCallsByStatus($todayHistoryQuery);
        $overallCounts = $this->countCallsByStatus($historyQuery);

        $statusOptions = $filterByHistory
            ? $overallCounts->keys()
            : (clone $leadScope)->distinct()->pluck('call_status');

        $statuses = $statusOptions
            ->map(fn ($status) => $this->normalizeStatus((string) $status))
            ->filter(fn ($status) => $status !== null && $status !== '')
            ->unique()
            ->sort()
            ->values();

        $contactsQuery = clone $leadScope;

        if ($search !== '') {
            $contactsQuery->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if (!$filterByHistory) {
            $this->applyStatus($contactsQuery, $selectedStatus);
        }

        $callDates = match ($dateRange) {
            'today'        => [$today, $tomorrow],
            'yesterday'    => [$today->subDay(), $today],
            'last_7_days'  => [$today->subDays(6), $tomorrow],
            'last_30_days' => [$today->subDays(29), $tomorrow],
            default        => null,
        };

        if ($filterByHistory || $callDates !== null) {
            $contactsQuery->whereHas('callHistories', function (Builder $query) use (
                $filterByHistory,
                $selectedStatus,
                $callDates,
                $storageTimezone
            ) {
                if ($filterByHistory) {
                    $this->applyStatus($query, $selectedStatus);
                }

                if ($callDates !== null) {
                    [$start, $end] = $callDates;

                    $this->withinCallDates(
                        $query,
                        $start->setTimezone($storageTimezone),
                        $end->setTimezone($storageTimezone)
                    );
                }
            });
        }

        $contacts = $contactsQuery
            ->with([
                'leadCategory',
                'assignedUser:id,name',
                'callHistories.user:id,name',
            ])
            ->withCount('callHistories')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Keep the overview scope when switching cards.
        $cardFilters = [];

        if ($categoryId !== null) {
            $cardFilters['category'] = $categoryId;
        }

        if ($isSuperAdmin && $selectedUserId !== null) {
            $cardFilters['user_id'] = $selectedUserId;
        }

        return view('calling-crm.index', [
            'title'           => 'Calling CRM',
            'contacts'        => $contacts,
            'categories'      => $categories,
            'statuses'        => $statuses,
            'users'           => $users,
            'isSuperAdmin'    => $isSuperAdmin,
            'selectedUserId'  => $selectedUserId,
            'selectedStatus'  => $selectedStatus,
            'filterByHistory' => $filterByHistory,
            'dateRange'       => $dateRange,
            'search'          => $search,
            'cardFilters'     => $cardFilters,
            'today'           => $today,
            'reportTimezone'  => $reportTimezone,
            'storageTimezone' => $storageTimezone,
            'todayOverview'   => $this->overview($todayCounts),
            'overallOverview' => $this->overview($overallCounts),
        ]);
    }

    private function normalizeStatus(?string $status): ?string
    {
        return $status === 'not_answer' ? 'not_answered' : $status;
    }

    private function applyStatus(Builder $query, ?string $status): void
    {
        if ($status === null || $status === '') {
            return;
        }

        $column = $query->getModel()->qualifyColumn('call_status');

        if ($status === 'not_answered') {
            $query->whereIn($column, self::NOT_ANSWERED_STATUSES);

            return;
        }

        $query->where($column, $status);
    }

    private function withinCallDates(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): void {
        $callAt = $query->getModel()->qualifyColumn('call_at');
        $createdAt = $query->getModel()->qualifyColumn('created_at');

        $query->where(function (Builder $dates) use ($callAt, $createdAt, $start, $end) {
            $dates->where(function (Builder $recorded) use ($callAt, $start, $end) {
                $recorded->where($callAt, '>=', $start)
                    ->where($callAt, '<', $end);
            })->orWhere(function (Builder $fallback) use ($callAt, $createdAt, $start, $end) {
                $fallback->whereNull($callAt)
                    ->where($createdAt, '>=', $start)
                    ->where($createdAt, '<', $end);
            });
        });
    }

    private function countCallsByStatus(Builder $query): \Illuminate\Support\Collection
    {
        $statusColumn = $query->getModel()->qualifyColumn('call_status');

        return $query->reorder()
            ->select($statusColumn)
            ->selectRaw('COUNT(*) AS call_count')
            ->groupBy($statusColumn)
            ->pluck('call_count', 'call_status');
    }

    private function overview(\Illuminate\Support\Collection $counts): array
    {
        return [
            'demo_sent'      => (int) $counts->get('demo_sent', 0),
            'total'          => (int) $counts->sum(),
            'not_answered'   => (int) $counts->get('not_answered', 0)
                + (int) $counts->get('not_answer', 0),
            'not_interested' => (int) $counts->get('not_interested', 0),
        ];
    }
}