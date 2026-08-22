@extends('layouts.crm', [
    'title' => $lead->name,
])

@section('content')
@once
<style>
    [x-cloak] { display: none !important; }

    .lead-show-ui {
        --crm-primary: #5b3df0;
        --crm-blue: #2864f7;
        --crm-violet: #8b2de7;
        --crm-ink: #17203a;
        --crm-muted: #68738a;
        --crm-border: #e5e9f2;
        color: var(--crm-ink);
    }

    .lead-show-ui::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(circle at 88% 4%, rgba(116, 82, 244, .07), transparent 25%),
            #f8faff;
    }

    .lead-page-header,
    .lead-card,
    .lead-side-card {
        border: 1px solid var(--crm-border) !important;
        background: #fff;
        border-radius: 14px !important;
        box-shadow: 0 5px 18px rgba(31, 42, 80, .055) !important;
    }

    .lead-page-header { padding: 16px 18px; }

    .lead-page-icon,
    .lead-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, var(--crm-blue), var(--crm-violet));
        color: #fff;
        box-shadow: 0 8px 18px rgba(76, 61, 232, .23);
    }

    .lead-page-icon { width: 42px; height: 42px; border-radius: 11px; }
    .lead-page-icon svg { width: 20px; height: 20px; }
    .lead-avatar { width: 54px; height: 54px; border-radius: 16px; font-size: 18px; font-weight: 800; }

    .lead-btn {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid #e1e5ed;
        border-radius: 9px;
        background: #fff;
        padding: 0 13px;
        color: #33405b;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .035);
        transition: .18s ease;
    }
    .lead-btn:hover { border-color: #c9c0ff; background: #faf9ff; color: #6138e8; transform: translateY(-1px); }
    .lead-btn svg { width: 15px; height: 15px; }
    .lead-btn:disabled { cursor: not-allowed; opacity: .55; transform: none; }
    .lead-btn-primary {
        border-color: transparent;
        background: linear-gradient(100deg, var(--crm-blue), #6439ee 58%, var(--crm-violet));
        color: #fff;
        box-shadow: 0 7px 16px rgba(76, 61, 232, .22);
    }
    .lead-btn-primary:hover { border-color: transparent; color: #fff; filter: brightness(.96); }

    .lead-card-header,
    .lead-side-header {
        border-bottom: 1px solid var(--crm-border) !important;
        background: linear-gradient(180deg, #fff, #fdfdff);
    }

    .lead-section-heading { display: flex; align-items: center; gap: 10px; }
    .lead-section-icon {
        display: inline-flex;
        width: 31px;
        height: 31px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #f0efff;
        color: #5b48dc;
    }
    .lead-section-icon svg { width: 16px; height: 16px; }

    .lead-stat-grid { background: var(--crm-border) !important; }
    .lead-stat { position: relative; background: #fff; transition: background .18s ease; }
    .lead-stat:hover { background: #fbfaff; }
    .lead-stat-label { color: #7a8499; font-size: 10px; font-weight: 800; letter-spacing: .065em; text-transform: uppercase; }
    .lead-stat-value { margin-top: 5px; color: #1d2740; font-size: 13px; font-weight: 700; overflow-wrap: anywhere; }

    .lead-timeline-item { position: relative; }
    .lead-timeline-item:not(:last-child)::before {
        content: "";
        position: absolute;
        left: 17px;
        top: 38px;
        bottom: -20px;
        width: 1px;
        background: #e7eaf2;
    }
    .lead-activity-content {
        border: 1px solid #edf0f5 !important;
        border-radius: 11px;
        background: #fcfdff;
        padding: 13px 14px !important;
    }

    .lead-show-ui input,
    .lead-show-ui select,
    .lead-show-ui textarea {
        width: 100%;
        border: 1px solid #dbe0ea !important;
        border-radius: 9px !important;
        background: #fff;
        color: #1f2937;
        font-size: 13px !important;
        box-shadow: none !important;
        transition: .18s ease;
    }
    .lead-show-ui input,
    .lead-show-ui select { min-height: 41px; }
    .lead-show-ui textarea { padding: 10px 12px; }
    .lead-show-ui input:focus,
    .lead-show-ui select:focus,
    .lead-show-ui textarea:focus {
        border-color: #7660ef !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(102, 78, 238, .10) !important;
    }

    .lead-form-button {
        display: inline-flex;
        min-height: 41px;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(100deg, var(--crm-blue), #6539ee 58%, var(--crm-violet));
        padding: 0 16px;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 7px 16px rgba(76, 61, 232, .19);
        transition: .18s ease;
    }
    .lead-form-button:hover { filter: brightness(.96); transform: translateY(-1px); }
    .lead-form-button svg { width: 15px; height: 15px; }
    .lead-form-button.is-emerald { background: linear-gradient(100deg, #059669, #10b981); box-shadow: 0 7px 16px rgba(5, 150, 105, .18); }
    .lead-form-button.is-slate { background: linear-gradient(100deg, #334155, #0f172a); box-shadow: 0 7px 16px rgba(15, 23, 42, .16); }

    .lead-modal-card { border: 1px solid #e5e9f2; border-radius: 16px; box-shadow: 0 24px 70px rgba(15,23,42,.24); }

    @media (max-width: 640px) {
        .lead-page-header { padding: 14px; }
        .lead-btn { flex: 1 1 auto; }
        .lead-page-actions { width: 100%; }
        .lead-card-header { padding: 16px !important; }
    }
</style>
@endonce

@php
    $priorityClass = match ($lead->priority) {
        'hot', 'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
        'low' => 'bg-slate-50 text-slate-600 border-slate-200',
        default => 'bg-blue-50 text-blue-700 border-blue-200',
    };

    $statusName = $lead->status?->name ?? 'New';
@endphp

<div class="lead-show-ui mx-auto max-w-none space-y-4">

    {{-- Header --}}
    <div class="lead-page-header flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <span class="lead-page-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            </span>
            <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('leads.index', $navigationParams) }}" class="hover:text-blue-600">
                    Leads
                </a>
                <span>/</span>
                <span>{{ $lead->name }}</span>
            </div>

            <h1 class="mt-1 text-[21px] font-bold tracking-tight text-slate-900">
                Lead Details
            </h1>
            </div>
        </div>

        <div class="lead-page-actions flex flex-wrap gap-2">
            @if ($previousLead)
                <a
                    href="{{ route('leads.show', array_merge(['lead' => $previousLead->id], $navigationParams)) }}"
                    title="{{ $previousLead->name }}"
                    class="lead-btn"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Previous
                </a>
            @else
                <button
                    type="button"
                    disabled
                    class="lead-btn"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Previous
                </button>
            @endif

            @if ($nextLead)
                <a
                    href="{{ route('leads.show', array_merge(['lead' => $nextLead->id], $navigationParams)) }}"
                    title="{{ $nextLead->name }}"
                    class="lead-btn"
                >
                    Next
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            @else
                <button
                    type="button"
                    disabled
                    class="lead-btn"
                >
                    Next
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>
            @endif

            <a
                href="{{ route('leads.index', $navigationParams) }}"
                class="lead-btn"
            >
                Back
            </a>

            @can('leads.update')
            <a
                href="{{ route('leads.edit', array_merge(['lead' => $lead->id], $navigationParams)) }}"
                class="lead-btn lead-btn-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                </svg>
                Edit Lead
            </a>
            @endcan
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
            <div class="font-semibold text-rose-800">
                Please correct the following:
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">

        {{-- Left Content --}}
        <div class="space-y-5">

            {{-- Lead Summary --}}
            <section class="lead-card overflow-hidden">
                <div class="lead-card-header flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="lead-avatar shrink-0">
                            {{ mb_strtoupper(mb_substr($lead->name, 0, 1)) }}
                        </div>

                        <div>
                            <h2 class="text-xl font-bold text-slate-900">
                                {{ $lead->name }}
                            </h2>

                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $lead->company_name ?: 'Individual Lead' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                            {{ $statusName }}
                        </span>

                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold capitalize {{ $priorityClass }}">
                            {{ $lead->priority }}
                        </span>

                        @foreach ($lead->labels as $label)
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
                                  style="border-color: {{ $label->color }}55; background: {{ $label->color }}12; color: {{ $label->color }};">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $label->color }};"></span>
                                {{ $label->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="lead-stat-grid grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lead-stat px-5 py-4">
                        <div class="lead-stat-label">
                            Mobile
                        </div>
                        <div class="lead-stat-value">
                            {{ $lead->mobile }}
                        </div>
                    </div>

                    <div class="lead-stat px-5 py-4">
                        <div class="lead-stat-label">
                            Owner
                        </div>
                        <div class="lead-stat-value">
                            {{ $lead->assignedUser?->name ?? 'Unassigned' }}
                        </div>
                    </div>

                    <div class="lead-stat px-5 py-4">
                        <div class="lead-stat-label">
                            Source
                        </div>
                        <div class="lead-stat-value">
                            {{ $lead->source?->name ?? '—' }}
                        </div>
                    </div>

                    <div class="lead-stat px-5 py-4">
                        <div class="lead-stat-label">
                            Team
                        </div>
                        <div class="lead-stat-value">
                            {{ $lead->team?->name ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-slate-200 px-5 py-5 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Email
                        </div>
                        <div class="mt-1 text-sm text-slate-800">
                            {{ $lead->email ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            WhatsApp
                        </div>
                        <div class="mt-1 text-sm text-slate-800">
                            {{ $lead->whatsapp_number ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Required Product
                        </div>
                        <div class="mt-1 text-sm text-slate-800">
                            {{ $lead->required_product ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Next Follow-up
                        </div>
                        <div class="mt-1 text-sm text-slate-800">
                            {{ $lead->next_follow_up_at?->format('d M Y, h:i A') ?? 'Not scheduled' }}
                        </div>
                    </div>
                </div>
            </section>

            {{-- Activity Timeline --}}
            <section class="lead-card overflow-hidden">
                <div class="lead-card-header border-b border-slate-200 px-5 py-4">
                    <div class="lead-section-heading">
                        <span class="lead-section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5"/><path d="M3.05 13a9 9 0 1 0 .5-4"/><path d="M12 7v5l3 2"/></svg></span>
                        <div>
                            <h2 class="font-bold text-slate-900">Activity Timeline</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Calls aur notes ka complete history.</p>
                        </div>
                    </div>
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
                                @if ($activity['type'] === 'call')
                                    @php $call = $activity['data']; @endphp

                                    <div class="lead-timeline-item flex gap-4">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                                            </svg>
                                        </div>

                                        <div class="lead-activity-content min-w-0 flex-1 pb-5">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="font-semibold text-slate-900">
                                                    Call · {{ $call->disposition?->name ?? 'No disposition' }}
                                                </div>

                                                <div class="text-xs text-slate-500">
                                                    {{ $call->created_at->format('d M Y, h:i A') }}
                                                </div>
                                            </div>

                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                {{ $call->remarks }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-500">
                                                <span>
                                                    Duration: {{ $call->duration_seconds ?? 0 }} sec
                                                </span>

                                                @if ($call->user)
                                                    <span>
                                                        By: {{ $call->user->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php $note = $activity['data']; @endphp

                                    <div class="lead-timeline-item flex gap-4">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8M8 17h6"/>
                                            </svg>
                                        </div>

                                        <div class="lead-activity-content min-w-0 flex-1 pb-5">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="font-semibold text-slate-900">
                                                    Note by {{ $note->user?->name ?? 'User' }}
                                                </div>

                                                <div class="text-xs text-slate-500">
                                                    {{ $note->created_at->format('d M Y, h:i A') }}
                                                </div>
                                            </div>

                                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                                                {{ $note->body }}
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 8v4l3 2"/>
                                    <circle cx="12" cy="12" r="9"/>
                                </svg>
                            </div>

                            <div class="mt-3 font-semibold text-slate-700">
                                No activity yet
                            </div>

                            <div class="mt-1 text-sm text-slate-500">
                                Call result ya note add karne ke baad activity yahan dikhegi.
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Right Sidebar --}}
        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">

            {{-- Demo Send --}}
            @can('leads.update')
            <form
                method="POST"
                action="{{ route('leads.update', $lead) }}"
                class="lead-side-card overflow-hidden {{ $lead->demo_send ? '!border-emerald-200 bg-emerald-50' : '' }}"
            >
                @csrf
                @method('PATCH')

                <input type="hidden" name="demo_send_only" value="1">
                <input type="hidden" name="demo_send" value="{{ $lead->demo_send ? 0 : 1 }}">

                <div class="lead-side-header border-b {{ $lead->demo_send ? 'border-emerald-200' : 'border-slate-200' }} px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold {{ $lead->demo_send ? 'text-emerald-900' : 'text-slate-900' }}">
                                Demo Send
                            </h3>
                            <p class="mt-0.5 text-xs {{ $lead->demo_send ? 'text-emerald-700' : 'text-slate-500' }}">
                                Demo bhejne ke baad is lead ko mark karein.
                            </p>
                        </div>

                        @if ($lead->demo_send)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                MARKED
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                NOT MARKED
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-5">
                    <button
                        type="submit"
                        class="lead-form-button {{ $lead->demo_send ? 'is-slate' : 'is-emerald' }}"
                    >
                        {{ $lead->demo_send ? 'Remove Demo Send Mark' : 'Mark as Demo Send' }}
                    </button>
                </div>
            </form>
            @endcan

            {{-- Manage Labels --}}
            <section class="lead-side-card overflow-hidden">
                <div class="lead-side-header border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">Lead Labels</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Is lead ko custom groups me add/remove karein.</p>
                </div>
                <div class="space-y-4 p-5">
                    @if ($lead->labels->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($lead->labels as $label)
                                @can('leads.labels.manage')
                                <form method="POST" action="{{ route('leads.labels.remove', ['lead' => $lead->id, 'label' => $label->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="Remove {{ $label->name }}"
                                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold hover:opacity-80"
                                            style="border-color: {{ $label->color }}55; background: {{ $label->color }}12; color: {{ $label->color }};">
                                        <span class="h-2 w-2 rounded-full" style="background: {{ $label->color }};"></span>
                                        {{ $label->name }}
                                        <span class="ml-1">×</span>
                                    </button>
                                </form>
                                @endcan
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-center text-xs text-slate-500">No label assigned yet.</div>
                    @endif

                    @php $availableLabels = $labels->whereNotIn('id', $lead->labels->pluck('id')); @endphp
                    @if ($availableLabels->isNotEmpty())
                        @can('leads.labels.manage')
                        <form method="POST" action="{{ route('leads.labels.add', $lead) }}" class="space-y-3">
                            @csrf
                            <select name="label_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                                <option value="">Select label...</option>
                                @foreach ($availableLabels as $label)
                                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="lead-form-button">Add Label</button>
                        </form>
                        @endcan
                    @else
                        <div class="text-center text-xs text-slate-500">All available labels are already attached.</div>
                    @endif
                </div>
            </section>

            {{-- Save Call --}}
            @can('calls.create')
            <form
                id="saveCallForm"
                method="POST"
                action="{{ route('calls.store', $lead) }}"
                class="lead-side-card overflow-hidden"
            >
                @csrf

                <div class="lead-side-header border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">
                        Save Call Result
                    </h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Call outcome aur next follow-up save karein.
                    </p>
                </div>

                <div class="space-y-4 p-5">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Disposition <span class="text-rose-500">*</span>
                        </span>

                        <select
                            id="callDispositionSelect"
                            name="call_disposition_id"
                            required
                            onchange="updateDispositionFields()"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option
                                value=""
                                data-requires-remarks="0"
                                data-requires-follow-up="0"
                            >
                                Select disposition
                            </option>

                            @foreach ($dispositions as $disposition)
                                <option
                                    value="{{ $disposition->id }}"
                                    data-requires-remarks="{{ $disposition->requires_remarks ? '1' : '0' }}"
                                    data-requires-follow-up="{{ $disposition->requires_follow_up ? '1' : '0' }}"
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

                        <div
                            id="dispositionRuleHint"
                            class="mt-2 hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
                        ></div>

                        @error('call_disposition_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Duration
                        </span>

                        <div class="relative">
                            <input
                                name="duration_seconds"
                                type="number"
                                min="0"
                                value="{{ old('duration_seconds') }}"
                                placeholder="0"
                                class="w-full rounded-lg border-slate-300 pr-16 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >

                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                seconds
                            </span>
                        </div>

                        @error('duration_seconds')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div
                        id="remarksFieldWrapper"
                        class="hidden"
                    >
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Call Remarks
                                <span
                                    id="remarksRequiredMark"
                                    class="hidden text-rose-500"
                                >
                                    *
                                </span>
                            </span>

                            <textarea
                                id="callRemarks"
                                name="remarks"
                                rows="4"
                                placeholder="Call discussion aur customer response..."
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >{{ old('remarks') }}</textarea>

                            <p
                                id="remarksHelpText"
                                class="mt-1 text-xs text-slate-500"
                            >
                                Selected disposition ke liye remarks required hain.
                            </p>

                            @error('remarks')
                                <p class="mt-1 text-xs text-rose-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </label>
                    </div>

                    <div
                        id="followUpFieldWrapper"
                        class="hidden"
                    >
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Follow-up Date & Time
                                <span
                                    id="followUpRequiredMark"
                                    class="hidden text-rose-500"
                                >
                                    *
                                </span>
                            </span>

                            <input
                                id="followUpAt"
                                name="follow_up_at"
                                type="datetime-local"
                                value="{{ old('follow_up_at') }}"
                                min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >

                            <p
                                id="followUpHelpText"
                                class="mt-1 text-xs text-slate-500"
                            >
                                Is disposition ke liye next follow-up date/time mandatory hai.
                            </p>

                            @error('follow_up_at')
                                <p class="mt-1 text-xs text-rose-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </label>
                    </div>

                    <button
                        type="button"
                        onclick="openSaveCallConfirmation()"
                        class="lead-form-button is-emerald"
                    >
                        Save Call
                    </button>
                </div>
            </form>
            @endcan

            {{-- Assign --}}
            @if ($hasFullAccess)
                @can('leads.assign')
                <form
                    method="POST"
                    action="{{ route('leads.assign', $lead) }}"
                    class="lead-side-card overflow-hidden"
                >
                    @csrf

                    <div class="lead-side-header border-b border-slate-200 px-5 py-4">
                        <h3 class="font-bold text-slate-900">
                            Assign Lead
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Lead owner change karein.
                        </p>
                    </div>

                    <div class="space-y-4 p-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Employee <span class="text-rose-500">*</span>
                            </span>

                            <select
                                name="assigned_to"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select employee</option>
                                @foreach ($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) old('assigned_to', $lead->assigned_to) === (string) $user->id)
                                    >
                                        {{ $user->name }}
                                        @if ($user->employee_code)
                                            ({{ $user->employee_code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('assigned_to')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Reason <span class="text-rose-500">*</span>
                            </span>

                            <textarea
                                name="reason"
                                rows="3"
                                required
                                placeholder="Assignment ka reason..."
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('reason') }}</textarea>

                            @error('reason')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <button
                            type="submit"
                            class="lead-form-button"
                        >
                            Assign Lead
                        </button>
                    </div>
                </form>
                @endcan
            @endif

            {{-- Add Note --}}
            @can('leads.notes.create')
            <form
                method="POST"
                action="{{ route('leads.notes', $lead) }}"
                class="lead-side-card overflow-hidden"
            >
                @csrf

                <div class="lead-side-header border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">
                        Add Note
                    </h3>
                </div>

                <div class="space-y-4 p-5">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Note <span class="text-rose-500">*</span>
                        </span>

                        <textarea
                            name="body"
                            rows="4"
                            required
                            placeholder="Lead ke baare mein internal note..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                        >{{ old('body') }}</textarea>

                        @error('body')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button
                        type="submit"
                        class="lead-form-button is-slate"
                    >
                        Add Note
                    </button>
                </div>
            </form>
            @endcan
        </aside>
    </div>
</div>

{{-- Save Call Confirmation Modal --}}
<div
    id="saveCallConfirmationModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="saveCallConfirmationTitle"
>
    <div class="lead-modal-card w-full max-w-md overflow-hidden bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 id="saveCallConfirmationTitle" class="text-lg font-bold text-slate-900">
                Save Call Result?
            </h3>
            <p class="mt-1 text-sm leading-6 text-slate-500">
                Save karne ke baad current lead par reh sakte hain ya seedha next lead open kar sakte hain.
            </p>
        </div>

        <div class="space-y-3 p-5">
            <button
                type="button"
                onclick="confirmSaveCall(false)"
                class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50"
            >
                <span>
                    <span class="block text-sm font-bold text-slate-900">Save</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Call save hoga aur yahi lead open rahegi.</span>
                </span>

                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/>
                    <path d="m13 6 6 6-6 6"/>
                </svg>
            </button>

            @if ($nextLead)
                <button
                    type="button"
                    onclick="confirmSaveCall(true)"
                    class="flex w-full items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-left hover:bg-emerald-100"
                >
                    <span>
                        <span class="block text-sm font-bold text-emerald-900">Save & Next</span>
                        <span class="mt-0.5 block text-xs text-emerald-700">
                            Save ke baad {{ $nextLead->name }} open hogi.
                        </span>
                    </span>

                    <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>
                </button>
            @else
                <button
                    type="button"
                    disabled
                    class="flex w-full cursor-not-allowed items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left opacity-70"
                >
                    <span>
                        <span class="block text-sm font-bold text-slate-500">Save & Next</span>
                        <span class="mt-0.5 block text-xs text-slate-400">Ye last accessible lead hai.</span>
                    </span>
                </button>
            @endif

            <button
                type="button"
                onclick="closeSaveCallConfirmation()"
                class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-700"
            >
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        const storageKey = 'crm_save_call_next_lead_{{ $lead->id }}';

        @if ($errors->any())
            // Validation failed: stay on the current lead and cancel pending navigation.
            sessionStorage.removeItem(storageKey);
        @elseif (session('success'))
            // The normal POST has completed successfully and returned to this lead.
            const pendingNextUrl = sessionStorage.getItem(storageKey);

            if (pendingNextUrl) {
                sessionStorage.removeItem(storageKey);
                window.location.replace(pendingNextUrl);
                return;
            }
        @endif

        // Remove stale navigation when this page was opened normally.
        if (!sessionStorage.getItem(storageKey)) {
            sessionStorage.removeItem(storageKey);
        }
    })();

    function updateDispositionFields() {
        const select = document.getElementById('callDispositionSelect');
        const remarksWrapper = document.getElementById('remarksFieldWrapper');
        const remarksInput = document.getElementById('callRemarks');
        const remarksRequiredMark = document.getElementById('remarksRequiredMark');

        const followUpWrapper = document.getElementById('followUpFieldWrapper');
        const followUpInput = document.getElementById('followUpAt');
        const followUpRequiredMark = document.getElementById('followUpRequiredMark');

        const hint = document.getElementById('dispositionRuleHint');

        if (
            !select
            || !remarksWrapper
            || !remarksInput
            || !followUpWrapper
            || !followUpInput
        ) {
            return;
        }

        const option = select.options[select.selectedIndex];

        const hasDisposition =
            !!option
            &&
            !!option.value;

        const requiresRemarks =
            hasDisposition
            &&
            option.dataset.requiresRemarks === '1';

        const requiresFollowUp =
            hasDisposition
            &&
            option.dataset.requiresFollowUp === '1';

        /*
        |--------------------------------------------------------------------------
        | Remarks
        |--------------------------------------------------------------------------
        */

        remarksInput.required = requiresRemarks;

        if (requiresRemarks) {
            remarksWrapper.classList.remove('hidden');
            remarksRequiredMark?.classList.remove('hidden');
        } else {
            remarksWrapper.classList.add('hidden');
            remarksRequiredMark?.classList.add('hidden');

            /*
            | Validation error ke baad old value ho sakti hai.
            | User agar disposition change karke non-remarks disposition select
            | kare to stale remarks submit nahi hone denge.
            */
            remarksInput.value = '';
        }

        /*
        |--------------------------------------------------------------------------
        | Follow-up
        |--------------------------------------------------------------------------
        */

        followUpInput.required = requiresFollowUp;

        if (requiresFollowUp) {
            followUpWrapper.classList.remove('hidden');
            followUpRequiredMark?.classList.remove('hidden');
        } else {
            followUpWrapper.classList.add('hidden');
            followUpRequiredMark?.classList.add('hidden');

            /*
            | Stale follow-up value clear karo.
            */
            followUpInput.value = '';
        }

        /*
        |--------------------------------------------------------------------------
        | Rule Hint
        |--------------------------------------------------------------------------
        */

        if (!hint) {
            return;
        }

        if (!hasDisposition) {
            hint.classList.add('hidden');
            hint.textContent = '';

            return;
        }

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

        hint.textContent =
            `${option.text.trim()} — ${rules.join(' • ')}`;

        hint.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateDispositionFields();
    });

    function openSaveCallConfirmation() {
        const form = document.getElementById('saveCallForm');

        if (!form) {
            return;
        }

        // Browser's required/min validation should run before confirmation.
        if (!form.reportValidity()) {
            return;
        }

        const modal = document.getElementById('saveCallConfirmationModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSaveCallConfirmation() {
        const modal = document.getElementById('saveCallConfirmationModal');

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmSaveCall(saveAndNext) {
        const form = document.getElementById('saveCallForm');

        if (!form) {
            return;
        }

        const storageKey = 'crm_save_call_next_lead_{{ $lead->id }}';

        if (saveAndNext) {
            @if ($nextLead)
                sessionStorage.setItem(
                    storageKey,
                    @json(route('leads.show', $nextLead))
                );
            @else
                sessionStorage.removeItem(storageKey);
            @endif
        } else {
            sessionStorage.removeItem(storageKey);
        }

        closeSaveCallConfirmation();

        // Prevent double click while the request is being submitted.
        const buttons = document.querySelectorAll('#saveCallConfirmationModal button');
        buttons.forEach(button => button.disabled = true);

        form.submit();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSaveCallConfirmation();
        }
    });

    document.getElementById('saveCallConfirmationModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeSaveCallConfirmation();
        }
    });
</script>
@endsection
