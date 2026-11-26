@extends('layouts.crm', ['title' => 'Dialed Call History'])

@section('content')
@php
    $routeName = 'dialed-call-history.index';
    $indexUrl = route($routeName);

    $cards = [
        [
            'label' => 'Total Calls',
            'count' => $totalCalls,
            'tone' => 'blue',
            'icon' => 'phone-call',
            'params' => [],
            'active' => !$direction && !$callType && !$onlyWithoutLead,
        ],
        [
            'label' => 'Incoming',
            'count' => $incomingCalls,
            'tone' => 'green',
            'icon' => 'arrow-down',
            'params' => ['direction' => 'incoming'],
            'active' => $direction === 'incoming' && !$callType && !$onlyWithoutLead,
        ],
        [
            'label' => 'Outgoing',
            'count' => $outgoingCalls,
            'tone' => 'purple',
            'icon' => 'arrow-up',
            'params' => ['direction' => 'outgoing'],
            'active' => $direction === 'outgoing' && !$callType && !$onlyWithoutLead,
        ],
        [
            'label' => 'Missed',
            'count' => $missedCalls,
            'tone' => 'rose',
            'icon' => 'phone-missed',
            'params' => ['call_type' => 'missed'],
            'active' => !$direction && $callType === 'missed' && !$onlyWithoutLead,
        ],
        [
            'label' => 'Without Lead',
            'count' => $withoutLead,
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

    $statusColors = [
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
        gap: 15px;
        max-width: 1560px;
        margin: 0 auto;
        padding-bottom: 20px;
        color: var(--ink);
        font-size: 13px;
        line-height: 1.4;
    }

    .dialed-report h1,
    .dialed-report h2,
    .dialed-report p { margin: 0; }

    .dialed-report h1 { font-size: 24px; font-weight: 800; line-height: 1.15; }
    .dialed-report h2 { font-size: 17px; font-weight: 800; }

    .dialed-report .dc-icon {
        display: inline-block;
        width: 17px;
        height: 17px;
        flex: none;
    }

    .dialed-report .dc-header,
    .dialed-report .dc-panel-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 9px;
    }

    .dialed-report .dc-header {
        min-height: 57px;
        padding: 2px 3px 5px 12px;
        border-left: 2px solid #7746ed;
        border-radius: 3px 11px 11px 3px;
        background: linear-gradient(112deg, #f5f4fe, #f7f9ff 63%, #f0ebff);
    }

    .dialed-report .dc-title { display: flex; align-items: center; gap: 9px; }
    .dialed-report .dc-subtitle { color: var(--muted); font-size: 12px; }

    .dialed-report .dc-header-icon {
        display: grid;
        width: 42px;
        height: 42px;
        flex: none;
        place-items: center;
        border-radius: 12px;
        background: linear-gradient(140deg, #dfd0ff, #eee6ff);
        color: #5325ca;
    }

    .dialed-report .dc-header-icon .dc-icon { width: 23px; height: 23px; }
    .dialed-report .dc-section-icon { width: 20px; height: 20px; color: #3e29a7; }

    .dialed-report .dc-cards {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 11px;
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
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 80px;
        padding: 8px 13px;
        border: 1px solid var(--edge);
        border-radius: 12px;
        background: linear-gradient(150deg, var(--light), var(--soft));
        color: var(--ink);
        box-shadow: 0 2px 5px #29335f12;
        text-decoration: none;
    }

    .dialed-report .dc-card::after {
        position: absolute;
        z-index: -1;
        right: -18px;
        bottom: -35px;
        width: 72%;
        height: 61px;
        border-radius: 75% 32% 0 0;
        background: var(--wave);
        transform: rotate(-10deg);
        content: "";
    }

    .dialed-report .dc-card:hover,
    .dialed-report .dc-card[aria-current="true"] {
        border-color: var(--tone);
    }

    .dialed-report .dc-card[aria-current="true"] {
        box-shadow: inset 0 0 0 1px var(--tone), 0 2px 5px #29335f12;
    }

    .dialed-report .dc-card-icon {
        display: grid;
        height: 39px;
        flex: 0 0 39px;
        place-items: center;
        border-radius: 50%;
        background: var(--icon-bg);
        color: var(--tone);
    }

    .dialed-report .dc-card-icon .dc-icon { width: 21px; height: 21px; }
    .dialed-report .dc-card-label { display: block; font-size: 12px; font-weight: 750; }
    .dialed-report .dc-card-count {
        display: block;
        margin-top: 2px;
        color: var(--tone);
        font-size: 26px;
        font-weight: 800;
        line-height: 1.05;
        font-variant-numeric: tabular-nums;
    }

    .dialed-report .dc-panel {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 2px 5px #29335f12;
    }

    .dialed-report .dc-panel-heading {
        padding: 9px 13px;
        background: linear-gradient(90deg, #f2f4fc, #fafaff);
    }

    .dialed-report .dc-form { padding: 10px 13px 12px; }
    .dialed-report .dc-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 9px 13px;
    }

    .dialed-report .dc-field {
        display: grid;
        min-width: 0;
        gap: 4px;
        font-size: 11px;
        font-weight: 750;
    }

    .dialed-report .dc-field input,
    .dialed-report .dc-field select {
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        height: 35px;
        margin: 0;
        padding: 0 10px;
        border: 1px solid #dce1ed;
        border-radius: 7px;
        background-color: #fff;
        color: var(--ink);
        font: inherit;
        font-size: 12px;
        font-weight: 500;
    }

    .dialed-report .dc-field select { padding-right: 24px; }
    .dialed-report .dc-field input[readonly] { background: #f6f7fb; }
    .dialed-report .dc-search { position: relative; }
    .dialed-report .dc-search input { padding-left: 32px; }
    .dialed-report .dc-search .dc-icon {
        position: absolute;
        top: 9px;
        left: 10px;
        color: var(--muted);
        pointer-events: none;
    }

    .dialed-report .dc-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 11px;
    }

    .dialed-report .dc-button,
    .dialed-report .dc-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-weight: 750;
        text-decoration: none;
    }

    .dialed-report .dc-button {
        min-height: 35px;
        padding: 0 12px;
        border: 1px solid #dedff2;
        border-radius: 8px;
        background: #fff;
        color: var(--ink);
        font-size: 12px;
        cursor: pointer;
    }

    .dialed-report .dc-button:hover { background: #f2f3fc; }
    .dialed-report .dc-primary {
        border-color: transparent;
        background: linear-gradient(120deg, #8738ed, #4539e8);
        color: #fff;
    }
    .dialed-report .dc-primary:hover { background: #5635d9; }
    .dialed-report .dc-error { margin-top: 8px; color: #a62038; }
    .dialed-report .dc-chip {
        padding: 4px 9px;
        border-radius: 999px;
        background: #efebff;
        color: #39238e;
        font-size: 11px;
    }

    .dialed-report .dc-records { scroll-margin-top: 76px; }
    .dialed-report .dc-scroll { overflow-x: auto; }
    .dialed-report table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        text-align: left;
        font-size: 12px;
    }

    .dialed-report th {
        padding: 9px 10px;
        background: #eceffd;
        color: #222d5b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dialed-report td {
        padding: 8px 10px;
        border-bottom: 1px solid #edf0f7;
        vertical-align: middle;
    }

    .dialed-report tbody tr:hover { background: #fafaff; }
    .dialed-report .dc-detail { display: block; color: var(--muted); font-size: 11px; }
    .dialed-report .dc-nowrap { white-space: nowrap; }
    .dialed-report .dc-duration { font-weight: 700; font-variant-numeric: tabular-nums; }

    .dialed-report .dc-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 7px;
        border-radius: 6px;
        background: var(--soft, #eef0f8);
        color: var(--tone, #35415f);
        font-size: 11px;
        font-weight: 750;
        white-space: nowrap;
    }

    .dialed-report .dc-badge .dc-icon { width: 12px; height: 12px; }
    .dialed-report .dc-remarks { max-width: 170px; overflow-wrap: anywhere; }
    .dialed-report .dc-empty { padding: 24px 12px; text-align: center; }
    .dialed-report .dc-pagination { padding: 10px 13px; }

    .dialed-report :is(a, button, input, select, [tabindex]):focus-visible {
        outline: 2px solid #6044e8;
        outline-offset: 2px;
    }

    @media (max-width: 1100px) {
        .dialed-report .dc-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .dialed-report .dc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 600px) {
        .dialed-report { gap: 10px; }
        .dialed-report h1 { font-size: 20px; }
        .dialed-report .dc-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; }
        .dialed-report .dc-card { min-height: 65px; gap: 7px; padding: 7px 8px; }
        .dialed-report .dc-card-icon { height: 30px; flex-basis: 30px; }
        .dialed-report .dc-card-icon .dc-icon { width: 16px; height: 16px; }
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

    <nav class="dc-cards" aria-label="Filter call records by summary">
        @foreach($cards as $card)
            @php
                $cardUrl = route($routeName, array_merge($cardFilters, $card['params']));
            @endphp

            <a
                href="{{ $cardUrl }}#dialed-call-records"
                class="dc-card dc-{{ $card['tone'] }}"
                @if($card['active']) aria-current="true" @endif
            >
                <span class="dc-card-icon">
                    <i data-lucide="{{ $card['icon'] }}" class="dc-icon" aria-hidden="true"></i>
                </span>

                <span>
                    <span class="dc-card-label">{{ $card['label'] }}</span>
                    <strong class="dc-card-count">{{ number_format($card['count']) }}</strong>
                </span>
            </a>
        @endforeach
    </nav>

    <section class="dc-panel" aria-labelledby="dialed-filters-heading">
        <header class="dc-panel-heading">
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
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
                </label>

                <label class="dc-field">
                    To Date
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
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
                    <a
                        class="dc-chip"
                        href="{{ route($routeName, \Illuminate\Support\Arr::except($filterUrlParams, ['without_lead'])) }}#dialed-call-records"
                        aria-label="Clear Without Lead filter"
                    >
                        Without Lead
                        <span aria-hidden="true">×</span>
                    </a>
                @endif
            </div>

            @if($errors->any())
                <div class="dc-error" role="alert">{{ $errors->first() }}</div>
            @endif
        </form>
    </section>

    <section
        id="dialed-call-records"
        class="dc-panel dc-records"
        aria-labelledby="dialed-records-heading"
    >
        <header class="dc-panel-heading">
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

                            $typeTone = match ($rowType) {
                                'missed' => 'rose',
                                'rejected' => 'amber',
                                default => 'neutral',
                            };
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
                                        <i data-lucide="{{ $rowDirection === 'incoming' ? 'arrow-down' : 'arrow-up' }}"
                                            class="dc-icon" aria-hidden="true"></i>
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
                                <span class="dc-badge dc-{{ $statusColors[$rowStatus] ?? 'neutral' }}">
                                    {{ $statusText }}
                                </span>
                            </td>

                            <td class="dc-nowrap dc-duration">
                                {{ $formatDuration($call->call_duration) }}
                            </td>

                            <td class="dc-nowrap">
                                @if($call->call_at)
                                    <time datetime="{{ $call->call_at->toIso8601String() }}">
                                        <strong>{{ $call->call_at->format('d M Y') }}</strong>
                                        <span class="dc-detail">{{ $call->call_at->format('h:i A') }}</span>
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
</script>
@endsection