<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'TeleCRM')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style>

    /*
    |--------------------------------------------------------------------------
    | Sidebar Follow-up Blink
    |--------------------------------------------------------------------------
    */

    @keyframes sidebar-followup-blink {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .45;
            transform: scale(.96);
        }
    }


    @keyframes sidebar-followup-dot-blink {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .25;
            transform: scale(.6);
        }
    }


    .sidebar-followup-reminder {

        animation:
            sidebar-followup-blink
            1.2s
            infinite;
    }


    .sidebar-followup-dot {

        animation:
            sidebar-followup-dot-blink
            .7s
            infinite;
    }

</style>
</head>

<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen lg:flex">

    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    <aside
        class="
            w-full
            bg-slate-950
            text-white

            lg:fixed
            lg:inset-y-0
            lg:left-0
            lg:w-72
            lg:overflow-y-auto
        "
    >

        {{-- Logo / Title --}}

        <div class="border-b border-slate-800 px-6 py-5">

            <div class="text-xl font-bold">
                Telecalling Sales CRM
            </div>

            <div class="mt-1 text-xs text-slate-400">
                Lead • Call • Follow-up • Conversion
            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- MENU CONFIGURATION --}}
        {{-- ===================================================== --}}

        @php

            $user = auth()->user();

            $isSuperAdmin = $user->hasRole('super_admin');



                /*
                |--------------------------------------------------------------------------
                | Nearest Pending / Overdue Follow-up For Sidebar
                |--------------------------------------------------------------------------
                |
                | Current time ke sabse paas scheduled_at wala pending
                | follow-up select hoga. Future aur overdue dono include hain.
                |
                */

                $nearestSidebarFollowUp = \App\Models\FollowUp::query()
                    ->where('company_id', $user->company_id)
                    ->where('assigned_to', $user->id)
                    ->where('status', 'pending')
                    ->whereNotNull('scheduled_at')
                    ->orderByRaw("
                        ABS(
                            TIMESTAMPDIFF(
                                SECOND,
                                NOW(),
                                scheduled_at
                            )
                        ) ASC
                    ")
                    ->first();

                $nearestFollowUpIso = null;
                $nearestFollowUpIsOverdue = false;

                if ($nearestSidebarFollowUp?->scheduled_at) {

                    $nearestFollowUpIso =
                        $nearestSidebarFollowUp
                            ->scheduled_at
                            ->toIso8601String();

                    $nearestFollowUpIsOverdue =
                        $nearestSidebarFollowUp
                            ->scheduled_at
                            ->isPast();
                }

            /*
            |--------------------------------------------------------------------------
            | Sidebar Sections
            |--------------------------------------------------------------------------
            */

            $sections = [

                'Platform' => [

                    [
                        'Companies',
                        'companies.index',
                        'companies.*',
                        'companies.view'
                    ],

                ],

                'Workspace' => [

                    [
                        'Dashboard',
                        'dashboard',
                        'dashboard',
                        'dashboard.view'
                    ],

                    [
                        'Leads',
                        'leads.index',
                        'leads.*',
                        'leads.view'
                    ],

                    [
                        'Pipeline',
                        'pipeline.index',
                        'pipeline.*',
                        'pipeline.view'
                    ],

                    [
                        'Follow-ups',
                        'followups.index',
                        'followups.*',
                        'followups.view'
                    ],

                    [
                        'Call Logs',
                        'calls.index',
                        'calls.*',
                        'calls.view'
                    ],

                ],

                'Organization' => [

                    [
                        'Employees',
                        'employees.index',
                        'employees.*',
                        'employees.view'
                    ],

                    [
                        'Branches',
                        'branches.index',
                        'branches.*',
                        'branches.view'
                    ],

                    [
                        'Teams',
                        'teams.index',
                        'teams.*',
                        'teams.view'
                    ],

                    [
                        'Roles & Permissions',
                        'access-control.index',
                        'access-control.*',
                        'access-control.view'
                    ],

                ],

                'Sales' => [

                    [
                        'Campaigns',
                        'campaigns.index',
                        'campaigns.*',
                        'campaigns.view'
                    ],

                    [
                        'Products',
                        'products.index',
                        'products.*',
                        'products.view'
                    ],

                    [
                        'Customers',
                        'customers.index',
                        'customers.*',
                        'customers.view'
                    ],

                    [
                        'Tasks',
                        'tasks.index',
                        'tasks.*',
                        'tasks.view'
                    ],

                    [
                        'Orders',
                        'orders.index',
                        'orders.*',
                        'orders.view'
                    ],

                    [
                        'Payments',
                        'payments.index',
                        'payments.*',
                        'payments.view'
                    ],

                    [
                        'Reports',
                        'reports.index',
                        'reports.*',
                        'reports.view'
                    ],

                ],

                'CRM Settings' => [

                    [
                        'Lead Sources',
                        'crm-settings.lead-sources.index',
                        'crm-settings.lead-sources.*',
                        'lead-sources.view'
                    ],

                    [
                        'Lead Statuses',
                        'crm-settings.lead-statuses.index',
                        'crm-settings.lead-statuses.*',
                        'lead-statuses.view'
                    ],

                    [
                        'Call Dispositions',
                        'crm-settings.call-dispositions.index',
                        'crm-settings.call-dispositions.*',
                        'call-dispositions.view'
                    ],

                ],

            ];

        @endphp


        {{-- ===================================================== --}}
        {{-- SIDEBAR NAVIGATION --}}
        {{-- ===================================================== --}}

        <nav class="space-y-5 p-4">

            @if (session()->has('impersonator_id'))

                <div
                    class="
                        sticky
                        top-0
                        z-[9999]
                        border-b
                        border-amber-300
                        bg-amber-50
                    "
                >

                    <div
                        class="
                            mx-auto
                            flex
                            max-w-7xl
                            items-center
                            justify-between
                            gap-4
                            px-4
                            py-3
                        "
                    >

                        <div class="text-sm text-amber-900">

                            You are viewing

                            <strong>
                                {{ auth()->user()->name }}
                            </strong>

                            dashboard.

                        </div>

                        <form
                            method="POST"
                            action="{{ route('employees.stop-impersonating') }}"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="
                                    rounded-lg
                                    bg-slate-900
                                    px-4
                                    py-2
                                    text-sm
                                    font-semibold
                                    text-white
                                    hover:bg-slate-800
                                "
                            >
                                ← Back to My Account
                            </button>

                        </form>

                    </div>

                </div>

            @endif


            @foreach($sections as $label => $links)

                <div>

                    {{-- Section Heading --}}

                    <div
                        class="
                            mb-2
                            px-3
                            text-[11px]
                            font-semibold
                            uppercase
                            tracking-widest
                            text-slate-500
                        "
                    >
                        {{ $label }}
                    </div>


                    <div class="space-y-1">

                        @foreach($links as $link)

                            @php
                                $text = $link[0];
                                $route = $link[1];
                                $pattern = $link[2];
                                $permission = $link[3];
                            @endphp

                            @can($permission)

                                <a
                                    href="{{ route($route) }}"
                                    class="
                                        flex
                                        items-center
                                        justify-between
                                        gap-3
                                        rounded-lg
                                        px-4
                                        py-2.5
                                        text-sm
                                        transition

                                        {{
                                            request()->routeIs($pattern)
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                        }}
                                    "
                                >

                                    <span>
                                        {{ $text }}
                                    </span>


                                    {{-- ========================================================= --}}
                                    {{-- Nearest Follow-up Live Timer --}}
                                    {{-- ========================================================= --}}

                                    @if($route === 'followups.index')

                                        <span
                                            id="sidebarNearestFollowup"
                                            data-time="{{ $nearestFollowUpIso }}"
                                            class="
                                                {{ $nearestSidebarFollowUp ? '' : 'hidden' }}
                                                inline-flex
                                                shrink-0
                                                items-center
                                                gap-1.5
                                                rounded-full
                                                border
                                                px-2
                                                py-1
                                                text-[10px]
                                                font-black
                                                tabular-nums

                                                {{
                                                    $nearestFollowUpIsOverdue
                                                        ? 'sidebar-followup-reminder border-red-400/50 bg-red-500 text-white'
                                                        : 'border-amber-300/40 bg-amber-400 text-slate-950'
                                                }}
                                            "
                                            title="Nearest pending follow-up"
                                        >
                                            <span
                                                id="sidebarNearestFollowupDot"
                                                class="
                                                    sidebar-followup-dot
                                                    h-1.5
                                                    w-1.5
                                                    rounded-full
                                                    bg-current
                                                "
                                            ></span>

                                            <span id="sidebarNearestFollowupText">
                                                Loading...
                                            </span>
                                        </span>

                                    @endif

                                </a>

                            @endcan

                        @endforeach

                    </div>

                </div>

            @endforeach

        </nav>

    </aside>


    {{-- ========================================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================================= --}}

    <main class="min-w-0 flex-1 lg:ml-72">


        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <header
            class="
                sticky
                top-0
                z-20
                flex
                items-center
                justify-between
                border-b
                bg-white/95
                px-5
                py-4
                backdrop-blur
                lg:px-8
            "
        >

            {{-- Company / Branch --}}

            <div>

                <div class="font-semibold text-slate-900">

                    @if($isSuperAdmin)

                        {{
                            auth()->user()->company?->name
                            ?? 'TeleCRM Platform'
                        }}

                    @else

                        {{
                            auth()->user()->company?->name
                            ?? 'TeleCRM Workspace'
                        }}

                    @endif

                </div>


                <div class="text-xs text-slate-500">

                    @if($isSuperAdmin)

                        Super Admin

                    @else

                        {{
                            auth()->user()->branch?->name
                            ?? 'All Branches'
                        }}

                    @endif

                </div>

            </div>


            {{-- User --}}

            <div class="flex items-center gap-4">

                <div class="text-right">

                    <div class="text-sm font-medium">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-slate-500">

                        {{
                            auth()
                                ->user()
                                ->roles
                                ->pluck('name')
                                ->join(', ')
                        }}

                    </div>

                </div>


                {{-- Logout --}}

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="
                            rounded-lg
                            border
                            border-rose-200
                            px-3
                            py-2
                            text-sm
                            font-medium
                            text-rose-600
                            transition
                            hover:bg-rose-50
                        "
                    >
                        Logout
                    </button>

                </form>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- PAGE CONTENT --}}
        {{-- ===================================================== --}}

        <section class="p-5 lg:p-8">

            {{-- Success Message --}}

            @if(session('success'))

                <div
                    class="
                        mb-5
                        rounded-lg
                        border
                        border-emerald-200
                        bg-emerald-50
                        p-4
                        text-emerald-700
                    "
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- Error Message --}}

            @if(session('error'))

                <div
                    class="
                        mb-5
                        rounded-lg
                        border
                        border-rose-200
                        bg-rose-50
                        p-4
                        text-rose-700
                    "
                >
                    {{ session('error') }}
                </div>

            @endif


            {{-- Validation Errors --}}

            @if($errors->any())

                <div
                    class="
                        mb-5
                        rounded-lg
                        border
                        border-rose-200
                        bg-rose-50
                        p-4
                        text-rose-700
                    "
                >

                    <div class="font-semibold">
                        Please fix the following errors:
                    </div>

                    <ul
                        class="
                            mt-2
                            list-disc
                            space-y-1
                            pl-5
                            text-sm
                        "
                    >

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            @yield('content')

        </section>

    </main>

