@extends('layouts.crm', [
    'title' => $lead->name,
])

@section('content')

@once
<style>
    [x-cloak] {
        display: none !important;
    }

    .lead-simple-page {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --green: #059669;
        --green-dark: #047857;
        --border: #e5e7eb;
        --muted: #64748b;
        --dark: #0f172a;

        color: var(--dark);
    }

    .lead-simple-page::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background: #f8fafc;
    }

    .lead-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    }

    .top-bar {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
    }

    .simple-btn {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 9px;
        border: 1px solid #dce1e8;
        background: #ffffff;
        padding: 0 13px;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.15s ease;
    }

    .simple-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .simple-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .simple-btn-primary {
        border: 0;
        background: var(--primary);
        color: #ffffff;
    }

    .simple-btn-primary:hover {
        background: var(--primary-dark);
        color: #ffffff;
    }

    .header-call-btn {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 10px;
        background: var(--green);
        color: #ffffff;
        padding: 0 18px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: 0.15s ease;
    }

    .header-call-btn:hover {
        background: var(--green-dark);
        color: #ffffff;
    }

    .form-btn {
        display: inline-flex;
        width: 100%;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 0;
        border-radius: 10px;
        background: var(--primary);
        color: #ffffff;
        padding: 0 16px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
    }

    .form-btn:hover {
        background: var(--primary-dark);
    }

    .form-btn-green {
        background: var(--green);
    }

    .form-btn-green:hover {
        background: var(--green-dark);
    }

    .form-btn-dark {
        background: #334155;
    }

    .form-btn-dark:hover {
        background: #1e293b;
    }

    .field-label {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .lead-simple-page input,
    .lead-simple-page select,
    .lead-simple-page textarea {
        width: 100%;
        border: 1px solid #d8dee8;
        border-radius: 9px;
        background: #ffffff;
        color: #1e293b;
        font-size: 13px;
        outline: none;
        box-shadow: none;
    }

    .lead-simple-page input,
    .lead-simple-page select {
        min-height: 43px;
        padding-left: 11px;
        padding-right: 11px;
    }

    .lead-simple-page textarea {
        padding: 10px 11px;
        resize: vertical;
    }

    .lead-simple-page input:focus,
    .lead-simple-page select:focus,
    .lead-simple-page textarea:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.10);
    }

    .activity-item {
        position: relative;
        display: flex;
        gap: 12px;
    }

    .activity-item:not(:last-child)::before {
        content: "";
        position: absolute;
        left: 17px;
        top: 38px;
        bottom: -18px;
        width: 1px;
        background: #e2e8f0;
    }

    .activity-icon {
        position: relative;
        z-index: 1;
        display: flex;
        width: 35px;
        height: 35px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .activity-box {
        flex: 1;
        min-width: 0;
        border: 1px solid #edf0f4;
        border-radius: 11px;
        background: #fafcff;
        padding: 12px 14px;
    }

    .modal-card {
        width: 100%;
        max-width: 430px;
        overflow: hidden;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 25px 70px rgba(15, 23, 42, 0.30);
    }

    @media (max-width: 900px) {
        .lead-header-main {
            align-items: flex-start !important;
        }

        .lead-header-info {
            width: 100%;
        }
    }

    @media (max-width: 640px) {
        .simple-btn {
            flex: 1;
        }

        .header-call-btn {
            width: 100%;
        }
    }
</style>
@endonce


<div class="lead-simple-page mx-auto max-w-7xl space-y-4">

    {{-- =====================================================
        HEADER
        NAME + MOBILE + CALL NOW + ACTION BUTTONS
    ====================================================== --}}
    <div class="top-bar">

        <div class="lead-header-main flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            {{-- LEFT SIDE --}}
            <div class="lead-header-info flex min-w-0 items-center gap-4">

                {{-- Avatar --}}
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg font-extrabold text-white">
                    {{ mb_strtoupper(
                        mb_substr($lead->name ?: 'L', 0, 1)
                    ) }}
                </div>


                {{-- Name + Mobile --}}
                <div class="min-w-0">

                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                        Lead
                    </div>

                    <div class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-1">

                        <h1 class="truncate text-lg font-extrabold text-slate-900">
                            {{ $lead->name ?: 'No Name' }}
                        </h1>

                        @if ($lead->mobile)

                            <div class="flex items-center gap-1.5 text-base font-extrabold text-slate-700">

                                <svg
                                    width="15"
                                    height="15"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="text-slate-400"
                                >
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                                </svg>

                                {{ $lead->mobile }}

                            </div>

                        @else

                            <div class="text-sm font-semibold text-slate-400">
                                No Mobile Number
                            </div>

                        @endif

                    </div>

                </div>

            </div>



            {{-- RIGHT SIDE --}}
            <div class="flex flex-wrap items-center gap-2">


                {{-- CALL --}}
                @if ($lead->mobile)

                    <a
                        href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->mobile) }}"
                        class="header-call-btn"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                        </svg>

                        Call Now
                    </a>

                @endif


                {{-- CALL ON MOBILE --}}
                @if ($lead->mobile)

                    <button
                        type="button"
                        id="callOnMobileButton"
                        onclick="callLeadOnMobile()"
                        class="header-call-btn"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                        </svg>

                        <span id="callOnMobileText">
                            Call on Mobile
                        </span>
                    </button>

                @endif



                {{-- PREVIOUS --}}
                @if ($previousLead)

                    <a
                        href="{{ route('leads.show', array_merge(
                            ['lead' => $previousLead->id],
                            $navigationParams
                        )) }}"
                        class="simple-btn"
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m15 18-6-6 6-6"/>
                        </svg>

                        Previous
                    </a>

                @else

                    <button
                        type="button"
                        disabled
                        class="simple-btn"
                    >
                        Previous
                    </button>

                @endif



                {{-- NEXT --}}
                @if ($nextLead)

                    <a
                        href="{{ route('leads.show', array_merge(
                            ['lead' => $nextLead->id],
                            $navigationParams
                        )) }}"
                        class="simple-btn"
                    >
                        Next

                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>

                @else

                    <button
                        type="button"
                        disabled
                        class="simple-btn"
                    >
                        Next
                    </button>

                @endif



                {{-- BACK --}}
                <a
                    href="{{ route('leads.index', $navigationParams) }}"
                    class="simple-btn"
                >
                    Back
                </a>



                {{-- EDIT --}}
                @can('leads.update')

                    <a
                        href="{{ route('leads.edit', array_merge(
                            ['lead' => $lead->id],
                            $navigationParams
                        )) }}"
                        class="simple-btn simple-btn-primary"
                    >
                        Edit
                    </a>

                @endcan

            </div>

        </div>

    </div>



    {{-- SUCCESS --}}
    @if (session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>

    @endif



    {{-- ERRORS --}}
    @if ($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <div class="font-bold text-red-800">
                Please check:
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">

                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif



    {{-- =====================================================
        MAIN GRID
    ====================================================== --}}
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">


        {{-- =====================================================
            LEFT SIDE
        ====================================================== --}}
        <div>


            {{-- =================================================
                PREVIOUS CONVERSATION
            ================================================== --}}
            <section class="lead-card overflow-hidden">

                <div class="border-b border-slate-200 px-5 py-4">

                    <h2 class="font-bold text-slate-900">
                        Previous Conversation
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Is customer se pehle kya baat hui thi.
                    </p>

                </div>


                <div class="p-5">

                    @php

                        $activities = collect();

                        foreach ($lead->calls as $call) {

                            $activities->push([
                                'type' => 'call',
                                'created_at' => $call->created_at,
                                'data' => $call,
                            ]);

                        }


                        foreach ($lead->notes as $note) {

                            $activities->push([
                                'type' => 'note',
                                'created_at' => $note->created_at,
                                'data' => $note,
                            ]);

                        }


                        $activities = $activities
                            ->sortByDesc('created_at')
                            ->values();

                    @endphp



                    @if ($activities->isNotEmpty())

                        <div class="space-y-5">


                            @foreach ($activities as $activity)


                                {{-- =============================
                                    CALL
                                ============================== --}}
                                @if ($activity['type'] === 'call')

                                    @php
                                        $call = $activity['data'];
                                    @endphp


                                    <div class="activity-item">


                                        {{-- Icon --}}
                                        <div class="activity-icon bg-emerald-100 text-emerald-700">

                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                                            </svg>

                                        </div>



                                        {{-- Content --}}
                                        <div class="activity-box">


                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">


                                                <div class="font-bold text-slate-900">

                                                    {{ $call->disposition?->name ?? 'Call' }}

                                                </div>


                                                <div class="text-xs text-slate-400">

                                                    {{ $call->created_at?->format('d M Y, h:i A') }}

                                                </div>

                                            </div>



                                            @if ($call->remarks)

                                                <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                                    {{ $call->remarks }}
                                                </div>

                                            @endif



                                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-400">


                                                @if (!is_null($call->duration_seconds))

                                                    <span>
                                                        Duration:
                                                        {{ $call->duration_seconds }}
                                                        sec
                                                    </span>

                                                @endif



                                                @if ($call->user)

                                                    <span>
                                                        By:
                                                        {{ $call->user->name }}
                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </div>



                                {{-- =============================
                                    NOTE
                                ============================== --}}
                                @else

                                    @php
                                        $note = $activity['data'];
                                    @endphp


                                    <div class="activity-item">


                                        {{-- Icon --}}
                                        <div class="activity-icon bg-slate-100 text-slate-600">

                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6"/>
                                            </svg>

                                        </div>



                                        {{-- Content --}}
                                        <div class="activity-box">


                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">


                                                <div class="font-bold text-slate-900">
                                                    Note
                                                </div>


                                                <div class="text-xs text-slate-400">
                                                    {{ $note->created_at?->format('d M Y, h:i A') }}
                                                </div>

                                            </div>



                                            <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                                {{ $note->body }}
                                            </div>



                                            @if ($note->user)

                                                <div class="mt-2 text-xs text-slate-400">
                                                    By: {{ $note->user->name }}
                                                </div>

                                            @endif

                                        </div>

                                    </div>

                                @endif

                            @endforeach

                        </div>


                    @else


                        <div class="py-10 text-center">

                            <div class="text-sm font-bold text-slate-600">
                                No previous conversation
                            </div>

                            <div class="mt-1 text-xs text-slate-400">
                                Call feedback save karne ke baad history yahan dikhegi.
                            </div>

                        </div>


                    @endif

                </div>

            </section>

        </div>



        {{-- =====================================================
            RIGHT SIDE
        ====================================================== --}}
        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">


            {{-- =================================================
                SAVE CALL
            ================================================== --}}
            @can('calls.create')

                <form
                    id="saveCallForm"
                    method="POST"
                    action="{{ route('calls.store', $lead) }}"
                    class="lead-card overflow-hidden"
                >

                    @csrf


                    <div class="border-b border-slate-200 px-5 py-4">

                        <h3 class="font-bold text-slate-900">
                            Save Call Feedback
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Customer se baat hone ke baad result save karein.
                        </p>

                    </div>


                    <div class="space-y-4 p-5">


                        {{-- CALL RESULT --}}
                        <div>

                            <label
                                for="callDispositionSelect"
                                class="field-label"
                            >
                                Call Result

                                <span class="text-red-500">
                                    *
                                </span>
                            </label>


                            <select
                                id="callDispositionSelect"
                                name="call_disposition_id"
                                required
                                onchange="updateDispositionFields()"
                            >

                                <option
                                    value=""
                                    data-requires-remarks="0"
                                    data-requires-follow-up="0"
                                    data-auto-remarks=""
                                    data-next-followup=""
                                >
                                    Select call result
                                </option>


                                @foreach ($dispositions as $disposition)

                                    <option
                                        value="{{ $disposition->id }}"
                                        data-requires-remarks="{{ $disposition->requires_remarks ? '1' : '0' }}"
                                        data-requires-follow-up="{{ $disposition->requires_follow_up ? '1' : '0' }}"
                                        data-auto-remarks="{{ e($disposition->auto_remarks ?? '') }}"
                                        data-next-followup="{{ $disposition->next_followup ?? '' }}"
                                        @selected(
                                            (string) old('call_disposition_id')
                                            ===
                                            (string) $disposition->id
                                        )
                                    >
                                        {{ $disposition->name }}
                                    </option>

                                @endforeach

                            </select>


                            @error('call_disposition_id')

                                <div class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>



                        {{-- DURATION --}}
                        <div>

                            <label
                                for="duration_seconds"
                                class="field-label"
                            >
                                Call Duration
                            </label>


                            <div class="relative">

                                <input
                                    id="duration_seconds"
                                    name="duration_seconds"
                                    type="number"
                                    min="0"
                                    value="{{ old('duration_seconds') }}"
                                    placeholder="0"
                                    class="pr-16"
                                >

                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                    sec
                                </span>

                            </div>


                            @error('duration_seconds')

                                <div class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>



                        {{-- REMARKS --}}
                        <div
                            id="remarksFieldWrapper"
                            class="hidden"
                        >

                            <label
                                for="callRemarks"
                                class="field-label"
                            >
                                Remarks

                                <span
                                    id="remarksRequiredMark"
                                    class="hidden text-red-500"
                                >
                                    *
                                </span>
                            </label>


                            <textarea
                                id="callRemarks"
                                name="remarks"
                                rows="4"
                                placeholder="Customer ne kya kaha..."
                            >{{ old('remarks') }}</textarea>


                            @error('remarks')

                                <div class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>



                        {{-- FOLLOW-UP --}}
                        <div
                            id="followUpFieldWrapper"
                            class="hidden"
                        >

                            <label
                                for="followUpAt"
                                class="field-label"
                            >
                                Next Follow-up

                                <span
                                    id="followUpRequiredMark"
                                    class="hidden text-red-500"
                                >
                                    *
                                </span>
                            </label>


                            <input
                                id="followUpAt"
                                name="follow_up_at"
                                type="datetime-local"
                                value="{{ old('follow_up_at') }}"
                                min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                            >


                            @error('follow_up_at')

                                <div class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>



                        {{-- SAVE BUTTON --}}
                        <button
                            type="button"
                            onclick="openSaveCallConfirmation()"
                            class="form-btn form-btn-green"
                        >
                            Save Feedback
                        </button>

                    </div>

                </form>

            @endcan



            {{-- =================================================
                DEMO SEND
            ================================================== --}}
            @can('leads.update')

                <form
                    method="POST"
                    action="{{ route('leads.update', $lead) }}"
                    class="lead-card overflow-hidden"
                >

                    @csrf
                    @method('PATCH')


                    <input
                        type="hidden"
                        name="demo_send_only"
                        value="1"
                    >

                    <input
                        type="hidden"
                        name="demo_send"
                        value="{{ $lead->demo_send ? 0 : 1 }}"
                    >


                    <div class="p-4">

                        <button
                            type="submit"
                            class="form-btn {{ $lead->demo_send ? 'form-btn-dark' : '' }}"
                        >

                            @if ($lead->demo_send)

                                ✓ Demo Sent

                            @else

                                Send Demo

                            @endif

                        </button>

                    </div>

                </form>

            @endcan



            {{-- =================================================
                ADD NOTE
            ================================================== --}}
            @can('leads.notes.create')

                <form
                    method="POST"
                    action="{{ route('leads.notes', $lead) }}"
                    class="lead-card overflow-hidden"
                >

                    @csrf


                    <div class="border-b border-slate-200 px-5 py-4">

                        <h3 class="font-bold text-slate-900">
                            Add Note
                        </h3>

                    </div>


                    <div class="space-y-3 p-5">

                        <textarea
                            name="body"
                            rows="3"
                            required
                            placeholder="Internal note..."
                        >{{ old('body') }}</textarea>


                        @error('body')

                            <div class="text-xs text-red-600">
                                {{ $message }}
                            </div>

                        @enderror


                        <button
                            type="submit"
                            class="form-btn form-btn-dark"
                        >
                            Add Note
                        </button>

                    </div>

                </form>

            @endcan

        </aside>

    </div>

</div>



{{-- =========================================================
    SAVE CALL CONFIRMATION MODAL
========================================================= --}}
<div
    id="saveCallConfirmationModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4"
    role="dialog"
    aria-modal="true"
>

    <div class="modal-card">


        <div class="border-b border-slate-200 px-5 py-4">

            <h3 class="text-lg font-bold text-slate-900">
                Save Feedback
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Feedback save karne ke baad kya karna hai?
            </p>

        </div>



        <div class="space-y-3 p-5">


            {{-- SAVE --}}
            <button
                type="button"
                onclick="confirmSaveCall(false)"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50"
            >

                <div class="font-bold text-slate-900">
                    Save
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Feedback save hoga aur isi lead par rahenge.
                </div>

            </button>



            {{-- SAVE & NEXT --}}
            @if ($nextLead)

                <button
                    type="button"
                    onclick="confirmSaveCall(true)"
                    class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left hover:bg-emerald-100"
                >

                    <div class="font-bold text-emerald-900">
                        Save & Next
                    </div>

                    <div class="mt-1 text-xs text-emerald-700">
                        Feedback save karke next lead open hogi.
                    </div>

                </button>


            @else


                <button
                    type="button"
                    disabled
                    class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left opacity-50"
                >

                    <div class="font-bold text-slate-500">
                        Save & Next
                    </div>

                    <div class="mt-1 text-xs text-slate-400">
                        Koi next lead nahi hai.
                    </div>

                </button>


            @endif



            {{-- CANCEL --}}
            <button
                type="button"
                onclick="closeSaveCallConfirmation()"
                class="w-full rounded-lg px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100"
            >
                Cancel
            </button>

        </div>

    </div>

</div>



<script>

    /*
    |--------------------------------------------------------------------------
    | SAVE & NEXT
    |--------------------------------------------------------------------------
    */

    (() => {

        const storageKey =
            'crm_save_call_next_lead_{{ $lead->id }}';


        @if ($errors->any())

            sessionStorage.removeItem(storageKey);

        @elseif (session('success'))

            const pendingNextUrl =
                sessionStorage.getItem(storageKey);


            if (pendingNextUrl) {

                sessionStorage.removeItem(storageKey);

                window.location.replace(
                    pendingNextUrl
                );

                return;
            }

        @endif

    })();



    /*
    |--------------------------------------------------------------------------
    | DISPOSITION FIELDS
    |--------------------------------------------------------------------------
    */

    function datetimeLocalAfterMinutes(minutes)
    {
        const value = Number(minutes);

        if (!Number.isFinite(value) || value <= 0) {
            return '';
        }

        const date = new Date(
            Date.now() + (value * 60 * 1000)
        );

        const pad = number =>
            String(number).padStart(2, '0');

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


    function updateDispositionFields()
    {
        const select =
            document.getElementById(
                'callDispositionSelect'
            );

        const remarksWrapper =
            document.getElementById(
                'remarksFieldWrapper'
            );

        const remarksInput =
            document.getElementById(
                'callRemarks'
            );

        const remarksRequiredMark =
            document.getElementById(
                'remarksRequiredMark'
            );

        const followUpWrapper =
            document.getElementById(
                'followUpFieldWrapper'
            );

        const followUpInput =
            document.getElementById(
                'followUpAt'
            );

        const followUpRequiredMark =
            document.getElementById(
                'followUpRequiredMark'
            );

        if (
            !select
            || !remarksWrapper
            || !remarksInput
            || !followUpWrapper
            || !followUpInput
        ) {
            return;
        }

        const option =
            select.options[
                select.selectedIndex
            ];

        const hasDisposition =
            !!option
            && !!option.value;

        const requiresRemarks =
            hasDisposition
            && option.dataset.requiresRemarks === '1';

        const requiresFollowUp =
            hasDisposition
            && option.dataset.requiresFollowUp === '1';

        const autoRemarks =
            hasDisposition
                ? String(
                    option.dataset.autoRemarks || ''
                ).trim()
                : '';

        const nextFollowupMinutes =
            hasDisposition
                ? Number(
                    option.dataset.nextFollowup || 0
                )
                : 0;

        const hasAutoNextFollowup =
            Number.isFinite(nextFollowupMinutes)
            && nextFollowupMinutes > 0;


        /*
        |--------------------------------------------------------------------------
        | AUTO REMARKS
        |--------------------------------------------------------------------------
        */

        remarksInput.value = autoRemarks;

        remarksInput.required =
            requiresRemarks;

        if (
            hasDisposition
            && (
                requiresRemarks
                || autoRemarks !== ''
            )
        ) {

            remarksWrapper.classList.remove(
                'hidden'
            );

        } else {

            remarksWrapper.classList.add(
                'hidden'
            );
        }

        if (requiresRemarks) {

            remarksRequiredMark?.classList.remove(
                'hidden'
            );

        } else {

            remarksRequiredMark?.classList.add(
                'hidden'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | AUTO NEXT FOLLOW-UP
        |--------------------------------------------------------------------------
        | next_followup = minutes
        | Example: 30 => current time + 30 minutes
        */

        if (hasAutoNextFollowup) {

            followUpInput.value =
                datetimeLocalAfterMinutes(
                    nextFollowupMinutes
                );

        } else {

            followUpInput.value = '';
        }

        followUpInput.required =
            requiresFollowUp
            || hasAutoNextFollowup;

        if (
            hasDisposition
            && (
                requiresFollowUp
                || hasAutoNextFollowup
            )
        ) {

            followUpWrapper.classList.remove(
                'hidden'
            );

        } else {

            followUpWrapper.classList.add(
                'hidden'
            );
        }

        if (
            requiresFollowUp
            || hasAutoNextFollowup
        ) {

            followUpRequiredMark?.classList.remove(
                'hidden'
            );

        } else {

            followUpRequiredMark?.classList.add(
                'hidden'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PAGE LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            updateDispositionFields();

        }
    );



    /*
    |--------------------------------------------------------------------------
    | OPEN MODAL
    |--------------------------------------------------------------------------
    */

    function openSaveCallConfirmation()
    {
        const form =
            document.getElementById(
                'saveCallForm'
            );


        if (!form) {
            return;
        }


        if (!form.reportValidity()) {
            return;
        }


        const modal =
            document.getElementById(
                'saveCallConfirmationModal'
            );


        if (!modal) {
            return;
        }


        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }



    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    function closeSaveCallConfirmation()
    {
        const modal =
            document.getElementById(
                'saveCallConfirmationModal'
            );


        if (!modal) {
            return;
        }


        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }



    /*
    |--------------------------------------------------------------------------
    | CONFIRM SAVE
    |--------------------------------------------------------------------------
    */

    function confirmSaveCall(saveAndNext)
    {
        const form =
            document.getElementById(
                'saveCallForm'
            );


        if (!form) {
            return;
        }


        if (!form.reportValidity()) {
            return;
        }


        const storageKey =
            'crm_save_call_next_lead_{{ $lead->id }}';


        if (saveAndNext) {

            @if ($nextLead)

                sessionStorage.setItem(
                    storageKey,
                    @json(
                        route(
                            'leads.show',
                            array_merge(
                                ['lead' => $nextLead->id],
                                $navigationParams
                            )
                        )
                    )
                );

            @else

                sessionStorage.removeItem(
                    storageKey
                );

            @endif

        } else {

            sessionStorage.removeItem(
                storageKey
            );

        }


        closeSaveCallConfirmation();


        document
            .querySelectorAll(
                '#saveCallConfirmationModal button'
            )
            .forEach(function (button) {

                button.disabled = true;

            });


        form.submit();
    }



    /*
    |--------------------------------------------------------------------------
    | ESC CLOSE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeSaveCallConfirmation();
            }

        }
    );



    /*
    |--------------------------------------------------------------------------
    | OUTSIDE CLICK CLOSE
    |--------------------------------------------------------------------------
    */

    document
        .getElementById(
            'saveCallConfirmationModal'
        )
        ?.addEventListener(
            'click',
            function (event) {

                if (event.target === this) {
                    closeSaveCallConfirmation();
                }

            }
        );

</script>

<script>
async function callLeadOnMobile() {

    const button = document.getElementById(
        'callOnMobileButton'
    );

    const text = document.getElementById(
        'callOnMobileText'
    );

    if (!button) {
        return;
    }

    button.disabled = true;

    if (text) {
        text.innerText = 'Sending...';
    }

    try {

        const response = await fetch(
            @json(route('leads.call-on-mobile', $lead)),
            {
                method: 'POST',

                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',

                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.getAttribute('content') ?? ''
                },

                credentials: 'same-origin',

                body: JSON.stringify({})
            }
        );

        const data = await response.json();

        if (!response.ok || data.status !== true) {

            throw new Error(
                data.message ??
                'Unable to send call to mobile.'
            );
        }

        if (text) {
            text.innerText = 'Sent ✓';
        }

        showCallToast(
            data.message ||
            'Call sent to mobile.'
        );

        setTimeout(() => {

            if (text) {
                text.innerText = 'Call on Mobile';
            }

        }, 2000);

    } catch (error) {

        console.error(error);

        showCallToast(
            error.message ||
            'Something went wrong.',
            true
        );

    } finally {

        button.disabled = false;
    }
}


function showCallToast(message, isError = false) {

    const oldToast =
        document.getElementById('mobile-call-toast');

    if (oldToast) {
        oldToast.remove();
    }

    const toast = document.createElement('div');

    toast.id = 'mobile-call-toast';

    toast.style.position = 'fixed';
    toast.style.right = '20px';
    toast.style.bottom = '20px';
    toast.style.zIndex = '99999';

    toast.style.padding = '12px 16px';
    toast.style.borderRadius = '10px';

    toast.style.fontSize = '13px';
    toast.style.fontWeight = '700';

    toast.style.color = '#fff';

    toast.style.background =
        isError
            ? '#dc2626'
            : '#059669';

    toast.style.boxShadow =
        '0 10px 30px rgba(0,0,0,.18)';

    toast.innerText = message;

    document.body.appendChild(toast);

    setTimeout(() => {

        toast.remove();

    }, 3500);
}
</script>

@endsection
