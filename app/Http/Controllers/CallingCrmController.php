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
            'search'     => 'nullable|string|max:255',
            'category'   => 'nullable|integer|min:1',
            'status'     => 'nullable|string|max:255',
            'user_id'    => 'nullable|integer|min:1',
            'date_range' => 'nullable|in:all,today,yesterday,last_7_days,last_30_days',
        ]);

        $search = trim($filters['search'] ?? '');
        $categoryId = $filters['category'] ?? null;
        $selectedStatus = $filters['status'] ?? null;
        $dateRange = $filters['date_range'] ?? 'all';

        if (in_array($selectedStatus, self::NOT_ANSWERED_STATUSES, true)) {
            $selectedStatus = 'not_answered';
        }

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

        $statuses = (clone $leadScope)
            ->distinct()
            ->orderBy('call_status')
            ->pluck('call_status')
            ->map(fn ($status) => $status === 'not_answer' ? 'not_answered' : $status)
            ->unique()
            ->values();

        // Use the existing relation to get the history model and its lead foreign key.
        $lead = $leadScope->getModel();
        $historyRelation = Relation::noConstraints(fn () => $lead->callHistories());

        $eligibleLeadIds = (clone $leadScope)
            ->select($lead->qualifyColumn($historyRelation->getLocalKeyName()));

        $historyQuery = $historyRelation->getQuery()
            ->whereIn($historyRelation->getQualifiedForeignKeyName(), $eligibleLeadIds);

        $todayHistoryQuery = $this->withinCallDates(
            clone $historyQuery,
            $today->setTimezone($storageTimezone),
            $tomorrow->setTimezone($storageTimezone)
        );

        $todayOverview = $this->overview($todayHistoryQuery);
        $overallOverview = $this->overview($historyQuery);

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

        if ($selectedStatus === 'not_answered') {
            $contactsQuery->whereIn('call_status', self::NOT_ANSWERED_STATUSES);
        } elseif ($selectedStatus !== null && $selectedStatus !== '') {
            $contactsQuery->where('call_status', $selectedStatus);
        }

        if ($dateRange !== 'all') {
            [$start, $end] = match ($dateRange) {
                'yesterday'    => [$today->subDay(), $today],
                'last_7_days'  => [$today->subDays(6), $tomorrow],
                'last_30_days' => [$today->subDays(29), $tomorrow],
                default        => [$today, $tomorrow],
            };

            $contactsQuery->whereHas(
                'callHistories',
                fn (Builder $query) => $this->withinCallDates(
                    $query,
                    $start->setTimezone($storageTimezone),
                    $end->setTimezone($storageTimezone)
                )
            );
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

        return view('calling-crm.index', [
            'title'           => 'Calling CRM',
            'contacts'        => $contacts,
            'categories'      => $categories,
            'statuses'        => $statuses,
            'users'           => $users,
            'isSuperAdmin'    => $isSuperAdmin,
            'selectedUserId'  => $selectedUserId,
            'selectedStatus'  => $selectedStatus,
            'dateRange'       => $dateRange,
            'today'           => $today,
            'reportTimezone'  => $reportTimezone,
            'storageTimezone' => $storageTimezone,
            'todayOverview'   => $todayOverview,
            'overallOverview' => $overallOverview,
        ]);
    }

    private function withinCallDates(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): Builder {
        $callAt = $query->getModel()->qualifyColumn('call_at');
        $createdAt = $query->getModel()->qualifyColumn('created_at');

        return $query->where(function (Builder $dates) use ($callAt, $createdAt, $start, $end) {
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

    private function overview(Builder $query): array
    {
        $statusColumn = $query->getModel()->qualifyColumn('call_status');

        $counts = $query->reorder()
            ->select($statusColumn)
            ->selectRaw('COUNT(*) AS call_count')
            ->groupBy($statusColumn)
            ->pluck('call_count', 'call_status');

        return [
            'demo_sent'     => (int) $counts->get('demo_sent', 0),
            'total'         => (int) $counts->sum(),
            'not_answered'  => (int) $counts->get('not_answered', 0)
                + (int) $counts->get('not_answer', 0),
            'not_interested' => (int) $counts->get('not_interested', 0),
        ];
    }
}