</div>

{{-- ============================================================= --}}
{{-- GLOBAL FOLLOW-UP REMINDER BACKDROP --}}
{{-- ============================================================= --}}

<div
    id="followUpReminderBackdrop"
    class="fixed inset-0 z-[99998] hidden bg-slate-950/60 backdrop-blur-sm"
></div>

{{-- ============================================================= --}}
{{-- GLOBAL FOLLOW-UP REMINDER MODAL --}}
{{-- ============================================================= --}}

<div
    id="followUpReminderModal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center overflow-y-auto p-4"
>
    <div
        id="followUpReminderCard"
        class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5"
    >
        {{-- Header --}}
        <div
            id="followUpReminderTop"
            class="bg-amber-500 px-6 py-4 text-white"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-2xl">
                        🔔
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-[0.18em] text-white/80">
                            Follow-up Reminder
                        </div>

                        <div
                            id="followUpReminderHeading"
                            class="mt-1 text-xl font-black"
                        >
                            Follow-up is due soon
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white/15 px-3 py-2 text-xs font-bold hover:bg-white/25"
                        title="Disable follow-up reminder popups for this user on this browser"
                    >
                        <input
                            type="checkbox"
                            id="followUpReminderEnabledToggle"
                            class="h-4 w-4 rounded border-white/50"
                            checked
                        >
                        <span id="followUpReminderEnabledText">Popup ON</span>
                    </label>

                    {{-- Close only hides current reminder for 1 minute --}}
                    <button
                    type="button"
                    id="followUpReminderClose"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-xl font-bold transition hover:bg-white/25"
                    title="Close reminder"
                    aria-label="Close reminder"
                >
                    ×
                    </button>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="max-h-[82vh] overflow-y-auto p-6">

            {{-- Timer --}}
            <div
                id="followUpTimerBox"
                class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-center"
            >
                <div
                    id="followUpTimerLabel"
                    class="text-xs font-bold uppercase tracking-wider text-amber-700"
                >
                    Time remaining
                </div>

                <div
                    id="followUpCountdown"
                    class="mt-1 text-3xl font-black text-amber-800"
                >
                    01:00
                </div>
            </div>

            {{-- Lead --}}
            <div class="mb-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Lead
                </div>

                <div
                    id="followUpLeadName"
                    class="mt-1 text-2xl font-black text-slate-900"
                >
                    -
                </div>

                <div
                    id="followUpMobile"
                    class="mt-1 text-sm font-medium text-slate-500"
                >
                    -
                </div>
            </div>

            {{-- Information --}}
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-400">
                        Scheduled Time
                    </div>
                    <div id="followUpScheduledAt" class="mt-1 text-sm font-bold text-slate-900">-</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-400">
                        Type
                    </div>
                    <div id="followUpType" class="mt-1 text-sm font-bold capitalize text-slate-900">-</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-400">
                        Priority
                    </div>
                    <div id="followUpPriority" class="mt-1 text-sm font-bold capitalize text-slate-900">-</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-400">
                        Assigned To
                    </div>
                    <div id="followUpAssignedUser" class="mt-1 text-sm font-bold text-slate-900">-</div>
                </div>
            </div>

            {{-- Notes --}}
            <div
                id="followUpNotesWrapper"
                class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50 p-4"
            >
                <div class="text-xs font-semibold uppercase tracking-wider text-indigo-500">
                    Follow-up Notes
                </div>

                <div
                    id="followUpNotes"
                    class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"
                >
                    -
                </div>
            </div>

            {{-- Save Call Result inside Reminder Popup --}}
            @can('calls.create')
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-black text-slate-900">Save Call Result</div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            Lead page wala same call result yahin se save karein.
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                        QUICK SAVE
                    </span>
                </div>

                <form id="followUpPopupCallForm" method="POST" action="">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Disposition <span class="text-rose-500">*</span>
                            </span>
                            <select
                                id="followUpPopupDisposition"
                                name="call_disposition_id"
                                required
                                class="w-full rounded-xl border-slate-300 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Select disposition</option>
                            </select>
                            <div
                                id="followUpPopupDispositionHint"
                                class="mt-2 hidden rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600"
                            ></div>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Duration</span>
                            <div class="relative">
                                <input
                                    id="followUpPopupDuration"
                                    name="duration_seconds"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full rounded-xl border-slate-300 bg-white pr-16 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                    seconds
                                </span>
                            </div>
                        </label>
                    </div>

                    <div
                        id="followUpPopupRemarksWrapper"
                        class="mt-4 hidden"
                    >
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Call Remarks
                                <span id="followUpPopupRemarksRequired" class="hidden text-rose-500">*</span>
                            </span>
                            <textarea
                                id="followUpPopupRemarks"
                                name="remarks"
                                rows="3"
                                placeholder="Call discussion aur customer response..."
                                class="w-full rounded-xl border-slate-300 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            ></textarea>
                        </label>
                    </div>

                    <div
                        id="followUpPopupNextFollowUpWrapper"
                        class="mt-4 hidden"
                    >
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Next Follow-up Date & Time
                                <span id="followUpPopupNextFollowUpRequired" class="hidden text-rose-500">*</span>
                            </span>
                            <input
                                id="followUpPopupNextFollowUp"
                                name="follow_up_at"
                                type="datetime-local"
                                class="w-full rounded-xl border-slate-300 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                    </div>

                    <button
                        type="submit"
                        id="followUpPopupSaveCall"
                        class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        ✓ Save Call Result
                    </button>
                </form>
            </div>
            @endcan

            {{-- Queue --}}
            <div
                id="followUpQueueInfo"
                class="mt-4 hidden rounded-xl bg-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-slate-600"
            >
                -
            </div>

            {{-- Primary Actions --}}
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <a
                    id="followUpCallButton"
                    href="#"
                    class="hidden items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                >
                    ☎ Call Now
                </a>

                <a
                    id="followUpOpenLeadButton"
                    href="#"
                    class="flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    👤 Open Lead
                </a>
            </div>

            {{-- Complete --}}
            <button
                type="button"
                id="followUpCompleteButton"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
                ✓ Mark as Completed
            </button>

            {{-- Reschedule / Cancel --}}
            <div class="mt-3 grid grid-cols-2 gap-3">
                <button
                    type="button"
                    id="followUpRescheduleToggle"
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100"
                >
                    🗓 Reschedule
                </button>

                <button
                    type="button"
                    id="followUpCancelButton"
                    class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    ✕ Cancel Follow-up
                </button>
            </div>

            {{-- Reschedule Panel --}}
            <div
                id="followUpReschedulePanel"
                class="mt-3 hidden rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4"
            >
                <label
                    for="followUpRescheduleInput"
                    class="block text-xs font-bold uppercase tracking-wider text-indigo-700"
                >
                    New Date & Time
                </label>

                <input
                    type="datetime-local"
                    id="followUpRescheduleInput"
                    class="mt-2 w-full rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                <div class="mt-3 flex gap-2">
                    <button
                        type="button"
                        id="followUpRescheduleSave"
                        class="flex-1 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Save New Time
                    </button>

                    <button
                        type="button"
                        id="followUpRescheduleClose"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Close
                    </button>
                </div>
            </div>

            {{-- Quick Snooze --}}
            <div class="mt-5">
                <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    Quick Snooze
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        data-snooze="10"
                        class="followup-snooze-btn rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                    >
                        +10 Min
                    </button>

                    <button
                        type="button"
                        data-snooze="30"
                        class="followup-snooze-btn rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                    >
                        +30 Min
                    </button>

                    <button
                        type="button"
                        data-snooze="60"
                        class="followup-snooze-btn rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                    >
                        +1 Hour
                    </button>
                </div>
            </div>

            {{-- Remind Later --}}
            <button
                type="button"
                id="followUpRemindFiveButton"
                class="mt-3 w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
            >
                Remind me again in 5 minutes
            </button>

            {{-- AJAX Status --}}
            <div
                id="followUpActionMessage"
                class="mt-4 hidden rounded-xl p-3 text-sm font-semibold"
            ></div>
        </div>
    </div>
