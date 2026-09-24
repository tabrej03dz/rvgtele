@extends('layouts.crm', [
    'title' => 'Dashboard',
])

@section('content')

@php
    $crmCompletion = 30;
    $crmRemaining = 100 - $crmCompletion;

    $currentUser = auth()->user();
    $currentRole = method_exists($currentUser, 'getRoleNames')
        ? ($currentUser->getRoleNames()->first() ?? 'User')
        : 'User';

    $ordersUrl = \Illuminate\Support\Facades\Route::has('orders.index')
        ? route('orders.index')
        : '#overall-statistics';

    $paymentsUrl = \Illuminate\Support\Facades\Route::has('payments.index')
        ? route('payments.index')
        : '#overall-statistics';

    $employeesUrl = $hasFullAccess
        ? '#employee-performance'
        : route('leads.index');

    $todayCards = [
        [
            'label' => 'Today Leads',
            'value' => number_format($todayNewLeads),
            'icon' => 'users',
            'url' => route('leads.index'),
            'card' => 'border-violet-200 bg-gradient-to-br from-violet-50 to-violet-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-violet-600',
            'arrow' => 'text-violet-600',
        ],
        [
            'label' => 'Today Called',
            'value' => number_format($todayCalls),
            'icon' => 'phone',
            'url' => route('calls.index'),
            'card' => 'border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-blue-600',
            'arrow' => 'text-blue-600',
        ],
        [
            'label' => 'Today Connected',
            'value' => number_format($todayConnected),
            'icon' => 'connected',
            'url' => route('calls.index'),
            'card' => 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-emerald-600',
            'arrow' => 'text-emerald-600',
        ],
        [
            'label' => 'Today Demo',
            'value' => number_format($todayDemos),
            'icon' => 'demo',
            'url' => route('leads.index'),
            'card' => 'border-rose-200 bg-gradient-to-br from-rose-50 to-rose-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-rose-600',
            'arrow' => 'text-rose-600',
        ],
        [
            'label' => 'Today Follow-ups',
            'value' => number_format($todayPendingFollowUps),
            'icon' => 'calendar',
            'url' => route('followups.index'),
            'card' => 'border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-amber-600',
            'arrow' => 'text-amber-600',
        ],
        [
            'label' => 'Today Overdue',
            'value' => number_format($todayOverdue),
            'icon' => 'alert',
            'url' => route('followups.index'),
            'card' => 'border-red-200 bg-gradient-to-br from-red-50 to-red-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-red-600',
            'arrow' => 'text-red-600',
        ],
        [
            'label' => 'Hot Leads',
            'value' => number_format($hotLeads),
            'icon' => 'hot',
            'url' => route('leads.index'),
            'card' => 'border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-orange-600',
            'arrow' => 'text-orange-600',
        ],
        [
            'label' => 'Active Employees',
            'value' => number_format($activeUsers),
            'icon' => 'employee',
            'url' => $employeesUrl,
            'card' => 'border-cyan-200 bg-gradient-to-br from-cyan-50 to-cyan-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-cyan-600',
            'arrow' => 'text-cyan-600',
        ],
    ];

    $overallCards = [
        [
            'label' => 'Total Leads',
            'value' => number_format($totalLeads),
            'icon' => 'users',
            'url' => route('leads.index'),
            'card' => 'border-violet-200 bg-gradient-to-br from-violet-50 to-violet-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-violet-600',
            'arrow' => 'text-violet-600',
        ],
        [
            'label' => 'Total Calls',
            'value' => number_format($allCalls),
            'icon' => 'phone',
            'url' => route('calls.index'),
            'card' => 'border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-blue-600',
            'arrow' => 'text-blue-600',
        ],
        [
            'label' => 'Total Connected',
            'value' => number_format($allConnected),
            'icon' => 'connected',
            'url' => route('calls.index'),
            'card' => 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-emerald-600',
            'arrow' => 'text-emerald-600',
        ],
        [
            'label' => 'Total Demo',
            'value' => number_format($totalLeadSend),
            'icon' => 'demo',
            'url' => route('leads.index'),
            'card' => 'border-rose-200 bg-gradient-to-br from-rose-50 to-rose-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-rose-600',
            'arrow' => 'text-rose-600',
        ],
        [
            'label' => 'Pending Follow-ups',
            'value' => number_format($allPendingFollowUps),
            'icon' => 'calendar',
            'url' => route('followups.index'),
            'card' => 'border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-amber-600',
            'arrow' => 'text-amber-600',
        ],
        [
            'label' => 'Total Overdue',
            'value' => number_format($allOverdue),
            'icon' => 'alert',
            'url' => route('followups.index'),
            'card' => 'border-red-200 bg-gradient-to-br from-red-50 to-red-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-red-600',
            'arrow' => 'text-red-600',
        ],
        [
            'label' => 'Sales Value',
            'value' => '₹' . number_format($allSales, 2),
            'icon' => 'sales',
            'url' => $ordersUrl,
            'card' => 'border-indigo-200 bg-gradient-to-br from-indigo-50 to-indigo-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-indigo-600',
            'arrow' => 'text-indigo-600',
        ],
        [
            'label' => 'Payment Received',
            'value' => '₹' . number_format($allReceived, 2),
            'icon' => 'payment',
            'url' => $paymentsUrl,
            'card' => 'border-teal-200 bg-gradient-to-br from-teal-50 to-teal-100/70',
            'icon_bg' => 'bg-white',
            'icon_text' => 'text-teal-600',
            'arrow' => 'text-teal-600',
        ],
    ];
@endphp

<style>
    .rvg-dashboard-card {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .rvg-dashboard-card:hover {
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.11);
    }

    .rvg-progress-shine {
        position: relative;
        overflow: hidden;
    }

    .rvg-progress-shine::after {
        content: "";
        position: absolute;
        inset: 0;
        width: 36%;
        transform: translateX(-140%);
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.45),
            transparent
        );
        animation: rvg-progress-shine 3.8s linear infinite;
    }

    @keyframes rvg-progress-shine {
        to {
            transform: translateX(390%);
        }
    }

    .rvg-kpi-card .rvg-kpi-icon svg {
        width: 20px !important;
        height: 20px !important;
    }

    .rvg-kpi-card .rvg-kpi-value {
        font-size: 1.25rem !important;
    }

    .rvg-kpi-card .rvg-kpi-label {
        font-size: .75rem !important;
        line-height: 1rem;
    }
</style>

<div class="mx-auto max-w-[1600px] space-y-4">

    {{-- ================================================================ --}}
    {{-- CRM Completion Banner --}}
    {{-- ================================================================ --}}

    <a
        href="#quick-links"
        class="group relative block overflow-hidden rounded-[22px] border border-sky-800/40 bg-slate-950 shadow-[0_18px_50px_rgba(2,32,71,0.18)]"
        style="background:
            radial-gradient(circle at 72% 30%, rgba(255,193,92,.45), transparent 22%),
            radial-gradient(circle at 92% 15%, rgba(80,176,255,.38), transparent 28%),
            linear-gradient(110deg, #07345a 0%, #07588a 48%, #0b3154 100%);"
    >
        <div class="absolute inset-0 opacity-20"
             style="background-image:
                linear-gradient(30deg, rgba(255,255,255,.08) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.08) 87.5%, rgba(255,255,255,.08)),
                linear-gradient(150deg, rgba(255,255,255,.08) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.08) 87.5%, rgba(255,255,255,.08)); background-size: 46px 80px;">
        </div>

        <svg
            class="absolute bottom-0 right-[12%] h-16 w-[300px] text-slate-950/30 sm:h-20"
            viewBox="0 0 500 140"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M0 140 95 63l51 38 62-70 72 67 44-36 93 78H0Z"/>
        </svg>

        <div class="relative grid gap-4 px-4 py-4 lg:grid-cols-[1.15fr_.85fr] lg:items-center lg:px-5 lg:py-4">
            <div class="flex items-center gap-3">
                <div class="hidden h-14 w-14 shrink-0 items-center justify-center rounded-full border-4 border-white/25 bg-white/10 shadow-xl backdrop-blur sm:flex">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-500 text-xl shadow-inner">
                        🎯
                    </div>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1.5 text-base font-black leading-tight text-white sm:text-lg lg:text-xl">
                        <span>Aapne CRM Panel ka</span>
                        <span class="rounded-xl bg-gradient-to-b from-amber-300 to-amber-400 px-3 py-1 text-slate-950 shadow-lg">
                            {{ $crmCompletion }}%
                        </span>
                        <span>complete kar liya hai — ab</span>
                        <span class="rounded-xl bg-gradient-to-b from-emerald-400 to-teal-500 px-3 py-1 text-white shadow-lg">
                            {{ $crmRemaining }}%
                        </span>
                        <span>aur complete karna baaki hai.</span>
                    </div>

                    <div class="mt-2 text-xs font-semibold italic text-white/80 sm:text-sm">
                        Thoda aur effort, aur badi success!
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="mb-1.5 flex items-center justify-between text-[10px] font-bold text-white/80 sm:text-xs">
                    <span>{{ $crmCompletion }}% Complete</span>
                    <span>{{ $crmRemaining }}% Remaining</span>
                </div>

                <div class="rounded-full border border-white/35 bg-slate-950/35 p-1.5 shadow-inner backdrop-blur">
                    <div class="h-3 overflow-hidden rounded-full bg-white/15 sm:h-3.5">
                        <div
                            class="rvg-progress-shine h-full rounded-full bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400 shadow-[0_0_20px_rgba(251,191,36,.55)] transition-all duration-700"
                            style="width: {{ $crmCompletion }}%"
                        ></div>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-end gap-1.5 text-xs font-black italic text-white">
                    <span>You Can Do It!</span>
                    <svg class="h-4 w-4 text-amber-300 transition group-hover:translate-x-1 group-hover:-translate-y-1"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2 11 13"/>
                        <path d="m22 2-7 20-4-9-9-4Z"/>
                    </svg>
                </div>
            </div>
        </div>
    </a>

    {{-- ================================================================ --}}
    {{-- Today Performance Cards --}}
    {{-- ================================================================ --}}

    <section id="today-performance" class="rounded-[22px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="mb-3 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-200 to-amber-400 text-slate-900 shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-950 sm:text-lg">
                        Today’s Performance Overview
                    </h2>
                    <p class="text-xs text-slate-500">
                        Aaj ke calls, leads, demo aur follow-ups ka live data.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-bold text-slate-700">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>
                    {{ now()->format('d M Y') }}
                </span>

                @can('leads.import')
                    <a href="{{ route('leads.import.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                        Import Leads
                    </a>
                @endcan

                @can('leads.create')
                    <a href="{{ route('leads.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-2.5 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-blue-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Add Lead
                    </a>
                @endcan

                <span class="hidden text-[11px] font-black italic text-slate-600 lg:inline">
                    Small Steps <span class="text-blue-600">Big Results</span> ✈
                </span>
            </div>
        </div>

        <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($todayCards as $card)
                <a
                    href="{{ $card['url'] }}"
                    class="rvg-dashboard-card rvg-kpi-card group relative min-h-[84px] overflow-hidden rounded-xl border p-3 transition duration-200 hover:-translate-y-0.5 {{ $card['card'] }}"
                >
                    <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/35"></div>

                    <div class="relative flex items-center gap-3">
                        <div class="rvg-kpi-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full shadow-[0_4px_12px_rgba(15,23,42,.10)] {{ $card['icon_bg'] }} {{ $card['icon_text'] }}">
                            @switch($card['icon'])
                                @case('users')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6M23 11h-6"/>
                                    </svg>
                                    @break
                                @case('phone')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.09 5.18 2 2 0 0 1 5.07 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L9 10.68a16 16 0 0 0 4.32 4.32l1.23-1.23a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92Z"/>
                                    </svg>
                                    @break
                                @case('connected')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2"/>
                                        <path d="M15.05 14.95a16 16 0 0 1-6-6"/>
                                        <path d="m14 4 2 2 4-4"/>
                                        <path d="M7.1 3H4a2 2 0 0 0-2 2c0 9.4 7.6 17 17 17a2 2 0 0 0 2-2v-3.1"/>
                                    </svg>
                                    @break
                                @case('demo')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="m3 3 18 9-18 9 4-9-4-9Z"/>
                                        <path d="M7 12h14"/>
                                    </svg>
                                    @break
                                @case('calendar')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <rect x="3" y="4" width="18" height="17" rx="2"/>
                                        <path d="M16 2v4M8 2v4M3 10h18"/>
                                        <circle cx="12" cy="15" r="2"/>
                                    </svg>
                                    @break
                                @case('alert')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/>
                                        <path d="M12 9v4M12 17h.01"/>
                                    </svg>
                                    @break
                                @case('hot')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M12 22c4 0 7-3 7-7 0-3-1.5-5.5-4.5-8.5.2 2-1 3.2-2.5 4.5.2-4-1.8-6.5-5-9 0 4-3 6.5-3 11 0 5 3.5 9 8 9Z"/>
                                    </svg>
                                    @break
                                @case('employee')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M2 21v-2a6 6 0 0 1 6-6h2"/>
                                        <path d="m16 17 2 2 4-5"/>
                                    </svg>
                                    @break
                            @endswitch
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="rvg-kpi-value text-xl font-black leading-none tracking-tight text-slate-950">
                                {{ $card['value'] }}
                            </div>
                            <div class="rvg-kpi-label mt-1.5 text-xs font-bold text-slate-700">
                                {{ $card['label'] }}
                            </div>
                        </div>

                        <svg class="h-5 w-5 shrink-0 transition group-hover:translate-x-1 {{ $card['arrow'] }}"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Overall Statistics --}}
    {{-- ================================================================ --}}

    <section id="overall-statistics" class="rounded-[22px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-200 to-amber-400 text-slate-900 shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-950 sm:text-lg">
                        Overall Statistics <span class="text-slate-400">(All Time)</span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Total reports with complete data.
                    </p>
                </div>
            </div>

            <a
                href="#quick-links"
                class="inline-flex items-center gap-1.5 self-start rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-bold text-slate-700 transition hover:bg-white hover:text-blue-600 sm:self-auto"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <path d="M16 3v4M8 3v4M3 11h18"/>
                </svg>
                All Time
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
            </a>
        </div>

        <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($overallCards as $card)
                <a
                    href="{{ $card['url'] }}"
                    class="rvg-dashboard-card rvg-kpi-card group relative min-h-[84px] overflow-hidden rounded-xl border p-3 transition duration-200 hover:-translate-y-0.5 {{ $card['card'] }}"
                >
                    <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/35"></div>

                    <div class="relative flex items-center gap-3">
                        <div class="rvg-kpi-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full shadow-[0_4px_12px_rgba(15,23,42,.10)] {{ $card['icon_bg'] }} {{ $card['icon_text'] }}">
                            @switch($card['icon'])
                                @case('users')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6M23 11h-6"/>
                                    </svg>
                                    @break
                                @case('phone')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.09 5.18 2 2 0 0 1 5.07 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L9 10.68a16 16 0 0 0 4.32 4.32l1.23-1.23a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92Z"/>
                                    </svg>
                                    @break
                                @case('connected')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2"/>
                                        <path d="M15.05 14.95a16 16 0 0 1-6-6"/>
                                        <path d="m14 4 2 2 4-4"/>
                                        <path d="M7.1 3H4a2 2 0 0 0-2 2c0 9.4 7.6 17 17 17a2 2 0 0 0 2-2v-3.1"/>
                                    </svg>
                                    @break
                                @case('demo')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="m3 3 18 9-18 9 4-9-4-9Z"/>
                                        <path d="M7 12h14"/>
                                    </svg>
                                    @break
                                @case('calendar')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <rect x="3" y="4" width="18" height="17" rx="2"/>
                                        <path d="M16 2v4M8 2v4M3 10h18"/>
                                        <circle cx="12" cy="15" r="2"/>
                                    </svg>
                                    @break
                                @case('alert')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/>
                                        <path d="M12 9v4M12 17h.01"/>
                                    </svg>
                                    @break
                                @case('sales')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                    @break
                                @case('payment')
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                                        <path d="M2 10h20M16 15h2"/>
                                    </svg>
                                    @break
                            @endswitch
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="rvg-kpi-value truncate text-xl font-black leading-none tracking-tight text-slate-950" title="{{ $card['value'] }}">
                                {{ $card['value'] }}
                            </div>
                            <div class="rvg-kpi-label mt-1.5 text-xs font-bold text-slate-700">
                                {{ $card['label'] }}
                            </div>
                        </div>

                        <svg class="h-5 w-5 shrink-0 transition group-hover:translate-x-1 {{ $card['arrow'] }}"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>

        <a
            href="#quick-links"
            class="mt-3 flex flex-col gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-2.5 text-white transition hover:-translate-y-0.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-400 text-lg shadow-md">
                    🏆
                </div>
                <div>
                    <div class="text-sm font-black text-amber-300">
                        Keep Going!
                    </div>
                    <div class="text-sm text-white/70">
                        Track your performance and achieve your targets.
                    </div>
                </div>
            </div>

            <div class="text-sm font-bold italic text-white/70">
                Success is a Journey →
            </div>
        </a>
    </section>

    {{-- ================================================================ --}}
    {{-- Dynamic Disposition Statistics --}}
    {{-- ================================================================ --}}

    <section id="call-dispositions"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        {{-- Header --}}

        <div
            class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between"
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
                            {{ $dispositionPeriodLabel }} disposition wise call statistics
                        </p>

                    </div>

                </div>

            </div>


            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ url()->current() }}"
                    class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                    <input type="hidden" name="period" value="{{ $period }}">

                    @foreach(['today' => 'Today', 'month' => 'Month', 'all' => 'All'] as $key => $label)
                        <button type="submit" name="disposition_period" value="{{ $key }}"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition
                            {{ $dispositionPeriod === $key
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </form>

                <div class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                    Total Calls:
                    <span class="text-slate-900">
                        {{ number_format($dispositionTotalCalls) }}
                    </span>
                </div>

            </div>

        </div>


        {{-- Disposition Cards --}}

        <div class="p-5">

            @if($dispositionStats->isNotEmpty())

                <div
                    class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
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


                            $percentage = $dispositionTotalCalls > 0
                                ? ($disposition['total'] / $dispositionTotalCalls) * 100
                                : 0;

                        @endphp


                        <div
                            class="group relative overflow-hidden rounded-xl border {{ $style['border'] }} {{ $style['bg'] }} p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
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
                                class="mt-3 text-2xl font-black tracking-tight {{ $style['number'] }}"
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

                            <div class="mt-3 flex flex-wrap gap-1.5">

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

                            $withoutDispositionPercentage = $dispositionTotalCalls > 0
                                ? ($withoutDisposition / $dispositionTotalCalls) * 100
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
{{-- Employee Performance --}}
{{-- ================================================================ --}}

@if($hasFullAccess)

<section id="employee-performance"
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
>

    {{-- Header --}}

    <div
        class="border-b border-slate-200 px-4 py-3.5"
    >

        <div
            class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
        >

            <div>

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600"
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
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        </svg>
                    </div>

                    <div>

                        <h2
                            class="font-bold text-slate-900"
                        >
                            Employee Performance
                        </h2>

                        <p
                            class="mt-0.5 text-sm text-slate-500"
                        >
                            Calls, demos, leads aur follow-ups ki employee-wise tracking.
                        </p>

                    </div>

                </div>

            </div>


            {{-- Quick Period Filter --}}

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="flex flex-wrap items-center gap-2"
            >

                {{-- Preserve Main Dashboard Filter --}}

                <input
                    type="hidden"
                    name="period"
                    value="{{ $period }}"
                >

                <input
                    type="hidden"
                    name="disposition_period"
                    value="{{ $dispositionPeriod }}"
                >


                <div
                    class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1"
                >

                    @foreach([
                        'today' => 'Today',
                        'month' => 'Month',
                        'all' => 'All'
                    ] as $key => $label)

                        <button
                            type="submit"
                            name="employee_period"
                            value="{{ $key }}"
                            class="rounded-lg px-3 py-2 text-xs font-bold transition
                            {{
                                $employeePeriod === $key
                                    ? 'bg-violet-600 text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-white'
                            }}"
                        >
                            {{ $label }}
                        </button>

                    @endforeach

                </div>

            </form>

        </div>


        {{-- Custom Date Filter --}}

        <form
            method="GET"
            action="{{ url()->current() }}"
            class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-end"
        >

            <input
                type="hidden"
                name="period"
                value="{{ $period }}"
            >

            <input
                type="hidden"
                name="disposition_period"
                value="{{ $dispositionPeriod }}"
            >

            <input
                type="hidden"
                name="employee_period"
                value="custom"
            >


            <div>

                <label
                    class="mb-1 block text-xs font-bold text-slate-600"
                >
                    From Date
                </label>

                <input
                    type="date"
                    name="employee_from"
                    value="{{ $employeeFrom }}"
                    required
                    class="rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                >

            </div>


            <div>

                <label
                    class="mb-1 block text-xs font-bold text-slate-600"
                >
                    To Date
                </label>

                <input
                    type="date"
                    name="employee_to"
                    value="{{ $employeeTo }}"
                    required
                    class="rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                >

            </div>


            <button
                type="submit"
                class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700"
            >
                Apply Filter
            </button>


            <div
                class="sm:ml-auto"
            >

                <div
                    class="rounded-lg border border-violet-100 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700"
                >
                    Showing:
                    {{ $employeePeriodLabel }}
                </div>

            </div>

        </form>

    </div>


    {{-- Employee Table --}}

    <div class="overflow-x-auto">

        <table
            class="w-full min-w-[1050px] text-sm"
        >

            <thead class="bg-slate-50">

                <tr
                    class="border-b border-slate-200 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500"
                >

                    <th class="px-5 py-3">
                        Employee
                    </th>

                    <th class="px-4 py-3 text-center">
                        Leads
                    </th>

                    <th class="px-4 py-3 text-center">
                        Calls
                    </th>

                    <th class="px-4 py-3 text-center">
                        Connected
                    </th>

                    <th class="px-4 py-3 text-center">
                        Connect %
                    </th>

                    <th class="px-4 py-3 text-center">
                        Demo Sent
                    </th>

                    <th class="px-4 py-3 text-center">
                        Follow-ups
                    </th>

                    <th class="px-4 py-3 text-center">
                        Pending
                    </th>

                    <th class="px-5 py-3 text-right">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody
                class="divide-y divide-slate-100"
            >

                @forelse(
                    $employeePerformance
                    as $row
                )

                    @php
                        $employee = $row['user'];
                    @endphp

                    <tr
                        class="transition hover:bg-violet-50/40"
                    >

                        {{-- Employee --}}

                        <td class="px-5 py-4">

                            <a
                                href="{{ route('dashboard.employee', $employee) }}"
                                class="font-bold text-slate-900 hover:text-violet-600"
                            >
                                {{ $employee->name }}
                            </a>


                            @if($employee->employee_code)

                                <div
                                    class="mt-0.5 text-xs text-slate-500"
                                >
                                    {{ $employee->employee_code }}
                                </div>

                            @endif

                        </td>


                        {{-- Leads --}}

                        <td
                            class="px-4 py-4 text-center font-bold text-slate-700"
                        >
                            {{ number_format(
                                $row['total_leads']
                            ) }}
                        </td>


                        {{-- Calls --}}

                        <td
                            class="px-4 py-4 text-center"
                        >

                            <span
                                class="inline-flex min-w-[44px] justify-center rounded-lg bg-blue-50 px-2.5 py-1.5 font-black text-blue-700"
                            >
                                {{ number_format(
                                    $row['calls']
                                ) }}
                            </span>

                        </td>


                        {{-- Connected --}}

                        <td
                            class="px-4 py-4 text-center"
                        >

                            <span
                                class="font-bold text-emerald-600"
                            >
                                {{ number_format(
                                    $row['connected']
                                ) }}
                            </span>

                        </td>


                        {{-- Percentage --}}

                        <td
                            class="px-4 py-4 text-center"
                        >

                            <div
                                class="font-bold text-slate-800"
                            >
                                {{
                                    number_format(
                                        $row[
                                            'connected_percentage'
                                        ],
                                        1
                                    )
                                }}%
                            </div>

                            <div
                                class="mx-auto mt-1 h-1.5 w-16 overflow-hidden rounded-full bg-slate-100"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500"
                                    style="width: {{
                                        min(
                                            100,
                                            $row[
                                                'connected_percentage'
                                            ]
                                        )
                                    }}%"
                                ></div>
                            </div>

                        </td>


                        {{-- Demo --}}

                        <td
                            class="px-4 py-4 text-center"
                        >

                            <span
                                class="inline-flex min-w-[40px] justify-center rounded-lg bg-violet-50 px-2 py-1.5 font-black text-violet-700"
                            >
                                {{ number_format(
                                    $row['demos']
                                ) }}
                            </span>

                        </td>


                        {{-- Followups --}}

                        <td
                            class="px-4 py-4 text-center font-bold text-amber-600"
                        >
                            {{ number_format(
                                $row['followups']
                            ) }}
                        </td>


                        {{-- Pending --}}

                        <td
                            class="px-4 py-4 text-center"
                        >

                            @if(
                                $row[
                                    'pending_followups'
                                ] > 0
                            )

                                <span
                                    class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600"
                                >
                                    {{
                                        number_format(
                                            $row[
                                                'pending_followups'
                                            ]
                                        )
                                    }}
                                </span>

                            @else

                                <span
                                    class="text-emerald-600"
                                >
                                    ✓
                                </span>

                            @endif

                        </td>


                        {{-- Action --}}

                        <td
                            class="px-5 py-4 text-right"
                        >

                            <a
                                href="{{ route(
                                    'dashboard.employee',
                                    $employee
                                ) }}"
                                class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white"
                            >
                                View Details

                                <span>
                                    →
                                </span>
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="px-5 py-14 text-center text-slate-500"
                        >
                            No employees found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</section>

@endif






    {{-- ================================================================ --}}
    {{-- Quick Links --}}
    {{-- ================================================================ --}}

    <div id="quick-links" class="grid gap-4 md:grid-cols-3">

        @can('leads.view')

            <a
                href="{{ route('leads.index') }}"
                class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md"
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
                class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-amber-200 hover:shadow-md"
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
                class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md"
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

    <section id="recent-leads"
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
