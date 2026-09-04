@extends('layouts.crm', [
    'title' => 'Dashboard',
])

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | Main Dashboard Stats
    |--------------------------------------------------------------------------
    */

    $stats = [

        [
            'label' => 'Total Leads',
            'sub_label' => 'All Time',
            'value' => number_format($totalLeads),
            'accent' => 'text-blue-700',
            'bg' => 'bg-blue-50',
            'url' => route('leads.index'),
        ],

        [
            'label' => 'New Leads',
            'sub_label' => $periodLabel,
            'value' => number_format($newToday),
            'accent' => 'text-violet-700',
            'bg' => 'bg-violet-50',
            'url' => route('leads.index'),
        ],

        [
            'label' => 'Demo Sent',
            'sub_label' => $periodLabel,
            'value' => number_format($todayLeadSend),
            'accent' => 'text-green-700',
            'bg' => 'bg-green-50',
            'url' => route('leads.index'),
        ],

        [
            'label' => 'Total Demo Sent',
            'sub_label' => 'All Time',
            'value' => number_format($totalLeadSend),
            'accent' => 'text-teal-700',
            'bg' => 'bg-teal-50',
            'url' => route('leads.index'),
        ],

        [
            'label' => 'Total Calls',
            'sub_label' => $periodLabel,
            'value' => number_format($callsToday),
            'accent' => 'text-sky-700',
            'bg' => 'bg-sky-50',
            'url' => route('calls.index'),
        ],

        [
            'label' => 'Connected Calls',
            'sub_label' => $periodLabel,
            'value' => number_format($connectedToday),
            'accent' => 'text-emerald-700',
            'bg' => 'bg-emerald-50',
            'url' => route('calls.index'),
        ],

        [
            'label' => 'Follow-ups',
            'sub_label' => $periodLabel,
            'value' => number_format($followUpsDue),
            'accent' => 'text-amber-700',
            'bg' => 'bg-amber-50',
            'url' => route('followups.index'),
        ],

        [
            'label' => 'Overdue',
            'sub_label' => $periodLabel,
            'value' => number_format($overdue),
            'accent' => 'text-rose-700',
            'bg' => 'bg-rose-50',
            'url' => route('followups.index'),
        ],

        [
            'label' => 'Hot Leads',
            'sub_label' => 'Current',
            'value' => number_format($hotLeads),
            'accent' => 'text-orange-700',
            'bg' => 'bg-orange-50',
            'url' => route('leads.index'),
        ],

        [
            'label' => 'Active Employees',
            'sub_label' => 'Current',
            'value' => number_format($activeUsers),
            'accent' => 'text-slate-700',
            'bg' => 'bg-slate-100',
            'url' => null,
        ],

        [
            'label' => 'Sales Value',
            'sub_label' => $periodLabel,
            'value' => '₹' . number_format($sales, 2),
            'accent' => 'text-indigo-700',
            'bg' => 'bg-indigo-50',
            'url' => null,
        ],

        [
            'label' => 'Payment Received',
            'sub_label' => $periodLabel,
            'value' => '₹' . number_format($received, 2),
            'accent' => 'text-cyan-700',
            'bg' => 'bg-cyan-50',
            'url' => null,
        ],

    ];

@endphp


