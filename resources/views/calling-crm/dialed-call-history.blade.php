
@extends('layouts.crm', [
    'title' => 'Dialed Call History',
])

@section('content')

<div class="mx-auto max-w-7xl space-y-6 pb-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-wrap items-center justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Dialed Call History
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Android Dialer Incoming, Outgoing & Missed Call Records
            </p>
        </div>

        <a href="{{ route('dialed-call-history.index') }}"
           class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Refresh
        </a>

    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">

        @foreach ([
            ['Total Calls', $totalCalls, 'bg-blue-50 text-blue-700'],
            ['Incoming', $incomingCalls, 'bg-emerald-50 text-emerald-700'],
            ['Outgoing', $outgoingCalls, 'bg-indigo-50 text-indigo-700'],
            ['Missed', $missedCalls, 'bg-rose-50 text-rose-700'],
            ['Without Lead', $withoutLead, 'bg-amber-50 text-amber-700'],
        ] as [$label, $count, $color])

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

                <p class="text-xs font-medium text-slate-500">
                    {{ $label }}
                </p>

                <div class="mt-3">
                    <span class="inline-flex rounded-lg px-3 py-1 text-xl font-bold {{ $color }}">
                        {{ number_format($count) }}
                    </span>
                </div>

            </div>

        @endforeach

    </div>

    {{-- FILTER FORM --}}
    <form method="GET"
          action="{{ route('dialed-call-history.index') }}"
          class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

        <div class="mb-4 text-sm font-semibold text-slate-800">
            Search & Filters
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">

            {{-- Search --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Mobile / Lead Name
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search mobile or name"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
                >
            </div>

            {{-- Employee --}}
            @if($canFilterEmployees)
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">
                        Employee
                    </label>

                    <select
                        name="user_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All Employees</option>

                        @foreach($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                @selected(
                                    (string)($filters['user_id'] ?? '') ===
                                    (string)$employee->id
                                )
                            >
                                {{ $employee->name }}
                            </option>
                        @endforeach

                    </select>
                </div>
            @endif

            {{-- Direction --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Direction
                </label>

                <select name="direction"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">

                    <option value="">All Directions</option>

                    <option value="incoming"
                        @selected(($filters['direction'] ?? '') === 'incoming')>
                        Incoming
                    </option>

                    <option value="outgoing"
                        @selected(($filters['direction'] ?? '') === 'outgoing')>
                        Outgoing
                    </option>

                </select>
            </div>

            {{-- Call Type --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Call Type
                </label>

                <select name="call_type"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">

                    <option value="">All Call Types</option>

                    @foreach(['incoming', 'outgoing', 'missed', 'rejected'] as $type)
                        <option
                            value="{{ $type }}"
                            @selected(($filters['call_type'] ?? '') === $type)
                        >
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Call Status
                </label>

                <input
                    type="text"
                    name="call_status"
                    value="{{ $filters['call_status'] ?? '' }}"
                    placeholder="completed / demo_sent"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
            </div>

            {{-- From Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    From Date
                </label>

                <input
                    type="date"
                    name="from_date"
                    value="{{ $filters['from_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
            </div>

            {{-- To Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    To Date
                </label>

                <input
                    type="date"
                    name="to_date"
                    value="{{ $filters['to_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
            </div>

            {{-- Per Page --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Records Per Page
                </label>

                <select name="per_page"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">

                    @foreach([20, 50, 100] as $size)
                        <option value="{{ $size }}"
                            @selected((int)($filters['per_page'] ?? 20) === $size)>
                            {{ $size }}
                        </option>
                    @endforeach

                </select>
            </div>

        </div>

        <div class="mt-4 flex flex-wrap gap-2">

            <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Apply Filters
            </button>

            <a href="{{ route('dialed-call-history.index') }}"
               class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Reset
            </a>

        </div>

    </form>

    {{-- HISTORY TABLE --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">

            <h2 class="font-bold text-slate-900">
                Call Records
            </h2>

            <span class="text-xs text-slate-500">
                Showing {{ $histories->firstItem() ?? 0 }}
                to {{ $histories->lastItem() ?? 0 }}
                of {{ $histories->total() }}
            </span>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1100px] text-left text-sm">

                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Mobile Number</th>
                        <th class="px-4 py-3">Lead Details</th>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Direction</th>
                        <th class="px-4 py-3">Call Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Remarks</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($histories as $call)

                        @php
                            $seconds = max(0, (int) $call->call_duration);

                            $duration = sprintf(
                                '%02d:%02d:%02d',
                                intdiv($seconds, 3600),
                                intdiv($seconds % 3600, 60),
                                $seconds % 60
                            );

                            $direction = strtolower(
                                (string)$call->direction
                            );

                            $type = strtolower(
                                (string)$call->call_type
                            );
                        @endphp

                        <tr class="hover:bg-slate-50">

                            {{-- Serial --}}
                            <td class="px-4 py-3 text-slate-500">
                                {{ $histories->firstItem() + $loop->index }}
                            </td>

                            {{-- Mobile --}}
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">
                                    {{ $call->mobile ?: '—' }}
                                </div>
                            </td>

                            {{-- Lead --}}
                            <td class="px-4 py-3">

                                @if($call->lead_id)

                                    <div class="font-semibold text-slate-800">
                                        {{ $call->lead?->name ?? 'Lead unavailable' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        Lead ID: {{ $call->lead_id }}
                                    </div>

                                @else

                                    <span class="rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                        No Lead
                                    </span>

                                @endif

                            </td>

                            {{-- Employee --}}
                            <td class="px-4 py-3 font-medium text-slate-700">
                                {{ $call->user?->name ?? 'Unknown' }}
                            </td>

                            {{-- Direction --}}
                            <td class="px-4 py-3">

                                @if($direction === 'incoming')
                                    <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        ↓ Incoming
                                    </span>
                                @elseif($direction === 'outgoing')
                                    <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        ↑ Outgoing
                                    </span>
                                @else
                                    —
                                @endif

                            </td>

                            {{-- Call Type --}}
                            <td class="px-4 py-3">

                                <span class="rounded-md px-2 py-1 text-xs font-semibold
                                    {{ $type === 'missed'
                                        ? 'bg-rose-50 text-rose-700'
                                        : ($type === 'rejected'
                                            ? 'bg-orange-50 text-orange-700'
                                            : 'bg-slate-100 text-slate-700') }}">

                                    {{ ucfirst($type ?: 'Unknown') }}

                                </span>

                            </td>

                            {{-- Call Status --}}
                            <td class="px-4 py-3">

                                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ ucwords(str_replace('_', ' ', $call->call_status ?: 'Unknown')) }}
                                </span>

                            </td>

                            {{-- Duration --}}
                            <td class="px-4 py-3 font-mono text-slate-700">
                                {{ $duration }}
                            </td>

                            {{-- Date --}}
                            <td class="px-4 py-3">

                                <div class="font-medium text-slate-800">
                                    {{ $call->call_at?->format('d M Y') ?? '—' }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $call->call_at?->format('h:i A') ?? '' }}
                                </div>

                            </td>

                            {{-- Remarks --}}
                            <td class="px-4 py-3 text-slate-600">

                                <div class="max-w-[180px]"
                                     title="{{ $call->remarks }}">
                                    {{ \Illuminate\Support\Str::limit($call->remarks ?: '—', 50) }}
                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="10" class="px-4 py-16 text-center">

                                <div class="font-semibold text-slate-700">
                                    No Dialed Call History Found
                                </div>

                                <p class="mt-2 text-sm text-slate-500">
                                    Android dialer calls sync hone ke baad
                                    yahan records dikhenge.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

        {{-- PAGINATION --}}
        @if($histories->hasPages())

            <div class="border-t border-slate-200 px-4 py-4">
                {{ $histories->links() }}
            </div>

        @endif

    </div>

</div>

@endsection
