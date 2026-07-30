@extends('layouts.crm', [
    'title' => 'Leads',
])

@section('content')
<div
    class="min-h-screen bg-slate-50/70"
    x-data="{
        selected: [],
        selectAllPage: false,
        showBulkModal: false,
        assignmentScope: 'selected',
        showFilters: true,

        togglePage(ids) {
            if (this.selectAllPage) {
                this.selected = [...new Set([...this.selected, ...ids])];
            } else {
                this.selected = this.selected.filter(id => !ids.includes(id));
            }
        }
    }"
>
    <div class="mx-auto max-w-[1600px] space-y-6 px-3 py-4 sm:px-5 lg:px-6">

        {{-- Alerts --}}
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
                <div class="pt-1 font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                </div>
                <div class="pt-1 font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2 font-semibold text-rose-800">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    Please fix the following errors
                </div>

                <ul class="list-disc space-y-1 pl-6 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Hero Header --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-900 px-5 py-6 text-white shadow-xl sm:px-7 sm:py-8">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-violet-100 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        RVG Tele CRM
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Lead Management
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Search, manage, assign and follow up with every lead from one powerful workspace.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">Total Leads</div>
                            <div class="mt-1 text-xl font-bold">{{ number_format($leads->total()) }}</div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">Selected</div>
                            <div class="mt-1 text-xl font-bold" x-text="selected.length">0</div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <div class="text-xs text-slate-300">Current Page</div>
                            <div class="mt-1 text-xl font-bold">{{ $leads->currentPage() }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="assignmentScope = 'selected'; showBulkModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M19 8v6M22 11h-6"/>
                        </svg>
                        Bulk Assign
                    </button>

                    <a
                        href="{{ route('leads.import.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-400"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v12"/>
                            <path d="m7 10 5 5 5-5"/>
                            <path d="M5 21h14"/>
                        </svg>
                        Import Excel
                    </a>

                    <a
                        href="{{ route('leads.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg transition hover:bg-slate-100"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Create Lead
                    </a>
                </div>
            </div>
        </section>

        {{-- Filter Card --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Advanced Filters</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Find the exact leads you want to work on.</p>
                </div>

                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:self-auto"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M7 12h10M10 18h4"/>
                    </svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
                </button>
            </div>

            <form method="GET" action="{{ route('leads.index') }}">
                <div x-show="showFilters" x-collapse>
                    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">

                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">
                                Search
                            </span>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="7"/>
                                    <path d="m20 20-3.5-3.5"/>
                                </svg>
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Name, mobile, company, email or city"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                >
                            </div>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Status</span>
                            <select name="status" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" @selected((string) request('status') === (string) $status->id)>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Source</span>
                            <select name="source" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Sources</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}" @selected((string) request('source') === (string) $source->id)>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Assigned Employee</span>
                            <select name="assigned_to" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Employees</option>
                                <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned Leads</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>
                                        {{ $user->name }} @if ($user->employee_code) ({{ $user->employee_code }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Team</span>
                            <select name="team_id" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Teams</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Priority</span>
                            <select name="priority" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Priorities</option>
                                @foreach (['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>
                                        {{ ucfirst($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Temperature</span>
                            <select name="temperature" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100">
                                <option value="">All Temperatures</option>
                                @foreach (['cold', 'warm', 'hot'] as $temperature)
                                    <option value="{{ $temperature }}" @selected(request('temperature') === $temperature)>
                                        {{ ucfirst($temperature) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Created From</span>
                            <input
                                type="date"
                                name="date_from"
                                value="{{ request('date_from') }}"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Created To</span>
                            <input
                                type="date"
                                name="date_to"
                                value="{{ request('date_to') }}"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-100"
                            >
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-500">
                        Total matching:
                        <strong class="ml-1 text-slate-900">{{ number_format($leads->total()) }}</strong>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('leads.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Clear Filters
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16M7 12h10M10 18h4"/>
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Selected Action Bar --}}
        <div
            x-show="selected.length > 0"
            x-cloak
            x-transition
            class="sticky top-3 z-20 flex flex-col gap-3 rounded-2xl border border-violet-200 bg-violet-50/95 p-4 shadow-lg shadow-violet-100/70 backdrop-blur sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M19 8v6M22 11h-6"/>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-violet-900">
                        <span x-text="selected.length"></span> lead(s) selected
                    </div>
                    <div class="text-xs text-violet-600">You can assign all selected leads together.</div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="assignmentScope = 'selected'; showBulkModal = true"
                    class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700"
                >
                    Assign Selected
                </button>

                <button
                    type="button"
                    @click="selected = []; selectAllPage = false"
                    class="rounded-xl border border-violet-200 bg-white px-4 py-2.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100"
                >
                    Clear Selection
                </button>
            </div>
        </div>

        {{-- Leads Table --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Lead Directory</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Review ownership, priority and status at a glance.</p>
                </div>

                <div class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                    {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }}
                    <span class="text-slate-400">of</span>
                    {{ $leads->total() }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="w-12 px-5 py-4">
                                <input
                                    type="checkbox"
                                    x-model="selectAllPage"
                                    @change="togglePage(@js($leads->pluck('id')->map(fn ($id) => (int) $id)->values()))"
                                    class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                >
                            </th>
                            <th class="px-4 py-4">Lead</th>
                            <th class="px-4 py-4">Contact</th>
                            <th class="px-4 py-4">Source</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Priority</th>
                            <th class="px-4 py-4">Temperature</th>
                            <th class="px-4 py-4">Team</th>
                            <th class="px-4 py-4">Owner</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($leads as $lead)
                            @php
                                $priorityClasses = match ($lead->priority) {
                                    'hot', 'urgent' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    'high' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    'low' => 'bg-slate-100 text-slate-600 ring-slate-200',
                                    default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                };

                                $temperatureClasses = match ($lead->temperature) {
                                    'hot' => 'bg-rose-50 text-rose-700',
                                    'warm' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-sky-50 text-sky-700',
                                };

                                $initials = collect(explode(' ', trim($lead->name)))
                                    ->filter()
                                    ->map(fn ($part) => mb_substr($part, 0, 1))
                                    ->take(2)
                                    ->implode('');
                            @endphp

                            <tr class="group transition hover:bg-violet-50/40">
                                <td class="px-5 py-4 align-middle">
                                    <input
                                        type="checkbox"
                                        value="{{ $lead->id }}"
                                        x-model.number="selected"
                                        class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                    >
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-100 to-blue-100 text-xs font-bold uppercase text-violet-700">
                                            {{ $initials ?: 'L' }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-slate-900">{{ $lead->name }}</div>
                                            <div class="mt-0.5 truncate text-xs text-slate-500">{{ $lead->company_name ?: 'Individual Lead' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-800">{{ $lead->mobile }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $lead->city ?: 'City not added' }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $lead->source?->name ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                        {{ $lead->status?->name ?? 'New' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize ring-1 ring-inset {{ $priorityClasses }}">
                                        {{ $lead->priority }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $temperatureClasses }}">
                                        {{ $lead->temperature }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="font-medium text-slate-700">{{ $lead->team?->name ?? '—' }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    @if ($lead->assignedUser)
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-[11px] font-bold text-white">
                                                {{ mb_substr($lead->assignedUser->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">{{ $lead->assignedUser->name }}</div>
                                                @if ($lead->assignedUser->employee_code)
                                                    <div class="text-xs text-slate-500">{{ $lead->assignedUser->employee_code }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Unassigned
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <a
                                        href="{{ route('leads.show', $lead) }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-violet-600"
                                    >
                                        Open
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-20 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="11" cy="11" r="7"/>
                                            <path d="m20 20-3.5-3.5"/>
                                        </svg>
                                    </div>
                                    <div class="mt-4 text-lg font-bold text-slate-700">No leads found</div>
                                    <div class="mt-1 text-sm text-slate-400">Change the filters or create a new lead.</div>
                                    <a
                                        href="{{ route('leads.create') }}"
                                        class="mt-5 inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14M5 12h14"/>
                                        </svg>
                                        Create Lead
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    {{ $leads->links() }}
                </div>
            @endif
        </section>

        {{-- Bulk Assignment Modal --}}
        <div
            x-show="showBulkModal"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
            @keydown.escape.window="showBulkModal = false"
        >
            <div
                class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl"
                @click.outside="showBulkModal = false"
                x-transition
            >
                <form
                    method="POST"
                    action="{{ route('leads.bulk-assign') }}"
                    @submit="
                        if (assignmentScope === 'selected' && selected.length === 0) {
                            $event.preventDefault();
                            alert('Please select at least one lead.');
                        }
                    "
                >
                    @csrf

                    <div class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-900 px-6 py-6 text-white">
                        <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-violet-400/20 blur-3xl"></div>

                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <div class="mb-2 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-violet-100">
                                    Lead Assignment
                                </div>
                                <h2 class="text-xl font-bold">Bulk Assign Leads</h2>
                                <p class="mt-1 text-sm text-slate-300">Assign selected or filtered leads to an employee.</p>
                            </div>

                            <button
                                type="button"
                                @click="showBulkModal = false"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-white/20"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6 p-6">
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
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">

                        <div>
                            <div class="mb-3 text-sm font-bold text-slate-800">Assignment Scope</div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label
                                    class="cursor-pointer rounded-2xl border p-4 transition"
                                    :class="assignmentScope === 'selected'
                                        ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-100'
                                        : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <div class="flex gap-3">
                                        <input type="radio" value="selected" x-model="assignmentScope" class="mt-1 text-violet-600 focus:ring-violet-500">

                                        <div>
                                            <div class="font-bold text-slate-900">Selected Leads</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                <span x-text="selected.length"></span> selected lead(s)
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label
                                    class="cursor-pointer rounded-2xl border p-4 transition"
                                    :class="assignmentScope === 'filtered'
                                        ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-100'
                                        : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <div class="flex gap-3">
                                        <input type="radio" value="filtered" x-model="assignmentScope" class="mt-1 text-violet-600 focus:ring-violet-500">

                                        <div>
                                            <div class="font-bold text-slate-900">All Filtered Leads</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $leads->total() }} matching lead(s)</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700">
                                Assign Employee <span class="text-rose-500">*</span>
                            </span>

                            <select
                                name="assigned_to"
                                required
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-violet-500 focus:bg-white focus:ring-violet-100"
                            >
                                <option value="">Select Employee</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} @if ($user->employee_code) ({{ $user->employee_code }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700">
                                Assignment Reason <span class="text-rose-500">*</span>
                            </span>

                            <textarea
                                name="reason"
                                rows="4"
                                required
                                placeholder="Example: Assigning website leads for today's calling..."
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-violet-500 focus:bg-white focus:ring-violet-100"
                            ></textarea>
                        </label>

                        <div
                            x-show="assignmentScope === 'filtered'"
                            class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                        >
                            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v5M12 16h.01"/>
                            </svg>
                            <div>
                                All <strong>{{ $leads->total() }}</strong> leads matching the current filters may be assigned.
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="showBulkModal = false"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-200 transition hover:bg-violet-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M19 8v6M22 11h-6"/>
                            </svg>
                            Assign Leads
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection