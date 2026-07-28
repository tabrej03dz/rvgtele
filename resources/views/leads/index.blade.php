@extends('layouts.crm', [
    'title' => 'Leads',
])

@section('content')
<div
    class="space-y-5"
    x-data="{
        selected: [],
        selectAllPage: false,
        showBulkModal: false,
        assignmentScope: 'selected',

        togglePage(ids) {
            if (this.selectAllPage) {
                this.selected = [...new Set([
                    ...this.selected,
                    ...ids
                ])];
            } else {
                this.selected = this.selected.filter(
                    id => !ids.includes(id)
                );
            }
        }
    }"
>
    {{-- Success Message --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <ul class="list-disc space-y-1 pl-5 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Leads
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Search, filter, import aur bulk assign leads.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                @click="
                    assignmentScope = 'selected';
                    showBulkModal = true
                "
                class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
            >
                Bulk Assign
            </button>

            <a
                href="{{ route('leads.import.create') }}"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
            >
                Import Excel
            </a>

            <a
                href="{{ route('leads.create') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Create Lead
            </a>
        </div>
    </div>

    {{-- Advanced Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <form
            method="GET"
            action="{{ route('leads.index') }}"
        >
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="block xl:col-span-2">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Search
                    </span>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, mobile, company, email or city"
                        class="w-full rounded-lg border-slate-300"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </span>

                    <select
                        name="status"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Statuses
                        </option>

                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->id }}"
                                @selected(
                                    (string) request('status') ===
                                    (string) $status->id
                                )
                            >
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Source
                    </span>

                    <select
                        name="source"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Sources
                        </option>

                        @foreach ($sources as $source)
                            <option
                                value="{{ $source->id }}"
                                @selected(
                                    (string) request('source') ===
                                    (string) $source->id
                                )
                            >
                                {{ $source->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Assigned Employee
                    </span>

                    <select
                        name="assigned_to"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Employees
                        </option>

                        <option
                            value="unassigned"
                            @selected(
                                request('assigned_to') ===
                                'unassigned'
                            )
                        >
                            Unassigned Leads
                        </option>

                        @foreach ($users as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected(
                                    (string) request('assigned_to') ===
                                    (string) $user->id
                                )
                            >
                                {{ $user->name }}

                                @if ($user->employee_code)
                                    ({{ $user->employee_code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Team
                    </span>

                    <select
                        name="team_id"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Teams
                        </option>

                        @foreach ($teams as $team)
                            <option
                                value="{{ $team->id }}"
                                @selected(
                                    (string) request('team_id') ===
                                    (string) $team->id
                                )
                            >
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Priority
                    </span>

                    <select
                        name="priority"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Priorities
                        </option>

                        @foreach ([
                            'low',
                            'normal',
                            'high',
                            'urgent',
                            'hot',
                        ] as $priority)
                            <option
                                value="{{ $priority }}"
                                @selected(
                                    request('priority') ===
                                    $priority
                                )
                            >
                                {{ ucfirst($priority) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Temperature
                    </span>

                    <select
                        name="temperature"
                        class="w-full rounded-lg border-slate-300"
                    >
                        <option value="">
                            All Temperatures
                        </option>

                        @foreach ([
                            'cold',
                            'warm',
                            'hot',
                        ] as $temperature)
                            <option
                                value="{{ $temperature }}"
                                @selected(
                                    request('temperature') ===
                                    $temperature
                                )
                            >
                                {{ ucfirst($temperature) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Created From
                    </span>

                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full rounded-lg border-slate-300"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Created To
                    </span>

                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="w-full rounded-lg border-slate-300"
                    >
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
                <div class="text-sm text-slate-500">
                    Total matching:
                    <strong class="text-slate-800">
                        {{ $leads->total() }}
                    </strong>
                </div>

                <div class="flex gap-2">
                    <a
                        href="{{ route('leads.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        Clear Filters
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-800 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-900"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Selected Action Bar --}}
    <div
        x-show="selected.length > 0"
        x-cloak
        class="flex flex-col gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="font-semibold text-violet-800">
            <span x-text="selected.length"></span>
            lead(s) selected
        </div>

        <div class="flex gap-2">
            <button
                type="button"
                @click="
                    assignmentScope = 'selected';
                    showBulkModal = true
                "
                class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white"
            >
                Assign Selected
            </button>

            <button
                type="button"
                @click="
                    selected = [];
                    selectAllPage = false
                "
                class="rounded-lg border border-violet-300 bg-white px-4 py-2 text-sm font-medium text-violet-700"
            >
                Clear Selection
            </button>
        </div>
    </div>

    {{-- Lead Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left">
                            <input
                                type="checkbox"
                                x-model="selectAllPage"
                                @change="togglePage(
                                    @js(
                                        $leads->pluck('id')
                                            ->map(fn ($id) => (int) $id)
                                            ->values()
                                    )
                                )"
                                class="rounded border-slate-300"
                            >
                        </th>

                        <th class="px-4 py-3 text-left">
                            Lead
                        </th>

                        <th class="px-4 py-3 text-left">
                            Mobile
                        </th>

                        <th class="px-4 py-3 text-left">
                            Source
                        </th>

                        <th class="px-4 py-3 text-left">
                            Status
                        </th>

                        <th class="px-4 py-3 text-left">
                            Priority
                        </th>

                        <th class="px-4 py-3 text-left">
                            Temperature
                        </th>

                        <th class="px-4 py-3 text-left">
                            Team
                        </th>

                        <th class="px-4 py-3 text-left">
                            Owner
                        </th>

                        <th class="px-4 py-3 text-right">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    value="{{ $lead->id }}"
                                    x-model.number="selected"
                                    class="rounded border-slate-300"
                                >
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">
                                    {{ $lead->name }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ $lead->company_name ?: '—' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ $lead->mobile }}
                                </div>

                                @if ($lead->city)
                                    <div class="text-xs text-slate-500">
                                        {{ $lead->city }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $lead->source?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {{ $lead->status?->name ?? 'New' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $priorityClasses = match ($lead->priority) {
                                        'hot',
                                        'urgent' =>
                                            'bg-rose-100 text-rose-700',

                                        'high' =>
                                            'bg-amber-100 text-amber-700',

                                        default =>
                                            'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize {{ $priorityClasses }}">
                                    {{ $lead->priority }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="capitalize">
                                    {{ $lead->temperature }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
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
                                    <span class="font-medium text-amber-600">
                                        Unassigned
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="rounded-lg bg-blue-50 px-3 py-1.5 font-medium text-blue-700 hover:bg-blue-100"
                                >
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="10"
                                class="px-5 py-16 text-center"
                            >
                                <div class="font-semibold text-slate-600">
                                    No leads found
                                </div>

                                <div class="mt-1 text-sm text-slate-400">
                                    Filter change karein ya new lead create karein.
                                </div>
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
    </div>

    {{-- Bulk Assignment Modal --}}
    <div
        x-show="showBulkModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
        @keydown.escape.window="showBulkModal = false"
    >
        <div
            class="w-full max-w-xl rounded-2xl bg-white shadow-2xl"
            @click.outside="showBulkModal = false"
        >
            <form
                method="POST"
                action="{{ route('leads.bulk-assign') }}"
                @submit="
                    if (
                        assignmentScope === 'selected' &&
                        selected.length === 0
                    ) {
                        $event.preventDefault();
                        alert('Please select at least one lead.');
                    }
                "
            >
                @csrf

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Bulk Assign Leads
                        </h2>

                        <p class="text-sm text-slate-500">
                            Selected ya current filtered leads assign karein.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showBulkModal = false"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <input
                        type="hidden"
                        name="assignment_scope"
                        x-model="assignmentScope"
                    >

                    {{-- Selected IDs --}}
                    <template
                        x-for="leadId in selected"
                        :key="leadId"
                    >
                        <input
                            type="hidden"
                            name="lead_ids[]"
                            :value="leadId"
                        >
                    </template>

                    {{-- Current Filters --}}
                    <input
                        type="hidden"
                        name="search"
                        value="{{ request('search') }}"
                    >

                    <input
                        type="hidden"
                        name="status"
                        value="{{ request('status') }}"
                    >

                    <input
                        type="hidden"
                        name="source"
                        value="{{ request('source') }}"
                    >

                    <input
                        type="hidden"
                        name="filter_assigned_to"
                        value="{{ request('assigned_to') }}"
                    >

                    <input
                        type="hidden"
                        name="team_id"
                        value="{{ request('team_id') }}"
                    >

                    <input
                        type="hidden"
                        name="priority"
                        value="{{ request('priority') }}"
                    >

                    <input
                        type="hidden"
                        name="temperature"
                        value="{{ request('temperature') }}"
                    >

                    <input
                        type="hidden"
                        name="date_from"
                        value="{{ request('date_from') }}"
                    >

                    <input
                        type="hidden"
                        name="date_to"
                        value="{{ request('date_to') }}"
                    >

                    {{-- Scope --}}
                    <div>
                        <div class="mb-2 text-sm font-semibold text-slate-700">
                            Assignment Scope
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-4">
                                <div class="flex gap-3">
                                    <input
                                        type="radio"
                                        value="selected"
                                        x-model="assignmentScope"
                                    >

                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            Selected Leads
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            <span x-text="selected.length"></span>
                                            selected lead(s)
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer rounded-xl border border-slate-200 p-4">
                                <div class="flex gap-3">
                                    <input
                                        type="radio"
                                        value="filtered"
                                        x-model="assignmentScope"
                                    >

                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            All Filtered Leads
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $leads->total() }}
                                            matching lead(s)
                                        </div>
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

                        <select
                            name="assigned_to"
                            required
                            class="w-full rounded-lg border-slate-300"
                        >
                            <option value="">
                                Select Employee
                            </option>

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

                        <textarea
                            name="reason"
                            rows="3"
                            required
                            placeholder="Example: New website leads assigned for today's calling"
                            class="w-full rounded-lg border-slate-300"
                        ></textarea>
                    </label>

                    <div
                        x-show="assignmentScope === 'filtered'"
                        class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"
                    >
                        Current filters se match hone wali sabhi
                        {{ $leads->total() }} leads assign ho sakti hain.
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button
                        type="button"
                        @click="showBulkModal = false"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2 font-medium text-slate-700"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-violet-600 px-5 py-2 font-semibold text-white hover:bg-violet-700"
                    >
                        Assign Leads
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection