@extends('layouts.crm', ['title' => 'Dialed Call History'])

@section('content')
@php
    $routeName = 'dialed-call-history.index';
    $indexUrl = route($routeName);
    $todayDate = $today->toDateString();

    $sections = [
        [
            'id' => 'dialed-today-heading',
            'title' => "Today's Calling Overview",
            'icon' => 'calendar-days',
            'badge' => $today->format('d M Y, l'),
            'badge_icon' => 'calendar',
            'counts' => $todayCounts,
            'params' => ['from_date' => $todayDate, 'to_date' => $todayDate],
            'is_today' => true,
            'active_range' => $fromDate === $todayDate && $toDate === $todayDate,
        ],
        [
            'id' => 'dialed-total-heading',
            'title' => 'Total Calling Overview',
            'icon' => 'chart-no-axes-column-increasing',
            'badge' => 'All Time (Complete Data)',
            'badge_icon' => 'layers',
            'counts' => $totalCounts,
            'params' => [],
            'is_today' => false,
            'active_range' => !$fromDate && !$toDate,
        ],
    ];

    $cards = [
        [
            'key' => 'total',
            'label' => 'Total Calls',
            'tone' => 'blue',
            'icon' => 'phone-call',
            'params' => [],
            'active' => !$direction && !$callType && !$onlyWithoutLead,
        ],
        [
            'key' => 'incoming',
            'label' => 'Incoming',
            'tone' => 'green',
            'icon' => 'arrow-down',
            'params' => ['direction' => 'incoming'],
            'active' => $direction === 'incoming' && !$callType && !$onlyWithoutLead,
        ],
        [
            'key' => 'outgoing',
            'label' => 'Outgoing',
            'tone' => 'purple',
            'icon' => 'arrow-up',
            'params' => ['direction' => 'outgoing'],
            'active' => $direction === 'outgoing' && !$callType && !$onlyWithoutLead,
        ],
        [
            'key' => 'missed',
            'label' => 'Missed',
            'tone' => 'rose',
            'icon' => 'x',
            'params' => ['call_type' => 'missed'],
            'active' => !$direction && $callType === 'missed' && !$onlyWithoutLead,
        ],
        [
            'key' => 'without_lead',
            'label' => 'Without Lead',
            'tone' => 'amber',
            'icon' => 'users',
            'params' => ['without_lead' => 1],
            'active' => !$direction && !$callType && $onlyWithoutLead,
        ],
    ];

    $directions = [
        'incoming' => 'Incoming',
        'outgoing' => 'Outgoing',
    ];

    $callTypes = [
        'incoming' => 'Incoming',
        'outgoing' => 'Outgoing',
        'missed' => 'Missed',
        'rejected' => 'Rejected',
    ];

    $statusLabels = [
        'not_answer' => 'Not Answered',
        'not_answered' => 'Not Answered',
        'no_answer' => 'Not Answered',
    ];

    $statusTones = [
        'completed' => 'green',
        'answered' => 'green',
        'converted' => 'green',
        'demo_sent' => 'blue',
        'not_answer' => 'amber',
        'not_answered' => 'amber',
        'no_answer' => 'amber',
        'busy' => 'amber',
        'missed' => 'rose',
        'rejected' => 'rose',
        'not_interested' => 'rose',
        'failed' => 'rose',
    ];

    $formatDuration = static function ($value) {
        if ($value === null || !is_numeric($value)) {
            return '—';
        }

        $seconds = max(0, (int) $value);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    };
@endphp