</div>

{{-- ============================================================= --}}
{{-- GLOBAL FOLLOW-UP REMINDER SCRIPT --}}
{{-- ============================================================= --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    const reminderUrl = @json(route('followups.reminders'));
    const sidebarNearestUrl = @json(route('followups.nearest'));

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const modal = document.getElementById('followUpReminderModal');
    const backdrop = document.getElementById('followUpReminderBackdrop');
    const topBar = document.getElementById('followUpReminderTop');
    const heading = document.getElementById('followUpReminderHeading');
    const timerBox = document.getElementById('followUpTimerBox');
    const timerLabel = document.getElementById('followUpTimerLabel');
    const countdown = document.getElementById('followUpCountdown');
    const leadName = document.getElementById('followUpLeadName');
    const mobile = document.getElementById('followUpMobile');
    const scheduledAt = document.getElementById('followUpScheduledAt');
    const type = document.getElementById('followUpType');
    const priority = document.getElementById('followUpPriority');
    const assignedUser = document.getElementById('followUpAssignedUser');
    const notesWrapper = document.getElementById('followUpNotesWrapper');
    const notes = document.getElementById('followUpNotes');
    const queueInfo = document.getElementById('followUpQueueInfo');
    const callButton = document.getElementById('followUpCallButton');
    const openLeadButton = document.getElementById('followUpOpenLeadButton');
    const completeButton = document.getElementById('followUpCompleteButton');
    const closeButton = document.getElementById('followUpReminderClose');
    const remindFiveButton = document.getElementById('followUpRemindFiveButton');
    const cancelButton = document.getElementById('followUpCancelButton');
    const rescheduleToggle = document.getElementById('followUpRescheduleToggle');
    const reschedulePanel = document.getElementById('followUpReschedulePanel');
    const rescheduleInput = document.getElementById('followUpRescheduleInput');
    const rescheduleSave = document.getElementById('followUpRescheduleSave');
    const rescheduleClose = document.getElementById('followUpRescheduleClose');
    const actionMessage = document.getElementById('followUpActionMessage');

    const reminderEnabledToggle = document.getElementById('followUpReminderEnabledToggle');
    const reminderEnabledText = document.getElementById('followUpReminderEnabledText');

    const popupCallForm = document.getElementById('followUpPopupCallForm');
    const popupDisposition = document.getElementById('followUpPopupDisposition');
    const popupDispositionHint = document.getElementById('followUpPopupDispositionHint');
    const popupDuration = document.getElementById('followUpPopupDuration');
    const popupRemarksWrapper = document.getElementById('followUpPopupRemarksWrapper');
    const popupRemarks = document.getElementById('followUpPopupRemarks');
    const popupRemarksRequired = document.getElementById('followUpPopupRemarksRequired');
    const popupNextFollowUpWrapper = document.getElementById('followUpPopupNextFollowUpWrapper');
    const popupNextFollowUp = document.getElementById('followUpPopupNextFollowUp');
    const popupNextFollowUpRequired = document.getElementById('followUpPopupNextFollowUpRequired');

    const sidebarNearestFollowup = document.getElementById('sidebarNearestFollowup');
    const sidebarNearestFollowupText = document.getElementById('sidebarNearestFollowupText');
    const sidebarNearestFollowupDot = document.getElementById('sidebarNearestFollowupDot');

    let reminderQueue = [];
    let currentReminder = null;
    let countdownInterval = null;
    let isFetching = false;
    let modalOpen = false;
    let audioContext = null;
    let callDispositions = [];

    const reminderPreferenceKey =
        'followup_popup_enabled_user_' + @json(auth()->id());

    function isReminderPopupEnabled() {
        return localStorage.getItem(reminderPreferenceKey) !== '0';
    }

    function syncReminderPreferenceUi() {
        const enabled = isReminderPopupEnabled();

        if (reminderEnabledToggle) {
            reminderEnabledToggle.checked = enabled;
        }

        if (reminderEnabledText) {
            reminderEnabledText.textContent = enabled ? 'Popup ON' : 'Popup OFF';
        }
    }

    function disableReminderPopupNow() {
        reminderQueue = [];
        if (modalOpen) {
            closeModal(false);
        }
    }

    function escapePhone(phone) {
        if (!phone) {
            return '';
        }

        return String(phone).replace(/[^\d+]/g, '');
    }

    function snoozeStorageKey(id) {
        return 'followup_reminder_hidden_' + id;
    }

    function dismissedStorageKey(id) {
        return 'followup_reminder_dismissed_' + id;
    }

    function isTemporarilyHidden(id) {
        const value = localStorage.getItem(snoozeStorageKey(id));

        if (!value) {
            return false;
        }

        const hiddenUntil = parseInt(value, 10);

        if (!hiddenUntil || Date.now() >= hiddenUntil) {
            localStorage.removeItem(snoozeStorageKey(id));
            return false;
        }

        return true;
    }

    function hideTemporarily(id, minutes = 5) {
        const until = Date.now() + (minutes * 60 * 1000);

        localStorage.setItem(
            snoozeStorageKey(id),
            String(until)
        );
    }

    function clearTemporaryHide(id) {
        localStorage.removeItem(snoozeStorageKey(id));
    }

    /*
    |--------------------------------------------------------------------------
    | Close/Dismiss
    |--------------------------------------------------------------------------
    | Close button DB ko modify nahi karta.
    | Same scheduled_at ke liye current browser tab/session me dobara popup nahi
    | khulega. Agar follow-up reschedule hota hai to scheduled_at change hone ki
    | wajah se reminder automatically eligible ho jayega.
    */
    function dismissReminder(reminder) {
        if (!reminder) {
            return;
        }

        sessionStorage.setItem(
            dismissedStorageKey(reminder.id),
            String(reminder.scheduled_at || '')
        );
    }

    function isDismissed(reminder) {
        const saved = sessionStorage.getItem(
            dismissedStorageKey(reminder.id)
        );

        if (!saved) {
            return false;
        }

        if (saved !== String(reminder.scheduled_at || '')) {
            sessionStorage.removeItem(
                dismissedStorageKey(reminder.id)
            );
            return false;
        }

        return true;
    }

    function clearDismissed(id) {
        sessionStorage.removeItem(
            dismissedStorageKey(id)
        );
    }

    function toDatetimeLocalValue(dateString) {
        if (!dateString) {
            return '';
        }

        const date = new Date(dateString);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const pad = value => String(value).padStart(2, '0');

        return date.getFullYear()
            + '-'
            + pad(date.getMonth() + 1)
            + '-'
            + pad(date.getDate())
            + 'T'
            + pad(date.getHours())
            + ':'
            + pad(date.getMinutes());
    }

    function minimumDatetimeLocalValue() {
        const date = new Date();
        const pad = value => String(value).padStart(2, '0');

        return date.getFullYear()
            + '-'
            + pad(date.getMonth() + 1)
            + '-'
            + pad(date.getDate())
            + 'T'
            + pad(date.getHours())
            + ':'
            + pad(date.getMinutes());
    }

    function playReminderSound() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;

            if (!AudioContext) {
                return;
            }

            if (!audioContext) {
                audioContext = new AudioContext();
            }

            const playTone = (frequency, start, duration) => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;

                gain.gain.setValueAtTime(
                    0.0001,
                    audioContext.currentTime + start
                );

                gain.gain.exponentialRampToValueAtTime(
                    0.20,
                    audioContext.currentTime + start + 0.02
                );

                gain.gain.exponentialRampToValueAtTime(
                    0.0001,
                    audioContext.currentTime + start + duration
                );

                oscillator.connect(gain);
                gain.connect(audioContext.destination);

                oscillator.start(audioContext.currentTime + start);
                oscillator.stop(
                    audioContext.currentTime + start + duration + 0.05
                );
            };

            playTone(880, 0, 0.22);
            playTone(1100, 0.28, 0.22);
            playTone(880, 0.56, 0.30);

        } catch (error) {
            console.log('Reminder sound unavailable.', error);
        }
    }

    function showBrowserNotification(reminder) {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission !== 'granted') {
            return;
        }

        try {
            const statusText = reminder.is_overdue
                ? 'Follow-up is overdue'
                : 'Follow-up due in ' + reminder.minutes_remaining + ' minute(s)';

            const notification = new Notification(
                'Follow-up Reminder',
                {
                    body: reminder.lead_name + '\n' + statusText,
                    tag: 'followup-' + reminder.id,
                    requireInteraction: true,
                }
            );

            notification.onclick = function () {
                window.focus();

                if (reminder.lead_url) {
                    window.location.href = reminder.lead_url;
                }

                notification.close();
            };

        } catch (error) {
            console.log('Notification error', error);
        }
    }

    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
    }

    function showActionMessage(message, success = true) {
        actionMessage.classList.remove(
            'hidden',
            'bg-emerald-50',
            'text-emerald-700',
            'border',
            'border-emerald-200',
            'bg-rose-50',
            'text-rose-700',
            'border-rose-200'
        );

        if (success) {
            actionMessage.classList.add(
                'bg-emerald-50',
                'text-emerald-700',
                'border',
                'border-emerald-200'
            );
        } else {
            actionMessage.classList.add(
                'bg-rose-50',
                'text-rose-700',
                'border',
                'border-rose-200'
            );
        }

        actionMessage.textContent = message;
    }

    function clearActionMessage() {
        actionMessage.classList.add('hidden');
        actionMessage.textContent = '';
    }

    function populatePopupDispositions() {
        if (!popupDisposition) {
            return;
        }

        const currentValue = popupDisposition.value;

        popupDisposition.innerHTML =
            '<option value="">Select disposition</option>';

        callDispositions.forEach(disposition => {
            const option = document.createElement('option');

            option.value = disposition.id;
            option.textContent = disposition.name;
            option.dataset.requiresRemarks =
                disposition.requires_remarks ? '1' : '0';
            option.dataset.requiresFollowUp =
                disposition.requires_follow_up ? '1' : '0';

            popupDisposition.appendChild(option);
        });

        if (
            currentValue
            && [...popupDisposition.options].some(
                option => String(option.value) === String(currentValue)
            )
        ) {
            popupDisposition.value = currentValue;
        }
    }

    function updatePopupDispositionFields() {
        if (
            !popupDisposition
            || !popupRemarksWrapper
            || !popupRemarks
            || !popupNextFollowUpWrapper
            || !popupNextFollowUp
        ) {
            return;
        }

        const option =
            popupDisposition.options[popupDisposition.selectedIndex];

        const hasDisposition = !!option && !!option.value;
        const requiresRemarks =
            hasDisposition
            && option.dataset.requiresRemarks === '1';
        const requiresFollowUp =
            hasDisposition
            && option.dataset.requiresFollowUp === '1';

        popupRemarks.required = requiresRemarks;
        popupNextFollowUp.required = requiresFollowUp;

        if (requiresRemarks) {
            popupRemarksWrapper.classList.remove('hidden');
            popupRemarksRequired?.classList.remove('hidden');
        } else {
            popupRemarksWrapper.classList.add('hidden');
            popupRemarksRequired?.classList.add('hidden');
            popupRemarks.value = '';
        }

        if (requiresFollowUp) {
            popupNextFollowUpWrapper.classList.remove('hidden');
            popupNextFollowUpRequired?.classList.remove('hidden');
        } else {
            popupNextFollowUpWrapper.classList.add('hidden');
            popupNextFollowUpRequired?.classList.add('hidden');
            popupNextFollowUp.value = '';
        }

        if (popupDispositionHint) {
            if (!hasDisposition) {
                popupDispositionHint.classList.add('hidden');
                popupDispositionHint.textContent = '';
            } else {
                const rules = [];

                if (requiresRemarks) {
                    rules.push('Remarks required');
                }

                if (requiresFollowUp) {
                    rules.push('Follow-up required');
                }

                if (rules.length === 0) {
                    rules.push('No remarks or follow-up required');
                }

                popupDispositionHint.textContent =
                    option.text.trim() + ' — ' + rules.join(' • ');

                popupDispositionHint.classList.remove('hidden');
            }
        }
    }

    function resetPopupCallForm(reminder) {
        if (!popupCallForm) {
            return;
        }

        popupCallForm.reset();
        populatePopupDispositions();

        popupCallForm.action =
            reminder.call_store_url || '';

        if (popupNextFollowUp) {
            popupNextFollowUp.min = minimumDatetimeLocalValue();
        }

        updatePopupDispositionFields();
    }

    function openModal(reminder) {
        if (!reminder || !isReminderPopupEnabled()) {
            return;
        }

        currentReminder = reminder;
        modalOpen = true;

        clearActionMessage();
        reschedulePanel.classList.add('hidden');

        leadName.textContent = reminder.lead_name || 'Unknown Lead';

        mobile.textContent = reminder.mobile
            ? reminder.mobile
            : 'Mobile number not available';

        scheduledAt.textContent = reminder.scheduled_at_formatted || '-';
        type.textContent = reminder.type || 'Follow-up';
        priority.textContent = reminder.priority || 'Normal';
        assignedUser.textContent = reminder.assigned_user || '-';

        if (reminder.notes && String(reminder.notes).trim()) {
            notesWrapper.classList.remove('hidden');
            notes.textContent = reminder.notes;
        } else {
            notesWrapper.classList.add('hidden');
        }

        const phone = escapePhone(reminder.mobile);

        if (phone) {
            callButton.href = 'tel:' + phone;
            callButton.classList.remove('hidden');
            callButton.classList.add('flex');
        } else {
            callButton.classList.add('hidden');
            callButton.classList.remove('flex');
        }

        if (reminder.lead_url) {
            openLeadButton.href = reminder.lead_url;
            openLeadButton.classList.remove('hidden');
            openLeadButton.classList.add('flex');
        } else {
            openLeadButton.classList.add('hidden');
            openLeadButton.classList.remove('flex');
        }

        const remainingQueue = reminderQueue.length;

        if (queueInfo) {
            if (remainingQueue > 0) {
                queueInfo.classList.remove('hidden');
                queueInfo.textContent =
                    remainingQueue
                    + ' more follow-up reminder'
                    + (remainingQueue > 1 ? 's' : '')
                    + ' waiting';
            } else {
                queueInfo.classList.add('hidden');
            }
        }

        rescheduleInput.min = minimumDatetimeLocalValue();
        rescheduleInput.value = toDatetimeLocalValue(
            reminder.scheduled_at
        );

        resetPopupCallForm(reminder);

        backdrop.classList.remove('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        startCountdown(reminder);
        playReminderSound();
        showBrowserNotification(reminder);
    }

    function closeModal(showNext = true) {
        modalOpen = false;

        backdrop.classList.add('hidden');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        reschedulePanel.classList.add('hidden');
        document.body.style.overflow = '';

        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }

        currentReminder = null;

        if (showNext) {
            setTimeout(showNextReminder, 350);
        }
    }

    function closeCurrentReminder() {

        if (!currentReminder) {
            closeModal(true);
            return;
        }

        const reminder = currentReminder;
        const reminderId = reminder.id;

        /*
        |--------------------------------------------------------------------------
        | Close For 1 Minute
        |--------------------------------------------------------------------------
        |
        | X button dabane par:
        | - popup immediately close hoga
        | - DB me koi change nahi hoga
        | - same follow-up 1 minute tak hide rahega
        | - 1 minute baad pending raha to fir popup open hoga
        |
        */

        hideTemporarily(
            reminderId,
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Purana Permanent/Session Dismiss Remove
        |--------------------------------------------------------------------------
        */

        clearDismissed(
            reminderId
        );

        /*
        |--------------------------------------------------------------------------
        | Current Queue Se Remove
        |--------------------------------------------------------------------------
        */

        reminderQueue = reminderQueue.filter(
            item =>
                Number(item.id)
                !==
                Number(reminderId)
        );

        /*
        |--------------------------------------------------------------------------
        | Close Popup
        |--------------------------------------------------------------------------
        */

        closeModal(true);


        /*
        |--------------------------------------------------------------------------
        | Exactly 1 Minute Baad Reminder Check
        |--------------------------------------------------------------------------
        */

        setTimeout(
            function () {

                clearTemporaryHide(
                    reminderId
                );

                fetchReminders();

            },
            60 * 1000
        );
    }

    function startCountdown(reminder) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        const targetTime = new Date(reminder.scheduled_at).getTime();

        const update = function () {
            const now = Date.now();
            const difference = targetTime - now;

            if (difference <= 0) {
                const overdueSeconds = Math.abs(
                    Math.floor(difference / 1000)
                );

                const overdueMinutes = Math.floor(overdueSeconds / 60);
                const overdueSecs = overdueSeconds % 60;

                heading.textContent = 'Follow-up is overdue';
                timerLabel.textContent = 'Overdue by';

                countdown.textContent =
                    String(overdueMinutes).padStart(2, '0')
                    + ':'
                    + String(overdueSecs).padStart(2, '0');

                topBar.classList.remove(
                    'bg-amber-500',
                    'bg-emerald-600'
                );

                topBar.classList.add('bg-rose-600');

                timerBox.classList.remove(
                    'border-amber-200',
                    'bg-amber-50',
                    'border-emerald-200',
                    'bg-emerald-50'
                );

                timerBox.classList.add(
                    'border-rose-200',
                    'bg-rose-50'
                );

                timerLabel.className =
                    'text-xs font-bold uppercase tracking-wider text-rose-700';

                countdown.className =
                    'mt-1 text-3xl font-black text-rose-800';

                return;
            }

            const totalSeconds = Math.floor(difference / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;

            heading.textContent = 'Follow-up is due soon';
            timerLabel.textContent = 'Time remaining';

            countdown.textContent =
                String(minutes).padStart(2, '0')
                + ':'
                + String(seconds).padStart(2, '0');

            topBar.classList.remove(
                'bg-rose-600',
                'bg-emerald-600'
            );

            topBar.classList.add('bg-amber-500');

            timerBox.classList.remove(
                'border-rose-200',
                'bg-rose-50',
                'border-emerald-200',
                'bg-emerald-50'
            );

            timerBox.classList.add(
                'border-amber-200',
                'bg-amber-50'
            );

            timerLabel.className =
                'text-xs font-bold uppercase tracking-wider text-amber-700';

            countdown.className =
                'mt-1 text-3xl font-black text-amber-800';
        };

        update();
        countdownInterval = setInterval(update, 1000);
    }

    function showNextReminder() {
        if (modalOpen || !isReminderPopupEnabled()) {
            return;
        }

        while (reminderQueue.length > 0) {
            const reminder = reminderQueue.shift();

            if (isTemporarilyHidden(reminder.id)) {
                continue;
            }

            const scheduledTime =
                new Date(reminder.scheduled_at).getTime();

            const diffMs = scheduledTime - Date.now();

            // Upcoming reminder only during the final 60 seconds.
            // Overdue pending reminder remains eligible.
            if (diffMs > 60 * 1000) {
                continue;
            }

            openModal(reminder);
            return;
        }
    }

    async function fetchReminders() {
        if (isFetching || !isReminderPopupEnabled()) {
            return;
        }

        isFetching = true;

        try {
            const response = await fetch(
                reminderUrl,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            if (response.status === 401 || response.status === 419) {
                return;
            }

            if (!response.ok) {
                console.error(
                    'Reminder API error:',
                    response.status
                );
                return;
            }

            const data = await response.json();

            if (
                !data.success
                || !Array.isArray(data.reminders)
            ) {
                return;
            }

            if (Array.isArray(data.dispositions)) {
                callDispositions = data.dispositions;
                populatePopupDispositions();
            }

            const currentId = currentReminder
                ? Number(currentReminder.id)
                : null;

            const queuedIds = reminderQueue.map(
                item => Number(item.id)
            );

            data.reminders.forEach(reminder => {
                const id = Number(reminder.id);

                if (currentId === id) {
                    currentReminder = reminder;
                    return;
                }

                if (queuedIds.includes(id)) {
                    return;
                }

                if (
                    isTemporarilyHidden(id)
                ) {
                    return;
                }

                reminderQueue.push(reminder);
            });

            reminderQueue.sort((a, b) => {
                return new Date(a.scheduled_at).getTime()
                    - new Date(b.scheduled_at).getTime();
            });

            if (!modalOpen) {
                showNextReminder();
            }

        } catch (error) {
            console.error(
                'Unable to fetch follow-up reminders:',
                error
            );
        } finally {
            isFetching = false;
        }
    }

    async function postJson(url, payload = {}) {
        const response = await fetch(
            url,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            }
        );

        let data = {};

        try {
            data = await response.json();
        } catch (error) {
            data = {};
        }

        if (!response.ok) {
            let message =
                data.message
                || 'Request failed. Please try again.';

            if (data.errors) {
                const firstError = Object.values(data.errors)
                    .flat()
                    .find(Boolean);

                if (firstError) {
                    message = firstError;
                }
            }

            throw new Error(message);
        }

        return data;
    }

    async function completeFollowUp() {
        if (!currentReminder) {
            return;
        }

        const reminder = currentReminder;

        completeButton.disabled = true;
        completeButton.textContent = 'Completing...';

        try {
            const data = await postJson(
                reminder.complete_url,
                {}
            );

            clearTemporaryHide(reminder.id);
            clearDismissed(reminder.id);

            reminderQueue = reminderQueue.filter(
                item => Number(item.id) !== Number(reminder.id)
            );

            showActionMessage(
                data.message
                || 'Follow-up completed successfully.',
                true
            );

            setTimeout(function () {
                closeModal(true);
                fetchReminders();
                fetchSidebarNearestFollowUp();
            }, 700);

        } catch (error) {
            showActionMessage(
                error.message || 'Something went wrong.',
                false
            );
        } finally {
            completeButton.disabled = false;
            completeButton.textContent = '✓ Mark as Completed';
        }
    }

    async function snoozeFollowUp(minutes) {
        if (!currentReminder) {
            return;
        }

        const reminder = currentReminder;
        const buttons = document.querySelectorAll(
            '.followup-snooze-btn'
        );

        buttons.forEach(button => button.disabled = true);

        try {
            const data = await postJson(
                reminder.snooze_url,
                {
                    minutes: Number(minutes)
                }
            );

            clearTemporaryHide(reminder.id);
            clearDismissed(reminder.id);

            reminderQueue = reminderQueue.filter(
                item => Number(item.id) !== Number(reminder.id)
            );

            showActionMessage(
                data.message || 'Follow-up snoozed.',
                true
            );

            setTimeout(function () {
                closeModal(true);
                fetchReminders();
                fetchSidebarNearestFollowUp();
            }, 650);

        } catch (error) {
            showActionMessage(
                error.message || 'Something went wrong.',
                false
            );
        } finally {
            buttons.forEach(button => button.disabled = false);
        }
    }

    async function rescheduleFollowUp() {
        if (!currentReminder) {
            return;
        }

        const reminder = currentReminder;
        const newTime = rescheduleInput.value;

        if (!newTime) {
            showActionMessage(
                'Please select new date and time.',
                false
            );
            return;
        }

        rescheduleSave.disabled = true;
        rescheduleSave.textContent = 'Saving...';

        try {
            const data = await postJson(
                reminder.reschedule_url,
                {
                    scheduled_at: newTime
                }
            );

            clearTemporaryHide(reminder.id);
            clearDismissed(reminder.id);

            reminderQueue = reminderQueue.filter(
                item => Number(item.id) !== Number(reminder.id)
            );

            showActionMessage(
                data.message
                || 'Follow-up rescheduled successfully.',
                true
            );

            setTimeout(function () {
                closeModal(true);
                fetchReminders();
                fetchSidebarNearestFollowUp();
            }, 700);

        } catch (error) {
            showActionMessage(
                error.message || 'Unable to reschedule follow-up.',
                false
            );
        } finally {
            rescheduleSave.disabled = false;
            rescheduleSave.textContent = 'Save New Time';
        }
    }

    async function cancelFollowUp() {
        if (!currentReminder) {
            return;
        }

        const reminder = currentReminder;

        const confirmed = window.confirm(
            'Are you sure you want to cancel this follow-up?'
        );

        if (!confirmed) {
            return;
        }

        cancelButton.disabled = true;
        cancelButton.textContent = 'Cancelling...';

        try {
            const data = await postJson(
                reminder.cancel_url,
                {}
            );

            clearTemporaryHide(reminder.id);
            clearDismissed(reminder.id);

            reminderQueue = reminderQueue.filter(
                item => Number(item.id) !== Number(reminder.id)
            );

            showActionMessage(
                data.message
                || 'Follow-up cancelled successfully.',
                true
            );

            setTimeout(function () {
                closeModal(true);
                fetchReminders();
                fetchSidebarNearestFollowUp();
            }, 700);

        } catch (error) {
            showActionMessage(
                error.message || 'Unable to cancel follow-up.',
                false
            );
        } finally {
            cancelButton.disabled = false;
            cancelButton.textContent = '✕ Cancel Follow-up';
        }
    }

    function remindAgainInFiveMinutes() {
        if (!currentReminder) {
            return;
        }

        const id = currentReminder.id;

        hideTemporarily(id, 5);

        reminderQueue = reminderQueue.filter(
            item => Number(item.id) !== Number(id)
        );

        closeModal(true);
    }


    /*
    |--------------------------------------------------------------------------
    | Sidebar Nearest Follow-up Live Timer
    |--------------------------------------------------------------------------
    */

    function resetSidebarTimerClasses() {

        if (!sidebarNearestFollowup) {
            return;
        }

        sidebarNearestFollowup.classList.remove(
            'sidebar-followup-reminder',
            'border-red-400/50',
            'bg-red-500',
            'text-white',
            'border-amber-300/40',
            'bg-amber-400',
            'text-slate-950',
            'border-sky-300/40',
            'bg-sky-500'
        );
    }


    function formatSidebarDuration(totalSeconds) {

        totalSeconds = Math.max(
            0,
            Math.floor(totalSeconds)
        );

        const hours = Math.floor(totalSeconds / 3600);

        const minutes = Math.floor(
            (totalSeconds % 3600) / 60
        );

        const seconds = totalSeconds % 60;

        return String(hours).padStart(2, '0')
            + ':'
            + String(minutes).padStart(2, '0')
            + ':'
            + String(seconds).padStart(2, '0');
    }


    function updateSidebarFollowupTimer() {

        if (
            !sidebarNearestFollowup
            || !sidebarNearestFollowupText
        ) {
            return;
        }

        const scheduledTime =
            sidebarNearestFollowup.dataset.time;

        if (!scheduledTime) {

            sidebarNearestFollowup.classList.add(
                'hidden'
            );

            return;
        }

        const target =
            new Date(scheduledTime).getTime();

        if (Number.isNaN(target)) {

            sidebarNearestFollowup.classList.add(
                'hidden'
            );

            return;
        }

        sidebarNearestFollowup.classList.remove(
            'hidden'
        );

        const diffMs =
            target - Date.now();

        const absoluteSeconds =
            Math.floor(
                Math.abs(diffMs) / 1000
            );

        resetSidebarTimerClasses();


        /*
        |--------------------------------------------------------------------------
        | Overdue = Red + Blink
        |--------------------------------------------------------------------------
        */

        if (diffMs <= 0) {

            sidebarNearestFollowupText.textContent =
                'OVD '
                + formatSidebarDuration(
                    absoluteSeconds
                );

            sidebarNearestFollowup.classList.add(
                'sidebar-followup-reminder',
                'border-red-400/50',
                'bg-red-500',
                'text-white'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Upcoming Timer
        |--------------------------------------------------------------------------
        */

        sidebarNearestFollowupText.textContent =
            formatSidebarDuration(
                absoluteSeconds
            );


        /*
        |--------------------------------------------------------------------------
        | Last 10 Minutes = Red + Blink
        |--------------------------------------------------------------------------
        */

        if (
            diffMs <=
            (10 * 60 * 1000)
        ) {

            sidebarNearestFollowup.classList.add(
                'sidebar-followup-reminder',
                'border-red-400/50',
                'bg-red-500',
                'text-white'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | 10 - 30 Minutes = Amber
        |--------------------------------------------------------------------------
        */

        if (
            diffMs <=
            (30 * 60 * 1000)
        ) {

            sidebarNearestFollowup.classList.add(
                'border-amber-300/40',
                'bg-amber-400',
                'text-slate-950'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | More Than 30 Minutes = Blue
        |--------------------------------------------------------------------------
        */

        sidebarNearestFollowup.classList.add(
            'border-sky-300/40',
            'bg-sky-500',
            'text-white'
        );
    }


    async function fetchSidebarNearestFollowUp() {

        if (!sidebarNearestFollowup) {
            return;
        }

        try {

            const response =
                await fetch(
                    sidebarNearestUrl,
                    {
                        method: 'GET',

                        credentials:
                            'same-origin',

                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                    }
                );

            if (
                response.status === 401
                || response.status === 419
            ) {
                return;
            }

            if (!response.ok) {
                return;
            }

            const data =
                await response.json();

            if (
                !data.success
                || !data.followup
            ) {

                sidebarNearestFollowup.dataset.time = '';

                sidebarNearestFollowup.classList.add(
                    'hidden'
                );

                return;
            }

            sidebarNearestFollowup.dataset.time =
                data.followup.scheduled_at;

            sidebarNearestFollowup.classList.remove(
                'hidden'
            );

            updateSidebarFollowupTimer();

        } catch (error) {

            console.error(
                'Unable to fetch nearest sidebar follow-up:',
                error
            );
        }
    }


    completeButton.addEventListener(
        'click',
        completeFollowUp
    );

    closeButton.addEventListener(
        'click',
        closeCurrentReminder
    );

    remindFiveButton.addEventListener(
        'click',
        remindAgainInFiveMinutes
    );

    cancelButton.addEventListener(
        'click',
        cancelFollowUp
    );

    rescheduleToggle.addEventListener(
        'click',
        function () {
            reschedulePanel.classList.toggle('hidden');

            if (!reschedulePanel.classList.contains('hidden')) {
                rescheduleInput.min = minimumDatetimeLocalValue();

                if (currentReminder) {
                    rescheduleInput.value = toDatetimeLocalValue(
                        currentReminder.scheduled_at
                    );
                }

                rescheduleInput.focus();
            }
        }
    );

    rescheduleClose.addEventListener(
        'click',
        function () {
            reschedulePanel.classList.add('hidden');
        }
    );

    rescheduleSave.addEventListener(
        'click',
        rescheduleFollowUp
    );

    document
        .querySelectorAll('.followup-snooze-btn')
        .forEach(button => {
            button.addEventListener(
                'click',
                function () {
                    const minutes = Number(
                        this.dataset.snooze
                    );

                    snoozeFollowUp(minutes);
                }
            );
        });

    reminderEnabledToggle?.addEventListener(
        'change',
        function () {
            localStorage.setItem(
                reminderPreferenceKey,
                this.checked ? '1' : '0'
            );

            syncReminderPreferenceUi();

            if (!this.checked) {
                disableReminderPopupNow();
                return;
            }

            fetchReminders();
        }
    );

    popupDisposition?.addEventListener(
        'change',
        updatePopupDispositionFields
    );

    popupCallForm?.addEventListener(
        'submit',
        function (event) {
            if (!currentReminder || !this.action) {
                event.preventDefault();
                showActionMessage(
                    'Call save URL not available for this lead.',
                    false
                );
                return;
            }

            if (!this.reportValidity()) {
                event.preventDefault();
                return;
            }
        }
    );

    syncReminderPreferenceUi();
    requestNotificationPermission();

    fetchReminders();
    fetchSidebarNearestFollowUp();
    updateSidebarFollowupTimer();

    setInterval(
        fetchReminders,
        10000
    );

    setInterval(
        fetchSidebarNearestFollowUp,
        30000
    );

    setInterval(
        updateSidebarFollowupTimer,
        1000
    );

    document.addEventListener(
        'visibilitychange',
        function () {
            if (document.visibilityState === 'visible') {
                fetchReminders();
                fetchSidebarNearestFollowUp();
            }
        }
    );

    window.addEventListener(
        'focus',
        function () {
            fetchReminders();
            fetchSidebarNearestFollowUp();
        }
    );
});



</script>

</body>
</html>