@extends('layouts.crm', [
    'title' => 'Leads',
])

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-5" x-data="{
        selected: [],
        selectAllPage: false,
        showBulkModal: false,
        assignmentScope: 'selected',
        showFilters: false,

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

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Leads
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    @if ($hasFullAccess)
                        Manage, filter and assign company CRM leads.
                    @elseif ($isTeamLeader)
                        View your own leads and leads assigned to employees in your team.
                    @else
                        View and manage leads assigned to you.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                <button type="button" @click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M7 12h10M10 18h4" />
                    </svg>

                    Filters
                </button>

                @if ($hasFullAccess)
                    <button type="button"
                        @click="
                        assignmentScope = 'selected';
                        showBulkModal = true
                    "
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Bulk Assign
                    </button>

                    <a href="{{ route('leads.import.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Import
                    </a>
                @endif

                <a href="{{ route('leads.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    Create Lead
                </a>

            </div>
        </div>

        {{-- Filters --}}
        <section x-show="showFilters" x-cloak class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('leads.index') }}">
                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- Search --}}
                    <label class="block sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Search
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Name, mobile, company, email or city"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    {{-- Status --}}
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Status
                        </span>

                        <select name="status"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All statuses</option>

                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected((string) request('status') === (string) $status->id)>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Source --}}
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Created From
                        </span>

                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    {{-- Date To --}}
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Created To
                        </span>

                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                </div>

                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-500">
                        {{ number_format($leads->total()) }} matching leads
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('leads.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Clear
                        </a>

                        <button type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Selected Bar --}}
        @if ($hasFullAccess)
            <div x-show="selected.length > 0" x-cloak
                class="flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
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
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-900">
                        Lead List
                    </h2>

                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }}
                        of {{ $leads->total() }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1120px] text-sm">
                    <thead class="bg-slate-50">
                        <tr
                            class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            @if ($hasFullAccess)
                                <th class="w-12 px-4 py-3">
                                    <input type="checkbox" x-model="selectAllPage"
                                        @change="
                                        togglePage(
                                            @js($leads->pluck('id')->map(fn($id) => (int) $id)->values())
                                        )
                                    "
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                </th>
                            @endif
                            <th class="px-4 py-3">Lead</th>
                            <th class="px-4 py-3">Mobile</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Temperature</th>
                            <th class="px-4 py-3">Team</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
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

                            <tr class="hover:bg-slate-50">
                                @if ($hasFullAccess)
                                    <td class="px-4 py-3">
                                        <input type="checkbox" value="{{ $lead->id }}" x-model.number="selected"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                @endif

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">
                                        {{ $lead->name }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->company_name ?: 'Individual Lead' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">
                                        {{ $lead->mobile }}
                                    </div>

                                    @if ($lead->city)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $lead->city }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    {{ $lead->source?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {{ $lead->status?->name ?? 'New' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $priorityClass }}">
                                        {{ $lead->priority }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium capitalize {{ $temperatureClass }}">
                                        {{ $lead->temperature }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    {{ $lead->team?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
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

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('leads.show', $lead) }}"
                                        class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
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
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $leads->links() }}
                </div>
            @endif
        </section>

        {{-- Bulk Assignment Modal --}}
        @if ($hasFullAccess)
            <div x-show="showBulkModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                @keydown.escape.window="showBulkModal = false">
                <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-2xl"
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
                                <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
                                <span class="mb-1.5 block text-sm font-medium text-slate-700">
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
