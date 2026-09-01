@extends('layouts.crm', ['title' => 'Leads'])

@section('content')

@once
<style>
    [x-cloak] { display: none !important; }

    .lead-board {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #172033;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(8, minmax(135px, 1fr));
        gap: 10px;
    }

    .stat-card {
        min-height: 82px;
        background: #fff;
        border: 1px solid #e7eaf0;
        border-radius: 11px;
        padding: 13px;
        display: flex;
        align-items: center;
        gap: 11px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg { width: 20px; height: 20px; }
    .stat-label { font-size: 11px; font-weight: 700; color: #344054; line-height: 1.15; }
    .stat-number { margin-top: 4px; font-size: 21px; line-height: 1; font-weight: 800; color: #111827; }
    .stat-sub { margin-top: 4px; font-size: 9px; color: #98a2b3; }

    .board-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(350px, 1fr));
        gap: 12px;
        align-items: start;
    }

    .call-column {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }

    .new-column { border-top: 3px solid #18a957; }
    .dialed-column { border-top: 3px solid #f28b19; }
    .connected-column { border-top: 3px solid #1769d2; }

    .column-header {
        padding: 14px 17px 12px;
        border-bottom: 1px solid #eceff3;
    }

    .column-heading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        font-size: 18px;
        font-weight: 800;
    }

    .column-heading svg { width: 20px; height: 20px; }
    .new-color { color: #159447; }
    .dialed-color { color: #e87508; }
    .connected-color { color: #0759b5; }

    .count-pill {
        padding: 3px 9px;
        border-radius: 12px;
        color: #fff;
        font-size: 11px;
        line-height: 1.4;
        font-weight: 800;
    }

    .new-pill { background: #35aa5e; }
    .dialed-pill { background: #f7a238; }
    .connected-pill { background: #2c7bdc; }

    .column-description {
        margin-top: 4px;
        text-align: center;
        font-size: 10px;
        color: #667085;
    }

    .filter-toggle-row {
        padding: 9px 12px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #fff;
    }

    .section-filter-btn {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #dde2ea;
        border-radius: 7px;
        padding: 0 11px;
        background: #fff;
        color: #344054;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
        transition: .15s;
    }

    .section-filter-btn:hover { background: #f8fafc; }
    .section-filter-btn svg { width: 13px; height: 13px; }
    .section-filter-btn.active-new { border-color: #86d7a2; background: #effbf3; color: #159447; }
    .section-filter-btn.active-dialed { border-color: #f5c27e; background: #fff8ed; color: #e87508; }
    .section-filter-btn.active-connected { border-color: #9ac4ee; background: #eff7ff; color: #0759b5; }

    .active-filter-count {
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: currentColor;
        font-size: 9px;
    }

    .active-filter-count span { color: #fff; }

    .column-filter {
        padding: 12px;
        border-bottom: 1px solid #edf0f4;
        background: #fbfcfe;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .filter-field label {
        display: block;
        margin-bottom: 4px;
        color: #667085;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .filter-grid select {
        width: 100%;
        min-height: 34px;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 8px;
        background: #fff;
        color: #344054;
        font-size: 10px;
        outline: none;
    }

    .filter-grid select:focus {
        border-color: #f5b900;
        box-shadow: 0 0 0 2px rgba(245, 185, 0, .08);
    }

    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        padding-top: 3px;
    }

    .apply-filter-btn {
        height: 32px;
        border: 0;
        border-radius: 6px;
        padding: 0 14px;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
    }

    .apply-new { background: #159447; }
    .apply-dialed { background: #e87508; }
    .apply-connected { background: #0759b5; }

    .clear-filter-btn {
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 12px;
        background: #fff;
        color: #667085;
        font-size: 9px;
        font-weight: 800;
    }

    .lead-list { padding: 10px; }

    .lead-card {
        position: relative;
        margin-bottom: 9px;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        transition: .15s ease;
    }

    .lead-card:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(15, 23, 42, .06); }
    .new-card { border-left: 3px solid #43b96b; }
    .dialed-card { border-left: 3px solid #f4ad51; background: linear-gradient(90deg, #fffdf8 0%, #fff 16%); }
    .connected-card { border-left: 3px solid #72aee8; background: linear-gradient(90deg, #fafdff 0%, #fff 16%); }

    .lead-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .lead-profile { display: flex; min-width: 0; gap: 10px; }

    .lead-avatar {
        width: 37px;
        height: 37px;
        flex: 0 0 37px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 13px;
        font-weight: 800;
    }

    .avatar-new { background: #e8f8ed; color: #16994b; }
    .avatar-dialed { background: #fff3df; color: #dc7609; }
    .avatar-connected { background: #eaf3ff; color: #125cb4; }

    .lead-name {
        display: block;
        max-width: 170px;
        overflow: hidden;
        color: #101828;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lead-name:hover { color: #1769d2; }
    .lead-meta { margin-top: 4px; display: flex; align-items: center; gap: 5px; color: #475467; font-size: 9.5px; }
    .lead-meta svg { width: 11px; height: 11px; }
    .lead-right { flex: 0 0 auto; text-align: right; font-size: 9px; }
    .lead-badge { font-weight: 700; }
    .call-time { display: flex; justify-content: flex-end; align-items: center; gap: 4px; color: #475467; }
    .call-time svg { width: 11px; height: 11px; }
    .call-state { margin-top: 6px; font-weight: 700; }
    .feedback-row { margin-top: 9px; font-size: 9.5px; color: #475467; line-height: 1.45; }
    .feedback-value { font-weight: 700; color: #101828; }
    .followup-row { margin-top: 4px; display: flex; align-items: center; gap: 4px; color: #475467; font-size: 9px; }
    .followup-row svg { width: 11px; height: 11px; }
    .card-bottom { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .category-tag {
        border-radius: 5px;
        padding: 4px 9px;
        background: #f4f3ff;
        color: #6941c6;
        font-size: 9px;
        font-weight: 600;
    }

    .action-group { display: flex; align-items: center; gap: 7px; }

    .round-action {
        width: 34px;
        height: 31px;
        border: 1px solid #dfe4ea;
        border-radius: 7px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .15s;
    }

    .round-action svg { width: 15px; height: 15px; }
    .call-action { color: #139b4b; }
    .whatsapp-action { color: #18a957; }
    .open-action { color: #d99b00; }
    .round-action:hover { background: #f8fafc; transform: translateY(-1px); }

    .demo-sent {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 6px;
        background: #eaf3ff;
        color: #1262c5;
        font-size: 9px;
        font-weight: 800;
    }

    .demo-sent svg { width: 13px; height: 13px; }
    .column-footer { padding: 10px 12px; border-top: 1px solid #edf0f4; text-align: center; font-size: 10px; font-weight: 800; }
    .empty-board { padding: 35px 15px; text-align: center; color: #98a2b3; font-size: 11px; }

    .board-tip {
        margin-top: 11px;
        padding: 11px 15px;
        border: 1px solid #f4e3aa;
        border-radius: 8px;
        background: #fffaf0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 10px;
        color: #475467;
    }

    @media (max-width: 1350px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (max-width: 1100px) {
        .board-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endonce

@php
    /*
    |--------------------------------------------------------------------------
    | Board Configuration
    |--------------------------------------------------------------------------
    |
    | Tino sections isi config se render hongi. Isliye filter fields tino me
    | EXACT same rahenge, sirf query parameter prefix alag hoga.
    |
    */

    $boardSections = [
        'new' => [
            'title' => 'New Call',
            'description' => 'Jin par abhi tak koi call nahi hui',
            'icon' => 'phone',
            'column_class' => 'new-column',
            'color_class' => 'new-color',
            'pill_class' => 'new-pill',
            'active_class' => 'active-new',
            'apply_class' => 'apply-new',
            'count' => $newCount,
            'leads' => $newLeads,
            'empty' => 'No new call leads found.',
            'more' => 'View More New Leads →',
            'all_loaded' => 'All New Leads Loaded',
            'show_var' => 'showNewFilters',
        ],

        'dialed' => [
            'title' => 'Dialed Call',
            'description' => 'Call lagayi hai — disposition kuch bhi ho',
            'icon' => 'phone',
            'column_class' => 'dialed-column',
            'color_class' => 'dialed-color',
            'pill_class' => 'dialed-pill',
            'active_class' => 'active-dialed',
            'apply_class' => 'apply-dialed',
            'count' => $dialedCount,
            'leads' => $dialedLeads,
            'empty' => 'No dialed leads found.',
            'more' => 'View More Dialed Leads →',
            'all_loaded' => 'All Dialed Leads Loaded',
            'show_var' => 'showDialedFilters',
        ],

        'connected' => [
            'title' => 'Connected Call',
            'description' => 'Jinse baat hui hai / feedback save hua hai',
            'icon' => 'phone-call',
            'column_class' => 'connected-column',
            'color_class' => 'connected-color',
            'pill_class' => 'connected-pill',
            'active_class' => 'active-connected',
            'apply_class' => 'apply-connected',
            'count' => $connectedCount,
            'leads' => $connectedLeads,
            'empty' => 'No connected leads found.',
            'more' => 'View More Connected Leads →',
            'all_loaded' => 'All Connected Leads Loaded',
            'show_var' => 'showConnectedFilters',
        ],
    ];

    $boardFilterFields = [
        'category',
        'source',
        'city',
        'priority',
        'assigned_to',
        'date_filter',
        'demo_status',
        'label_id',
    ];
@endphp

<div
    class="lead-board space-y-4"
    x-data="{
        sendingCall: null,

        showNewFilters: {{ request()->hasAny([
            'new_category',
            'new_source',
            'new_city',
            'new_priority',
            'new_assigned_to',
            'new_date_filter',
            'new_demo_status',
            'new_label_id'
        ]) ? 'true' : 'false' }},

        showDialedFilters: {{ request()->hasAny([
            'dialed_category',
            'dialed_source',
            'dialed_city',
            'dialed_priority',
            'dialed_assigned_to',
            'dialed_date_filter',
            'dialed_demo_status',
            'dialed_label_id'
        ]) ? 'true' : 'false' }},

        showConnectedFilters: {{ request()->hasAny([
            'connected_category',
            'connected_source',
            'connected_city',
            'connected_priority',
            'connected_assigned_to',
            'connected_date_filter',
            'connected_demo_status',
            'connected_label_id'
        ]) ? 'true' : 'false' }},

        async sendCall(leadId) {
            if (this.sendingCall) return;

            this.sendingCall = leadId;

            try {
                const token = document.querySelector('meta[name=csrf-token]')?.content;

                const response = await fetch(`/leads/${leadId}/call-on-mobile`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await response.json();

                if (!response.ok || !data.status) {
                    throw new Error(data.message || 'Unable to send call.');
                }

                alert(data.message || 'Call sent to mobile.');
            } catch (error) {
                alert(error.message || 'Unable to send call.');
            } finally {
                this.sendingCall = null;
            }
        }
    }"
>

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Leads</h1>
            <p class="mt-1 text-xs text-slate-500">Manage your leads and track every interaction</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('leads.index') }}" class="relative">
                {{-- Preserve all section filters while searching --}}
                @foreach(request()->except(['search', 'new_page', 'dialed_page', 'connected_page']) as $key => $value)
                    @if(!is_array($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <i
                    data-lucide="search"
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                ></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Name, Number or Business..."
                    class="h-10 w-[360px] rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-xs outline-none focus:border-amber-400"
                >
            </form>

            @can('leads.create')
                <a
                    href="{{ route('leads.create') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-400 px-5 text-xs font-extrabold text-slate-900 shadow-sm hover:bg-amber-500"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Lead
                </a>
            @endcan
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon bg-emerald-50 text-emerald-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Total Leads</div>
                <div class="stat-number">{{ number_format($totalLeads) }}</div>
                <div class="stat-sub">In Panel</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Calls Today</div>
                <div class="stat-number">{{ number_format($callsToday) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-violet-50 text-violet-600"><i data-lucide="users"></i></span>
            <div>
                <div class="stat-label">Connected Today</div>
                <div class="stat-number">{{ number_format($connectedToday) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-orange-50 text-orange-600"><i data-lucide="badge-headset"></i></span>
            <div>
                <div class="stat-label">Employee Total Calls</div>
                <div class="stat-number">{{ number_format($employeeTotalCalls) }}</div>
                <div class="stat-sub">Since Joining</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-cyan-50 text-cyan-600"><i data-lucide="users-round"></i></span>
            <div>
                <div class="stat-label">Unique Connected</div>
                <div class="stat-number">{{ number_format($uniqueConnected) }}</div>
                <div class="stat-sub">Distinct Leads</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-rose-50 text-rose-600"><i data-lucide="refresh-cw"></i></span>
            <div>
                <div class="stat-label">Follow-up Calls</div>
                <div class="stat-number">{{ number_format($followUpCount) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="video"></i></span>
            <div>
                <div class="stat-label">Demo Today</div>
                <div class="stat-number">{{ number_format($demoToday) }}</div>
            </div>
        </div>

        <div class="stat-card">
            <span class="stat-icon bg-amber-50 text-amber-500"><i data-lucide="send"></i></span>
            <div>
                <div class="stat-label">Total Demo</div>
                <div class="stat-number">{{ number_format($totalDemo) }}</div>
                <div class="stat-sub">Till Now</div>
            </div>
        </div>
    </div>

    {{-- THREE COLUMNS --}}
    <div class="board-grid">

        @foreach($boardSections as $sectionKey => $section)
            @php
                $sectionFilterKeys = collect($boardFilterFields)
                    ->map(fn ($field) => $sectionKey . '_' . $field)
                    ->values()
                    ->all();

                $activeFilters = collect($sectionFilterKeys)
                    ->map(fn ($key) => request($key))
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->count();

                $clearKeys = array_merge(
                    $sectionFilterKeys,
                    [$sectionKey . '_page']
                );

                $clearQuery = request()->except($clearKeys);
                $sectionLeads = $section['leads'];
                $showVar = $section['show_var'];
            @endphp

            <section class="call-column {{ $section['column_class'] }}">

                {{-- COLUMN HEADER --}}
                <div class="column-header">
                    <div class="column-heading {{ $section['color_class'] }}">
                        <i data-lucide="{{ $section['icon'] }}"></i>
                        <span>{{ $section['title'] }}</span>
                        <span class="count-pill {{ $section['pill_class'] }}">
                            {{ number_format($section['count']) }}
                        </span>
                    </div>

                    <div class="column-description">
                        {{ $section['description'] }}
                    </div>
                </div>

                {{-- FILTER TOGGLE --}}
                <div class="filter-toggle-row">
                    <button
                        type="button"
                        class="section-filter-btn"
                        :class="{{ $showVar }} ? '{{ $section['active_class'] }}' : ''"
                        @click="{{ $showVar }} = !{{ $showVar }}"
                    >
                        <i data-lucide="sliders-horizontal"></i>
                        FILTER

                        @if($activeFilters > 0)
                            <span class="active-filter-count">
                                <span>{{ $activeFilters }}</span>
                            </span>
                        @endif

                        <span x-text="{{ $showVar }} ? '▲' : '▼'" class="text-[8px]"></span>
                    </button>

                    @if($activeFilters > 0)
                        <a
                            href="{{ route('leads.index', $clearQuery) }}"
                            class="text-[9px] font-bold text-rose-600"
                        >
                            CLEAR FILTER
                        </a>
                    @endif
                </div>

                {{-- FILTER PANEL --}}
                <div
                    x-show="{{ $showVar }}"
                    x-cloak
                    class="column-filter"
                >
                    <form
                        method="GET"
                        action="{{ route('leads.index') }}"
                        class="filter-grid"
                    >
                        {{--
                            Preserve global search + OTHER sections filters.
                            Current section values are replaced by its own select fields below.
                        --}}
                        @foreach(request()->except($clearKeys) as $key => $value)
                            @if(!is_array($value) && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        {{-- Category --}}
                        <div class="filter-field">
                            <label>Category</label>
                            <select name="{{ $sectionKey }}_category">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option
                                        value="{{ $category }}"
                                        @selected(request($sectionKey . '_category') === $category)
                                    >
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Source --}}
                        <div class="filter-field">
                            <label>Source</label>
                            <select name="{{ $sectionKey }}_source">
                                <option value="">All Sources</option>
                                @foreach($sources as $source)
                                    <option
                                        value="{{ $source->id }}"
                                        @selected((string) request($sectionKey . '_source') === (string) $source->id)
                                    >
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- City --}}
                        <div class="filter-field">
                            <label>City</label>
                            <select name="{{ $sectionKey }}_city">
                                <option value="">All Cities</option>
                                @foreach($cities as $city)
                                    <option
                                        value="{{ $city }}"
                                        @selected(request($sectionKey . '_city') === $city)
                                    >
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="filter-field">
                            <label>Priority</label>
                            <select name="{{ $sectionKey }}_priority">
                                <option value="">All Priority</option>
                                @foreach(['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                    <option
                                        value="{{ $priority }}"
                                        @selected(request($sectionKey . '_priority') === $priority)
                                    >
                                        {{ ucfirst($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assigned Employee --}}
                        <div class="filter-field">
                            <label>Assigned To</label>
                            <select
                                name="{{ $sectionKey }}_assigned_to"
                                @disabled(!$canFilterByEmployee)
                            >
                                <option value="">All Employees</option>
                                @foreach($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) request($sectionKey . '_assigned_to') === (string) $user->id)
                                    >
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Added --}}
                        <div class="filter-field">
                            <label>Date Added</label>
                            <select name="{{ $sectionKey }}_date_filter">
                                <option value="">Any Time</option>
                                <option value="today" @selected(request($sectionKey . '_date_filter') === 'today')>Today</option>
                                <option value="yesterday" @selected(request($sectionKey . '_date_filter') === 'yesterday')>Yesterday</option>
                                <option value="week" @selected(request($sectionKey . '_date_filter') === 'week')>This Week</option>
                                <option value="month" @selected(request($sectionKey . '_date_filter') === 'month')>This Month</option>
                            </select>
                        </div>

                        {{-- Demo Status --}}
                        <div class="filter-field">
                            <label>Demo Status</label>
                            <select name="{{ $sectionKey }}_demo_status">
                                <option value="">All Demo Status</option>
                                <option value="sent" @selected(request($sectionKey . '_demo_status') === 'sent')>Demo Sent</option>
                                <option value="not_sent" @selected(request($sectionKey . '_demo_status') === 'not_sent')>Demo Not Sent</option>
                            </select>
                        </div>

                        {{-- Label --}}
                        <div class="filter-field">
                            <label>Label</label>
                            <select name="{{ $sectionKey }}_label_id">
                                <option value="">All Labels</option>
                                @foreach($labels as $label)
                                    <option
                                        value="{{ $label->id }}"
                                        @selected((string) request($sectionKey . '_label_id') === (string) $label->id)
                                    >
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Actions --}}
                        <div class="filter-actions">
                            <a
                                href="{{ route('leads.index', $clearQuery) }}"
                                class="clear-filter-btn"
                            >
                                CLEAR
                            </a>

                            <button
                                type="submit"
                                class="apply-filter-btn {{ $section['apply_class'] }}"
                            >
                                APPLY FILTER
                            </button>
                        </div>
                    </form>
                </div>

                {{-- LEADS LIST --}}
                <div class="lead-list">
                    @forelse($sectionLeads as $lead)
                        @include('leads.partials.board-card', [
                            'lead' => $lead,
                            'type' => $sectionKey,
                        ])
                    @empty
                        <div class="empty-board">
                            {{ $section['empty'] }}
                        </div>
                    @endforelse
                </div>

                {{-- COLUMN FOOTER --}}
                <div class="column-footer {{ $section['color_class'] }}">
                    @if($sectionLeads->hasMorePages())
                        <a href="{{ $sectionLeads->nextPageUrl() }}">
                            {{ $section['more'] }}
                        </a>
                    @else
                        {{ $section['all_loaded'] }}
                    @endif
                </div>
            </section>
        @endforeach

    </div>

    <div class="board-tip">
        <i data-lucide="lightbulb" class="h-5 w-5 text-amber-500"></i>
        <div>
            <strong>Flow:</strong>
            New Call → Dialed Call → Connected Call
            <span class="ml-2">Lead ki activity aur feedback clearly track hoti rahegi.</span>
        </div>
    </div>

</div>

@endsection
