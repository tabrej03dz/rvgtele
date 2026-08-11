@extends('layouts.crm', [
    'title' => 'Leads',
])

@section('content')
    @once
        <style>
            [x-cloak] {
                display: none !important;
            }

            .software-ui {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px;
                color: #1f2937;
            }

            .software-ui .software-panel {
                border: 1px solid #b9c1cc;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
            }

            .software-ui .software-panel-title {
                min-height: 32px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 6px 10px;
                border-bottom: 1px solid #cbd5e1;
                background: linear-gradient(to bottom, #ffffff 0%, #f3f4f6 100%);
                color: #111827;
                font-size: 13px;
                font-weight: 700;
            }

            .software-ui .software-toolbar {
                border: 1px solid #b9c1cc;
                background: linear-gradient(to bottom, #ffffff 0%, #eef2f7 100%);
                box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
            }

            .software-ui .software-btn {
                display: inline-flex;
                height: 30px;
                align-items: center;
                justify-content: center;
                gap: 6px;
                border: 1px solid #b8c0ca;
                border-radius: 2px;
                background: linear-gradient(to bottom, #ffffff, #f1f3f6);
                padding: 0 11px;
                color: #1f2937;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
                transition: .15s ease;
            }

            .software-ui .software-btn:hover {
                border-color: #7c8da3;
                background: #e9edf3;
            }

            .software-ui .software-btn-primary {
                border-color: #1d4ed8;
                background: linear-gradient(to bottom, #3b82f6, #2563eb);
                color: #ffffff;
            }

            .software-ui .software-btn-primary:hover {
                background: #1d4ed8;
            }

            .software-ui .software-tab {
                display: inline-flex;
                height: 28px;
                align-items: center;
                gap: 6px;
                border: 1px solid #c5ccd5;
                border-radius: 2px 2px 0 0;
                background: linear-gradient(to bottom, #ffffff, #eef1f5);
                padding: 0 12px;
                color: #334155;
                font-size: 11px;
                font-weight: 600;
                white-space: nowrap;
            }

            .software-ui .software-tab:hover {
                background: #e8edf4;
            }

            .software-ui .software-tab-active {
                border-color: #6b7f99;
                border-bottom-color: #ffffff;
                background: #ffffff;
                color: #0f4c8a;
                font-weight: 700;
            }

            .software-ui .software-tab-dark-active {
                border-color: #475569;
                border-bottom-color: #ffffff;
                background: #475569;
                color: #ffffff;
            }

            .software-ui .software-label {
                margin-bottom: 4px;
                display: block;
                color: #334155;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .025em;
            }

            .software-ui input[type="text"],
            .software-ui input[type="date"],
            .software-ui input[type="email"],
            .software-ui select,
            .software-ui textarea {
                min-height: 30px;
                border: 1px solid #bfc7d1 !important;
                border-radius: 1px !important;
                background: #fbfcfd;
                padding-top: 5px;
                padding-bottom: 5px;
                font-size: 11px !important;
                box-shadow: inset 0 1px 2px rgba(15,23,42,.04);
            }

            .software-ui input:focus,
            .software-ui select:focus,
            .software-ui textarea:focus {
                border-color: #2563eb !important;
                background: #ffffff;
                outline: none;
                box-shadow: 0 0 0 1px #2563eb !important;
            }

            .software-ui table {
                border-collapse: collapse;
            }

            .software-ui thead th {
                border-right: 1px solid #d5dae1;
                border-bottom: 1px solid #aeb8c4 !important;
                background: linear-gradient(to bottom, #f8fafc 0%, #e8edf3 100%);
                color: #334155 !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                letter-spacing: .025em !important;
            }

            .software-ui tbody td {
                border-right: 1px solid #edf0f3;
            }

            .software-ui tbody tr:nth-child(even) {
                background: #fafbfc;
            }

            .software-ui tbody tr:hover {
                background: #eef6ff !important;
            }

            .software-ui .rounded-2xl,
            .software-ui .rounded-xl,
            .software-ui .rounded-lg {
                border-radius: 2px !important;
            }

            .software-ui .shadow-sm {
                box-shadow: 0 1px 3px rgba(15, 23, 42, .10) !important;
            }

            .crm-scrollbar {
                scrollbar-width: thin;
                scrollbar-color: #9ca3af #eef1f4;
            }

            .crm-scrollbar::-webkit-scrollbar {
                height: 9px;
                width: 9px;
            }

            .crm-scrollbar::-webkit-scrollbar-track {
                background: #eef1f4;
            }

            .crm-scrollbar::-webkit-scrollbar-thumb {
                background: #9ca3af;
                border: 2px solid #eef1f4;
                border-radius: 0;
            }

            .crm-scrollbar::-webkit-scrollbar-thumb:hover {
                background: #6b7280;
            }
        </style>
    @endonce
    <div class="software-ui mx-auto max-w-[1700px] space-y-3" x-data="{
        selected: [],
        selectAllPage: false,
        showBulkModal: false,
        assignmentScope: 'selected',
        showFilters: @js(request()->hasAny([
            'search',
            'source',
            'assigned_to',
            'team_id',
            'priority',
            'temperature',
            'date_from',
            'date_to',
        ])),

        togglePage(ids) {
            if (this.selectAllPage) {
                this.selected = [...new Set([...this.selected, ...ids])];
            } else {
                this.selected = this.selected.filter(id => !ids.includes(id));
            }
        }
    }">
        {{-- Alerts --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                <div class="font-semibold text-rose-800">
                    Please correct the following errors:
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Desktop Software Toolbar --}}
        <section class="software-toolbar">
            <div class="flex flex-col gap-3 px-3 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center border border-slate-300 bg-slate-100 text-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M19 8v6M22 11h-6" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-[18px] font-bold uppercase tracking-tight text-slate-900">
                                Lead Management
                            </h1>

                            <span class="border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                {{ number_format($leads->total()) }} RECORDS
                            </span>
                        </div>

                        <p class="mt-0.5 text-[11px] text-slate-500">
                            @if ($hasFullAccess)
                                Company CRM leads — search, filter, assign and manage.
                            @elseif ($isTeamLeader)
                                Your leads and leads assigned to employees in your team.
                            @else
                                Leads assigned to your account.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        @click="showFilters = !showFilters"
                        class="software-btn"
                        :class="showFilters ? 'border-blue-500 bg-blue-50 text-blue-700' : ''"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M7 12h10M10 18h4" />
                        </svg>

                        FILTERS

                        @if (request()->hasAny(['search', 'source', 'assigned_to', 'team_id', 'priority', 'temperature', 'date_from', 'date_to']))
                            <span class="h-1.5 w-1.5 bg-blue-600"></span>
                        @endif
                    </button>

                    @if ($hasFullAccess)
                        <button
                            type="button"
                            @click="
                                assignmentScope = 'selected';
                                showBulkModal = true
                            "
                            class="software-btn"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M19 8v6M22 11h-6" />
                            </svg>
                            BULK ASSIGN
                        </button>

                        <a
                            href="{{ route('leads.import.create') }}"
                            class="software-btn"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3v12M7 8l5-5 5 5" />
                                <path d="M5 21h14a2 2 0 0 0 2-2v-5" />
                            </svg>
                            IMPORT
                        </a>
                    @endif

                    <a
                        href="{{ route('leads.create') }}"
                        class="software-btn software-btn-primary"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        NEW LEAD
                    </a>
                </div>
            </div>
        </section>

        {{-- Quick Tabs --}}
        @php
            $statusBaseQuery = request()->except([
                'page',
                'status',
            ]);

            $dispositionBaseQuery = request()->except([
                'page',
                'call_disposition',
            ]);

            $currentStatus = (string) request('status', '');
            $currentDisposition = (string) request('call_disposition', '');

            $activeStatus = $statuses->first(
                fn ($status) => (string) $status->id === $currentStatus
            );

            $activeDisposition = $dispositions->first(
                fn ($disposition) => (string) $disposition->id === $currentDisposition
            );
        @endphp

        <section class="software-panel">
            <div class="software-panel-title">
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16M4 12h10M4 17h7" />
                    </svg>

                    <span>Quick Lead Filters</span>
                </div>

                @if ($currentStatus !== '' || $currentDisposition !== '')
                    <a
                        href="{{ route('leads.index', request()->except(['page', 'status', 'call_disposition'])) }}"
                        class="text-[10px] font-bold uppercase text-rose-600 hover:underline"
                    >
                        Clear Quick Filters
                    </a>
                @endif
            </div>

            {{-- Status Strip --}}
            <div class="border-b border-slate-300 bg-slate-50 px-3 pt-2">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                        Lead Status
                        @if ($activeStatus)
                            <span class="ml-1 text-blue-700">: {{ $activeStatus->name }}</span>
                        @endif
                    </div>

                    @if ($currentStatus !== '')
                        <a
                            href="{{ route('leads.index', $statusBaseQuery) }}"
                            class="text-[10px] font-semibold text-slate-500 hover:text-blue-700"
                        >
                            Reset
                        </a>
                    @endif
                </div>

                <div class="crm-scrollbar overflow-x-auto">
                    <div class="flex min-w-max items-end gap-1">
                        <a
                            href="{{ route('leads.index', $statusBaseQuery) }}"
                            class="software-tab {{ $currentStatus === '' ? 'software-tab-active' : '' }}"
                        >
                            ALL STATUS
                        </a>

                        @foreach ($statuses as $status)
                            @php
                                $statusQuery = array_merge(
                                    $statusBaseQuery,
                                    ['status' => $status->id]
                                );

                                $isActiveStatusTab =
                                    $currentStatus === (string) $status->id;
                            @endphp

                            <a
                                href="{{ route('leads.index', $statusQuery) }}"
                                class="software-tab {{ $isActiveStatusTab ? 'software-tab-active' : '' }}"
                            >
                                @if ($status->color)
                                    <span
                                        class="h-2 w-2 border border-white"
                                        style="background-color: {{ $status->color }};"
                                    ></span>
                                @endif

                                {{ strtoupper($status->name) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Disposition Strip --}}
            <div class="bg-white px-3 pt-2">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                        Latest Call Disposition

                        @if ($currentDisposition === 'no_call')
                            <span class="ml-1 text-amber-700">: No Call Yet</span>
                        @elseif ($activeDisposition)
                            <span class="ml-1 text-indigo-700">: {{ $activeDisposition->name }}</span>
                        @endif
                    </div>

                    @if ($currentDisposition !== '')
                        <a
                            href="{{ route('leads.index', $dispositionBaseQuery) }}"
                            class="text-[10px] font-semibold text-slate-500 hover:text-indigo-700"
                        >
                            Reset
                        </a>
                    @endif
                </div>

                <div class="crm-scrollbar overflow-x-auto">
                    <div class="flex min-w-max items-end gap-1">
                        <a
                            href="{{ route('leads.index', $dispositionBaseQuery) }}"
                            class="software-tab {{ $currentDisposition === '' ? 'software-tab-dark-active' : '' }}"
                        >
                            ALL DISPOSITIONS
                        </a>

                        @php
                            $noCallQuery = array_merge(
                                $dispositionBaseQuery,
                                ['call_disposition' => 'no_call']
                            );
                        @endphp

                        <a
                            href="{{ route('leads.index', $noCallQuery) }}"
                            class="software-tab {{ $currentDisposition === 'no_call' ? 'software-tab-active' : '' }}"
                        >
                            <span class="h-2 w-2 bg-amber-500"></span>
                            NO CALL YET
                        </a>

                        @foreach ($dispositions as $disposition)
                            @php
                                $dispositionQuery = array_merge(
                                    $dispositionBaseQuery,
                                    ['call_disposition' => $disposition->id]
                                );

                                $isActiveDispositionTab =
                                    $currentDisposition === (string) $disposition->id;
                            @endphp

                            <a
                                href="{{ route('leads.index', $dispositionQuery) }}"
                                class="software-tab {{ $isActiveDispositionTab ? 'software-tab-active' : '' }}"
                            >
                                {{ strtoupper($disposition->name) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Filters --}}
        <section x-show="showFilters" x-cloak x-transition.opacity.duration.150ms class="software-panel">
            <form method="GET" action="{{ route('leads.index') }}">
                <div class="software-panel-title">
                    <div class="flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M7 12h10M10 18h4" />
                        </svg>
                        <span>Advanced Search / Filters</span>
                    </div>

                    <span class="text-[10px] font-normal text-slate-500">
                        Search, employee, team, priority and date range
                    </span>
                </div>

                <div class="grid gap-x-4 gap-y-3 p-3 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- Search --}}
                    <label class="block sm:col-span-2">
                        <span class="software-label">
                            Search
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Name, mobile, company, email or city"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    {{-- Keep Tab Filters --}}
                    <input
                        type="hidden"
                        name="status"
                        value="{{ request('status') }}"
                    >

                    <input
                        type="hidden"
                        name="call_disposition"
                        value="{{ request('call_disposition') }}"
                    >

                    {{-- Source --}}
                    <label class="block">
                        <span class="software-label">
                            Source
                        </span>

                        <select name="source"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All sources</option>

                            @foreach ($sources as $source)
                                <option value="{{ $source->id }}" @selected((string) request('source') === (string) $source->id)>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Assigned Employee --}}
                    @if ($canFilterByEmployee)
                        <label class="block">
                            <span class="software-label">
                                Assigned Employee
                            </span>

                            <select name="assigned_to"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">
                                    {{ $hasFullAccess ? 'All employees' : 'All team employees' }}
                                </option>

                                @if ($hasFullAccess)
                                    <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>
                                        Unassigned
                                    </option>
                                @endif

                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>
                                        {{ $user->name }}

                                        @if ($user->employee_code)
                                            ({{ $user->employee_code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    {{-- Team --}}
                    @if ($canFilterByTeam)
                        <label class="block">
                            <span class="software-label">
                                Team
                            </span>

                            <select name="team_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">
                                    {{ $hasFullAccess ? 'All teams' : 'All my teams' }}
                                </option>

                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    {{-- Priority --}}
                    <label class="block">
                        <span class="software-label">
                            Priority
                        </span>

                        <select name="priority"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All priorities</option>

                            @foreach (['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                <option value="{{ $priority }}" @selected(request('priority') === $priority)>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Temperature --}}
                    <label class="block">
                        <span class="software-label">
                            Temperature
                        </span>

                        <select name="temperature"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All temperatures</option>

                            @foreach (['cold', 'warm', 'hot'] as $temperature)
                                <option value="{{ $temperature }}" @selected(request('temperature') === $temperature)>
                                    {{ ucfirst($temperature) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Date From --}}
                    <label class="block">
                        <span class="software-label">
                            Created From
                        </span>

                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    {{-- Date To --}}
                    <label class="block">
                        <span class="software-label">
                            Created To
                        </span>

                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                </div>

                <div
                    class="flex flex-col gap-2 border-t border-slate-300 bg-slate-100 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-500">
                        {{ number_format($leads->total()) }} matching leads
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('leads.index') }}"
                            class="software-btn">
                            Clear
                        </a>

                        <button type="submit"
                            class="software-btn software-btn-primary">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Selected Bar --}}
        @if ($hasFullAccess)
            <div x-show="selected.length > 0" x-cloak
                class="software-panel flex flex-col gap-2 border-blue-300 bg-blue-50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-semibold text-blue-800">
                    <span x-text="selected.length"></span>
                    lead(s) selected
                </div>

                <div class="flex gap-2">
                    <button type="button"
                        @click="
                        assignmentScope = 'selected';
                        showBulkModal = true
                    "
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Assign Selected
                    </button>

                    <button type="button"
                        @click="
                        selected = [];
                        selectAllPage = false
                    "
                        class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                        Clear
                    </button>
                </div>
            </div>
        @endif

        {{-- Leads Table --}}
        <section class="software-panel overflow-hidden">
            <div
                class="flex flex-col gap-2 border-b border-slate-300 bg-gradient-to-b from-white to-slate-100 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-[13px] font-bold text-slate-900">Lead Register</h2>

                        @if ($currentStatus !== '' || $currentDisposition !== '')
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                Filtered
                            </span>
                        @endif
                    </div>

                    <p class="mt-0.5 text-[10px] text-slate-500">
                        Showing
                        <span class="font-semibold text-slate-700">{{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-semibold text-slate-700">{{ number_format($leads->total()) }}</span>
                        leads
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('leads.index') }}"
                    class="flex items-center gap-2"
                >
                    @foreach (request()->except(['page', 'per_page']) as $key => $value)
                        @if (is_scalar($value) && $value !== '')
                            <input
                                type="hidden"
                                name="{{ $key }}"
                                value="{{ $value }}"
                            >
                        @endif
                    @endforeach

                    <label
                        for="per_page"
                        class="text-[10px] font-bold uppercase text-slate-600"
                    >
                        Per Page
                    </label>

                    <select
                        id="per_page"
                        name="per_page"
                        onchange="this.form.submit()"
                        class="h-[28px] border-slate-300 bg-white py-1 pl-2 pr-7 text-[11px] font-semibold text-slate-700"
                    >
                        @foreach ([25, 50, 100, 200] as $size)
                            <option
                                value="{{ $size }}"
                                @selected((int) $perPage === $size)
                            >
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="crm-scrollbar overflow-x-auto">
                <table class="w-full min-w-[1180px] text-sm">
                    <thead class="bg-slate-50/90">
                        <tr
                            class="border-b border-slate-200 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            @if ($hasFullAccess)
                                <th class="w-10 px-3 py-2">
                                    <input type="checkbox" x-model="selectAllPage"
                                        @change="
                                        togglePage(
                                            @js($leads->pluck('id')->map(fn($id) => (int) $id)->values())
                                        )
                                    "
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                </th>
                            @endif
                            <th class="px-3 py-2.5">Lead</th>
                            <th class="px-3 py-2.5">Mobile</th>
                            <th class="px-3 py-2.5">Source</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Priority</th>
                            <th class="px-3 py-2.5">Temperature</th>
                            <th class="px-3 py-2.5">Team</th>
                            <th class="px-3 py-2.5">Owner</th>
                            <th class="px-3 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($leads as $lead)
                            @php
                                $priorityClass = match ($lead->priority) {
                                    'hot', 'urgent' => 'bg-rose-50 text-rose-700',
                                    'high' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };

                                $temperatureClass = match ($lead->temperature) {
                                    'hot' => 'text-rose-700',
                                    'warm' => 'text-amber-700',
                                    default => 'text-blue-700',
                                };
                            @endphp

                            <tr class="group transition hover:bg-blue-50/30">
                                @if ($hasFullAccess)
                                    <td class="px-3 py-2.5">
                                        <input type="checkbox" value="{{ $lead->id }}" x-model.number="selected"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                @endif

                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-900 transition group-hover:text-blue-700">
                                        {{ $lead->name }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->company_name ?: 'Individual Lead' }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-800">
                                        {{ $lead->mobile }}
                                    </div>

                                    @if ($lead->city)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $lead->city }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-3 py-2.5 text-slate-700">
                                    {{ $lead->source?->name ?? '—' }}
                                </td>

                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {{ $lead->status?->name ?? 'New' }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $priorityClass }}">
                                        {{ $lead->priority }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5">
                                    <span class="text-sm font-medium capitalize {{ $temperatureClass }}">
                                        {{ $lead->temperature }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5 text-slate-700">
                                    {{ $lead->team?->name ?? '—' }}
                                </td>

                                <td class="px-3 py-2.5">
                                    @if ($lead->assignedUser)
                                        <div class="font-medium text-slate-800">
                                            {{ $lead->assignedUser->name }}
                                        </div>

                                        @if ($lead->assignedUser->employee_code)
                                            <div class="text-xs text-slate-500">
                                                {{ $lead->assignedUser->employee_code }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-sm font-medium text-amber-600">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-2.5 text-right">
                                    <a href="{{ route('leads.show', $lead) }}"
                                        class="software-btn !h-[25px] !px-2.5 !text-[10px] text-blue-700">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $hasFullAccess ? 10 : 9 }}" class="px-5 py-14 text-center">
                                    <div class="font-semibold text-slate-700">
                                        No leads found
                                    </div>

                                    <div class="mt-1 text-sm text-slate-500">
                                        Filters change karein ya new lead create karein.
                                    </div>

                                    <a href="{{ route('leads.create') }}"
                                        class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        Create Lead
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="border-t border-slate-300 bg-slate-100 px-3 py-2">
                    {{ $leads->links() }}
                </div>
            @endif
        </section>

        {{-- Bulk Assignment Modal --}}
        @if ($hasFullAccess)
            <div x-show="showBulkModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
                @keydown.escape.window="showBulkModal = false">
                <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto border border-slate-500 bg-white shadow-2xl"
                    @click.outside="showBulkModal = false">
                    <form method="POST" action="{{ route('leads.bulk-assign') }}"
                        @submit="
                    if (assignmentScope === 'selected' && selected.length === 0) {
                        $event.preventDefault();
                        alert('Please select at least one lead.');
                    }
                ">
                        @csrf

                        <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">
                                    Bulk Assign Leads
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    Selected ya filtered leads assign karein.
                                </p>
                            </div>

                            <button type="button" @click="showBulkModal = false"
                                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                                ✕
                            </button>
                        </div>

                        <div class="space-y-5 p-5">
                            <input type="hidden" name="assignment_scope" x-model="assignmentScope">

                            <template x-for="leadId in selected" :key="leadId">
                                <input type="hidden" name="lead_ids[]" :value="leadId">
                            </template>

                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="source" value="{{ request('source') }}">
                            <input type="hidden" name="filter_assigned_to" value="{{ request('assigned_to') }}">
                            <input type="hidden" name="team_id" value="{{ request('team_id') }}">
                            <input type="hidden" name="priority" value="{{ request('priority') }}">
                            <input type="hidden" name="temperature" value="{{ request('temperature') }}">
                            <input type="hidden" name="call_disposition" value="{{ request('call_disposition') }}">
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">

                            <div>
                                <div class="mb-2 text-sm font-semibold text-slate-700">
                                    Assignment Scope
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4">
                                        <input type="radio" value="selected" x-model="assignmentScope"
                                            class="mt-1 text-blue-600 focus:ring-blue-500">

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                Selected Leads
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                <span x-text="selected.length"></span> selected
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4">
                                        <input type="radio" value="filtered" x-model="assignmentScope"
                                            class="mt-1 text-blue-600 focus:ring-blue-500">

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                All Filtered Leads
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $leads->total() }} matching
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <label class="block">
                                <span class="software-label">
                                    Assign Employee
                                    <span class="text-rose-500">*</span>
                                </span>

                                <select name="assigned_to" required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select employee</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                            @if ($user->employee_code)
                                                ({{ $user->employee_code }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="software-label">
                                    Assignment Reason
                                    <span class="text-rose-500">*</span>
                                </span>

                                <textarea name="reason" rows="3" required placeholder="Assignment reason..."
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </label>

                            <div x-show="assignmentScope === 'filtered'"
                                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Current filters se match hone wali
                                <strong>{{ $leads->total() }}</strong>
                                leads assign hongi.
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                            <button type="button" @click="showBulkModal = false"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Cancel
                            </button>

                            <button type="submit"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Assign Leads
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection