@extends('layouts.crm', [
    'title' => 'Dashboard',
])

@section('content')

@php
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


    /*
    |--------------------------------------------------------------------------
    | Logged-in User Overall Work Circle
    |--------------------------------------------------------------------------
    */

    $crmRingRadius = 58;
    $crmRingCircumference = 2 * pi() * $crmRingRadius;
    $crmRingOffset = $crmRingCircumference - (($crmCompletion / 100) * $crmRingCircumference);

    $crmPerformanceStatus =
        $crmCompletion >= 80 ? 'Excellent Progress' :
        ($crmCompletion >= 60 ? 'Good Progress' :
        ($crmCompletion >= 40 ? 'Average Progress' :
        ($crmCompletion >= 1 ? 'Needs More Activity' : 'Work Not Started')));

    $crmPerformanceColor =
        $crmCompletion >= 80 ? 'text-emerald-200' :
        ($crmCompletion >= 60 ? 'text-cyan-200' :
        ($crmCompletion >= 40 ? 'text-amber-200' :
        'text-rose-200'));

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

    .crm-hero-glass {
        background: linear-gradient(
            135deg,
            rgba(255,255,255,.12),
            rgba(255,255,255,.05)
        );
        border: 1px solid rgba(255,255,255,.14);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .crm-progress-ring {
        filter: drop-shadow(0 12px 28px rgba(15, 23, 42, .25));
    }

    .crm-progress-ring__track {
        stroke: rgba(255,255,255,.12);
    }

    .crm-progress-ring__bar {
        stroke-linecap: round;
        stroke: url(#crmProgressGradient);
        transition: stroke-dashoffset .8s ease;
    }

    .crm-metric-card {
        background: linear-gradient(
            180deg,
            rgba(255,255,255,.13),
            rgba(255,255,255,.08)
        );
        border: 1px solid rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
    }

    .crm-mini-bar {
        background: rgba(255,255,255,.10);
    }

    .crm-mini-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
    }

    .crm-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    .crm-circle-caption {
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    @media (max-width: 1024px) {
        .crm-work-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 640px) {
        .crm-metric-grid {
            grid-template-columns: 1fr !important;
        }
    }


    .analytics-chart-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background:
            radial-gradient(circle at top right, rgba(59,130,246,.08), transparent 26%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .analytics-chart-wrap {
        position: relative;
        height: 340px;
    }

    .analytics-mini-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    @media (max-width: 640px) {
        .analytics-chart-wrap {
            height: 280px;
        }
    }



    .trend-range-btn.active {
        background: #fff;
        color: #2563eb;
        box-shadow: 0 2px 8px rgba(15,23,42,.06);
    }

    #advanced-analytics .analytics-chart-card {
        background:
            radial-gradient(circle at 85% 0%, rgba(59,130,246,.07), transparent 25%),
            linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    @media (max-width: 640px) {
        #advanced-analytics .analytics-chart-wrap {
            height: 300px !important;
        }
    }

</style>

<div class="mx-auto max-w-[1600px] space-y-4">

    {{-- ================================================================ --}}
    {{-- Logged-in User Overall Work Progress --}}
    {{-- ================================================================ --}}

    <section
        class="relative overflow-hidden rounded-[26px] border border-sky-800/40 bg-slate-950 shadow-[0_20px_55px_rgba(2,32,71,0.22)]"
        style="background:
            radial-gradient(circle at 78% 20%, rgba(255,197,94,.30), transparent 18%),
            radial-gradient(circle at 92% 12%, rgba(84,190,255,.28), transparent 20%),
            linear-gradient(115deg, #07345a 0%, #0a5d91 46%, #0c3358 100%);"
    >
        {{-- Decorative Pattern --}}
        <div
            class="absolute inset-0 opacity-20"
            style="background-image:
                linear-gradient(30deg, rgba(255,255,255,.08) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.08) 87.5%, rgba(255,255,255,.08)),
                linear-gradient(150deg, rgba(255,255,255,.08) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.08) 87.5%, rgba(255,255,255,.08));
                background-size: 46px 80px;"
        ></div>

        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[38%] h-72 w-72 rounded-full bg-amber-300/10 blur-3xl"></div>

        <svg
            class="absolute bottom-0 right-[8%] h-20 w-[330px] text-slate-950/20 sm:h-24"
            viewBox="0 0 500 140"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M0 140 95 63l51 38 62-70 72 67 44-36 93 78H0Z"/>
        </svg>

        <div class="relative px-4 py-5 sm:px-5 lg:px-6 lg:py-6">
            <div class="crm-work-grid grid gap-5 lg:grid-cols-[1.28fr_.72fr] lg:items-stretch">

                {{-- ======================================================== --}}
                {{-- LEFT SIDE --}}
                {{-- ======================================================== --}}
                <div class="flex min-w-0 flex-col justify-between gap-5">
                    <div class="flex items-start gap-3">
                        <div class="hidden h-16 w-16 shrink-0 items-center justify-center rounded-full border-4 border-white/20 bg-white/10 shadow-xl backdrop-blur sm:flex">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-500 text-[22px] shadow-inner">
                                🎯
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="text-[11px] font-black uppercase tracking-[.16em] text-cyan-100/70">
                                My Work Performance
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-lg font-black leading-tight text-white sm:text-2xl">
                                <span>Assigned leads par overall</span>

                                <span class="rounded-2xl bg-gradient-to-b from-amber-300 to-amber-400 px-3.5 py-1.5 text-slate-950 shadow-lg">
                                    {{ $crmCompletion }}%
                                </span>

                                <span>work complete</span>
                            </div>

                            <div class="mt-2 max-w-3xl text-xs font-semibold leading-5 text-white/75 sm:text-sm">
                                Call coverage, Demo coverage aur Follow-up completion ke basis par score calculate hota hai.
                                Overdue follow-up hone par score se points reduce hote hain.
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="crm-stat-pill bg-white/10 text-white">
                                    <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                                    Overall {{ $crmCompletion }}%
                                </span>

                                <span class="crm-stat-pill bg-emerald-500/15 {{ $crmPerformanceColor }}">
                                    {{ $crmPerformanceStatus }}
                                </span>

                                <span class="crm-stat-pill bg-white/10 text-white/85">
                                    {{ $crmRemaining }}% Remaining
                                </span>

                                @if($myOverduePenalty > 0)
                                    <span class="crm-stat-pill bg-rose-500/20 text-rose-100">
                                        Overdue Penalty -{{ number_format($myOverduePenalty, 1) }} pts
                                    </span>
                                @else
                                    <span class="crm-stat-pill bg-cyan-500/15 text-cyan-100">
                                        No Overdue Penalty
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Metric Cards --}}
                    <div class="crm-metric-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-5">

                        {{-- Assigned Leads --}}
                        <div class="crm-metric-card rounded-2xl px-4 py-3 text-white">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
                                    Assigned Leads
                                </div>

                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-cyan-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6M23 11h-6"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="mt-2 text-3xl font-black leading-none">
                                {{ number_format($myAssignedLeads) }}
                            </div>

                            <div class="mt-2 text-[10px] text-white/55">
                                Current assigned leads
                            </div>
                        </div>

                        {{-- Called Leads --}}
                        <div class="crm-metric-card rounded-2xl px-4 py-3 text-white">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
                                    Called Leads
                                </div>

                                <span class="rounded-full bg-blue-400/20 px-2 py-1 text-[10px] font-black text-blue-100">
                                    {{ number_format($myCallPercentage, 1) }}%
                                </span>
                            </div>

                            <div class="mt-2 text-2xl font-black leading-none">
                                {{ number_format($myCalledLeads) }}
                                <span class="text-xs font-bold text-white/50">
                                    / {{ number_format($myAssignedLeads) }}
                                </span>
                            </div>

                            <div class="mt-3 h-1.5 overflow-hidden rounded-full crm-mini-bar">
                                <span
                                    class="bg-blue-300"
                                    style="width: {{ min(100, $myCallPercentage) }}%"
                                ></span>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[9px] font-bold text-white/50">
                                <span>Call Coverage</span>
                                <span>40% Weight</span>
                            </div>
                        </div>

                        {{-- Demo Leads --}}
                        <div class="crm-metric-card rounded-2xl px-4 py-3 text-white">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
                                    Demo Leads
                                </div>

                                <span class="rounded-full bg-violet-400/20 px-2 py-1 text-[10px] font-black text-violet-100">
                                    {{ number_format($myDemoPercentage, 1) }}%
                                </span>
                            </div>

                            <div class="mt-2 text-2xl font-black leading-none">
                                {{ number_format($myDemoLeads) }}
                                <span class="text-xs font-bold text-white/50">
                                    / {{ number_format($myAssignedLeads) }}
                                </span>
                            </div>

                            <div class="mt-3 h-1.5 overflow-hidden rounded-full crm-mini-bar">
                                <span
                                    class="bg-violet-300"
                                    style="width: {{ min(100, $myDemoPercentage) }}%"
                                ></span>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[9px] font-bold text-white/50">
                                <span>Demo Coverage</span>
                                <span>30% Weight</span>
                            </div>
                        </div>

                        {{-- Follow-up Complete --}}
                        <div class="crm-metric-card rounded-2xl px-4 py-3 text-white">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-black uppercase tracking-wide text-white/60">
                                    Follow-up Complete
                                </div>

                                <span class="rounded-full bg-emerald-400/20 px-2 py-1 text-[10px] font-black text-emerald-100">
                                    {{ number_format($myFollowUpPercentage, 1) }}%
                                </span>
                            </div>

                            <div class="mt-2 text-2xl font-black leading-none">
                                {{ number_format($myCompletedFollowUps) }}
                                <span class="text-xs font-bold text-white/50">
                                    / {{ number_format($myFollowUps) }}
                                </span>
                            </div>

                            <div class="mt-3 h-1.5 overflow-hidden rounded-full crm-mini-bar">
                                <span
                                    class="bg-emerald-300"
                                    style="width: {{ min(100, $myFollowUpPercentage) }}%"
                                ></span>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[9px] font-bold text-white/50">
                                <span>Completion</span>
                                <span>30% Weight</span>
                            </div>
                        </div>

                        {{-- Overdue --}}
                        <div class="rounded-2xl border border-rose-300/25 bg-rose-500/10 px-4 py-3 text-white backdrop-blur">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-[10px] font-black uppercase tracking-wide text-rose-100/70">
                                    Overdue Follow-up
                                </div>

                                <span class="rounded-full bg-rose-500/25 px-2 py-1 text-[10px] font-black text-rose-100">
                                    -{{ number_format($myOverduePenalty, 1) }} pts
                                </span>
                            </div>

                            <div class="mt-2 text-2xl font-black leading-none">
                                {{ number_format($myOverdueFollowUps) }}
                            </div>

                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-rose-950/20">
                                <span
                                    class="block h-full rounded-full bg-rose-300"
                                    style="width: {{ min(100, $myOverduePercentage) }}%"
                                ></span>
                            </div>

                            <div class="mt-2 text-[9px] font-bold text-rose-100/60">
                                {{ number_format($myOverduePercentage, 1) }}% active follow-ups overdue
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ======================================================== --}}
                {{-- RIGHT SIDE - CIRCULAR OVERALL PERCENTAGE --}}
                {{-- ======================================================== --}}
                <div class="crm-hero-glass flex flex-col justify-between rounded-[24px] p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-[.18em] text-white/55">
                                Overall Percentage
                            </div>

                            <div class="mt-1 text-xl font-black text-white">
                                Work Completion
                            </div>

                            <div class="mt-1 text-xs font-bold {{ $crmPerformanceColor }}">
                                {{ $crmPerformanceStatus }}
                            </div>
                        </div>

                        <span class="rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-black text-white/75">
                            {{ $crmRemaining }}% Left
                        </span>
                    </div>

                    {{-- Circular Progress --}}
                    <div class="my-3 flex justify-center">
                        <div class="relative h-[210px] w-[210px]">
                            <svg
                                class="crm-progress-ring h-full w-full -rotate-90"
                                viewBox="0 0 160 160"
                                aria-hidden="true"
                            >
                                <defs>
                                    <linearGradient id="crmProgressGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#fde047"/>
                                        <stop offset="52%" stop-color="#f59e0b"/>
                                        <stop offset="100%" stop-color="#fb7185"/>
                                    </linearGradient>
                                </defs>

                                <circle
                                    class="crm-progress-ring__track"
                                    cx="80"
                                    cy="80"
                                    r="{{ $crmRingRadius }}"
                                    stroke-width="14"
                                    fill="none"
                                />

                                <circle
                                    class="crm-progress-ring__bar"
                                    cx="80"
                                    cy="80"
                                    r="{{ $crmRingRadius }}"
                                    stroke-width="14"
                                    fill="none"
                                    stroke-dasharray="{{ $crmRingCircumference }}"
                                    stroke-dashoffset="{{ $crmRingOffset }}"
                                />
                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <div class="crm-circle-caption text-[10px] font-black text-white/55">
                                    Overall Score
                                </div>

                                <div class="mt-1 text-5xl font-black leading-none text-white">
                                    {{ $crmCompletion }}%
                                </div>

                                <div class="mt-2 rounded-full bg-white/10 px-3 py-1 text-[10px] font-black text-white/70">
                                    {{ $crmRemaining }}% Remaining
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Circle Breakdown --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-white/[.07] px-3 py-2 text-center">
                            <div class="text-[9px] font-black uppercase tracking-wide text-white/45">
                                Calls
                            </div>
                            <div class="mt-1 text-lg font-black text-blue-100">
                                {{ number_format($myCallPercentage, 1) }}%
                            </div>
                        </div>

                        <div class="rounded-xl bg-white/[.07] px-3 py-2 text-center">
                            <div class="text-[9px] font-black uppercase tracking-wide text-white/45">
                                Demo
                            </div>
                            <div class="mt-1 text-lg font-black text-violet-100">
                                {{ number_format($myDemoPercentage, 1) }}%
                            </div>
                        </div>

                        <div class="rounded-xl bg-white/[.07] px-3 py-2 text-center">
                            <div class="text-[9px] font-black uppercase tracking-wide text-white/45">
                                Follow-up
                            </div>
                            <div class="mt-1 text-lg font-black text-emerald-100">
                                {{ number_format($myFollowUpPercentage, 1) }}%
                            </div>
                        </div>

                        <div class="rounded-xl bg-white/[.07] px-3 py-2 text-center">
                            <div class="text-[9px] font-black uppercase tracking-wide text-white/45">
                                Penalty
                            </div>
                            <div class="mt-1 text-lg font-black text-rose-100">
                                -{{ number_format($myOverduePenalty, 1) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl border border-white/10 bg-slate-950/15 px-3 py-2 text-center text-[10px] font-semibold leading-4 text-white/55">
                        Formula: Calls 40% + Demo 30% + Follow-up 30% − Overdue Penalty
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    {{-- Advanced Daily Activity Trend --}}
    {{-- ================================================================ --}}

    <section id="advanced-analytics" class="rounded-[22px] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-blue-500 to-cyan-500 text-white shadow-[0_8px_18px_rgba(59,130,246,.24)]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M3 3v18h18"/>
                        <path d="m5 16 4-5 4 3 6-8"/>
                        <circle cx="9" cy="11" r="1"/>
                        <circle cx="13" cy="14" r="1"/>
                        <circle cx="19" cy="6" r="1"/>
                    </svg>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-black text-slate-950 sm:text-lg">
                            Activity & Conversion Trend
                        </h2>

                        <span class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-700">
                            LIVE ANALYTICS
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-slate-500">
                        Calls, connected calls, demos aur follow-ups ka real day-wise trend.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                <button
                    type="button"
                    class="trend-range-btn rounded-lg px-3 py-2 text-[11px] font-black text-slate-500 transition hover:text-slate-900"
                    data-days="7"
                >
                    7 Days
                </button>

                <button
                    type="button"
                    class="trend-range-btn rounded-lg px-3 py-2 text-[11px] font-black text-slate-500 transition hover:text-slate-900"
                    data-days="14"
                >
                    14 Days
                </button>

                <button
                    type="button"
                    class="trend-range-btn active rounded-lg bg-white px-3 py-2 text-[11px] font-black text-blue-600 shadow-sm ring-1 ring-slate-200 transition"
                    data-days="30"
                >
                    30 Days
                </button>
            </div>
        </div>

        {{-- Selected-range summary --}}
        <div class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white px-3 py-2.5">
                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-blue-600">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                    Calls
                </div>
                <div id="trendCallsTotal" class="mt-1 text-xl font-black text-slate-950">0</div>
                <div class="text-[10px] font-semibold text-slate-400">Selected period</div>
            </div>

            <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white px-3 py-2.5">
                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-emerald-600">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Connected
                </div>
                <div id="trendConnectedTotal" class="mt-1 text-xl font-black text-slate-950">0</div>
                <div class="text-[10px] font-semibold text-slate-400">Connected calls</div>
            </div>

            <div class="rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white px-3 py-2.5">
                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-violet-600">
                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                    Demo
                </div>
                <div id="trendDemoTotal" class="mt-1 text-xl font-black text-slate-950">0</div>
                <div class="text-[10px] font-semibold text-slate-400">Demo sent</div>
            </div>

            <div class="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white px-3 py-2.5">
                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wide text-amber-600">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Follow-ups
                </div>
                <div id="trendFollowUpTotal" class="mt-1 text-xl font-black text-slate-950">0</div>
                <div class="text-[10px] font-semibold text-slate-400">Scheduled activity</div>
            </div>

            <div class="rounded-xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white px-3 py-2.5">
                <div class="text-[10px] font-black uppercase tracking-wide text-cyan-600">
                    Connect Rate
                </div>
                <div id="trendConnectRate" class="mt-1 text-xl font-black text-slate-950">0%</div>
                <div class="text-[10px] font-semibold text-slate-400">Connected ÷ Calls</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white px-3 py-2.5">
                <div class="text-[10px] font-black uppercase tracking-wide text-slate-500">
                    Best Activity Day
                </div>
                <div id="trendBestDay" class="mt-1 truncate text-base font-black text-slate-950">—</div>
                <div class="text-[10px] font-semibold text-slate-400">Highest combined activity</div>
            </div>
        </div>

        {{-- Main advanced line chart --}}
        <div class="analytics-chart-card overflow-hidden p-3 sm:p-4">
            <div class="mb-3 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-sm font-black text-slate-900">Daily Performance Movement</div>
                    <div class="mt-1 text-xs text-slate-500">
                        Line upar jaaye to activity badh rahi hai; hover karke exact daily numbers dekhein.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-bold text-slate-600">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> Calls
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Connected
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span> Demo
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Follow-ups
                    </span>
                </div>
            </div>

            <div class="analytics-chart-wrap" style="height: 390px;">
                <canvas id="activityTrendChart"></canvas>
            </div>

            <div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3 text-[10px] font-semibold text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                <span>
                    Graph dashboard visibility rules follow karta hai — admin ko company view, employee ko allowed lead scope.
                </span>
                <span id="trendRangeCaption" class="font-black text-slate-600">
                    Last 30 days
                </span>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const trendSource = {
            labels: @json($performanceTrend['labels'] ?? []),
            calls: @json($performanceTrend['calls'] ?? []),
            connected: @json($performanceTrend['connected'] ?? []),
            demos: @json($performanceTrend['demos'] ?? []),
            followups: @json($performanceTrend['followups'] ?? []),
        };

        const canvas = document.getElementById('activityTrendChart');

        if (!canvas) {
            return;
        }

        Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        Chart.defaults.color = '#64748b';

        const ctx = canvas.getContext('2d');

        const callsGradient = ctx.createLinearGradient(0, 0, 0, 390);
        callsGradient.addColorStop(0, 'rgba(59,130,246,.22)');
        callsGradient.addColorStop(.55, 'rgba(59,130,246,.07)');
        callsGradient.addColorStop(1, 'rgba(59,130,246,0)');

        const formatNumber = (value) =>
            new Intl.NumberFormat('en-IN').format(Number(value || 0));

        const sum = (items) =>
            items.reduce((total, value) => total + Number(value || 0), 0);

        const sliceLast = (items, days) =>
            items.slice(Math.max(0, items.length - days));

        const rangeButtons = document.querySelectorAll('.trend-range-btn');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Calls',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: callsGradient,
                        fill: true,
                        borderWidth: 3,
                        tension: .38,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 2.8,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3,
                        order: 1,
                    },
                    {
                        label: 'Connected',
                        data: [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.08)',
                        fill: false,
                        borderWidth: 2.5,
                        tension: .38,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 2.5,
                        pointHoverRadius: 5.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        order: 2,
                    },
                    {
                        label: 'Demo',
                        data: [],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,.08)',
                        fill: false,
                        borderWidth: 2.5,
                        tension: .38,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 2.5,
                        pointHoverRadius: 5.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#8b5cf6',
                        pointBorderWidth: 2,
                        order: 3,
                    },
                    {
                        label: 'Follow-ups',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,.08)',
                        fill: false,
                        borderWidth: 2.5,
                        tension: .38,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 2.5,
                        pointHoverRadius: 5.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f59e0b',
                        pointBorderWidth: 2,
                        order: 4,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                normalized: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                animation: {
                    duration: 500,
                    easing: 'easeOutQuart',
                },
                layout: {
                    padding: {
                        top: 8,
                        right: 6,
                        bottom: 2,
                        left: 2,
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(15,23,42,.97)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,.10)',
                        borderWidth: 1,
                        padding: 13,
                        cornerRadius: 12,
                        displayColors: true,
                        boxWidth: 9,
                        boxHeight: 9,
                        usePointStyle: true,
                        callbacks: {
                            title: function(items) {
                                return items.length ? items[0].label : '';
                            },
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + formatNumber(context.raw);
                            },
                            footer: function(items) {
                                const dailyTotal = items.reduce(
                                    (total, item) => total + Number(item.raw || 0),
                                    0
                                );

                                return 'Total activity: ' + formatNumber(dailyTotal);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        border: {
                            display: false,
                        },
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#64748b',
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 10,
                            font: {
                                size: 11,
                                weight: '700',
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false,
                        },
                        grid: {
                            color: 'rgba(148,163,184,.15)',
                            drawTicks: false,
                        },
                        ticks: {
                            precision: 0,
                            padding: 10,
                            color: '#94a3b8',
                            font: {
                                size: 10,
                                weight: '700',
                            }
                        }
                    }
                }
            }
        });

        function updateSummary(days) {
            const labels = sliceLast(trendSource.labels, days);
            const calls = sliceLast(trendSource.calls, days);
            const connected = sliceLast(trendSource.connected, days);
            const demos = sliceLast(trendSource.demos, days);
            const followups = sliceLast(trendSource.followups, days);

            const callsTotal = sum(calls);
            const connectedTotal = sum(connected);
            const demoTotal = sum(demos);
            const followUpTotal = sum(followups);
            const connectRate = callsTotal > 0
                ? ((connectedTotal / callsTotal) * 100).toFixed(1)
                : '0.0';

            let bestIndex = -1;
            let bestActivity = -1;

            labels.forEach((label, index) => {
                const activity =
                    Number(calls[index] || 0)
                    + Number(connected[index] || 0)
                    + Number(demos[index] || 0)
                    + Number(followups[index] || 0);

                if (activity > bestActivity) {
                    bestActivity = activity;
                    bestIndex = index;
                }
            });

            document.getElementById('trendCallsTotal').textContent = formatNumber(callsTotal);
            document.getElementById('trendConnectedTotal').textContent = formatNumber(connectedTotal);
            document.getElementById('trendDemoTotal').textContent = formatNumber(demoTotal);
            document.getElementById('trendFollowUpTotal').textContent = formatNumber(followUpTotal);
            document.getElementById('trendConnectRate').textContent = connectRate + '%';
            document.getElementById('trendBestDay').textContent =
                bestIndex >= 0 && bestActivity > 0
                    ? labels[bestIndex] + ' · ' + formatNumber(bestActivity)
                    : 'No activity';

            document.getElementById('trendRangeCaption').textContent =
                'Last ' + days + ' days';

            chart.data.labels = labels;
            chart.data.datasets[0].data = calls;
            chart.data.datasets[1].data = connected;
            chart.data.datasets[2].data = demos;
            chart.data.datasets[3].data = followups;
            chart.update();
        }

        function setActiveButton(activeButton) {
            rangeButtons.forEach((button) => {
                button.classList.remove(
                    'active',
                    'bg-white',
                    'text-blue-600',
                    'shadow-sm',
                    'ring-1',
                    'ring-slate-200'
                );

                button.classList.add('text-slate-500');
            });

            activeButton.classList.remove('text-slate-500');
            activeButton.classList.add(
                'active',
                'bg-white',
                'text-blue-600',
                'shadow-sm',
                'ring-1',
                'ring-slate-200'
            );
        }

        rangeButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const days = Number(this.dataset.days || 30);
                setActiveButton(this);
                updateSummary(days);
            });
        });

        updateSummary(30);
    });
</script>

@endsection
