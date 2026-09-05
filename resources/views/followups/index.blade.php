@extends('layouts.crm', [
    'title' => 'Follow-ups',
])

@section('content')

@php

    $currentStatus = request('status');

    /*
     * Leads Index ke Feedback popup ko same backend flow ke saath
     * Follow-up Index par reuse karne ke liye dispositions yahin available rakhein.
     * Agar controller already $dispositions bhej raha hai to wahi use hoga.
     */
    if (!isset($dispositions)) {
        $dispositions = \App\Models\CallDisposition::query()
            ->orderBy('name')
            ->get();
    }

    $demoDisposition = $dispositions->first(
        fn ($item) => mb_strtolower(trim((string) $item->name)) === 'demo'
    );

    $tabClass = function (?string $status) use ($currentStatus) {

        $isActive =
            $status === $currentStatus
            || ($status === null && blank($currentStatus));

        return $isActive
            ? 'followup-tab-active'
            : 'followup-tab-inactive';
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

    .followups-ui {
        --crm-blue: #2864f7;
        --crm-violet: #8b2de7;
        --crm-ink: #17203a;
        --crm-border: #e5e9f2;
        color: var(--crm-ink);
    }

    .followups-ui::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background: radial-gradient(circle at 88% 4%, rgba(116,82,244,.07), transparent 26%), #f8faff;
    }

    .followup-hero {
        background:
            radial-gradient(circle at 92% 20%, rgba(153, 64, 255, .38), transparent 28%),
            linear-gradient(110deg, #14245c 0%, #173e9f 45%, #6336e8 78%, #8d2ce7 100%);
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(45, 48, 139, .18);
    }

    .followup-summary-card {
        border-radius: 14px !important;
        box-shadow: 0 4px 15px rgba(31,42,80,.055) !important;
    }

    .followup-tabs,
    .followup-table-card {
        border: 1px solid var(--crm-border) !important;
        border-radius: 14px !important;
        background: #fff;
        box-shadow: 0 5px 18px rgba(31,42,80,.055) !important;
    }

    .followup-tab-active {
        border-color: transparent !important;
        background: linear-gradient(100deg, var(--crm-blue), #6338ef 58%, var(--crm-violet)) !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(76,61,232,.20);
    }

    .followup-tab-inactive {
        border-color: #e3e7ef !important;
        background: #fff !important;
        color: #526078 !important;
    }
    .followup-tab-inactive:hover { border-color: #c9c0ff !important; background: #faf9ff !important; color: #603ae5 !important; }

    .followup-table-head { background: #fbfcff; }
    .followup-table-head th { color: #3d4860; letter-spacing: .035em; white-space: nowrap; }
    .followup-row td { vertical-align: middle; }

    .followup-open-btn {
        display: inline-flex;
        min-height: 34px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 0;
        border-radius: 8px;
        background: linear-gradient(100deg, var(--crm-blue), #6638ee 62%, var(--crm-violet));
        padding: 0 12px;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        box-shadow: 0 6px 14px rgba(76,61,232,.18);
        transition: .18s ease;
        white-space: nowrap;
    }
    .followup-open-btn:hover { color: #fff; filter: brightness(.96); transform: translateY(-1px); }
    .followup-open-btn svg { width: 14px; height: 14px; }
    .followup-open-btn.is-disabled { cursor: not-allowed; background: #e8ebf2; color: #9aa3b4; box-shadow: none; }

    .mobile-number-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #2157d5;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }
    .mobile-number-link:hover { color: #7038e8; }
    .mobile-number-link svg { width: 14px; height: 14px; flex: none; }

    @media (max-width: 640px) {
        .followup-hero { padding: 18px !important; }
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



    .followup-feedback-btn {
        display:inline-flex;
        min-height:34px;
        align-items:center;
        justify-content:center;
        gap:6px;
        border:1px solid #ddd6fe;
        border-radius:8px;
        background:#f5f3ff;
        padding:0 10px;
        color:#7c3aed;
        font-size:11px;
        font-weight:800;
        transition:.18s ease;
        white-space:nowrap;
        cursor:pointer;
    }
    .followup-feedback-btn:hover {
        background:#ede9fe;
        border-color:#c4b5fd;
        color:#6d28d9;
        transform:translateY(-1px);
    }
    .followup-feedback-btn svg {
        width:14px;
        height:14px;
    }

    /* QUICK FEEDBACK BUTTON + PREMIUM MODAL */
    .feedback-action {
        color:#7c3aed !important;
        background:#f5f3ff !important;
        border-color:#ddd6fe !important;
        padding:0 9px !important;
        width:auto !important;
    }

    .feedback-action span {
        font-size:9px;
        font-weight:800;
    }

    .quick-modal-backdrop {
        position:fixed;
        inset:0;
        z-index:100;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:18px;
        background:rgba(15,23,42,.58);
        backdrop-filter:blur(3px);
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
    }

    .quick-modal {
        width:100%;
        max-width:800px;
        max-height:calc(100dvh - 36px);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        border:1px solid #f4ddb0;
        border-radius:18px;
        background:
            linear-gradient(145deg,#fffef9 0%,#fffaf0 42%,#fff 100%);
        box-shadow:
            0 30px 90px rgba(15,23,42,.28),
            0 0 0 1px rgba(245,185,0,.05);
    }

    .quick-modal-head {
        position:relative;
        flex:0 0 auto;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:14px;
        padding:20px 24px 15px;
        border-bottom:0;
        background:
            radial-gradient(circle at 92% 10%,rgba(245,185,0,.12),transparent 34%),
            transparent;
    }

    .quick-modal-tag {
        display:inline-flex;
        align-items:center;
        gap:6px;
        min-height:28px;
        padding:0 10px;
        border-radius:8px;
        background:#fff1bf;
        color:#9a6500;
        font-size:9px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.03em;
    }

    .quick-modal-tag svg {
        width:13px;
        height:13px;
    }

    .quick-modal-title {
        margin-top:8px;
        color:#111827;
        font-size:22px;
        line-height:1.1;
        font-weight:900;
        letter-spacing:-.02em;
    }

    .quick-modal-meta {
        margin-top:8px;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:14px;
        color:#7c6b4d;
        font-size:10px;
        font-weight:600;
    }

    .quick-modal-meta span {
        display:inline-flex;
        align-items:center;
        gap:5px;
    }

    .quick-modal-meta svg {
        width:13px;
        height:13px;
        color:#d99b00;
    }

    .quick-close {
        width:42px;
        height:42px;
        flex:0 0 42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #dedede;
        border-radius:13px;
        background:#fff;
        color:#475467;
        box-shadow:0 4px 12px rgba(15,23,42,.05);
        cursor:pointer;
    }

    .quick-close:hover {
        background:#fff8e8;
        border-color:#efc95a;
        color:#111827;
    }

    .quick-close svg {
        width:20px;
        height:20px;
    }

    .quick-modal-body {
        flex:1 1 auto;
        min-height:0;
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
        padding:0 24px 22px;
        background:transparent;
    }

    .quick-summary {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
        margin-bottom:14px;
    }

    .quick-summary-card {
        min-height:86px;
        display:flex;
        align-items:center;
        gap:10px;
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.88);
        padding:12px;
        box-shadow:0 5px 16px rgba(83,63,22,.035);
    }

    .quick-summary-icon {
        width:38px;
        height:38px;
        flex:0 0 38px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        background:#fff4cb;
        color:#d99b00;
    }

    .quick-summary-icon svg {
        width:17px;
        height:17px;
    }

    .quick-summary-label {
        font-size:8px;
        font-weight:900;
        color:#8a8f98;
        text-transform:uppercase;
        letter-spacing:.04em;
    }

    .quick-summary-value {
        margin-top:4px;
        font-size:11px;
        line-height:1.35;
        font-weight:900;
        color:#1f2937;
        overflow-wrap:anywhere;
    }

    .quick-top-actions {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:9px;
        margin-bottom:10px;
    }

    .quick-primary-action {
        min-height:50px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:9px;
        border:1px solid #f0c333;
        border-radius:11px;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        box-shadow:0 7px 16px rgba(255,196,0,.15);
    }

    .quick-primary-action.secondary {
        background:#fff;
        border-color:#eccd71;
        box-shadow:none;
    }

    .quick-primary-action svg {
        width:18px;
        height:18px;
    }

    .quick-tabs {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:9px;
        margin-bottom:12px;
    }

    .quick-tab {
        min-height:50px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid #e8cb78;
        border-radius:11px;
        background:#fff;
        padding:0 12px;
        color:#344054;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        transition:.15s ease;
    }

    .quick-tab svg {
        width:17px;
        height:17px;
    }

    .quick-tab.active {
        border-color:#f1b900;
        background:#fff8dc;
        color:#1f2937;
        box-shadow:inset 0 0 0 1px rgba(241,185,0,.12);
    }

    .quick-panel {
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.90);
        padding:16px;
    }

    .quick-grid {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:14px;
    }

    .quick-field label {
        display:block;
        margin-bottom:6px;
        color:#475569;
        font-size:10px;
        font-weight:900;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        width:100%;
        border:1px solid #d7dde7;
        border-radius:10px;
        background:#fff;
        color:#1e293b;
        font-size:11px;
        outline:none;
    }

    .quick-field input,
    .quick-field select {
        min-height:46px;
        padding:0 12px;
    }

    .quick-field textarea {
        min-height:96px;
        padding:10px 12px;
        resize:vertical;
    }

    .quick-field input:focus,
    .quick-field select:focus,
    .quick-field textarea:focus {
        border-color:#f1b900;
        box-shadow:0 0 0 3px rgba(241,185,0,.12);
    }

    .quick-form-actions {
        position:sticky;
        bottom:-1px;
        z-index:12;
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:9px;
        margin-top:16px;
        padding:12px 0 4px;
        background:linear-gradient(to top, #fff 72%, rgba(255,255,255,.90) 88%, rgba(255,255,255,0));
    }

    .quick-btn {
        min-height:42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid #d8dee7;
        border-radius:10px;
        background:#fff;
        padding:0 15px;
        color:#475467;
        font-size:9px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
    }

    .quick-btn-green {
        border-color:#059669;
        background:#059669;
        color:#fff;
    }

    .quick-btn-blue {
        border-color:#2563eb;
        background:#2563eb;
        color:#fff;
    }

    .quick-btn-violet {
        border-color:#f1b900;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
    }

    .quick-btn-dark {
        border-color:#334155;
        background:#334155;
        color:#fff;
    }

    .quick-status {
        border:1px solid #f0dfac;
        border-radius:10px;
        background:#fffaf0;
        padding:12px;
    }

    @media (max-width:700px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
            border-radius:14px;
        }

        .quick-modal-head {
            padding-top:14px;
            padding-bottom:10px;
        }

        .quick-modal-head,
        .quick-modal-body {
            padding-left:14px;
            padding-right:14px;
        }

        .quick-modal-title {
            font-size:18px;
        }

        .quick-close {
            width:38px;
            height:38px;
            flex-basis:38px;
        }

        .quick-summary {
            grid-template-columns:repeat(2,1fr);
            gap:8px;
        }

        .quick-summary-card {
            min-height:74px;
            padding:10px;
        }

        .quick-top-actions,
        .quick-tabs,
        .quick-grid {
            grid-template-columns:1fr;
        }

        .quick-top-actions,
        .quick-tabs {
            gap:7px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:44px;
        }

        .quick-panel {
            padding:12px;
        }

        .quick-form-actions {
            margin-left:-12px;
            margin-right:-12px;
            padding:12px 12px 6px;
        }
    }

    @media (max-height:720px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding-top:8px;
            padding-bottom:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
        }

        .quick-modal-head {
            padding-top:12px;
            padding-bottom:10px;
        }

        .quick-modal-body {
            padding-bottom:12px;
        }
    }


    /* =========================================================
       COMPACT QUICK ACTION MODAL
       ========================================================= */
    .quick-modal {
        max-width: 720px;
        border-radius: 14px;
    }

    .quick-modal-head {
        gap: 10px;
        padding: 12px 16px 9px;
    }

    .quick-modal-tag {
        min-height: 22px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 8px;
    }

    .quick-modal-tag svg {
        width: 11px;
        height: 11px;
    }

    .quick-modal-title {
        margin-top: 5px;
        font-size: 18px;
    }

    .quick-modal-meta {
        margin-top: 5px;
        gap: 10px;
        font-size: 9px;
    }

    .quick-modal-meta svg {
        width: 11px;
        height: 11px;
    }

    .quick-close {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
    }

    .quick-close svg {
        width: 16px;
        height: 16px;
    }

    .quick-modal-body {
        padding: 0 16px 14px;
    }

    .quick-summary {
        gap: 7px;
        margin-bottom: 9px;
    }

    .quick-summary-card {
        min-height: 62px;
        gap: 8px;
        padding: 8px 9px;
        border-radius: 10px;
    }

    .quick-summary-icon {
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
    }

    .quick-summary-icon svg {
        width: 14px;
        height: 14px;
    }

    .quick-summary-label {
        font-size: 7px;
    }

    .quick-summary-value {
        margin-top: 2px;
        font-size: 10px;
        line-height: 1.2;
    }

    .quick-top-actions {
        gap: 7px;
        margin-bottom: 7px;
    }

    .quick-primary-action {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        font-size: 9px;
    }

    .quick-primary-action svg {
        width: 14px;
        height: 14px;
    }

    .quick-tabs {
        gap: 7px;
        margin-bottom: 8px;
    }

    .quick-tab {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        padding: 0 9px;
        font-size: 9px;
    }

    .quick-tab svg {
        width: 14px;
        height: 14px;
    }

    .quick-panel {
        padding: 11px;
        border-radius: 10px;
    }

    .quick-grid {
        gap: 9px;
    }

    .quick-field label {
        margin-bottom: 4px;
        font-size: 9px;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        border-radius: 8px;
        font-size: 10px;
    }

    .quick-field input,
    .quick-field select {
        min-height: 36px;
        padding: 0 9px;
    }

    .quick-field textarea {
        min-height: 68px;
        padding: 8px 9px;
    }

    .quick-status {
        padding: 9px;
        border-radius: 8px;
    }

    .quick-form-actions {
        gap: 7px;
        margin-top: 9px;
        padding: 8px 0 2px;
    }

    .quick-btn {
        min-height: 34px;
        gap: 5px;
        border-radius: 8px;
        padding: 0 11px;
        font-size: 8px;
    }

    .quick-btn svg {
        width: 13px;
        height: 13px;
    }

    }

    @media (max-width:700px) {
        .quick-modal-head {
            padding: 10px 11px 8px;
        }

        .quick-modal-body {
            padding-left: 11px;
            padding-right: 11px;
            padding-bottom: 10px;
        }

        .quick-summary {
            grid-template-columns: repeat(2,1fr);
            gap: 6px;
        }

        .quick-summary-card {
            min-height: 56px;
            padding: 7px;
        }

        .quick-top-actions,
        .quick-tabs {
            grid-template-columns: repeat(3,1fr);
            gap: 5px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height: 36px;
            padding: 0 6px;
            font-size: 8px;
        }

        .quick-grid {
            grid-template-columns:1fr;
        }
    }

    @media (max-height:720px) {
        .quick-modal-head {
            padding-top:8px;
            padding-bottom:7px;
        }

        .quick-summary-card {
            min-height:54px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:34px;
        }
    }

</style>


<div
    class="followups-ui mx-auto max-w-none space-y-4"
    x-data="followupIndexBoard()"
    @keydown.escape.window="feedbackOpen = false"
>


    {{-- =========================================================
        Header
    ========================================================== --}}

    <div
        class="followup-hero relative overflow-hidden p-6 text-white">

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


            <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">

                {{-- Permanent Follow-up Reminder ON/OFF Switch --}}
                <div
                    class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur"
                >
                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <div class="text-xs text-slate-400">
                                Reminder Popup
                            </div>

                            <div
                                id="followup-page-reminder-status"
                                class="mt-1 text-sm font-bold text-emerald-300"
                            >
                                ON
                            </div>
                        </div>

                        <label
                            for="followup-page-reminder-toggle"
                            class="relative inline-flex cursor-pointer items-center"
                            title="Follow-up reminder popup ON/OFF"
                        >
                            <input
                                type="checkbox"
                                id="followup-page-reminder-toggle"
                                class="peer sr-only"
                                checked
                            >

                            <span
                                class="
                                    relative
                                    h-7
                                    w-12
                                    rounded-full
                                    bg-slate-600
                                    transition
                                    duration-200

                                    after:absolute
                                    after:left-1
                                    after:top-1
                                    after:h-5
                                    after:w-5
                                    after:rounded-full
                                    after:bg-white
                                    after:transition
                                    after:duration-200
                                    after:content-['']

                                    peer-checked:bg-emerald-500
                                    peer-checked:after:translate-x-5

                                    peer-focus:ring-2
                                    peer-focus:ring-emerald-300/50
                                "
                            ></span>
                        </label>

                    </div>

                    <div
                        id="followup-page-reminder-help"
                        class="mt-1.5 text-[11px] text-slate-400"
                    >
                        Popup reminders are enabled
                    </div>
                </div>


                {{-- Current Time --}}
                <div
                    class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur"
                >

                    <div class="text-xs text-slate-400">
                        Current Time
                    </div>

                    <div
                        id="followup-current-time"
                        class="mt-1 font-semibold text-white"
                    >
                        {{ now()->format('d M Y, h:i:s A') }}
                    </div>

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
            class="followup-summary-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

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
            class="followup-summary-card group rounded-2xl border border-amber-200 bg-amber-50/40 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

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
            class="followup-summary-card reminder-soon group rounded-2xl border border-orange-200 bg-orange-50/60 p-4 shadow-sm transition hover:-translate-y-0.5">

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
            class="followup-summary-card {{ $overdueCount > 0 ? 'reminder-danger' : '' }}
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
            class="followup-summary-card group rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

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
        class="followup-tabs rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">

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
        class="followup-table-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


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
                class="w-full min-w-[1260px] text-sm">


                <thead class="followup-table-head bg-slate-50/80">

                    <tr
                        class="border-b border-slate-200 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">

                        <th class="px-5 py-4">
                            Lead / Business
                        </th>

                        <th class="px-4 py-4">
                            Mobile Number
                        </th>

                        <th class="px-4 py-4">
                            Reminder
                        </th>

                        <th class="px-4 py-4">
                            Status
                        </th>

                        <th class="px-4 py-4">
                            Type
                        </th>

                        <th class="px-4 py-4">
                            Assigned To
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

                            $popupLead = null;

                            if ($followup->lead) {
                                $lead = $followup->lead;

                                $popupLatestCall = $lead->latestCall;

                                $popupLastRemark = $popupLatestCall?->remarks
                                    ?? $popupLatestCall?->remark
                                    ?? $popupLatestCall?->auto_remarks
                                    ?? null;

                                $popupLastFeedback = $popupLastRemark
                                    ?: ($lead->latest_note_body ?? '');

                                $whatsappNumber = preg_replace(
                                    '/\D+/',
                                    '',
                                    (string) ($lead->whatsapp_number ?: $lead->mobile)
                                );

                                $popupLead = [
                                    'id' => (int) $lead->id,
                                    'name' => $lead->name ?: 'No Name',
                                    'business' => $lead->company_name ?: '',
                                    'mobile' => $lead->mobile ?: '',
                                    'whatsapp' => $whatsappNumber,
                                    'city' => $lead->city ?: '',
                                    'state' => $lead->state ?: '',
                                    'category' => $lead->category ?: '',
                                    'demoSent' => (bool) $lead->demo_send,
                                    'showUrl' => route('leads.show', $lead),
                                    'callStoreUrl' => route('calls.store', $lead),
                                    'noteStoreUrl' => route('leads.notes', $lead),
                                    'demoUpdateUrl' => route('leads.update', $lead),
                                    'lastDisposition' => $popupLatestCall?->disposition?->name ?: '',
                                    'lastRemark' => $popupLastFeedback ?: '',
                                    'lastEmployee' => $popupLatestCall?->user?->name ?: '',
                                    'lastCallAt' => $popupLatestCall?->created_at
                                        ? $popupLatestCall->created_at->format('d M Y, h:i A')
                                        : '',
                                    'demoCallUrl' => route('leads.demo.store', $lead),
                                ];
                            }

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

                                            <div class="mt-1 text-[11px] font-medium text-slate-500">
                                                {{ $followup->lead->company_name ?: 'Individual Lead' }}
                                            </div>

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



                            {{-- Mobile Number --}}

                            <td class="px-4 py-4">

                                @if($followup->lead?->mobile)
                                    <a
                                        href="tel:{{ preg_replace('/[^0-9+]/', '', $followup->lead->mobile) }}"
                                        class="mobile-number-link"
                                        title="Call {{ $followup->lead->mobile }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                                        </svg>
                                        {{ $followup->lead->mobile }}
                                    </a>
                                @else
                                    <span class="text-xs font-medium text-slate-400">Not available</span>
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



                            {{-- Type --}}

                            <td class="px-4 py-4">

                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold capitalize ring-1
                                    {{ $typeClass }}">

                                    {{ $followup->type ?: 'follow-up' }}

                                </span>

                            </td>



                            {{-- Assigned User --}}

                            <td class="px-4 py-4">

                                <div class="font-semibold text-slate-800">
                                    {{ $followup->assignedUser?->name ?? 'Unassigned' }}
                                </div>

                                @if($followup->assignedUser?->employee_code)
                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $followup->assignedUser->employee_code }}
                                    </div>
                                @endif

                            </td>



                            {{-- Actions --}}

                            <td class="px-5 py-4">

                                <div
                                    class="flex items-center justify-end gap-2">

                                    @if($followup->lead)
                                        <button
                                            type="button"
                                            class="followup-feedback-btn"
                                            title="Feedback & Actions"
                                            @click='openFeedback(@json($popupLead))'
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
                                                <path d="M8 9h8M8 13h5"/>
                                            </svg>
                                            Feedback
                                        </button>

                                        <a
                                            href="{{ route('leads.show', $followup->lead) }}"
                                            class="followup-open-btn"
                                            title="Open {{ $followup->lead->name }}"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M15 3h6v6"/>
                                                <path d="M10 14 21 3"/>
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                            </svg>
                                            Open
                                        </a>
                                    @else
                                        <span class="followup-open-btn is-disabled" title="Lead unavailable">
                                            Open
                                        </span>
                                    @endif


                                    @if($followup->status === 'pending')

                                        @can('followups.complete')
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
                                        @endcan

                                    @endif



                                    @can('followups.delete')
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
                                    @endcan

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="7"
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

    {{-- QUICK FEEDBACK / ALL ACTIONS MODAL --}}
    <div
        x-show="feedbackOpen"
        x-cloak
        class="quick-modal-backdrop"
        @click.self="feedbackOpen = false"
    >
        <div class="quick-modal" role="dialog" aria-modal="true">

            <div class="quick-modal-head">

                <div class="min-w-0">

                    <div class="quick-modal-tag">
                        <i data-lucide="zap"></i>
                        Quick Lead Actions
                    </div>

                    <div
                        class="quick-modal-title truncate"
                        x-text="
                            selectedLead.business
                            ||
                            selectedLead.name
                            ||
                            'Lead'
                        "
                    ></div>

                    <div class="quick-modal-meta">

                        <span>
                            <i data-lucide="phone"></i>

                            <span
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    'No mobile'
                                "
                            ></span>
                        </span>

                        <span x-show="selectedLead.city">
                            <i data-lucide="map-pin"></i>

                            <span
                                x-text="selectedLead.city"
                            ></span>
                        </span>

                        <span x-show="selectedLead.category">
                            <i data-lucide="briefcase-business"></i>

                            <span
                                x-text="selectedLead.category"
                            ></span>
                        </span>

                    </div>

                </div>


                <button
                    type="button"
                    class="quick-close"
                    @click="feedbackOpen = false"
                    aria-label="Close"
                >
                    <i data-lucide="x"></i>
                </button>

            </div>


            <div class="quick-modal-body">

                <div class="quick-summary">

                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="user"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Lead
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.name
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="briefcase-business"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Business
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.business
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="smartphone"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Mobile
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="video"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Demo
                            </div>

                            <div
                                class="quick-summary-value"
                                :class="
                                    selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-slate-700'
                                "
                                x-text="
                                    selectedLead.demoSent
                                        ? 'Demo Sent'
                                        : 'Not Sent'
                                "
                            ></div>
                        </div>

                    </div>

                </div>

                {{-- PREVIOUS LEAD ACTIVITY: visible even when this employee sees the lead as New Call --}}
                <div
                    x-show="
                        selectedLead.lastDisposition
                        || selectedLead.lastRemark
                        || selectedLead.lastEmployee
                    "
                    x-cloak
                    class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <i data-lucide="history" class="h-4 w-4 text-amber-600"></i>
                        <span class="text-[10px] font-black text-amber-700">
                            PREVIOUS ACTIVITY
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST DISPOSITION
                            </div>
                            <div
                                class="mt-1 text-[10px] font-extrabold text-slate-800"
                                x-text="selectedLead.lastDisposition || '—'"
                            ></div>
                        </div>

                        <div>
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST EMPLOYEE
                            </div>
                            <div
                                class="mt-1 text-[10px] font-extrabold text-slate-800"
                                x-text="selectedLead.lastEmployee || '—'"
                            ></div>
                        </div>

                        <div class="col-span-2">
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST REMARK
                            </div>
                            <div
                                class="mt-1 whitespace-pre-line text-[10px] font-semibold leading-5 text-slate-700"
                                x-text="selectedLead.lastRemark || 'No previous remark'"
                            ></div>
                        </div>

                        <div class="col-span-2" x-show="selectedLead.lastCallAt">
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST CALL
                            </div>
                            <div
                                class="mt-1 text-[9px] font-bold text-slate-600"
                                x-text="selectedLead.lastCallAt"
                            ></div>
                        </div>
                    </div>
                </div>


                <div class="quick-top-actions">

                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="sendCall(selectedLead.id)"
                        :disabled="
                            !selectedLead.id
                            ||
                            sendingCall === selectedLead.id
                        "
                    >
                        <i data-lucide="phone"></i>
                        Call on Mobile
                    </button>


                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="
                            openWhatsApp(
                                selectedLead.whatsapp
                            )
                        "
                        :disabled="
                            !selectedLead.whatsapp
                        "
                    >
                        <i data-lucide="message-circle"></i>
                        WhatsApp Web
                    </button>


                    <a
                        :href="
                            selectedLead.showUrl
                            ||
                            '#'
                        "
                        class="
                            quick-primary-action
                            secondary
                        "
                    >
                        <i data-lucide="external-link"></i>
                        Full Lead
                    </a>

                </div>


                <div class="quick-tabs">

                    @can('calls.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'call'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'call'
                            "
                        >
                            <i data-lucide="star"></i>
                            Save Feedback
                        </button>
                    @endcan


                    @can('leads.update')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'demo'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                openDemoTab()
                            "
                        >
                            <i data-lucide="video"></i>
                            Demo
                        </button>
                    @endcan


                    @can('leads.notes.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'note'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'note'
                            "
                        >
                            <i data-lucide="notebook-pen"></i>
                            Add Note
                        </button>
                    @endcan

                </div>



                @can('calls.create')
                    <div x-show="feedbackTab==='call'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.callStoreUrl">
                            @csrf

                            <div class="quick-grid">
                                <div class="quick-field">
                                    <label>Call Result <span class="text-rose-500">*</span></label>
                                    <select
                                        name="call_disposition_id"
                                        required
                                        x-model="callForm.dispositionId"
                                        @change="dispositionChanged($event)"
                                    >
                                        <option value="">Select call result</option>
                                        @foreach($dispositions as $disposition)
                                            <option
                                                value="{{ $disposition->id }}"
                                                data-requires-remarks="{{ $disposition->requires_remarks ? '1' : '0' }}"
                                                data-requires-follow-up="{{ $disposition->requires_follow_up ? '1' : '0' }}"
                                                data-auto-remarks="{{ e($disposition->auto_remarks ?? '') }}"
                                                data-next-followup="{{ $disposition->next_followup ?? '' }}"
                                            >
                                                {{ $disposition->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="quick-field">
                                    <label>Call Duration</label>
                                    <input type="number" min="0" name="duration_seconds"
                                           x-model="callForm.duration"
                                           placeholder="Seconds">
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showRemarks" x-cloak>
                                    <label>
                                        Remarks
                                        <span x-show="callForm.remarksRequired" class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        name="remarks"
                                        x-model="callForm.remarks"
                                        :required="callForm.remarksRequired"
                                        placeholder="Customer ne kya kaha..."
                                    ></textarea>
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showFollowup" x-cloak>
                                    <label>
                                        Next Follow-up
                                        <span x-show="callForm.followupRequired" class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        name="follow_up_at"
                                        x-model="callForm.followupAt"
                                        :required="callForm.followupRequired"
                                        min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                    >
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-green">
                                    <i data-lucide="save"></i> Save Feedback
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.update')
                    <div
                        x-show="feedbackTab === 'demo'"
                        x-cloak
                        class="quick-panel"
                    >
                        <form
                            method="POST"
                            :action="selectedLead.callStoreUrl"
                            @submit="prepareDemoSubmit($event)"
                        >
                            @csrf

                            {{-- Same backend flow as normal Call Result -> Demo --}}
                            <input type="hidden" name="mark_demo_send" value="1">
                            <input type="hidden" name="duration_seconds" value="0">

                            @if($demoDisposition)
                                <input
                                    type="hidden"
                                    name="call_disposition_id"
                                    value="{{ $demoDisposition->id }}"
                                >
                            @endif

                            <div class="quick-grid mt-3">

                                <div class="quick-field md:col-span-2">
                                    <label>Remarks</label>
                                    <textarea
                                        name="remarks"
                                        x-model="demoForm.remarks"
                                        readonly
                                    ></textarea>
                                </div>

                                @if(
                                    $demoDisposition
                                    && (
                                        $demoDisposition->requires_follow_up
                                        || (int) ($demoDisposition->next_followup ?? 0) > 0
                                    )
                                )
                                    <div class="quick-field md:col-span-2">
                                        <label>
                                            Next Follow-up
                                            @if($demoDisposition->requires_follow_up)
                                                <span class="text-rose-500">*</span>
                                            @endif
                                        </label>

                                        <input
                                            type="datetime-local"
                                            name="follow_up_at"
                                            x-model="demoForm.followupAt"
                                            @if($demoDisposition->requires_follow_up) required @endif
                                            min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                        >
                                    </div>
                                @endif
                            </div>

                            <div class="quick-status">
                                <div class="text-[9px] font-bold uppercase text-slate-500">
                                    Demo Action
                                </div>

                                <div
                                    class="mt-1 text-sm font-extrabold"
                                    :class="selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-violet-700'"
                                    x-text="selectedLead.demoSent
                                        ? 'Demo Already Sent — Resend Allowed'
                                        : 'Ready To Send Demo'"
                                ></div>

                                <div class="mt-2 text-[10px] leading-5 text-slate-500">
                                    <strong>Send Demo</strong> par click karte hi
                                    wahi flow chalega jo <strong>Call Result me Demo disposition</strong>
                                    select karke save karne par chalta hai:
                                    Demo status, Call Log, Auto Remark aur Next Follow-up
                                    ek hi backend transaction me save/update honge.
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg border border-violet-100 bg-violet-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[9px] font-bold uppercase text-violet-500">
                                            Call Disposition
                                        </div>
                                        <div class="mt-1 text-sm font-extrabold text-violet-800">
                                            Demo
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[9px] font-bold uppercase text-slate-400">
                                            Current Demo Status
                                        </div>
                                        <div
                                            class="mt-1 text-sm font-extrabold"
                                            :class="selectedLead.demoSent
                                                ? 'text-emerald-600'
                                                : 'text-slate-600'"
                                            x-text="selectedLead.demoSent
                                                ? 'Already Sent'
                                                : 'Not Sent'"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button
                                    type="button"
                                    class="quick-btn"
                                    @click="feedbackOpen = false"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="quick-btn quick-btn-violet"
                                >
                                    <i data-lucide="video"></i>
                                    <span x-text="selectedLead.demoSent ? 'Resend Demo' : 'Send Demo'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.notes.create')
                    <div x-show="feedbackTab==='note'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.noteStoreUrl">
                            @csrf

                            <div class="quick-field">
                                <label>Internal Note <span class="text-rose-500">*</span></label>
                                <textarea
                                    name="body"
                                    required
                                    x-model="noteBody"
                                    placeholder="Customer discussion, requirement ya internal note..."
                                ></textarea>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-dark">
                                    <i data-lucide="plus"></i> Add Note
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>

</div>



{{-- =========================================================
    Leads Index Same Feedback Popup Logic
========================================================== --}}


<script>
    function followupIndexBoard() {
        return {
            sendingCall: null,
            feedbackOpen: false,
            feedbackTab: 'call',

            selectedLead: {
                id:null,
                name:'',
                business:'',
                mobile:'',
                whatsapp:'',
                city:'',
                state:'',
                category:'',
                demoSent:false,
                lastDisposition:'',
                lastRemark:'',
                lastEmployee:'',
                lastCallAt:'',
                showUrl:'',
                callStoreUrl:'',
                noteStoreUrl:'',
                demoUpdateUrl:'',
                demoCallUrl:''
            },

            noteBody: '',

            callForm: {
                dispositionId:'',
                duration:'',
                remarks:'',
                followupAt:'',
                showRemarks:false,
                remarksRequired:false,
                showFollowup:false,
                followupRequired:false
            },

            demoConfig: {
                dispositionId: @json($demoDisposition?->id),
                remarks: @json($demoDisposition?->auto_remarks ?? ''),
                requiresFollowUp: {{ $demoDisposition?->requires_follow_up ? 'true' : 'false' }},
                nextFollowupMinutes: {{ (int) ($demoDisposition?->next_followup ?? 0) }}
            },

            demoForm: {
                remarks: @json($demoDisposition?->auto_remarks ?? ''),
                followupAt: ''
            },

            openFeedback(lead) {
                this.selectedLead = {
                    ...this.selectedLead,
                    ...lead
                };

                this.feedbackTab = 'call';
                this.noteBody = '';

                this.callForm = {
                    dispositionId:'',
                    duration:'',
                    remarks:'',
                    followupAt:'',
                    showRemarks:false,
                    remarksRequired:false,
                    showFollowup:false,
                    followupRequired:false
                };

                this.feedbackOpen = true;

                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            },

            openDemoTab() {
                this.feedbackTab = 'demo';

                this.demoForm.remarks =
                    String(this.demoConfig.remarks || '').trim();

                const minutes =
                    Number(this.demoConfig.nextFollowupMinutes || 0);

                this.demoForm.followupAt =
                    Number.isFinite(minutes) && minutes > 0
                        ? this.datetimeLocalAfterMinutes(minutes)
                        : '';

                this.$nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            },

            datetimeLocalAfterMinutes(minutes) {
                const value = Number(minutes);

                if (!Number.isFinite(value) || value <= 0) {
                    return '';
                }

                const d =
                    new Date(Date.now() + value * 60 * 1000);

                const pad =
                    n => String(n).padStart(2, '0');

                return d.getFullYear()
                    + '-'
                    + pad(d.getMonth() + 1)
                    + '-'
                    + pad(d.getDate())
                    + 'T'
                    + pad(d.getHours())
                    + ':'
                    + pad(d.getMinutes());
            },

            prepareDemoSubmit(event) {
                const form = event?.target;

                if (!form) {
                    return;
                }

                if (
                    this.demoConfig.requiresFollowUp
                    && !this.demoForm.followupAt
                ) {
                    event.preventDefault();

                    alert(
                        'Demo disposition ke liye Next Follow-up date and time required hai.'
                    );

                    return;
                }

                if (!this.demoForm.followupAt) {
                    const minutes =
                        Number(
                            this.demoConfig.nextFollowupMinutes || 0
                        );

                    if (
                        Number.isFinite(minutes)
                        && minutes > 0
                    ) {
                        this.demoForm.followupAt =
                            this.datetimeLocalAfterMinutes(minutes);
                    }
                }
            },

            dispositionChanged(event) {
                const option =
                    event.target.options[
                        event.target.selectedIndex
                    ];

                if (!option || !option.value) {
                    this.callForm.remarks = '';
                    this.callForm.followupAt = '';
                    this.callForm.showRemarks = false;
                    this.callForm.remarksRequired = false;
                    this.callForm.showFollowup = false;
                    this.callForm.followupRequired = false;
                    return;
                }

                const requiresRemarks =
                    option.dataset.requiresRemarks === '1';

                const requiresFollowUp =
                    option.dataset.requiresFollowUp === '1';

                const autoRemarks =
                    String(
                        option.dataset.autoRemarks || ''
                    ).trim();

                const nextMinutes =
                    Number(
                        option.dataset.nextFollowup || 0
                    );

                const hasAutoFollowup =
                    Number.isFinite(nextMinutes)
                    && nextMinutes > 0;

                this.callForm.remarks =
                    autoRemarks;

                this.callForm.remarksRequired =
                    requiresRemarks;

                this.callForm.showRemarks =
                    requiresRemarks
                    || autoRemarks !== '';

                this.callForm.followupAt =
                    hasAutoFollowup
                        ? this.datetimeLocalAfterMinutes(
                            nextMinutes
                        )
                        : '';

                this.callForm.followupRequired =
                    requiresFollowUp
                    || hasAutoFollowup;

                this.callForm.showFollowup =
                    requiresFollowUp
                    || hasAutoFollowup;
            },

            async sendCall(leadId) {
                if (!leadId || this.sendingCall) {
                    return;
                }

                this.sendingCall =
                    leadId;

                try {
                    const token =
                        document.querySelector(
                            'meta[name=csrf-token]'
                        )?.content || '';

                    const response =
                        await fetch(
                            `/leads/${leadId}/call-on-mobile`,
                            {
                                method:'POST',
                                headers:{
                                    'X-CSRF-TOKEN':token,
                                    'Accept':'application/json',
                                    'Content-Type':'application/json'
                                },
                                body:JSON.stringify({})
                            }
                        );

                    let data = {};

                    try {
                        data =
                            await response.json();
                    } catch (e) {}

                    if (
                        !response.ok
                        || !data.status
                    ) {
                        throw new Error(
                            data.message
                            || 'Unable to send call to mobile.'
                        );
                    }

                    alert(
                        data.message
                        || 'Call sent to mobile successfully.'
                    );

                } catch (error) {
                    alert(
                        error.message
                        || 'Unable to send call to mobile.'
                    );
                } finally {
                    this.sendingCall =
                        null;
                }
            },

            openWhatsApp(number) {
                const clean =
                    String(number || '')
                        .replace(/\D/g, '');

                if (!clean) {
                    alert(
                        'WhatsApp number is missing.'
                    );
                    return;
                }

                const url =
                    `https://web.whatsapp.com/send?phone=${encodeURIComponent(clean)}`;

                const whatsappTab =
                    window.open(
                        '',
                        'rvg_whatsapp_web'
                    );

                if (whatsappTab) {
                    whatsappTab.location.href =
                        url;

                    whatsappTab.focus();
                } else {
                    alert(
                        'Browser popup blocked hai. Popups allow karke dobara try karein.'
                    );
                }
            }
        };
    }
</script>

{{-- =========================================================
    Live Reminder Countdown
========================================================== --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const countdowns =
        document.querySelectorAll('.countdown');


    /*
    |--------------------------------------------------------------------------
    | Permanent Reminder Popup ON/OFF Switch
    |--------------------------------------------------------------------------
    |
    | Same localStorage key use hoti hai jo global CRM reminder popup
    | layout use karta hai. Isliye is page ka switch aur popup ka switch
    | hamesha same preference ko control karenge.
    |
    */

    const followupPageReminderToggle =
        document.getElementById('followup-page-reminder-toggle');

    const followupPageReminderStatus =
        document.getElementById('followup-page-reminder-status');

    const followupPageReminderHelp =
        document.getElementById('followup-page-reminder-help');

    const followupReminderPreferenceKey =
        'followup_popup_enabled_user_' + @json(auth()->id());


    function isFollowupReminderEnabled() {

        return localStorage.getItem(
            followupReminderPreferenceKey
        ) !== '0';

    }


    function syncFollowupReminderSwitch() {

        const enabled =
            isFollowupReminderEnabled();

        if (followupPageReminderToggle) {

            followupPageReminderToggle.checked =
                enabled;

        }


        if (followupPageReminderStatus) {

            followupPageReminderStatus.textContent =
                enabled
                    ? 'ON'
                    : 'OFF';

            followupPageReminderStatus.classList.remove(
                'text-emerald-300',
                'text-rose-300'
            );

            followupPageReminderStatus.classList.add(
                enabled
                    ? 'text-emerald-300'
                    : 'text-rose-300'
            );

        }


        if (followupPageReminderHelp) {

            followupPageReminderHelp.textContent =
                enabled
                    ? 'Popup reminders are enabled'
                    : 'Popup reminders are disabled';

        }

    }


    function notifyGlobalReminderToggle(enabled) {

        /*
        | Global CRM layout me popup ke andar jo toggle already hai,
        | usko bhi same state dete hain. Change event dispatch karne se
        | currently open popup OFF karte hi close ho jayega.
        */

        const globalToggle =
            document.getElementById(
                'followUpReminderEnabledToggle'
            );

        if (!globalToggle) {
            return;
        }

        globalToggle.checked =
            enabled;

        globalToggle.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true,
                }
            )
        );

    }


    if (followupPageReminderToggle) {

        followupPageReminderToggle.addEventListener(
            'change',
            function () {

                const enabled =
                    followupPageReminderToggle.checked;

                localStorage.setItem(
                    followupReminderPreferenceKey,
                    enabled
                        ? '1'
                        : '0'
                );

                syncFollowupReminderSwitch();
                notifyGlobalReminderToggle(enabled);

            }
        );

    }


    window.addEventListener(
        'storage',
        function (event) {

            if (
                event.key
                ===
                followupReminderPreferenceKey
            ) {

                syncFollowupReminderSwitch();

            }

        }
    );


    syncFollowupReminderSwitch();


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