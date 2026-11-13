
@extends('layouts.crm')

@section('title', $title)

@section('content')

@php
    $statusLabels = [
        'answered' => 'Answered',
        'not_answered' => 'Not Answered',
        'not_answer' => 'Not Answer',
        'busy' => 'Busy',
        'demo_sent' => 'Demo Sent',
        'not_interested' => 'Not Interested',
        'converted' => 'Converted',
    ];

    $statusColors = [
        'answered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'converted' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'demo_sent' => 'bg-blue-50 text-blue-700 border-blue-200',
        'not_answered' => 'bg-amber-50 text-amber-700 border-amber-200',
        'not_answer' => 'bg-amber-50 text-amber-700 border-amber-200',
        'busy' => 'bg-orange-50 text-orange-700 border-orange-200',
        'not_interested' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];

    $defaultColor = 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<div class="mx-auto max-w-[1500px] space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Calling CRM
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View leads, filter by category and user,
                and check complete call history.
            </p>
        </div>

        <span class="inline-flex items-center gap-2 self-start rounded-lg
                     border border-blue-200 bg-blue-50
                     px-4 py-2.5 text-sm font-semibold text-blue-700">

            <i data-lucide="headset" class="h-4 w-4"></i>
            Calling Records
        </span>

    </div>


    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-500">
                Total Leads
            </p>

            <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format($contacts->total()) }}
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Matching current filters
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-500">
                Leads on This Page
            </p>

            <h2 class="mt-2 text-3xl font-bold text-blue-600">
                {{ $contacts->count() }}
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Current page records
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-500">
                Total Call Histories
            </p>

            <h2 class="mt-2 text-3xl font-bold text-emerald-600">
                {{ number_format($contacts->sum('call_histories_count')) }}
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                History records on this page
            </p>
        </div>

    </div>


    {{-- FILTER SECTION --}}
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

        <div class="mb-4 flex items-center gap-2">

            <i data-lucide="filter" class="h-5 w-5 text-blue-600"></i>

            <h2 class="font-bold text-slate-900">
                Search & Filters
            </h2>

        </div>

        <form
            method="GET"
            action="{{ route('calling-crm.index') }}"
            class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12"
        >

            {{-- SEARCH --}}
            <div class="xl:col-span-4">

                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Search Lead
                </label>

                <div class="relative">

                    <i data-lucide="search"
                       class="pointer-events-none absolute left-3 top-1/2
                              h-4 w-4 -translate-y-1/2 text-slate-400"></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, phone, lead ID..."
                        class="w-full rounded-lg border-slate-300
                               py-2.5 pl-10 pr-3 text-sm
                               focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>


            {{-- CATEGORY FILTER --}}
            <div class="xl:col-span-2">

                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Category
                </label>

                <select
                    name="category"
                    class="w-full rounded-lg border-slate-300 py-2.5
                           text-sm focus:border-blue-500 focus:ring-blue-500"
                >

                    <option value="">All Categories</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category }}"
                            @selected(request('category') == $category)
                        >
                            {{ $category }}
                        </option>
                    @endforeach

                </select>

            </div>


            {{-- USER FILTER --}}
            <div class="xl:col-span-2">

                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Assigned User
                </label>

                @if($isSuperAdmin)

                    <select
                        name="user_id"
                        class="w-full rounded-lg border-slate-300 py-2.5
                               text-sm focus:border-blue-500 focus:ring-blue-500"
                    >

                        <option value="">All Users</option>

                        @foreach($users as $assignedUser)

                            <option
                                value="{{ $assignedUser->id }}"
                                @selected((string) request('user_id') === (string) $assignedUser->id)
                            >
                                {{ $assignedUser->name }}
                                (#{{ $assignedUser->id }})
                            </option>

                        @endforeach

                    </select>

                @else

                    <input
                        type="text"
                        value="{{ auth()->user()->name }}"
                        readonly
                        class="w-full rounded-lg border-slate-200
                               bg-slate-50 py-2.5 text-sm text-slate-600"
                    >

                @endif

            </div>


            {{-- STATUS FILTER --}}
            <div class="xl:col-span-2">

                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Call Status
                </label>

                <select
                    name="status"
                    class="w-full rounded-lg border-slate-300 py-2.5
                           text-sm focus:border-blue-500 focus:ring-blue-500"
                >

                    <option value="">All Statuses</option>

                    @foreach($statuses as $status)

                        <option
                            value="{{ $status }}"
                            @selected(request('status') == $status)
                        >
                            {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- FILTER BUTTONS --}}
            <div class="flex items-end gap-2 xl:col-span-2">

                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-slate-900
                           px-3 py-2.5 text-sm font-semibold
                           text-white hover:bg-slate-800"
                >
                    Filter
                </button>

                <a
                    href="{{ route('calling-crm.index') }}"
                    class="rounded-lg border border-slate-300
                           px-3 py-2.5 text-sm font-semibold
                           text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>

            </div>

        </form>

    </section>


    {{-- LEADS TABLE --}}
    <section class="overflow-hidden rounded-xl border
                    border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-2 border-b border-slate-200
                    px-5 py-4 sm:flex-row sm:items-center
                    sm:justify-between">

            <div>

                <h2 class="font-bold text-slate-900">
                    Calling Lead List
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $contacts->firstItem() ?? 0 }}
                    –
                    {{ $contacts->lastItem() ?? 0 }}
                    of
                    {{ $contacts->total() }}
                    leads
                </p>

            </div>

            <span class="self-start rounded-full bg-blue-50
                         px-3 py-1 text-xs font-semibold text-blue-700">
                Non-Pending Leads
            </span>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200
                               text-left text-xs font-semibold
                               uppercase tracking-wide text-slate-500">

                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Lead Details</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Assigned User</th>
                        <th class="px-4 py-3">Call Status</th>
                        <th class="px-4 py-3">Updated At</th>
                        <th class="px-4 py-3">Calls</th>
                        <th class="px-4 py-3 text-right">Action</th>

                    </tr>

                </thead>


                @forelse($contacts as $contact)

                    @php
                        $currentStatus = $contact->call_status ?? '';

                        $statusText = $statusLabels[$currentStatus]
                            ?? ucwords(str_replace('_', ' ', $currentStatus));

                        $badgeClass = $statusColors[$currentStatus]
                            ?? $defaultColor;

                        $historyRowId = 'history-row-' . $contact->id;
                    @endphp


                    <tbody class="border-b border-slate-100">

                        {{-- LEAD ROW --}}
                        <tr class="transition hover:bg-slate-50">

                            <td class="px-4 py-4 text-slate-500">
                                {{ $contacts->firstItem() + $loop->index }}
                            </td>


                            {{-- LEAD DETAILS --}}
                            <td class="px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0
                                                items-center justify-center
                                                rounded-lg bg-blue-50
                                                font-bold text-blue-600">

                                        {{ mb_strtoupper(mb_substr($contact->name ?: 'L', 0, 1)) }}

                                    </div>

                                    <div>

                                        <div class="max-w-[200px] truncate
                                                    font-semibold text-slate-900">

                                            {{ $contact->name ?: 'Unnamed Lead' }}

                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">

                                            Lead ID: {{ $contact->id }}

                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- PHONE --}}
                            <td class="px-4 py-4">

                                @if($contact->phone)

                                    <a
                                        href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->phone) }}"
                                        class="font-medium text-blue-600 hover:underline"
                                    >
                                        {{ $contact->phone }}
                                    </a>

                                @else

                                    <span class="text-slate-400">—</span>

                                @endif

                            </td>


                            {{-- CATEGORY --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-lg
                                             bg-slate-100 px-3 py-1
                                             text-xs font-medium text-slate-700">

                                    {{ $contact->category ?: '—' }}

                                </span>

                            </td>


                            {{-- ASSIGNED USER --}}
                            <td class="px-4 py-4">

                                <div class="flex items-center gap-2">

                                    <div class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-full bg-indigo-50
                                                text-xs font-bold text-indigo-700">

                                        {{ mb_strtoupper(mb_substr($contact->assignedUser?->name ?? 'U', 0, 1)) }}

                                    </div>

                                    <div>

                                        <p class="font-semibold text-slate-800">

                                            {{ $contact->assignedUser?->name ?? 'Unknown User' }}

                                        </p>

                                        <p class="text-xs text-slate-400">

                                            User ID: {{ $contact->assigned_to ?? '—' }}

                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- CURRENT CALL STATUS --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-full border
                                             px-3 py-1 text-xs font-semibold
                                             {{ $badgeClass }}">

                                    {{ $statusText }}

                                </span>

                            </td>


                            {{-- UPDATED AT --}}
                            <td class="px-4 py-4 text-slate-600">

                                @if($contact->updated_at)

                                    <div class="whitespace-nowrap">

                                        {{ \Carbon\Carbon::parse($contact->updated_at)->format('d M Y') }}

                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">

                                        {{ \Carbon\Carbon::parse($contact->updated_at)->format('h:i A') }}

                                    </div>

                                @else

                                    —

                                @endif

                            </td>


                            {{-- TOTAL CALLS --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-lg
                                             bg-indigo-50 px-3 py-1
                                             text-xs font-bold text-indigo-700">

                                    {{ $contact->call_histories_count }} Calls

                                </span>

                            </td>


                            {{-- VIEW HISTORY --}}
                            <td class="px-4 py-4 text-right">

                                <button
                                    type="button"
                                    data-history-toggle
                                    data-target="{{ $historyRowId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $historyRowId }}"
                                    class="inline-flex items-center gap-2
                                           rounded-lg border border-blue-200
                                           bg-blue-50 px-3 py-2
                                           text-xs font-semibold text-blue-700
                                           transition hover:bg-blue-100"
                                >

                                    <i data-lucide="history" class="h-4 w-4"></i>

                                    <span data-history-label>
                                        View History
                                    </span>

                                </button>

                            </td>

                        </tr>                            
                        {{-- ========================================================== --}}
                        {{-- EXPANDED CALL HISTORY - TABLE FORMAT --}}
                        {{-- ========================================================== --}}

                        <tr
                            id="{{ $historyRowId }}"
                            data-history-row
                            class="hidden bg-slate-50/80"
                        >
                            <td colspan="9" class="p-3 sm:p-5">

                                <div class="overflow-hidden rounded-xl
                                            border border-slate-200
                                            bg-white shadow-sm">

                                    {{-- HISTORY HEADER --}}
                                    <div class="flex flex-col gap-3
                                                border-b border-slate-200
                                                bg-slate-50 px-5 py-4
                                                sm:flex-row sm:items-center
                                                sm:justify-between">

                                        <div>
                                            <div class="flex items-center gap-2">

                                                <i data-lucide="history"
                                                class="h-5 w-5 text-blue-600"></i>

                                                <h3 class="font-bold text-slate-900">
                                                    Complete Call History
                                                </h3>

                                            </div>

                                            <p class="mt-1 text-xs text-slate-500">

                                                {{ $contact->name ?: 'Lead' }}

                                                · Lead ID: {{ $contact->id }}

                                                · Phone: {{ $contact->phone ?: '—' }}

                                            </p>
                                        </div>

                                        <span class="inline-flex self-start
                                            rounded-full border border-blue-200
                                            bg-blue-50 px-3 py-1
                                            text-xs font-semibold text-blue-700">

                                            {{ $contact->call_histories_count }}
                                            Call Records

                                        </span>

                                    </div>


                                    {{-- HISTORY TABLE --}}
                                    <div class="overflow-x-auto">

                                        <table class="w-full min-w-[1050px] text-sm">

                                            <thead class="bg-slate-50">

                                                <tr class="border-b border-slate-200
                                                        text-left text-xs font-semibold
                                                        uppercase tracking-wide text-slate-500">

                                                    <th class="w-14 px-4 py-3">
                                                        #
                                                    </th>

                                                    <th class="px-4 py-3">
                                                        Called By
                                                    </th>

                                                    <th class="px-4 py-3">
                                                        Call Status
                                                    </th>

                                                    <th class="px-4 py-3">
                                                        Call Date & Time
                                                    </th>

                                                    <th class="px-4 py-3">
                                                        Duration
                                                    </th>

                                                    <th class="px-4 py-3">
                                                        Remarks
                                                    </th>

                                                    <th class="px-4 py-3 text-right">
                                                        Recording
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody class="divide-y divide-slate-100">

                                                @forelse($contact->callHistories as $history)

                                                    @php

                                                        // Status
                                                        $historyStatus = $history->call_status ?? '';

                                                        $historyStatusText =
                                                            $statusLabels[$historyStatus]
                                                            ?? ucwords(str_replace('_', ' ', $historyStatus));

                                                        $historyColor =
                                                            $statusColors[$historyStatus]
                                                            ?? $defaultColor;


                                                        // Duration
                                                        $seconds = $history->call_duration;

                                                        $durationText = $seconds === null
                                                            ? '—'
                                                            : sprintf(
                                                                '%02d:%02d:%02d',
                                                                intdiv((int) $seconds, 3600),
                                                                intdiv(((int) $seconds % 3600), 60),
                                                                ((int) $seconds % 60)
                                                            );


                                                        // Call Date
                                                        $callDate = $history->call_at
                                                            ? \Carbon\Carbon::parse($history->call_at)
                                                            : null;


                                                        // Recording URL
                                                        $recordingUrl = $history->recording
                                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($history->recording)
                                                            : null;

                                                    @endphp


                                                    <tr class="transition hover:bg-blue-50/40">

                                                        {{-- SERIAL NUMBER --}}
                                                        <td class="px-4 py-4 text-slate-500">

                                                            {{ $loop->iteration }}

                                                        </td>


                                                        {{-- CALLED BY USER --}}
                                                        <td class="px-4 py-4">

                                                            <div class="flex items-center gap-3">

                                                                <div class="flex h-9 w-9
                                                                            shrink-0 items-center
                                                                            justify-center rounded-full
                                                                            bg-indigo-50
                                                                            text-xs font-bold
                                                                            text-indigo-700">

                                                                    {{
                                                                        mb_strtoupper(
                                                                            mb_substr(
                                                                                $history->user?->name ?? 'U',
                                                                                0,
                                                                                1
                                                                            )
                                                                        )
                                                                    }}

                                                                </div>

                                                                <div class="min-w-0">

                                                                    <div class="max-w-[180px]
                                                                                truncate font-semibold
                                                                                text-slate-900">

                                                                        {{ $history->user?->name ?? 'Unknown User' }}

                                                                    </div>

                                                                    <div class="mt-0.5 text-xs
                                                                                text-slate-400">

                                                                        User ID:
                                                                        {{ $history->user_id ?? '—' }}

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        </td>


                                                        {{-- CALL STATUS --}}
                                                        <td class="px-4 py-4">

                                                            <span class="inline-flex rounded-full
                                                                        border px-3 py-1
                                                                        text-xs font-semibold
                                                                        {{ $historyColor }}">

                                                                {{ $historyStatusText ?: 'Unknown' }}

                                                            </span>

                                                        </td>


                                                        {{-- CALL DATE & TIME --}}
                                                        <td class="px-4 py-4 text-slate-600">

                                                            @if($callDate)

                                                                <div class="whitespace-nowrap
                                                                            font-medium text-slate-800">

                                                                    {{ $callDate->format('d M Y') }}

                                                                </div>

                                                                <div class="mt-0.5
                                                                            text-xs text-slate-400">

                                                                    {{ $callDate->format('h:i A') }}

                                                                </div>

                                                            @else

                                                                <span class="text-slate-400">
                                                                    —
                                                                </span>

                                                            @endif

                                                        </td>


                                                        {{-- CALL DURATION --}}
                                                        <td class="px-4 py-4">

                                                            <span class="inline-flex items-center
                                                                        gap-1.5 rounded-lg
                                                                        bg-slate-100 px-2.5 py-1.5
                                                                        font-semibold text-slate-700">

                                                                <i data-lucide="clock-3"
                                                                class="h-3.5 w-3.5"></i>

                                                                {{ $durationText }}

                                                            </span>

                                                        </td>


                                                        {{-- REMARKS --}}
                                                        <td class="px-4 py-4">

                                                            <div class="max-w-[300px]
                                                                        whitespace-pre-wrap
                                                                        break-words text-slate-600">

                                                                {{ $history->remarks ?: 'No remarks added' }}

                                                            </div>

                                                        </td>


                                                        {{-- RECORDING --}}
                                                        <td class="px-4 py-4 text-right">

                                                            @if($recordingUrl)

                                                                <div class="flex flex-col
                                                                            items-end gap-2">

                                                                    <audio
                                                                        controls
                                                                        preload="none"
                                                                        class="h-9 w-[200px]"
                                                                        src="{{ $recordingUrl }}"
                                                                    >
                                                                        Your browser does not support audio.
                                                                    </audio>

                                                                    <a
                                                                        href="{{ $recordingUrl }}"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        class="inline-flex items-center
                                                                            gap-1 text-xs
                                                                            font-semibold text-blue-600
                                                                            hover:underline"
                                                                    >

                                                                        <i data-lucide="external-link"
                                                                        class="h-3.5 w-3.5"></i>

                                                                        Open Recording

                                                                    </a>

                                                                </div>

                                                            @else

                                                                <span class="text-xs text-slate-400">
                                                                    No Recording
                                                                </span>

                                                            @endif

                                                        </td>

                                                    </tr>


                                                @empty

                                                    {{-- EMPTY HISTORY --}}
                                                    <tr>

                                                        <td colspan="7"
                                                            class="px-5 py-14 text-center">

                                                            <div class="mx-auto flex h-12 w-12
                                                                        items-center justify-center
                                                                        rounded-full bg-slate-100">

                                                                <i data-lucide="history"
                                                                class="h-6 w-6 text-slate-400"></i>

                                                            </div>

                                                            <h3 class="mt-3 font-semibold
                                                                    text-slate-700">

                                                                No Call History Found

                                                            </h3>

                                                            <p class="mt-1 text-sm text-slate-500">

                                                                No call records available
                                                                for this lead.

                                                            </p>

                                                        </td>

                                                    </tr>

                                                @endforelse

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            </td>

                        </tr>


                    </tbody>


                @empty

                    <tbody>

                        <tr>

                            <td colspan="9" class="px-5 py-16 text-center">

                                <i data-lucide="users"
                                   class="mx-auto h-10 w-10 text-slate-400"></i>

                                <h3 class="mt-4 font-semibold text-slate-700">
                                    No Calling Leads Found
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    No leads match the selected filters.
                                </p>

                                <a
                                    href="{{ route('calling-crm.index') }}"
                                    class="mt-4 inline-flex rounded-lg
                                           bg-blue-600 px-4 py-2
                                           text-sm font-semibold text-white
                                           hover:bg-blue-700"
                                >
                                    Clear Filters
                                </a>

                            </td>

                        </tr>

                    </tbody>

                @endforelse

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($contacts->hasPages())

            <div class="border-t border-slate-200 px-5 py-4">

                {{ $contacts->links() }}

            </div>

        @endif

    </section>

</div>


{{-- EXPAND / COLLAPSE HISTORY --}}
<script>
(function () {

    function initializeCallingHistory() {

        const buttons = document.querySelectorAll(
            '[data-history-toggle]'
        );

        buttons.forEach(function (button) {

            if (button.dataset.initialized === '1') {
                return;
            }

            button.dataset.initialized = '1';

            button.addEventListener('click', function () {

                const rowId = button.dataset.target;
                const historyRow = document.getElementById(rowId);

                if (!historyRow) {
                    return;
                }

                const shouldOpen = historyRow.classList.contains('hidden');

                // Close all histories
                document.querySelectorAll('[data-history-row]')
                    .forEach(function (row) {
                        row.classList.add('hidden');
                    });

                // Reset buttons
                document.querySelectorAll('[data-history-toggle]')
                    .forEach(function (btn) {

                        btn.setAttribute('aria-expanded', 'false');

                        const label = btn.querySelector('[data-history-label]');

                        if (label) {
                            label.textContent = 'View History';
                        }

                    });

                // Open selected history
                if (shouldOpen) {

                    historyRow.classList.remove('hidden');

                    button.setAttribute('aria-expanded', 'true');

                    const label = button.querySelector('[data-history-label]');

                    if (label) {
                        label.textContent = 'Hide History';
                    }

                }

            });

        });

        // Load Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeCallingHistory
        );
    } else {
        initializeCallingHistory();
    }

})();
</script>

@endsection
