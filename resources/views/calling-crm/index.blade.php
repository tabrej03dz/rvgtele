
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

    {{-- PAGE HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Calling CRM
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View called leads, customer details and complete call history.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 self-start
                    rounded-lg border border-blue-200 bg-blue-50
                    px-4 py-2.5 text-sm font-semibold text-blue-700">

            <i data-lucide="headset" class="h-4 w-4"></i>
            Calling Records
        </div>
    </div>


    {{-- SUMMARY --}}
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
                Call Histories
            </p>

            <h2 class="mt-2 text-3xl font-bold text-emerald-600">
                {{ number_format($contacts->sum('call_histories_count')) }}
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Total histories on this page
            </p>
        </div>

    </div>


    {{-- SEARCH & FILTER --}}
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

        <form
            method="GET"
            action="{{ route('calling-crm.index') }}"
            class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12"
        >

            {{-- SEARCH --}}
            <div class="relative xl:col-span-5">

                <i data-lucide="search"
                   class="pointer-events-none absolute left-3 top-1/2
                          h-4 w-4 -translate-y-1/2 text-slate-400"></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search name, phone or lead ID..."
                    class="w-full rounded-lg border-slate-300
                           py-2.5 pl-10 pr-3 text-sm
                           focus:border-blue-500 focus:ring-blue-500"
                >
            </div>


            {{-- CATEGORY --}}
            <div class="xl:col-span-3">
                <select
                    name="category"
                    class="w-full rounded-lg border-slate-300
                           py-2.5 text-sm
                           focus:border-blue-500 focus:ring-blue-500"
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


            {{-- STATUS --}}
            <div class="xl:col-span-2">
                <select
                    name="status"
                    class="w-full rounded-lg border-slate-300
                           py-2.5 text-sm
                           focus:border-blue-500 focus:ring-blue-500"
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


            {{-- BUTTONS --}}
            <div class="flex gap-2 xl:col-span-2">

                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-slate-900
                           px-4 py-2.5 text-sm font-semibold text-white
                           hover:bg-slate-800"
                >
                    Search
                </button>

                <a
                    href="{{ route('calling-crm.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg border border-slate-300 bg-white
                           px-3 py-2.5 text-sm font-semibold
                           text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>

            </div>

        </form>
    </section>


    {{-- LEAD LIST --}}
    <section class="overflow-hidden rounded-xl border
                    border-slate-200 bg-white shadow-sm">

        <div class="flex items-center justify-between
                    border-b border-slate-200 px-5 py-4">

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
                </p>
            </div>

            <span class="rounded-full bg-blue-50
                         px-3 py-1 text-xs font-semibold text-blue-700">
                Non-Pending Leads
            </span>
        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200
                               text-left text-xs font-semibold
                               uppercase tracking-wide text-slate-500">

                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Call Status</th>
                        <th class="px-4 py-3">Updated At</th>
                        <th class="px-4 py-3">Total Calls</th>
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

                        $historyId = 'history-' . $contact->id;
                    @endphp

                    <tbody class="border-b border-slate-100">

                        {{-- MAIN LEAD ROW --}}
                        <tr class="transition hover:bg-slate-50">

                            <td class="px-4 py-4 text-slate-500">
                                {{ $contacts->firstItem() + $loop->index }}
                            </td>


                            {{-- LEAD NAME --}}
                            <td class="px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0
                                                items-center justify-center
                                                rounded-lg bg-blue-50
                                                font-bold text-blue-600">
                                        {{ mb_strtoupper(mb_substr($contact->name ?: 'L', 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">

                                        <p class="max-w-[200px] truncate
                                                  font-semibold text-slate-900">
                                            {{ $contact->name ?: 'Unnamed Lead' }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-slate-400">
                                            ID: {{ $contact->id }}
                                        </p>

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

                                <span class="rounded-lg bg-slate-100
                                             px-3 py-1 text-xs font-medium
                                             text-slate-700">
                                    {{ $contact->category ?: '—' }}
                                </span>

                            </td>


                            {{-- CALL STATUS --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-full border
                                             px-3 py-1 text-xs font-semibold
                                             {{ $badgeClass }}">
                                    {{ $statusText }}
                                </span>

                            </td>


                            {{-- LEAD UPDATED DATE --}}
                            <td class="px-4 py-4 text-slate-600">

                                @if($contact->updated_at)

                                    <p class="whitespace-nowrap">
                                        {{ $contact->updated_at->format('d M Y') }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ $contact->updated_at->format('h:i A') }}
                                    </p>

                                @else
                                    —
                                @endif

                            </td>


                            {{-- CALL COUNT --}}
                            <td class="px-4 py-4">

                                <span class="rounded-lg bg-indigo-50
                                             px-3 py-1 text-xs font-bold
                                             text-indigo-700">
                                    {{ $contact->call_histories_count }}
                                    Calls
                                </span>

                            </td>


                            {{-- ACTION --}}
                            <td class="px-4 py-4 text-right">

                                <button
                                    type="button"
                                    data-history-toggle
                                    data-target="{{ $historyId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $historyId }}"
                                    class="inline-flex items-center gap-2
                                           rounded-lg border border-blue-200
                                           bg-blue-50 px-3 py-2
                                           text-xs font-semibold text-blue-700
                                           hover:bg-blue-100"
                                >

                                    <i data-lucide="history" class="h-4 w-4"></i>

                                    <span data-label>View History</span>

                                    <i data-lucide="chevron-down"
                                       class="h-4 w-4"></i>

                                </button>

                            </td>

                        </tr>


                        {{-- EXPANDABLE CALL HISTORY --}}
                        <tr
                            id="{{ $historyId }}"
                            data-history-row
                            class="hidden bg-slate-50/80"
                        >

                            <td colspan="8" class="p-3 sm:p-5">

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

                                            <h3 class="font-bold text-slate-900">
                                                Complete Call History
                                            </h3>

                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $contact->name ?: 'Lead' }}
                                                · Lead ID: {{ $contact->id }}
                                            </p>

                                        </div>

                                        <span class="self-start rounded-full
                                                     border border-blue-200
                                                     bg-blue-50 px-3 py-1
                                                     text-xs font-semibold text-blue-700">
                                            {{ $contact->call_histories_count }}
                                            Records
                                        </span>

                                    </div>


                                    {{-- HISTORY CONTENT --}}
                                    @forelse($contact->callHistories as $history)

                                        @php
                                            $historyStatus = $history->call_status ?? '';

                                            $historyLabel = $statusLabels[$historyStatus]
                                                ?? ucwords(str_replace('_', ' ', $historyStatus));

                                            $historyClass = $statusColors[$historyStatus]
                                                ?? $defaultColor;

                                            $seconds = $history->call_duration;

                                            $durationText = $seconds === null
                                                ? '—'
                                                : sprintf(
                                                    '%02d:%02d:%02d',
                                                    intdiv((int) $seconds, 3600),
                                                    intdiv((int) $seconds % 3600, 60),
                                                    (int) $seconds % 60
                                                );

                                            $callDate = $history->call_at
                                                ? \Carbon\Carbon::parse($history->call_at)
                                                : null;
                                        @endphp


                                        <div class="border-b border-slate-100
                                                    p-4 last:border-b-0">

                                            <div class="flex flex-col gap-3
                                                        sm:flex-row
                                                        sm:items-start
                                                        sm:justify-between">

                                                {{-- STATUS --}}
                                                <div class="flex items-start gap-3">

                                                    <div class="flex h-10 w-10
                                                                shrink-0 items-center
                                                                justify-center
                                                                rounded-lg bg-blue-50
                                                                text-blue-600">

                                                        <i data-lucide="phone-call"
                                                           class="h-5 w-5"></i>

                                                    </div>

                                                    <div>

                                                        <div class="flex flex-wrap
                                                                    items-center gap-2">

                                                            <span class="font-semibold
                                                                         text-slate-900">
                                                                Call #{{ $history->id }}
                                                            </span>

                                                            <span class="rounded-full border
                                                                         px-2.5 py-1
                                                                         text-xs font-semibold
                                                                         {{ $historyClass }}">
                                                                {{ $historyLabel ?: 'Unknown' }}
                                                            </span>

                                                        </div>

                                                        <p class="mt-1 text-xs text-slate-500">
                                                            User ID:
                                                            {{ $history->user_id ?? '—' }}
                                                        </p>

                                                    </div>

                                                </div>


                                                {{-- CALL DATE --}}
                                                <div class="text-sm text-slate-600
                                                            sm:text-right">

                                                    @if($callDate)

                                                        <p class="font-semibold text-slate-800">
                                                            {{ $callDate->format('d M Y') }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-400">
                                                            {{ $callDate->format('h:i A') }}
                                                        </p>

                                                    @else

                                                        <span class="text-slate-400">
                                                            No date
                                                        </span>

                                                    @endif

                                                </div>

                                            </div>


                                            {{-- CALL INFORMATION --}}
                                            <div class="mt-4 grid grid-cols-1
                                                        gap-3 sm:grid-cols-2">

                                                {{-- DURATION --}}
                                                <div class="rounded-lg bg-slate-50 p-3">

                                                    <p class="text-xs font-medium
                                                              text-slate-500">
                                                        Call Duration
                                                    </p>

                                                    <p class="mt-1 font-semibold
                                                              text-slate-900">
                                                        {{ $durationText }}
                                                    </p>

                                                </div>


                                                {{-- REMARKS --}}
                                                <div class="rounded-lg bg-slate-50 p-3">

                                                    <p class="text-xs font-medium
                                                              text-slate-500">
                                                        Remarks
                                                    </p>

                                                    <p class="mt-1 whitespace-pre-line
                                                              break-words text-sm
                                                              text-slate-800">
                                                        {{ $history->remarks ?: 'No remarks added' }}
                                                    </p>

                                                </div>

                                            </div>


                                            {{-- RECORDING --}}
                                            @if($history->recording)

                                                @php
                                                    $recordingUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($history->recording);
                                                @endphp

                                                <div class="mt-4 rounded-lg
                                                            border border-blue-100
                                                            bg-blue-50/60 p-4">

                                                    <div class="mb-3 flex
                                                                items-center
                                                                justify-between gap-2">

                                                        <span class="text-xs
                                                                     font-semibold
                                                                     text-blue-800">
                                                            Call Recording
                                                        </span>

                                                        <a
                                                            href="{{ $recordingUrl }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="text-xs font-semibold
                                                                   text-blue-700
                                                                   hover:underline"
                                                        >
                                                            Open Recording
                                                        </a>

                                                    </div>

                                                    <audio
                                                        controls
                                                        preload="none"
                                                        class="w-full"
                                                        src="{{ $recordingUrl }}"
                                                    >
                                                        Your browser does not support audio.
                                                    </audio>

                                                </div>

                                            @endif

                                        </div>


                                    @empty

                                        <div class="px-5 py-14 text-center">

                                            <div class="mx-auto flex h-12 w-12
                                                        items-center justify-center
                                                        rounded-full bg-slate-100">

                                                <i data-lucide="history"
                                                   class="h-6 w-6 text-slate-400"></i>

                                            </div>

                                            <h3 class="mt-4 font-semibold
                                                       text-slate-700">
                                                No Call History Found
                                            </h3>

                                            <p class="mt-1 text-sm text-slate-500">
                                                No call records available for this lead.
                                            </p>

                                        </div>

                                    @endforelse

                                </div>

                            </td>

                        </tr>

                    </tbody>


                @empty

                    <tbody>
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">

                                <div class="mx-auto flex h-14 w-14
                                            items-center justify-center
                                            rounded-full bg-slate-100">

                                    <i data-lucide="users"
                                       class="h-6 w-6 text-slate-400"></i>

                                </div>

                                <h3 class="mt-4 font-semibold text-slate-700">
                                    No Calling Leads Found
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    No non-pending leads match your filters.
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


{{-- HISTORY TOGGLE SCRIPT --}}
<script>
(function () {

    function initializeHistory() {

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

                // Close all history rows
                document.querySelectorAll('[data-history-row]')
                    .forEach(function (row) {
                        row.classList.add('hidden');
                    });

                // Reset all buttons
                document.querySelectorAll('[data-history-toggle]')
                    .forEach(function (btn) {

                        btn.setAttribute('aria-expanded', 'false');

                        const label = btn.querySelector('[data-label]');

                        if (label) {
                            label.textContent = 'View History';
                        }

                    });

                if (shouldOpen) {

                    historyRow.classList.remove('hidden');

                    button.setAttribute('aria-expanded', 'true');

                    const label = button.querySelector('[data-label]');

                    if (label) {
                        label.textContent = 'Hide History';
                    }
                }

            });

        });

        // Icons already provided by layouts.crm
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeHistory
        );
    } else {
        initializeHistory();
    }

})();
</script>

@endsection
