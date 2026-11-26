@extends('layouts.crm')

@section('title', $title)

@section('content')
@php
    $overviewCards = [
        ['demo_sent', 'Today Demo Sent', 'Total Demo Sent', 'send', 'green', 'demo_sent'],
        ['total', 'Today Total Calling', 'Total Calling', 'phone', 'blue', null],
        ['not_answered', 'Today Not Answered', 'Total Not Answered', 'phone-missed', 'amber', 'not_answered'],
        ['not_interested', 'Today Not Interested', 'Total Not Interested', 'ban', 'rose', 'not_interested'],
    ];

    $overviewSections = [
        [
            'title' => "Today's Calling Overview",
            'icon' => 'calendar-days',
            'badge' => $today->format('d M Y, l'),
            'badgeIcon' => 'calendar',
            'counts' => $todayOverview,
            'range' => 'today',
        ],
        [
            'title' => 'Overall Calling Overview',
            'icon' => 'bar-chart-3',
            'badge' => 'All Time (Complete Data)',
            'badgeIcon' => 'layers',
            'counts' => $overallOverview,
            'range' => 'all',
        ],
    ];

    $dateRanges = [
        'all' => 'All Time',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
    ];

    $statusLabels = [
        'answered' => 'Answered',
        'not_answer' => 'Not Answered',
        'not_answered' => 'Not Answered',
        'busy' => 'Busy',
        'demo_sent' => 'Demo Sent',
        'not_interested' => 'Not Interested',
        'converted' => 'Converted',
    ];

    $statusColors = [
        'answered' => 'green',
        'converted' => 'green',
        'not_answer' => 'amber',
        'not_answered' => 'amber',
        'busy' => 'amber',
        'demo_sent' => 'blue',
        'not_interested' => 'rose',
    ];

    $statusLabel = static function ($value) use ($statusLabels) {
        $value = (string) $value;

        return $statusLabels[$value] ?? (ucwords(str_replace('_', ' ', $value)) ?: 'Unknown');
    };

    $formatDuration = static function ($seconds) {
        if ($seconds === null || !is_numeric($seconds)) {
            return '—';
        }

        $seconds = max(0, (int) $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    };

    $reportDate = static fn ($value) => $value
        ? \Carbon\CarbonImmutable::parse($value, $storageTimezone)->setTimezone($reportTimezone)
        : null;

    $indexUrl = route('calling-crm.index');
    $createRoute = 'calling-crm.create';
    $createUrl = \Illuminate\Support\Facades\Route::has($createRoute)
        ? route($createRoute)
        : null;
@endphp

<style>
    .calling-report {
        --ink: #101936;
        --muted: #53617c;
        --blue: #1736bd;
        --border: #e0e6f3;
        display: grid;
        gap: 12px;
        max-width: 1560px;
        margin: 0 auto;
        color: var(--ink);
        font-size: 13px;
        line-height: 1.4;
    }

    .calling-report h1, .calling-report h2,
    .calling-report h3, .calling-report p { margin: 0; }
    .calling-report h1 { font-size: 23px; font-weight: 800; line-height: 1.15; }
    .calling-report h2 { font-size: 16px; font-weight: 800; }
    .calling-report .cr-icon { display: inline-block; width: 16px; height: 16px; flex: none; }
    .calling-report .cr-heading-icon { width: 21px; height: 21px; color: #4325d5; }
    .calling-report .cr-hero-icon { width: 33px; height: 33px; color: #4924dc; }
    .calling-report .cr-hero, .calling-report .cr-strip, .calling-report .cr-list-header {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
    }

    .calling-report .cr-hero { min-height: 50px; padding: 0 2px; }
    .calling-report .cr-title { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .calling-report .cr-subtitle { color: var(--muted); font-size: 12px; }

    .calling-report .cr-panel {
        min-width: 0; padding: 10px 12px; border: 1px solid var(--border);
        border-radius: 13px; background: #fff; box-shadow: 0 2px 6px #253a7010;
    }

    .calling-report .cr-strip {
        flex-wrap: wrap; min-height: 32px; margin-bottom: 9px; padding: 3px 8px;
        border-radius: 8px; background: linear-gradient(90deg, #f1f3ff, #e8ecfb);
    }

    .calling-report .cr-chip, .calling-report .cr-button, .calling-report .cr-badge {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; white-space: nowrap; font-weight: 700;
    }

    .calling-report .cr-chip {
        padding: 3px 9px; border: 1px solid #dee5fc; border-radius: 999px;
        background: #f8faff; color: #213260; font-size: 11px;
    }

    .calling-report .cr-chip .cr-icon, .calling-report .cr-badge .cr-icon {
        width: 13px; height: 13px;
    }

    .calling-report .cr-cards {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px;
    }

    .calling-report .cr-green {
        --tone: #125735; --light: #f6fdf9; --soft: #e6f8ee; --edge: #d4eddf;
        --accent: #0d9a53; --wave: #d8f3e1;
    }

    .calling-report .cr-blue {
        --tone: #1739ae; --light: #f6faff; --soft: #eaf2ff; --edge: #d7e5fc;
        --accent: #2563eb; --wave: #d6e7ff;
    }

    .calling-report .cr-amber {
        --tone: #974013; --light: #fffcf6; --soft: #fff2dc; --edge: #f5dfbb;
        --accent: #d56b0c; --wave: #ffebc5;
    }

    .calling-report .cr-rose {
        --tone: #a51c3a; --light: #fff8fb; --soft: #fce9ef; --edge: #f5d5e0;
        --accent: #df345b; --wave: #f8d6e2;
    }

    .calling-report .cr-card {
        position: relative; isolation: isolate; overflow: hidden;
        display: flex; align-items: center; gap: 10px;
        min-height: 62px; padding: 6px 11px; border: 1px solid var(--edge);
        border-radius: 10px; background: linear-gradient(var(--light), var(--soft));
        color: var(--tone); text-decoration: none; cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .calling-report .cr-card:hover { border-color: var(--accent); }
    .calling-report .cr-card[aria-current="true"] {
        border-color: var(--accent); box-shadow: inset 0 0 0 1px var(--accent);
    }

    .calling-report .cr-card::after {
        position: absolute; z-index: -1; right: -14px; bottom: -35px;
        width: 102px; height: 60px; border-radius: 65% 44% 0 0;
        transform: rotate(-12deg); background: var(--wave); content: "";
    }

    .calling-report .cr-card-icon {
        display: grid; place-items: center; flex: 0 0 32px; height: 32px;
        border-radius: 50%; background: var(--accent); color: #fff;
    }

    .calling-report .cr-card-label { display: block; font-size: 12px; font-weight: 750; }
    .calling-report .cr-card-value {
        display: block; font-size: 25px; font-weight: 800; line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }

    .calling-report .cr-filters > .cr-title { margin-bottom: 5px; }
    .calling-report .cr-form {
        display: grid;
        align-items: end;
        gap: 8px;
        grid-template-columns: minmax(150px, 1.65fr) repeat(4, minmax(96px, 1fr))
            minmax(105px, .85fr) auto;
    }

    .calling-report .cr-field {
        display: grid; gap: 3px; min-width: 0; font-size: 11px; font-weight: 750;
    }

    .calling-report .cr-search { position: relative; min-width: 0; }
    .calling-report .cr-search .cr-icon {
        position: absolute; top: 9px; left: 9px; color: var(--muted); pointer-events: none;
    }

    .calling-report .cr-search input { padding-left: 31px; }
    .calling-report input, .calling-report select {
        box-sizing: border-box; width: 100%; min-width: 0; height: 34px;
        padding: 0 8px; border: 1px solid #d9e1f0; border-radius: 8px;
        background-color: #fff; color: var(--ink); font: inherit; font-size: 12px;
    }

    .calling-report select { padding-right: 24px; }
    .calling-report input[readonly] { background-color: #f5f7fc; }
    .calling-report .cr-actions { display: flex; gap: 6px; }
    .calling-report .cr-button {
        min-height: 34px; padding: 0 10px; border: 1px solid #c6d3f7;
        border-radius: 8px; background: #f3f7ff; color: var(--blue);
        font-size: 12px; text-decoration: none; cursor: pointer;
    }

    .calling-report .cr-button:hover { background: #e8efff; }
    .calling-report .cr-primary { background: #131c51; border-color: #131c51; color: #fff; }
    .calling-report .cr-primary:hover { background: #23347c; }
    .calling-report .cr-reset { background: #fff; color: var(--ink); }
    .calling-report .cr-error { margin-top: 7px; color: #a51c3a; }

    .calling-report .cr-list { padding: 0; overflow: hidden; scroll-margin-top: 76px; }
    .calling-report .cr-list-header { padding: 9px 12px; }
    .calling-report .cr-scroll { overflow-x: auto; }
    .calling-report table { width: 100%; border-collapse: collapse; text-align: left; }
    .calling-report .cr-leads { min-width: 930px; font-size: 12px; }
    .calling-report th {
        padding: 7px 8px; background: #eef2fe; color: #243356;
        font-size: 10px; font-weight: 800; text-transform: uppercase; white-space: nowrap;
    }

    .calling-report td {
        padding: 6px 8px; border-bottom: 1px solid #eef2f8; vertical-align: middle;
    }

    .calling-report .cr-row:hover { background: #f8faff; }
    .calling-report .cr-right { text-align: right; }
    .calling-report .cr-person { display: flex; align-items: center; gap: 7px; line-height: 1.2; }
    .calling-report .cr-avatar {
        display: grid; place-items: center; flex: 0 0 28px; height: 28px;
        border-radius: 8px; background: #e9f0ff; color: #1739c0; font-weight: 800;
    }

    .calling-report .cr-user-avatar { border-radius: 50%; background: #eeebff; color: #4933b7; }
    .calling-report .cr-name {
        display: block; max-width: clamp(112px, 12vw, 198px); overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap; font-weight: 750;
    }

    .calling-report .cr-user-name { max-width: 110px; }
    .calling-report .cr-detail { display: block; color: var(--muted); font-size: 10px; white-space: nowrap; }
    .calling-report .cr-mobile { color: var(--blue); font-weight: 750; white-space: nowrap; }
    .calling-report .cr-mobile:hover { text-decoration: underline; }
    .calling-report .cr-badge {
        padding: 2px 7px; border: 1px solid var(--edge, #e5eaf6);
        border-radius: 7px; background: var(--soft, #edf0f9);
        color: var(--tone, var(--ink)); font-size: 11px;
    }

    .calling-report .cr-status { border-radius: 999px; }
    .calling-report .cr-category {
        display: inline-block; max-width: 105px; overflow: hidden;
        text-overflow: ellipsis; vertical-align: middle;
    }

    .calling-report .cr-count { background: #eeebff; color: #4330ac; }
    .calling-report .cr-history-button { min-height: 27px; padding: 0 7px; font-size: 11px; }
    .calling-report .cr-history-button .cr-icon { width: 13px; height: 13px; }
    .calling-report [data-history-row][hidden] { display: none; }
    .calling-report .cr-history-wrap { background: #f7f9ff; }
    .calling-report .cr-history-wrap > td { padding: 10px; }
    .calling-report .cr-history-panel {
        overflow: hidden; border: 1px solid var(--border); border-radius: 9px; background: #fff;
    }

    .calling-report .cr-history-title { padding: 8px 10px; background: #f2f5ff; }
    .calling-report .cr-history-table { min-width: 790px; }
    .calling-report .cr-remarks { min-width: 150px; white-space: pre-wrap; overflow-wrap: anywhere; }
    .calling-report audio { width: 190px; height: 32px; }
    .calling-report .cr-recording-link { display: block; color: var(--blue); font-size: 11px; }
    .calling-report .cr-empty { padding: 22px 12px; text-align: center; }
    .calling-report .cr-pagination { padding: 8px 12px; }
    .calling-report :is(button, a, input, select, [tabindex]):focus-visible {
        outline: 2px solid #3d53dc; outline-offset: 2px;
    }

    @media (max-width: 1280px) {
        .calling-report .cr-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 820px) {
        .calling-report .cr-cards, .calling-report .cr-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .calling-report .cr-hero, .calling-report .cr-list-header { flex-wrap: wrap; }
        .calling-report .cr-actions { grid-column: 1 / -1; }
        .calling-report .cr-card { padding: 6px; gap: 6px; }
        .calling-report .cr-card-icon { flex-basis: 26px; height: 26px; }
        .calling-report .cr-card-label { font-size: 11px; }
        .calling-report .cr-card-value { font-size: 22px; }
    }

    .calling-report .cr-inactive-users {
    display: flex;
    flex: 1 1 0;
    min-width: 0;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 4px 8px;
}

.calling-report .cr-inactive-users[hidden] {
    display: none;
}

.calling-report .cr-inactive-label {
    color: #9a3412;
    font-size: 11px;
}

.calling-report .cr-inactive-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 4px;
    max-height: 50px;
    margin: 0;
    padding: 0;
    overflow: auto;
    list-style: none;
}

.calling-report .cr-inactive-name {
    max-width: 100%;
    padding: 2px 7px;
    border: 1px solid #fed7aa;
    border-radius: 999px;
    background: #fff7ed;
    color: #9a3412;
    font-size: 11px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

@media (max-width: 700px) {
    .calling-report .cr-list-header {
        flex-wrap: wrap;
    }

    .calling-report .cr-inactive-users {
        flex-basis: 100%;
        order: 3;
        justify-content: flex-start;
    }

    .calling-report .cr-inactive-list {
        justify-content: flex-start;
    }
}
</style>
<div class="calling-report" id="calling-report">
    <header class="cr-hero">
        <div class="cr-title">
            <i data-lucide="phone-call" class="cr-icon cr-hero-icon" aria-hidden="true"></i>
            <div>
                <h1>Calling CRM</h1>
                <p class="cr-subtitle">Manage leads, track calls, and review call history.</p>
            </div>
        </div>

        @if($createUrl)
            <a href="{{ $createUrl }}" class="cr-button">
                <i data-lucide="phone-plus" class="cr-icon" aria-hidden="true"></i>
                Add Calling Record
            </a>
        @endif
    </header>

    @foreach($overviewSections as $section)
        <section class="cr-panel">
            <header class="cr-strip">
                <div class="cr-title">
                    <i data-lucide="{{ $section['icon'] }}" class="cr-icon cr-heading-icon" aria-hidden="true"></i>
                    <h2>{{ $section['title'] }}</h2>
                </div>

                <span class="cr-chip">
                    <i data-lucide="{{ $section['badgeIcon'] }}" class="cr-icon" aria-hidden="true"></i>
                    {{ $section['badge'] }}
                </span>
            </header>

            <div class="cr-cards">
                @foreach($overviewCards as [$key, $todayLabel, $overallLabel, $icon, $tone, $cardStatus])
                    @php
                        $cardLabel = $section['range'] === 'today' ? $todayLabel : $overallLabel;
                        $count = number_format($section['counts'][$key]);

                        $cardParams = array_merge($cardFilters, [
                            'date_range' => $section['range'],
                            'status_source' => 'history',
                        ]);

                        if ($cardStatus !== null) {
                            $cardParams['status'] = $cardStatus;
                        }

                        $active = $filterByHistory
                            && $search === ''
                            && $dateRange === $section['range']
                            && ($selectedStatus ?: null) === $cardStatus;
                    @endphp

                    <a href="{{ route('calling-crm.index', $cardParams) }}#calling-leads"
                        class="cr-card cr-{{ $tone }}"
                        aria-label="{{ $cardLabel }}, {{ $count }} calls. Show matching leads."
                        @if($active) aria-current="true" @endif>
                        <span class="cr-card-icon">
                            <i data-lucide="{{ $icon }}" class="cr-icon" aria-hidden="true"></i>
                        </span>

                        <div>
                            <span class="cr-card-label">{{ $cardLabel }}</span>
                            <strong class="cr-card-value">{{ $count }}</strong>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="cr-panel cr-filters" aria-labelledby="calling-filters-heading">
        <div class="cr-title">
            <i data-lucide="filter" class="cr-icon cr-heading-icon" aria-hidden="true"></i>
            <h2 id="calling-filters-heading">Search &amp; Filters</h2>
        </div>

        <form method="GET" action="{{ $indexUrl }}" class="cr-form">
            @if($filterByHistory)
                <input type="hidden" name="status_source" value="history">
            @endif

            <div class="cr-search">
                <i data-lucide="search" class="cr-icon" aria-hidden="true"></i>
                <input type="search" name="search" maxlength="255" value="{{ $search }}"
                    aria-label="Search by name, mobile or lead ID"
                    placeholder="Search by name, mobile or lead ID…">
            </div>

            <label class="cr-field">
                Category
                <select name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected((string) request('category') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="cr-field">
                Assigned User
                @if($isSuperAdmin)
                    <select name="user_id">
                        <option value="">All Users</option>

                        @foreach($users as $assignedUser)
                            <option value="{{ $assignedUser->id }}"
                                @selected((string) $selectedUserId === (string) $assignedUser->id)>
                                {{ $assignedUser->name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" value="{{ auth()->user()->name }}" readonly>
                @endif
            </label>

            <label class="cr-field">
                {{ $filterByHistory ? 'Call History Status' : 'Call Status' }}
                <select name="status">
                    <option value="">All Statuses</option>

                    @if($selectedStatus && !$statuses->containsStrict($selectedStatus))
                        <option value="{{ $selectedStatus }}" selected>{{ $statusLabel($selectedStatus) }}</option>
                    @endif

                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>
                            {{ $statusLabel($status) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="cr-field">
                Date Range
                <select name="date_range">
                    @foreach($dateRanges as $range => $label)
                        <option value="{{ $range }}" @selected($dateRange === $range)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="cr-field">
                Records Per Page
                <input type="number" name="per_page" min="1" max="{{ $maxPerPage }}" step="1"
                    inputmode="numeric" value="{{ $perPage }}"
                    title="Enter a whole number between 1 and {{ $maxPerPage }}">
            </label>

            <div class="cr-actions">
                <button type="submit" class="cr-button cr-primary">
                    <i data-lucide="filter" class="cr-icon" aria-hidden="true"></i>
                    Apply Filters
                </button>

                <a href="{{ $indexUrl }}" class="cr-button cr-reset">
                    <i data-lucide="rotate-ccw" class="cr-icon" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>

        @if($errors->any())
            <div class="cr-error" role="alert">{{ $errors->first() }}</div>
        @endif
    </section>

    <section id="calling-leads" class="cr-panel cr-list" aria-labelledby="calling-leads-heading">
        <header class="cr-list-header">
            <div class="cr-title">
                <i data-lucide="users" class="cr-icon cr-heading-icon" aria-hidden="true"></i>
                <div>
                    <h2 id="calling-leads-heading">Calling Lead List</h2>
                    <p class="cr-subtitle">
                        {{ $contacts->firstItem() ?? 0 }} – {{ $contacts->lastItem() ?? 0 }}
                        of {{ number_format($contacts->total()) }} leads
                    </p>
                </div>
            </div>

            @if($isSuperAdmin)
    @php
        $activityParams = ['activity_only' => 1];

        if ($selectedUserId !== null) {
            $activityParams['user_id'] = $selectedUserId;
        }
    @endphp

    <div
        id="calling-inactive-users"
        class="cr-inactive-users"
        data-url="{{ route('calling-crm.index', $activityParams) }}"
        aria-label="Users without a recent logged call"
        @if(empty($inactiveCallers)) hidden @endif
    >
        <strong class="cr-inactive-label">No call logged in last 10 min</strong>

        <ul class="cr-inactive-list">
            @foreach($inactiveCallers as $name)
                <li class="cr-inactive-name">{{ $name }}</li>
            @endforeach
        </ul>
    </div>
@endif

            <span class="cr-chip">
                <i data-lucide="layers" class="cr-icon" aria-hidden="true"></i>
                @if($filterByHistory)
                    {{ $dateRanges[$dateRange] }} · {{ $selectedStatus ? $statusLabel($selectedStatus) : 'All Calls' }}
                @else
                    Non-Pending Leads
                @endif
            </span>
        </header>

        <div class="cr-scroll" role="region" tabindex="0" aria-label="Calling leads table">
            <table class="cr-leads">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Lead Details</th>
                        <th scope="col">Mobile</th>
                        <th scope="col">Category</th>
                        <th scope="col">Assigned User</th>
                        <th scope="col">{{ $filterByHistory ? 'Current Lead Status' : 'Call Status' }}</th>
                        <th scope="col">Updated At</th>
                        <th scope="col">Calls</th>
                        <th scope="col">Latest Call Duration</th>
                        <th scope="col" class="cr-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($contacts as $contact)
                        @php
                            $leadName = $contact->name ?: 'Unnamed Lead';
                            $assignedName = $contact->assignedUser?->name ?? 'Unknown User';
                            $categoryName = $contact->leadCategory?->name ?: '—';
                            $leadStatus = (string) $contact->call_status;
                            $historyRowId = 'calling-history-' . $contact->id;
                            $updatedAt = $reportDate($contact->updated_at);
                            $callCount = (int) $contact->call_histories_count;

                            $histories = $contact->callHistories->sort(
                                static function ($first, $second) use ($reportDate) {
                                    $firstDate = $reportDate($first->call_at ?? $first->created_at);
                                    $secondDate = $reportDate($second->call_at ?? $second->created_at);

                                    return (($secondDate?->getTimestamp() ?? 0) <=> ($firstDate?->getTimestamp() ?? 0))
                                        ?: ((int) $second->id <=> (int) $first->id);
                                }
                            )->values();

                            $latestDuration = $formatDuration($histories->first()?->call_duration);
                        @endphp

                        <tr class="cr-row">
                            <td>{{ $contacts->firstItem() + $loop->index }}</td>

                            <td>
                                <div class="cr-person">
                                    <span class="cr-avatar" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($leadName, 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong class="cr-name" title="{{ $leadName }}">{{ $leadName }}</strong>
                                        <span class="cr-detail">Lead ID: {{ $contact->id }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if($contact->mobile)
                                    <a class="cr-mobile" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->mobile) }}">
                                        {{ $contact->mobile }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <span class="cr-badge cr-category" title="{{ $categoryName }}">
                                    {{ $categoryName }}
                                </span>
                            </td>

                            <td>
                                <div class="cr-person">
                                    <span class="cr-avatar cr-user-avatar" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($assignedName, 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong class="cr-name cr-user-name" title="{{ $assignedName }}">
                                            {{ $assignedName }}
                                        </strong>
                                        <span class="cr-detail">User ID: {{ $contact->assigned_to ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="cr-badge cr-status cr-{{ $statusColors[$leadStatus] ?? 'neutral' }}">
                                    {{ $statusLabel($leadStatus) }}
                                </span>
                            </td>

                            <td>
                                @if($updatedAt)
                                    <time datetime="{{ $updatedAt->toIso8601String() }}">
                                        {{ $updatedAt->format('d M Y') }}
                                        <span class="cr-detail">{{ $updatedAt->format('h:i A') }}</span>
                                    </time>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <span class="cr-badge cr-count">
                                    {{ number_format($callCount) }} {{ $callCount === 1 ? 'Call' : 'Calls' }}
                                </span>
                            </td>

                            <td>
                                <span class="cr-badge cr-green" title="Latest call duration (HH:MM:SS)">
                                    <i data-lucide="clock-3" class="cr-icon" aria-hidden="true"></i>
                                    {{ $latestDuration }}
                                </span>
                            </td>

                            <td class="cr-right">
                                <button type="button" class="cr-button cr-history-button"
                                    data-history-toggle aria-controls="{{ $historyRowId }}" aria-expanded="false">
                                    <i data-lucide="history" class="cr-icon" aria-hidden="true"></i>
                                    <span data-history-label>View History</span>
                                </button>
                            </td>
                        </tr>

                        <tr id="{{ $historyRowId }}" class="cr-history-wrap" data-history-row hidden>
                            <td colspan="10">
                                <div class="cr-history-panel">
                                    <header class="cr-history-title">
                                        <h3><strong>Complete Call History</strong></h3>
                                        <p class="cr-subtitle">
                                            {{ $leadName }} · Lead ID: {{ $contact->id }} · {{ $contact->mobile ?: '—' }}
                                        </p>
                                    </header>

                                    <table class="cr-history-table" aria-label="Call history for {{ $leadName }}">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Called By</th>
                                                <th scope="col">Call Status</th>
                                                <th scope="col">Call Date &amp; Time</th>
                                                <th scope="col">Duration</th>
                                                <th scope="col">Remarks</th>
                                                <th scope="col" class="cr-right">Recording</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse($histories as $history)
                                                @php
                                                    $historyStatus = (string) $history->call_status;
                                                    $callDate = $reportDate($history->call_at ?? $history->created_at);
                                                    $recordingUrl = $history->recording
                                                        ? asset('storage/' . ltrim($history->recording, '/'))
                                                        : null;
                                                @endphp

                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>

                                                    <td>
                                                        <strong>{{ $history->user?->name ?? 'Unknown User' }}</strong>
                                                        <span class="cr-detail">User ID: {{ $history->user_id ?? '—' }}</span>
                                                    </td>

                                                    <td>
                                                        <span class="cr-badge cr-status cr-{{ $statusColors[$historyStatus] ?? 'neutral' }}">
                                                            {{ $statusLabel($historyStatus) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        @if($callDate)
                                                            <time datetime="{{ $callDate->toIso8601String() }}">
                                                                {{ $callDate->format('d M Y') }}
                                                                <span class="cr-detail">{{ $callDate->format('h:i A') }}</span>
                                                            </time>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <span class="cr-badge">{{ $formatDuration($history->call_duration) }}</span>
                                                    </td>

                                                    <td class="cr-remarks">{{ $history->remarks ?: '—' }}</td>

                                                    <td class="cr-right">
                                                        @if($recordingUrl)
                                                            <audio controls preload="none" src="{{ $recordingUrl }}" aria-label="Call recording">
                                                                Your browser does not support audio playback.
                                                            </audio>
                                                            <a href="{{ $recordingUrl }}" target="_blank"
                                                                rel="noopener noreferrer" class="cr-recording-link">
                                                                Open Recording
                                                            </a>
                                                        @else
                                                            <span class="cr-detail">No Recording</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="cr-empty">No call history found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="cr-empty">
                                <strong>No calling leads found</strong>
                                <p class="cr-subtitle">No leads match the selected filters.</p>
                                <a href="{{ $indexUrl }}" class="cr-button">Clear Filters</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
            <div class="cr-pagination">
                {{ $contacts->fragment('calling-leads')->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>

<script>
(function () {
    function initializeCallingReport() {
        const report = document.getElementById('calling-report');

        if (!report || report.dataset.initialized === '1') {
            return;
        }

        report.dataset.initialized = '1';

        function toggleHistory(button, open) {
            const row = document.getElementById(button.getAttribute('aria-controls'));

            if (!row) {
                return;
            }

            row.hidden = !open;
            button.setAttribute('aria-expanded', String(open));
            button.querySelector('[data-history-label]').textContent = open
                ? 'Hide History'
                : 'View History';

            if (!open) {
                row.querySelectorAll('audio').forEach(audio => audio.pause());
            }
        }

        report.addEventListener('click', function (event) {
            const button = event.target.closest('[data-history-toggle]');

            if (!button || !report.contains(button)) {
                return;
            }

            const wasOpen = button.getAttribute('aria-expanded') === 'true';

            report.querySelectorAll('[data-history-toggle][aria-expanded="true"]')
                .forEach(openButton => toggleHistory(openButton, false));

            if (!wasOpen) {
                toggleHistory(button, true);
            }
        });

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCallingReport);
    } else {
        initializeCallingReport();
    }
})();
</script>

<script>
(function () {
    const panel = document.getElementById('calling-inactive-users');

    if (!panel) {
        return;
    }

    const list = panel.querySelector('.cr-inactive-list');
    let loading = false;

    async function refreshInactiveCallers() {
        if (document.hidden || loading) {
            return;
        }

        loading = true;

        try {
            const response = await fetch(panel.dataset.url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const { users } = await response.json();

            if (!Array.isArray(users)) {
                return;
            }

            const items = users.map(function (name) {
                const item = document.createElement('li');
                item.className = 'cr-inactive-name';
                item.textContent = name;
                return item;
            });

            list.replaceChildren(...items);
            panel.hidden = items.length === 0;
        } catch (error) {
            // Keep the last successfully loaded list until the next refresh.
        } finally {
            loading = false;
        }
    }

    window.setInterval(refreshInactiveCallers, 60_000);
    document.addEventListener('visibilitychange', refreshInactiveCallers);
})();
</script>
@endsection