<div class="mx-auto max-w-7xl space-y-6">

    {{-- ================================================================ --}}
    {{-- Header --}}
    {{-- ================================================================ --}}

    <div
        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between"
    >

        <div>

            <div class="flex items-center gap-3">

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M4 19V9"/>
                        <path d="M10 19V5"/>
                        <path d="M16 19v-7"/>
                        <path d="M22 19V3"/>
                    </svg>
                </div>

                <div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        Dashboard
                    </h1>

                    <p class="mt-0.5 text-sm text-slate-500">
                        Leads, calls, demos aur follow-ups ka complete overview.
                    </p>

                </div>

            </div>

        </div>


        <div class="flex flex-wrap items-center gap-2">

            {{-- Period Filter --}}

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1"
            >

                <button
                    type="submit"
                    name="period"
                    value="today"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition
                    {{ $period === 'today'
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                    }}"
                >
                    Today
                </button>

                <button
                    type="submit"
                    name="period"
                    value="month"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition
                    {{ $period === 'month'
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                    }}"
                >
                    Month
                </button>

                <button
                    type="submit"
                    name="period"
                    value="all"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition
                    {{ $period === 'all'
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                    }}"
                >
                    All
                </button>

            </form>


            @can('leads.import')

                <a
                    href="{{ route('leads.import.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>

                    Import Leads

                </a>

            @endcan


            @can('leads.create')

                <a
                    href="{{ route('leads.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>

                    Add Lead

                </a>

            @endcan

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- Period Information --}}
    {{-- ================================================================ --}}

    <div
        class="flex flex-col gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >

        <div>

            <div
                class="text-xs font-bold uppercase tracking-wider text-blue-500"
            >
                Currently Showing
            </div>

            <div class="mt-0.5 text-base font-bold text-blue-900">
                {{ $periodLabel }} Statistics
            </div>

        </div>


        <div
            class="inline-flex self-start rounded-lg border border-blue-100 bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm sm:self-auto"
        >

            @if($period === 'today')

                {{ now()->format('d M Y') }}

            @elseif($period === 'month')

                {{ now()->format('F Y') }}

            @else

                All Records

            @endif

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- Main KPI Cards --}}
    {{-- ================================================================ --}}

    <div
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >

        @foreach($stats as $stat)

            @if(!empty($stat['url']))

                <a
                    href="{{ $stat['url'] }}"
                    class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
                >

                    <div
                        class="absolute right-0 top-0 h-20 w-20 translate-x-8 -translate-y-8 rounded-full {{ $stat['bg'] }}"
                    ></div>

                    <div class="relative flex items-start justify-between gap-4">

                        <div class="min-w-0">

                            <div
                                class="text-sm font-semibold text-slate-500"
                            >
                                {{ $stat['label'] }}
                            </div>

                            <div
                                class="mt-2 text-2xl font-black tracking-tight {{ $stat['accent'] }}"
                            >
                                {{ $stat['value'] }}
                            </div>

                            <div
                                class="mt-1 text-xs font-medium text-slate-400"
                            >
                                {{ $stat['sub_label'] }}
                            </div>

                        </div>


                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stat['bg'] }}"
                        >

                            <svg
                                class="h-5 w-5 {{ $stat['accent'] }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M5 12h14"/>
                                <path d="m13 6 6 6-6 6"/>
                            </svg>

                        </div>

                    </div>


                    <div
                        class="relative mt-4 border-t border-slate-100 pt-3 text-[11px] font-bold uppercase tracking-wide text-slate-400 transition group-hover:text-blue-600"
                    >
                        View Details →
                    </div>

                </a>

            @else

                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >

                    <div
                        class="absolute right-0 top-0 h-20 w-20 translate-x-8 -translate-y-8 rounded-full {{ $stat['bg'] }}"
                    ></div>

                    <div class="relative flex items-start justify-between gap-4">

                        <div class="min-w-0">

                            <div class="text-sm font-semibold text-slate-500">
                                {{ $stat['label'] }}
                            </div>

                            <div
                                class="mt-2 text-2xl font-black tracking-tight {{ $stat['accent'] }}"
                            >
                                {{ $stat['value'] }}
                            </div>

                            <div
                                class="mt-1 text-xs font-medium text-slate-400"
                            >
                                {{ $stat['sub_label'] }}
                            </div>

                        </div>


                        <div
                            class="h-11 w-11 shrink-0 rounded-xl {{ $stat['bg'] }}"
                        ></div>

                    </div>

                </div>

            @endif

        @endforeach

    </div>


    {{-- ================================================================ --}}
    {{-- Dynamic Disposition Statistics --}}
    {{-- ================================================================ --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        {{-- Header --}}

        <div
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between"
        >

            <div>

                <div class="flex items-center gap-2">

                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2"/>
                            <path d="M5 4h4"/>
                            <path d="M7 2v4"/>
                            <path d="M13 8h8"/>
                            <path d="M13 12h6"/>
                            <path d="M13 16h4"/>
                        </svg>

                    </div>

                    <div>

                        <h2 class="font-bold text-slate-900">
                            Call Dispositions
                        </h2>

                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $periodLabel }} disposition wise call statistics
                        </p>

                    </div>

                </div>

            </div>


            <div class="flex flex-wrap items-center gap-2">

                <div
                    class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600"
                >
                    Total Calls:
                    <span class="text-slate-900">
                        {{ number_format($callsToday) }}
                    </span>
                </div>





            </div>

        </div>


        {{-- Disposition Cards --}}

        <div class="p-5">

            @if($dispositionStats->isNotEmpty())

                <div
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >

                    @foreach($dispositionStats as $disposition)

                        @php

                            $type = strtolower(
                                trim(
                                    (string) (
                                        $disposition['type'] ?? ''
                                    )
                                )
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | Only styling is based on type
                            |--------------------------------------------------------------------------
                            |
                            | Names are never hard-coded.
                            |
                            */

                            $style = match($type) {

                                'connected' => [
                                    'border' => 'border-emerald-200',
                                    'bg' => 'bg-emerald-50',
                                    'number' => 'text-emerald-700',
                                    'dot' => 'bg-emerald-500',
                                    'badge' => 'bg-emerald-100 text-emerald-700',
                                ],

                                'not connected',
                                'not_connected',
                                'not-connected' => [
                                    'border' => 'border-rose-200',
                                    'bg' => 'bg-rose-50',
                                    'number' => 'text-rose-700',
                                    'dot' => 'bg-rose-500',
                                    'badge' => 'bg-rose-100 text-rose-700',
                                ],

                                'demo' => [
                                    'border' => 'border-blue-200',
                                    'bg' => 'bg-blue-50',
                                    'number' => 'text-blue-700',
                                    'dot' => 'bg-blue-500',
                                    'badge' => 'bg-blue-100 text-blue-700',
                                ],

                                'other' => [
                                    'border' => 'border-violet-200',
                                    'bg' => 'bg-violet-50',
                                    'number' => 'text-violet-700',
                                    'dot' => 'bg-violet-500',
                                    'badge' => 'bg-violet-100 text-violet-700',
                                ],

                                default => [
                                    'border' => 'border-slate-200',
                                    'bg' => 'bg-slate-50',
                                    'number' => 'text-slate-700',
                                    'dot' => 'bg-slate-500',
                                    'badge' => 'bg-slate-200 text-slate-700',
                                ],
                            };


                            $percentage = $callsToday > 0
                                ? ($disposition['total'] / $callsToday) * 100
                                : 0;

                        @endphp


                        <div
                            class="group relative overflow-hidden rounded-xl border {{ $style['border'] }} {{ $style['bg'] }} p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
                        >

                            {{-- Top right active dot --}}

                            <div class="absolute right-3 top-3">

                                @if($disposition['is_active'] ?? true)

                                    <span
                                        class="block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"
                                        title="Active"
                                    ></span>

                                @else

                                    <span
                                        class="block h-2.5 w-2.5 rounded-full bg-slate-400 ring-2 ring-white"
                                        title="Inactive"
                                    ></span>

                                @endif

                            </div>


                            {{-- Name --}}

                            <div class="pr-6">

                                <div
                                    class="flex items-start gap-2 text-sm font-bold text-slate-800"
                                >

                                    <span
                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $style['dot'] }}"
                                    ></span>

                                    <span
                                        class="line-clamp-2"
                                        title="{{ $disposition['name'] }}"
                                    >
                                        {{ $disposition['name'] }}
                                    </span>

                                </div>

                            </div>


                            {{-- Count --}}

                            <div
                                class="mt-4 text-3xl font-black tracking-tight {{ $style['number'] }}"
                            >
                                {{ number_format($disposition['total']) }}
                            </div>


                            {{-- Percentage --}}

                            <div class="mt-1 text-xs font-medium text-slate-500">

                                {{ number_format($percentage, 1) }}%

                                <span class="text-slate-400">
                                    of total calls
                                </span>

                            </div>


                            {{-- Progress --}}

                            <div
                                class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/80"
                            >
                                <div
                                    class="h-full rounded-full {{ $style['dot'] }}"
                                    style="width: {{ min(100, $percentage) }}%"
                                ></div>
                            </div>


                            {{-- Footer Badges --}}

                            <div class="mt-4 flex flex-wrap gap-1.5">

                                @if(!empty($disposition['type']))

                                    <span
                                        class="rounded-md px-2 py-1 text-[10px] font-bold {{ $style['badge'] }}"
                                    >
                                        {{ $disposition['type'] }}
                                    </span>

                                @endif


                                @if($disposition['requires_follow_up'] ?? false)

                                    <span
                                        class="rounded-md bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700"
                                    >
                                        Follow-up
                                    </span>

                                @endif


                                @if($disposition['requires_remarks'] ?? false)

                                    <span
                                        class="rounded-md bg-cyan-100 px-2 py-1 text-[10px] font-bold text-cyan-700"
                                    >
                                        Remarks
                                    </span>

                                @endif


                                @if(!($disposition['is_active'] ?? true))

                                    <span
                                        class="rounded-md bg-slate-200 px-2 py-1 text-[10px] font-bold text-slate-600"
                                    >
                                        Inactive
                                    </span>

                                @endif

                            </div>

                        </div>

                    @endforeach


                    {{-- Calls without disposition --}}

                    @if($withoutDisposition > 0)

                        @php

                            $withoutDispositionPercentage = $callsToday > 0
                                ? ($withoutDisposition / $callsToday) * 100
                                : 0;

                        @endphp

                        <div
                            class="rounded-xl border border-slate-200 bg-white p-4"
                        >

                            <div
                                class="flex items-start gap-2 text-sm font-bold text-slate-700"
                            >

                                <span
                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-slate-400"
                                ></span>

                                No Disposition

                            </div>


                            <div
                                class="mt-4 text-3xl font-black text-slate-700"
                            >
                                {{ number_format($withoutDisposition) }}
                            </div>


                            <div class="mt-1 text-xs font-medium text-slate-500">

                                {{ number_format($withoutDispositionPercentage, 1) }}%

                                <span class="text-slate-400">
                                    of total calls
                                </span>

                            </div>


                            <div
                                class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100"
                            >

                                <div
                                    class="h-full rounded-full bg-slate-400"
                                    style="width: {{ min(100, $withoutDispositionPercentage) }}%"
                                ></div>

                            </div>

                        </div>

                    @endif

                </div>

            @else

                <div
                    class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center"
                >

                    <div
                        class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm"
                    >

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>

                    </div>


                    <div class="mt-3 font-semibold text-slate-700">
                        No dispositions available
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        Naya disposition add karte hi yahan automatically show hoga.
                    </div>


                    @can('call-dispositions.create')

                        <a
                            href="{{ route('call-dispositions.create') }}"
                            class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Add Disposition
                        </a>

                    @endcan

                </div>

            @endif

        </div>

    </section>


    {{-- ================================================================ --}}
    {{-- Quick Links --}}
    {{-- ================================================================ --}}

    <div class="grid gap-4 md:grid-cols-3">

        @can('leads.view')

            <a
                href="{{ route('leads.index') }}"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md"
            >

                <div class="flex items-start justify-between gap-3">

                    <div>

                        <div class="font-bold text-slate-900">
                            View All Leads
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            Search, filter aur assign leads.
                        </div>

                    </div>


                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-100"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M5 12h14"/>
                            <path d="m13 6 6 6-6 6"/>
                        </svg>

                    </div>

                </div>

            </a>

        @endcan


        @can('followups.view')

            <a
                href="{{ route('followups.index') }}"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md"
            >

                <div class="flex items-start justify-between gap-3">

                    <div>

                        <div class="font-bold text-slate-900">
                            Manage Follow-ups
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            Pending aur overdue follow-ups dekhein.
                        </div>

                    </div>


                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 8v4l3 2"/>
                            <circle cx="12" cy="12" r="9"/>
                        </svg>

                    </div>

                </div>

            </a>

        @endcan


        @can('calls.view')

            <a
                href="{{ route('calls.index') }}"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md"
            >

                <div class="flex items-start justify-between gap-3">

                    <div>

                        <div class="font-bold text-slate-900">
                            View Call Logs
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            Team ki call activity review karein.
                        </div>

                    </div>


                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2"/>
                            <path d="M15.05 14.95a16 16 0 0 1-6-6"/>
                            <path d="M7.1 3H4a2 2 0 0 0-2 2c0 9.4 7.6 17 17 17a2 2 0 0 0 2-2v-3.1"/>
                        </svg>

                    </div>

                </div>

            </a>

        @endcan

    </div>


    {{-- ================================================================ --}}
    {{-- Recent Leads --}}
    {{-- ================================================================ --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        <div
            class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >

            <div>

                <h2 class="font-bold text-slate-900">
                    Recent Leads
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Latest added leads.
                </p>

            </div>


            @can('leads.view')

                <a
                    href="{{ route('leads.index') }}"
                    class="inline-flex self-start items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 sm:self-auto"
                >
                    View All

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>

                </a>

            @endcan

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[760px] text-sm">

                <thead class="bg-slate-50">

                    <tr
                        class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500"
                    >

                        <th class="px-5 py-3">
                            Lead
                        </th>

                        <th class="px-4 py-3">
                            Mobile
                        </th>

                        <th class="px-4 py-3">
                            Status
                        </th>

                        <th class="px-4 py-3">
                            Owner
                        </th>

                        <th class="px-5 py-3 text-right">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($recentLeads as $lead)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Lead --}}

                            <td class="px-5 py-3">

                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="font-semibold text-blue-600 hover:text-blue-700"
                                >
                                    {{ $lead->name }}
                                </a>


                                @if($lead->company_name)

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->company_name }}
                                    </div>

                                @endif

                            </td>


                            {{-- Mobile --}}

                            <td class="px-4 py-3">

                                <div class="font-medium text-slate-800">
                                    {{ $lead->mobile }}
                                </div>


                                @if($lead->city)

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->city }}
                                    </div>

                                @endif

                            </td>


                            {{-- Status --}}

                            <td class="px-4 py-3">

                                <span
                                    class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"
                                >
                                    {{ $lead->status?->name ?? 'New' }}
                                </span>

                            </td>


                            {{-- Owner --}}

                            <td class="px-4 py-3">

                                @if($lead->assignedUser)

                                    <div class="font-medium text-slate-800">
                                        {{ $lead->assignedUser->name }}
                                    </div>


                                    @if($lead->assignedUser->employee_code)

                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $lead->assignedUser->employee_code }}
                                        </div>

                                    @endif

                                @else

                                    <span
                                        class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                    >
                                        Unassigned
                                    </span>

                                @endif

                            </td>


                            {{-- Action --}}

                            <td class="px-5 py-3 text-right">

                                @can('leads.view')

                                    <a
                                        href="{{ route('leads.show', $lead) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600"
                                    >
                                        Open
                                    </a>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="px-5 py-14 text-center"
                            >

                                <div
                                    class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                                >

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                    </svg>

                                </div>


                                <div class="mt-3 font-semibold text-slate-700">
                                    No leads yet
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    First lead add karke CRM use karna start karein.
                                </div>


                                @can('leads.create')

                                    <a
                                        href="{{ route('leads.create') }}"
                                        class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Add Lead
                                    </a>

                                @endcan

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

</div>

@endsection