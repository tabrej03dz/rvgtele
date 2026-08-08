@extends('layouts.crm', [
    'title' => 'Follow-ups',
])

@section('content')

@php

    $currentStatus = request('status');

    $tabClass = function (?string $status) use ($currentStatus) {

        $isActive =
            $status === $currentStatus
            || ($status === null && blank($currentStatus));

        return $isActive
            ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900';
    };

@endphp


<style>

    /*
    |--------------------------------------------------------------------------
    | Reminder Animations
    |--------------------------------------------------------------------------
    */

    @keyframes reminder-soft-pulse {

        0%, 100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
        }

        50% {
            box-shadow: 0 0 0 5px rgba(245, 158, 11, 0.12);
        }
    }


    @keyframes reminder-danger-pulse {

        0%, 100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.16);
        }
    }


    @keyframes reminder-critical {

        0%, 100% {
            background-color: rgba(254, 242, 242, 1);
            box-shadow: inset 4px 0 0 #dc2626;
        }

        50% {
            background-color: rgba(254, 226, 226, 1);
            box-shadow:
                inset 4px 0 0 #dc2626,
                0 0 15px rgba(220, 38, 38, 0.18);
        }
    }


    @keyframes reminder-dot {

        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .3;
            transform: scale(.7);
        }
    }


    .reminder-soon {
        animation: reminder-soft-pulse 2s infinite;
    }


    .reminder-danger {
        animation: reminder-danger-pulse 1.3s infinite;
    }


    .reminder-critical {
        animation: reminder-critical .8s infinite;
    }


    .reminder-dot {
        animation: reminder-dot .8s infinite;
    }


    /*
    |--------------------------------------------------------------------------
    | Accessibility
    |--------------------------------------------------------------------------
    */

    @media (prefers-reduced-motion: reduce) {

        .reminder-soon,
        .reminder-danger,
        .reminder-critical,
        .reminder-dot {
            animation: none !important;
        }
    }

</style>