<style>
    .dialed-report {
        --ink: #151838;
        --muted: #515d79;
        --line: #e2e7f3;
        display: grid;
        gap: 13px;
        max-width: 1560px;
        margin: 0 auto;
        padding-bottom: 20px;
        color: var(--ink);
        font-size: 13px;
        line-height: 1.4;
    }

    .dialed-report *, .dialed-report *::after { box-sizing: border-box; }
    .dialed-report h1, .dialed-report h2, .dialed-report p { margin: 0; }
    .dialed-report h1 { font-size: 24px; font-weight: 800; line-height: 1.15; }
    .dialed-report h2 { font-size: 16px; font-weight: 800; }
    .dialed-report .dc-icon { display: inline-block; width: 17px; height: 17px; flex: none; }

    .dialed-report .dc-header, .dialed-report .dc-heading {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 8px;
    }

    .dialed-report .dc-header {
        min-height: 51px;
        padding: 2px 4px 3px 10px;
        border-left: 2px solid #7746ed;
        border-radius: 3px 11px 11px 3px;
        background: linear-gradient(112deg, #f5f4fe, #f7f9ff 63%, #f0ebff);
    }

    .dialed-report .dc-title { display: flex; align-items: center; gap: 9px; }
    .dialed-report .dc-subtitle { color: var(--muted); font-size: 12px; }

    .dialed-report .dc-header-icon {
        display: grid; place-items: center; width: 40px; height: 40px; flex: none;
        border-radius: 11px; background: #e9dcff; color: #5325ca;
    }

    .dialed-report .dc-header-icon .dc-icon { width: 22px; height: 22px; }
    .dialed-report .dc-section-icon { width: 19px; height: 19px; color: #352582; }

    .dialed-report .dc-panel {
        min-width: 0; overflow: hidden; border: 1px solid var(--line);
        border-radius: 12px; background: #fff; box-shadow: 0 2px 5px #29335f15;
    }

    .dialed-report .dc-heading {
        min-height: 35px; padding: 5px 12px;
        background: linear-gradient(90deg, #f0efff, #eae9fc);
    }

    .dialed-report .dc-filter-heading, .dialed-report .dc-records-heading {
        min-height: 41px; background: linear-gradient(90deg, #f3f5fd, #fafaff);
    }

    .dialed-report .dc-cards {
        display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px; padding: 7px 10px 9px;
    }

    .dialed-report .dc-blue {
        --tone: #133caa; --icon-bg: #64b0fb; --soft: #e7f1ff;
        --light: #f6fbff; --edge: #d8e7fa; --wave: #d5eaff;
    }

    .dialed-report .dc-green {
        --tone: #106033; --icon-bg: #63dc87; --soft: #e6f8ed;
        --light: #f4fff8; --edge: #d7efdf; --wave: #d2f5e2;
    }

    .dialed-report .dc-purple {
        --tone: #4c1eb3; --icon-bg: #b68df9; --soft: #f0e9ff;
        --light: #fbf8ff; --edge: #e9dffc; --wave: #eadbff;
    }

    .dialed-report .dc-rose {
        --tone: #bb233b; --icon-bg: #ff9da8; --soft: #ffebef;
        --light: #fff8fa; --edge: #f7dce2; --wave: #fbd7df;
    }

    .dialed-report .dc-amber {
        --tone: #974b12; --icon-bg: #ffc94c; --soft: #fff2d5;
        --light: #fffdf6; --edge: #f5e8c9; --wave: #fceabb;
    }

    .dialed-report .dc-card {
        position: relative; isolation: isolate; overflow: hidden;
        display: flex; align-items: center; gap: 11px;
        min-width: 0; min-height: 67px; padding: 7px 11px;
        border: 1px solid var(--edge); border-radius: 10px;
        background: linear-gradient(150deg, var(--light), var(--soft));
        color: var(--ink); box-shadow: 0 2px 4px #29335f0e; text-decoration: none;
    }

    .dialed-report .dc-card::after {
        position: absolute; z-index: -1; right: -15px; bottom: -36px;
        width: 70%; height: 62px; border-radius: 75% 32% 0 0;
        background: var(--wave); transform: rotate(-10deg); content: "";
    }

    .dialed-report .dc-card:hover,
    .dialed-report .dc-card[aria-current="true"] { border-color: var(--tone); }

    .dialed-report .dc-card[aria-current="true"] {
        box-shadow: inset 0 0 0 1px var(--tone), 0 2px 4px #29335f0e;
    }

    .dialed-report .dc-card-icon {
        display: grid; place-items: center; height: 36px; flex: 0 0 36px;
        border-radius: 50%; background: var(--icon-bg); color: var(--tone);
    }

    .dialed-report .dc-card-icon .dc-icon { width: 20px; height: 20px; }
    .dialed-report .dc-card-label { display: block; font-size: 12px; font-weight: 750; }
    .dialed-report .dc-card-count {
        display: block; margin-top: 2px; color: var(--tone);
        font-size: 25px; font-weight: 800; line-height: 1.05;
        font-variant-numeric: tabular-nums;
    }

    .dialed-report .dc-form { padding: 8px 12px 11px; }
    .dialed-report .dc-grid {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px 13px;
    }

    .dialed-report .dc-field {
        display: grid; min-width: 0; gap: 3px; font-size: 11px; font-weight: 750;
    }

    .dialed-report .dc-field input, .dialed-report .dc-field select {
        width: 100%; min-width: 0; height: 34px; margin: 0; padding: 0 9px;
        border: 1px solid #dce1ed; border-radius: 7px; background: #fff;
        color: var(--ink); font: inherit; font-size: 12px; font-weight: 500;
    }

    .dialed-report .dc-field select { padding-right: 24px; }
    .dialed-report .dc-field input[readonly] { background: #f6f7fb; }
    .dialed-report .dc-search { position: relative; }
    .dialed-report .dc-search input { padding-left: 31px; }
    .dialed-report .dc-search .dc-icon {
        position: absolute; top: 8px; left: 9px; color: var(--muted); pointer-events: none;
    }

    .dialed-report .dc-actions {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 10px;
    }

    .dialed-report .dc-button, .dialed-report .dc-chip {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; font-weight: 750; text-decoration: none;
    }

    .dialed-report .dc-button {
        min-height: 34px; padding: 0 12px; border: 1px solid #dedff2;
        border-radius: 8px; background: #fff; color: var(--ink); font-size: 12px; cursor: pointer;
    }

    .dialed-report .dc-button:hover { background: #f2f3fc; }
    .dialed-report .dc-primary {
        border-color: transparent; background: linear-gradient(120deg, #8738ed, #4539e8); color: #fff;
    }

    .dialed-report .dc-primary:hover { background: #5635d9; }
    .dialed-report .dc-error { margin-top: 8px; color: #a62038; }
    .dialed-report .dc-chip {
        padding: 3px 9px; border-radius: 999px; background: #efebff;
        color: #39238e; font-size: 11px;
    }

    .dialed-report .dc-chip .dc-icon { width: 13px; height: 13px; }
    .dialed-report .dc-records { scroll-margin-top: 76px; }
    .dialed-report .dc-scroll { overflow-x: auto; }

    .dialed-report table {
        width: 100%; min-width: 920px; border-collapse: collapse; text-align: left; font-size: 12px;
    }

    .dialed-report th {
        padding: 7px 9px; background: #eceffd; color: #222d5b;
        font-size: 10px; font-weight: 800; text-transform: uppercase; white-space: nowrap;
    }

    .dialed-report td { padding: 7px 9px; border-bottom: 1px solid #edf0f7; vertical-align: middle; }
    .dialed-report tbody tr:hover { background: #fafaff; }
    .dialed-report .dc-detail { display: block; color: var(--muted); font-size: 11px; }
    .dialed-report .dc-nowrap { white-space: nowrap; }
    .dialed-report .dc-duration { font-weight: 700; font-variant-numeric: tabular-nums; }

    .dialed-report .dc-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 3px 7px;
        border-radius: 6px; background: var(--soft, #eef0f8); color: var(--tone, #35415f);
        font-size: 11px; font-weight: 750; white-space: nowrap;
    }

    .dialed-report .dc-badge .dc-icon { width: 12px; height: 12px; }
    .dialed-report .dc-remarks { max-width: 165px; overflow-wrap: anywhere; }
    .dialed-report .dc-empty { padding: 24px 12px; text-align: center; }
    .dialed-report .dc-pagination { padding: 9px 12px; }

    .dialed-report :is(a, button, input, select, [tabindex]):focus-visible {
        outline: 2px solid #6044e8; outline-offset: 2px;
    }

    @media (max-width: 1100px) {
        .dialed-report .dc-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .dialed-report .dc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 600px) {
        .dialed-report { gap: 10px; }
        .dialed-report h1 { font-size: 20px; }
        .dialed-report .dc-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; }
        .dialed-report .dc-card { min-height: 61px; gap: 7px; padding: 6px 8px; }
        .dialed-report .dc-card-icon { height: 29px; flex-basis: 29px; }
        .dialed-report .dc-card-icon .dc-icon { width: 16px; height: 16px; }
        .dialed-report .dc-card-label { font-size: 11px; }
        .dialed-report .dc-card-count { font-size: 22px; }
    }

    @media (max-width: 400px) {
        .dialed-report .dc-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>

<div class="dialed-report">
    <header class="dc-header">
        <div class="dc-title">
            <span class="dc-header-icon">
                <i data-lucide="phone-call" class="dc-icon" aria-hidden="true"></i>
            </span>

            <div>
                <h1>Dialed Call History</h1>
                <p class="dc-subtitle">Android Dialer Incoming, Outgoing &amp; Missed Call Records</p>
            </div>
        </div>

        <a class="dc-button" href="{{ route($routeName, $refreshParams) }}">
            <i data-lucide="refresh-cw" class="dc-icon" aria-hidden="true"></i>
            Refresh
        </a>
    </header>

    @foreach($sections as $section)
        <section class="dc-panel" aria-labelledby="{{ $section['id'] }}">
            <header class="dc-heading">
                <div class="dc-title">
                    <i data-lucide="{{ $section['icon'] }}" class="dc-icon dc-section-icon" aria-hidden="true"></i>
                    <h2 id="{{ $section['id'] }}">{{ $section['title'] }}</h2>
                </div>

                <span class="dc-chip">
                    <i data-lucide="{{ $section['badge_icon'] }}" class="dc-icon" aria-hidden="true"></i>
                    {{ $section['badge'] }}
                </span>
            </header>

            <div class="dc-cards">
                @foreach($cards as $card)
                    @php
                        $params = array_merge($cardFilters, $section['params'], $card['params']);
                        $isActive = $section['active_range'] && $card['active'];
                        $prefix = $section['is_today'] ? 'Today ' : ($card['key'] === 'total' ? '' : 'Total ');
                    @endphp

                    <a href="{{ route($routeName, $params) }}#dialed-call-records"
                        class="dc-card dc-{{ $card['tone'] }}"
                        @if($isActive) aria-current="true" @endif>
                        <span class="dc-card-icon">
                            <i data-lucide="{{ $card['icon'] }}" class="dc-icon" aria-hidden="true"></i>
                        </span>

                        <span>
                            <span class="dc-card-label">{{ $prefix . $card['label'] }}</span>
                            <strong class="dc-card-count">
                                {{ number_format($section['counts'][$card['key']]) }}
                            </strong>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="dc-panel" aria-labelledby="dialed-filters-heading">
        <header class="dc-heading dc-filter-heading">
            <div class="dc-title">
                <i data-lucide="filter" class="dc-icon dc-section-icon" aria-hidden="true"></i>
                <h2 id="dialed-filters-heading">Search &amp; Filters</h2>
            </div>
        </header>

        <form method="GET" action="{{ $indexUrl }}" class="dc-form">
            @if($onlyWithoutLead)
                <input type="hidden" name="without_lead" value="1">
            @endif

            <div class="dc-grid">
                <label class="dc-field">
                    Mobile / Lead Name
                    <span class="dc-search">
                        <i data-lucide="search" class="dc-icon" aria-hidden="true"></i>
                        <input type="search" name="search" maxlength="100"
                            value="{{ $search }}" placeholder="Search mobile or name">
                    </span>
                </label>

                <label class="dc-field">
                    Employee
                    @if($canFilterEmployees)
                        <select name="user_id">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}"
                                    @selected((string) ($filters['user_id'] ?? '') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" value="{{ auth()->user()->name }}" readonly>
                    @endif
                </label>

                <label class="dc-field">
                    Direction
                    <select name="direction">
                        <option value="">All Directions</option>
                        @foreach($directions as $value => $label)
                            <option value="{{ $value }}" @selected($direction === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="dc-field">
                    Call Type
                    <select name="call_type">
                        <option value="">All Call Types</option>
                        @foreach($callTypes as $value => $label)
                            <option value="{{ $value }}" @selected($callType === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="dc-field">
                    Call Status
                    <input type="text" name="call_status" maxlength="255"
                        value="{{ $callStatus }}" placeholder="completed / demo_sent">
                </label>

                <label class="dc-field">
                    From Date
                    <input type="date" name="from_date" value="{{ $fromDate ?? '' }}">
                </label>

                <label class="dc-field">
                    To Date
                    <input type="date" name="to_date" value="{{ $toDate ?? '' }}">
                </label>

                <label class="dc-field">
                    Records Per Page
                    <select name="per_page">
                        @foreach([20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="dc-actions">
                <button type="submit" class="dc-button dc-primary">
                    <i data-lucide="filter" class="dc-icon" aria-hidden="true"></i>
                    Apply Filters
                </button>

                <a href="{{ $indexUrl }}" class="dc-button">
                    <i data-lucide="rotate-ccw" class="dc-icon" aria-hidden="true"></i>
                    Reset
                </a>

                @if($onlyWithoutLead)
                    <a href="{{ route($routeName, $clearWithoutLeadParams) }}#dialed-call-records"
                        class="dc-chip" aria-label="Clear Without Lead filter">
                        Without Lead <span aria-hidden="true">×</span>
                    </a>
                @endif
            </div>

            @if($errors->any())
                <div class="dc-error" role="alert">{{ $errors->first() }}</div>
            @endif
        </form>
    </section>

    <section id="dialed-call-records" class="dc-panel dc-records"
        aria-labelledby="dialed-records-heading">
        <header class="dc-heading dc-records-heading">
            <div class="dc-title">
                <i data-lucide="phone-call" class="dc-icon dc-section-icon" aria-hidden="true"></i>
                <h2 id="dialed-records-heading">Call Records</h2>
            </div>

            <span class="dc-chip">
                Showing {{ number_format($histories->firstItem() ?? 0) }}
                to {{ number_format($histories->lastItem() ?? 0) }}
                of {{ number_format($histories->total()) }}
            </span>
        </header>

        <div class="dc-scroll" role="region" tabindex="0" aria-label="Dialed call records table">
            <table>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Mobile Number</th>
                        <th scope="col">Lead Details</th>
                        <th scope="col">Employee</th>
                        <th scope="col">Direction</th>
                        <th scope="col">Call Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Date &amp; Time</th>
                        <th scope="col">Remarks</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($histories as $call)
                        @php
                            $rowDirection = strtolower((string) $call->direction);
                            $rowType = strtolower((string) $call->call_type);
                            $rowStatus = strtolower((string) $call->call_status);

                            $statusText = $statusLabels[$rowStatus]
                                ?? (ucwords(str_replace('_', ' ', $rowStatus)) ?: 'Unknown');

                            $directionTone = $rowDirection === 'incoming' ? 'green' : 'blue';
                            $directionIcon = $rowDirection === 'incoming' ? 'arrow-down' : 'arrow-up';

                            $typeTone = match ($rowType) {
                                'missed' => 'rose',
                                'rejected' => 'amber',
                                default => 'neutral',
                            };

                            $callAt = $call->call_at
                                ? \Carbon\CarbonImmutable::instance($call->call_at)->setTimezone($reportTimezone)
                                : null;
                        @endphp

                        <tr>
                            <td class="dc-subtitle">{{ $histories->firstItem() + $loop->index }}</td>
                            <td class="dc-nowrap"><strong>{{ $call->mobile }}</strong></td>

                            <td>
                                @if($call->lead_id !== null)
                                    <strong>{{ $call->lead?->name ?? 'Lead unavailable' }}</strong>
                                    <span class="dc-detail">Lead ID: {{ $call->lead_id }}</span>
                                @else
                                    <span class="dc-badge dc-amber">No Lead</span>
                                @endif
                            </td>

                            <td>{{ $call->user?->name ?? 'Unknown' }}</td>

                            <td>
                                @if(isset($directions[$rowDirection]))
                                    <span class="dc-badge dc-{{ $directionTone }}">
                                        <i data-lucide="{{ $directionIcon }}" class="dc-icon" aria-hidden="true"></i>
                                        {{ $directions[$rowDirection] }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <span class="dc-badge dc-{{ $typeTone }}">
                                    {{ $callTypes[$rowType] ?? (ucfirst($rowType) ?: 'Unknown') }}
                                </span>
                            </td>

                            <td>
                                <span class="dc-badge dc-{{ $statusTones[$rowStatus] ?? 'neutral' }}">
                                    {{ $statusText }}
                                </span>
                            </td>

                            <td class="dc-nowrap dc-duration">{{ $formatDuration($call->call_duration) }}</td>

                            <td class="dc-nowrap">
                                @if($callAt)
                                    <time datetime="{{ $callAt->toIso8601String() }}">
                                        <strong>{{ $callAt->format('d M Y') }}</strong>
                                        <span class="dc-detail">{{ $callAt->format('h:i A') }}</span>
                                    </time>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                <div class="dc-remarks" title="{{ $call->remarks }}">
                                    {{ \Illuminate\Support\Str::limit($call->remarks ?: '—', 50) }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="dc-empty">
                                <strong>No dialed calls found</strong>
                                <p class="dc-subtitle">No call records match your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($histories->hasPages())
            <div class="dc-pagination">
                {{ $histories->fragment('dialed-call-records')->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>

<script>
    (function () {
        function initializeDialedHistoryIcons() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDialedHistoryIcons, { once: true });
        } else {
            initializeDialedHistoryIcons();
        }
    })();
</script>
@endsection