<div class="mx-auto max-w-7xl space-y-6">


    {{-- =========================================================
        Header
    ========================================================== --}}

    <div
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-lg">

        {{-- Decorative Background --}}

        <div
            class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-indigo-500/20 blur-3xl">
        </div>

        <div
            class="absolute -bottom-20 left-1/3 h-48 w-48 rounded-full bg-blue-500/10 blur-3xl">
        </div>


        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <div class="mb-2 flex items-center gap-2">

                    <span
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="h-5 w-5">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967
                                8.967 0 0118 9.75V9A6 6 0 006
                                9v.75a8.967 8.967 0 01-2.312
                                6.022c1.733.64 3.56 1.085
                                5.455 1.31m5.714 0a24.255
                                24.255 0 01-5.714 0m5.714
                                0a3 3 0 11-5.714 0"
                            />

                        </svg>

                    </span>

                    <span
                        class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-200">

                        Reminder Center

                    </span>

                </div>


                <h1 class="text-2xl font-bold sm:text-3xl">
                    Follow-ups
                </h1>


                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">

                    Leads ke calls, meetings aur important reminders ko
                    ek jagah manage karein.

                </p>

            </div>


            <div
                class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">

                <div class="text-xs text-slate-400">
                    Current Time
                </div>

                <div
                    id="followup-current-time"
                    class="mt-1 font-semibold text-white">
                    {{ now()->format('d M Y, h:i:s A') }}
                </div>

            </div>

        </div>

    </div>



    {{-- =========================================================
        Summary Cards
    ========================================================== --}}

    <div
        class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">


        {{-- Total --}}

        <a
            href="{{ route('followups.index') }}"
            class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

            <div class="flex items-center justify-between">

                <div>

                    <div
                        class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Total
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold text-slate-900">
                        {{ number_format($totalCount) }}
                    </div>

                </div>


                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="h-5 w-5">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 6.75h7.5M8.25
                            12h7.5m-7.5 5.25h7.5M3.75
                            6.75h.008v.008H3.75V6.75zm0
                            5.25h.008v.008H3.75V12zm0
                            5.25h.008v.008H3.75v-.008z"
                        />

                    </svg>

                </div>

            </div>

        </a>



        {{-- Pending --}}

        <a
            href="{{ route('followups.index', ['status' => 'pending']) }}"
            class="group rounded-2xl border border-amber-200 bg-amber-50/40 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

            <div class="flex items-center justify-between">

                <div>

                    <div
                        class="text-xs font-semibold uppercase tracking-wide text-amber-600">
                        Pending
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold text-amber-800">
                        {{ number_format($pendingCount) }}
                    </div>

                </div>


                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="h-5 w-5">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5
                            0a9 9 0 11-18 0
                            9 9 0 0118 0z"
                        />

                    </svg>

                </div>

            </div>

        </a>



        {{-- Due Soon --}}

        <a
            href="{{ route('followups.index', ['status' => 'due_soon']) }}"
            class="reminder-soon group rounded-2xl border border-orange-200 bg-orange-50/60 p-4 shadow-sm transition hover:-translate-y-0.5">

            <div class="flex items-center justify-between">

                <div>

                    <div class="flex items-center gap-2">

                        <span
                            class="reminder-dot h-2 w-2 rounded-full bg-orange-500">
                        </span>

                        <div
                            class="text-xs font-semibold uppercase tracking-wide text-orange-600">

                            Due Soon

                        </div>

                    </div>


                    <div
                        class="mt-2 text-2xl font-bold text-orange-800">

                        {{ number_format($dueSoonCount) }}

                    </div>

                </div>


                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-700">

                    🔔

                </div>

            </div>

        </a>



        {{-- Overdue --}}

        <a
            href="{{ route('followups.index', ['status' => 'overdue']) }}"
            class="{{ $overdueCount > 0 ? 'reminder-danger' : '' }}
            rounded-2xl border border-rose-200 bg-rose-50/70 p-4 shadow-sm transition hover:-translate-y-0.5">

            <div class="flex items-center justify-between">

                <div>

                    <div class="flex items-center gap-2">

                        @if($overdueCount > 0)

                            <span
                                class="reminder-dot h-2 w-2 rounded-full bg-red-600">
                            </span>

                        @endif


                        <div
                            class="text-xs font-semibold uppercase tracking-wide text-rose-600">

                            Overdue

                        </div>

                    </div>


                    <div
                        class="mt-2 text-2xl font-bold text-rose-800">

                        {{ number_format($overdueCount) }}

                    </div>

                </div>


                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700">

                    ⚠️

                </div>

            </div>

        </a>



        {{-- Completed --}}

        <a
            href="{{ route('followups.index', ['status' => 'completed']) }}"
            class="group rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

            <div class="flex items-center justify-between">

                <div>

                    <div
                        class="text-xs font-semibold uppercase tracking-wide text-emerald-600">

                        Completed

                    </div>


                    <div
                        class="mt-2 text-2xl font-bold text-emerald-800">

                        {{ number_format($completedCount) }}

                    </div>

                </div>


                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

                    ✓

                </div>

            </div>

        </a>

    </div>



    {{-- =========================================================
        Status Tabs
    ========================================================== --}}

    <div
        class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">

        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('followups.index') }}"
                class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition
                {{ $tabClass(null) }}">

                All

            </a>


            <a
                href="{{ route('followups.index', ['status' => 'pending']) }}"
                class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition
                {{ $tabClass('pending') }}">

                Pending

                @if($pendingCount)

                    <span
                        class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">

                        {{ $pendingCount }}

                    </span>

                @endif

            </a>


            <a
                href="{{ route('followups.index', ['status' => 'due_soon']) }}"
                class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition
                {{ $currentStatus === 'due_soon'
                    ? 'border-orange-600 bg-orange-600 text-white'
                    : 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'
                }}">

                Due Soon

            </a>


            <a
                href="{{ route('followups.index', ['status' => 'overdue']) }}"
                class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition
                {{ $currentStatus === 'overdue'
                    ? 'border-rose-600 bg-rose-600 text-white'
                    : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                }}">

                @if($overdueCount > 0)

                    <span
                        class="reminder-dot mr-2 h-2 w-2 rounded-full bg-current">
                    </span>

                @endif

                Overdue

            </a>


            <a
                href="{{ route('followups.index', ['status' => 'completed']) }}"
                class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition
                {{ $tabClass('completed') }}">

                Completed

            </a>

        </div>

    </div>



    {{-- =========================================================
        Alerts
    ========================================================== --}}

    @if(session('success'))

        <div
            class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">

            <span
                class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">
                ✓
            </span>

            {{ session('success') }}

        </div>

    @endif



    {{-- =========================================================
        Follow-up List
    ========================================================== --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


        {{-- Table Header --}}

        <div
            class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2
                    class="text-lg font-bold text-slate-900">
                    Follow-up List
                </h2>

                <p
                    class="mt-1 text-sm text-slate-500">

                    Showing
                    <b class="text-slate-700">
                        {{ $followups->firstItem() ?? 0 }}
                    </b>

                    –

                    <b class="text-slate-700">
                        {{ $followups->lastItem() ?? 0 }}
                    </b>

                    of

                    <b class="text-slate-700">
                        {{ $followups->total() }}
                    </b>

                </p>

            </div>


            <div class="flex items-center gap-2 text-xs">

                <span
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-2 font-medium text-orange-700">

                    <span
                        class="h-2 w-2 rounded-full bg-orange-500">
                    </span>

                    Under 30 min

                </span>


                <span
                    class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 font-medium text-red-700">

                    <span
                        class="reminder-dot h-2 w-2 rounded-full bg-red-600">
                    </span>

                    Urgent

                </span>

            </div>

        </div>



        <div class="overflow-x-auto">

            <table
                class="w-full min-w-[1100px] text-sm">


                <thead class="bg-slate-50/80">

                    <tr
                        class="border-b border-slate-200 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">

                        <th class="px-5 py-4">
                            Lead
                        </th>

                        <th class="px-4 py-4">
                            Assigned To
                        </th>

                        <th class="px-4 py-4">
                            Reminder
                        </th>

                        <th class="px-4 py-4">
                            Type
                        </th>

                        <th class="px-4 py-4">
                            Status
                        </th>

                        <th class="px-5 py-4 text-right">
                            Action
                        </th>

                    </tr>

                </thead>



                <tbody class="divide-y divide-slate-100">

                    @forelse($followups as $followup)

                        @php

                            $scheduledAt = $followup->scheduled_at;

                            $isPending =
                                $followup->status === 'pending';

                            $isCompleted =
                                $followup->status === 'completed';

                            $isOverdue =
                                $isPending
                                && $scheduledAt
                                && $scheduledAt->isPast();


                            $minutesRemaining = null;

                            if (
                                $isPending
                                && $scheduledAt
                                && !$isOverdue
                            ) {

                                $minutesRemaining =
                                    now()->diffInMinutes(
                                        $scheduledAt,
                                        false
                                    );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Alert Level
                            |--------------------------------------------------------------------------
                            |
                            | > 30 min       = normal
                            | 11 - 30 min    = warning
                            | 6 - 10 min     = danger
                            | <= 5 min       = critical
                            | overdue        = critical
                            |
                            */

                            $alertLevel = 'normal';

                            if ($isOverdue) {

                                $alertLevel = 'overdue';

                            } elseif (
                                $minutesRemaining !== null
                                && $minutesRemaining <= 5
                            ) {

                                $alertLevel = 'critical';

                            } elseif (
                                $minutesRemaining !== null
                                && $minutesRemaining <= 10
                            ) {

                                $alertLevel = 'danger';

                            } elseif (
                                $minutesRemaining !== null
                                && $minutesRemaining <= 30
                            ) {

                                $alertLevel = 'warning';
                            }


                            $rowClass = match($alertLevel) {

                                'overdue' =>
                                    'reminder-critical bg-red-50',

                                'critical' =>
                                    'reminder-critical bg-red-50',

                                'danger' =>
                                    'reminder-danger bg-rose-50/60',

                                'warning' =>
                                    'reminder-soon bg-orange-50/40',

                                default =>
                                    'hover:bg-slate-50/70',

                            };


                            $statusClass = match($followup->status) {

                                'completed' =>
                                    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',

                                'cancelled' =>
                                    'bg-slate-100 text-slate-600 ring-1 ring-slate-200',

                                default => $isOverdue
                                    ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
                                    : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',

                            };


                            $typeClass = match($followup->type) {

                                'call' =>
                                    'bg-blue-50 text-blue-700 ring-blue-200',

                                'meeting' =>
                                    'bg-violet-50 text-violet-700 ring-violet-200',

                                'whatsapp' =>
                                    'bg-emerald-50 text-emerald-700 ring-emerald-200',

                                'email' =>
                                    'bg-sky-50 text-sky-700 ring-sky-200',

                                default =>
                                    'bg-slate-100 text-slate-700 ring-slate-200',

                            };

                        @endphp



                        <tr
                            id="followup-row-{{ $followup->id }}"
                            class="followup-row transition
                            {{ $isPending ? $rowClass : 'hover:bg-slate-50/70' }}"
                            data-status="{{ $followup->status }}"
                            data-scheduled-at="{{ $scheduledAt?->toIso8601String() }}">


                            {{-- Lead --}}

                            <td class="px-5 py-4">

                                <div class="flex items-start gap-3">


                                    <div
                                        class="
                                        flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                        font-bold

                                        @if($alertLevel === 'overdue' || $alertLevel === 'critical')
                                            bg-red-100 text-red-700
                                        @elseif($alertLevel === 'danger')
                                            bg-rose-100 text-rose-700
                                        @elseif($alertLevel === 'warning')
                                            bg-orange-100 text-orange-700
                                        @else
                                            bg-indigo-50 text-indigo-700
                                        @endif
                                        ">

                                        {{ strtoupper(
                                            substr(
                                                $followup->lead?->name
                                                    ?? 'L',
                                                0,
                                                1
                                            )
                                        ) }}

                                    </div>


                                    <div class="min-w-0">

                                        @if($followup->lead)

                                            <a
                                                href="{{ route('leads.show', $followup->lead) }}"
                                                class="font-bold text-slate-900 hover:text-indigo-600">

                                                {{ $followup->lead->name }}

                                            </a>

                                        @else

                                            <span
                                                class="font-semibold text-slate-500">

                                                Lead unavailable

                                            </span>

                                        @endif


                                        @if($followup->notes)

                                            <div
                                                class="mt-1 max-w-sm text-xs leading-5 text-slate-500">

                                                {{ \Illuminate\Support\Str::limit(
                                                    $followup->notes,
                                                    100
                                                ) }}

                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </td>



                            {{-- Assigned User --}}

                            <td class="px-4 py-4">

                                <div
                                    class="font-semibold text-slate-800">

                                    {{ $followup->assignedUser?->name ?? 'Unassigned' }}

                                </div>


                                @if($followup->assignedUser?->employee_code)

                                    <div
                                        class="mt-1 text-xs text-slate-400">

                                        {{ $followup->assignedUser->employee_code }}

                                    </div>

                                @endif

                            </td>



                            {{-- Scheduled / Reminder --}}

                            <td class="px-4 py-4">

                                @if($scheduledAt)

                                    <div
                                        class="
                                        font-semibold

                                        @if(
                                            $alertLevel === 'critical'
                                            || $alertLevel === 'overdue'
                                        )
                                            text-red-700
                                        @elseif($alertLevel === 'danger')
                                            text-rose-700
                                        @elseif($alertLevel === 'warning')
                                            text-orange-700
                                        @else
                                            text-slate-800
                                        @endif
                                        ">

                                        {{ $scheduledAt->format(
                                            'd M Y, h:i A'
                                        ) }}

                                    </div>



                                    @if($isPending)

                                        <div
                                            class="countdown mt-2 inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs font-bold"

                                            data-time="{{ $scheduledAt->toIso8601String() }}">

                                            <span
                                                class="countdown-dot h-2 w-2 rounded-full">
                                            </span>

                                            <span class="countdown-text">
                                                Calculating...
                                            </span>

                                        </div>

                                    @elseif($isCompleted)

                                        <div
                                            class="mt-1 text-xs font-medium text-emerald-600">

                                            ✓ Completed

                                        </div>

                                    @endif

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>



                            {{-- Type --}}

                            <td class="px-4 py-4">

                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold capitalize ring-1
                                    {{ $typeClass }}">

                                    {{ $followup->type ?: 'follow-up' }}

                                </span>

                            </td>



                            {{-- Status --}}

                            <td class="px-4 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold capitalize
                                    {{ $statusClass }}">

                                    @if($isOverdue)

                                        <span
                                            class="reminder-dot h-1.5 w-1.5 rounded-full bg-red-600">
                                        </span>

                                        Overdue

                                    @else

                                        {{ ucfirst($followup->status) }}

                                    @endif

                                </span>

                            </td>



                            {{-- Actions --}}

                            <td class="px-5 py-4">

                                <div
                                    class="flex items-center justify-end gap-2">


                                    @if($followup->status === 'pending')

                                        <form
                                            method="POST"
                                            action="{{ route('followups.complete', $followup) }}"
                                            onsubmit="return confirm('Mark this follow-up as completed?')">

                                            @csrf


                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                    class="h-4 w-4">

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M4.5 12.75l6
                                                        6 9-13.5"
                                                    />

                                                </svg>

                                                Complete

                                            </button>

                                        </form>

                                    @endif



                                    <form
                                        method="POST"
                                        action="{{ route('followups.destroy', $followup) }}"
                                        onsubmit="return confirm('Delete this follow-up?')">

                                        @csrf
                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50"
                                            title="Delete">

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.8"
                                                stroke="currentColor"
                                                class="h-4 w-4">

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m14.74 9-.346
                                                    9m-4.788 0L9.26
                                                    9m9.968-3.21c.342.052.682.107
                                                    1.022.166m-1.022-.165L18.16
                                                    19.673A2.25 2.25 0
                                                    0115.916 21H8.084a2.25
                                                    2.25 0 01-2.244-2.077L4.772
                                                    5.79m14.456 0a48.108
                                                    48.108 0 00-3.478-.397m-12
                                                    .562c.34-.059.68-.114
                                                    1.022-.165m0 0a48.11
                                                    48.11 0 013.478-.397m7.5
                                                    0v-.916c0-1.18-.91-2.164
                                                    -2.09-2.201a51.964
                                                    51.964 0 00-3.32
                                                    0c-1.18.037-2.09
                                                    1.022-2.09 2.201v.916m7.5
                                                    0a48.667 48.667 0
                                                    00-7.5 0"
                                                />

                                            </svg>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-16 text-center">

                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">

                                    🔔

                                </div>


                                <div
                                    class="mt-4 font-bold text-slate-800">

                                    No follow-ups found

                                </div>


                                <div
                                    class="mt-1 text-sm text-slate-500">

                                    Selected filter ke liye koi
                                    follow-up available nahi hai.

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        {{-- Pagination --}}

        @if($followups->hasPages())

            <div
                class="border-t border-slate-200 bg-slate-50/50 px-5 py-4">

                {{ $followups->links() }}

            </div>

        @endif


    </section>

</div>



{{-- =========================================================
    Live Reminder Countdown
========================================================== --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const countdowns =
        document.querySelectorAll('.countdown');


    function updateClock() {

        const clock =
            document.getElementById('followup-current-time');

        if (!clock) {
            return;
        }


        const now = new Date();


        clock.innerText =
            now.toLocaleString('en-IN', {

                day: '2-digit',
                month: 'short',
                year: 'numeric',

                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',

                hour12: true

            });
    }


    function updateCountdowns() {

        const now =
            new Date().getTime();


        countdowns.forEach(function (element) {

            const time =
                element.dataset.time;


            if (!time) {
                return;
            }


            const target =
                new Date(time).getTime();


            const diff =
                target - now;


            const text =
                element.querySelector('.countdown-text');


            const dot =
                element.querySelector('.countdown-dot');


            const row =
                element.closest('.followup-row');


            /*
            |--------------------------------------------------------------------------
            | Reset Classes
            |--------------------------------------------------------------------------
            */

            element.classList.remove(

                'bg-slate-100',
                'text-slate-600',

                'bg-orange-100',
                'text-orange-700',

                'bg-rose-100',
                'text-rose-700',

                'bg-red-100',
                'text-red-700',

                'reminder-soon',
                'reminder-danger',
                'reminder-critical'

            );


            dot.classList.remove(

                'bg-slate-400',
                'bg-orange-500',
                'bg-rose-500',
                'bg-red-600',
                'reminder-dot'

            );


            if (diff <= 0) {

                /*
                |--------------------------------------------------------------------------
                | Overdue
                |--------------------------------------------------------------------------
                */

                const overdueMs =
                    Math.abs(diff);


                const overdueMinutes =
                    Math.floor(
                        overdueMs / 60000
                    );


                const overdueHours =
                    Math.floor(
                        overdueMinutes / 60
                    );


                if (overdueHours >= 1) {

                    text.innerText =
                        overdueHours
                        + 'h '
                        + (overdueMinutes % 60)
                        + 'm overdue';

                } else {

                    text.innerText =
                        overdueMinutes
                        + 'm overdue';
                }


                element.classList.add(

                    'bg-red-100',
                    'text-red-700',
                    'reminder-critical'

                );


                dot.classList.add(

                    'bg-red-600',
                    'reminder-dot'

                );


                if (row) {

                    row.classList.add(
                        'reminder-critical'
                    );
                }


                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Remaining Time
            |--------------------------------------------------------------------------
            */

            const totalSeconds =
                Math.floor(
                    diff / 1000
                );


            const days =
                Math.floor(
                    totalSeconds / 86400
                );


            const hours =
                Math.floor(
                    (totalSeconds % 86400)
                    / 3600
                );


            const minutes =
                Math.floor(
                    (totalSeconds % 3600)
                    / 60
                );


            const seconds =
                totalSeconds % 60;


            let label = '';


            if (days > 0) {

                label =
                    days
                    + 'd '
                    + hours
                    + 'h remaining';

            } else if (hours > 0) {

                label =
                    hours
                    + 'h '
                    + minutes
                    + 'm remaining';

            } else {

                label =
                    minutes
                    + 'm '
                    + seconds
                    + 's remaining';
            }


            text.innerText = label;


            /*
            |--------------------------------------------------------------------------
            | More Than 30 Minutes
            |--------------------------------------------------------------------------
            */

            if (diff > (30 * 60 * 1000)) {

                element.classList.add(

                    'bg-slate-100',
                    'text-slate-600'

                );


                dot.classList.add(
                    'bg-slate-400'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | 11 - 30 Minutes
            |--------------------------------------------------------------------------
            */

            else if (diff > (10 * 60 * 1000)) {

                element.classList.add(

                    'bg-orange-100',
                    'text-orange-700',
                    'reminder-soon'

                );


                dot.classList.add(

                    'bg-orange-500',
                    'reminder-dot'

                );


                if (row) {

                    row.classList.add(
                        'reminder-soon'
                    );
                }

            }

            /*
            |--------------------------------------------------------------------------
            | 6 - 10 Minutes
            |--------------------------------------------------------------------------
            */

            else if (diff > (5 * 60 * 1000)) {

                element.classList.add(

                    'bg-rose-100',
                    'text-rose-700',
                    'reminder-danger'

                );


                dot.classList.add(

                    'bg-rose-500',
                    'reminder-dot'

                );


                if (row) {

                    row.classList.add(
                        'reminder-danger'
                    );
                }

            }

            /*
            |--------------------------------------------------------------------------
            | Last 5 Minutes
            |--------------------------------------------------------------------------
            */

            else {

                element.classList.add(

                    'bg-red-100',
                    'text-red-700',
                    'reminder-critical'

                );


                dot.classList.add(

                    'bg-red-600',
                    'reminder-dot'

                );


                if (row) {

                    row.classList.add(
                        'reminder-critical'
                    );
                }

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Initial Run
    |--------------------------------------------------------------------------
    */

    updateClock();
    updateCountdowns();


    /*
    |--------------------------------------------------------------------------
    | Update Every Second
    |--------------------------------------------------------------------------
    */

    setInterval(function () {

        updateClock();
        updateCountdowns();

    }, 1000);

});

</script>

@endsection
