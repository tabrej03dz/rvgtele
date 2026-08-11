@extends('layouts.crm', [
    'title' => $lead->name,
])

@section('content')
@php
    $priorityClass = match ($lead->priority) {
        'hot', 'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
        'low' => 'bg-slate-50 text-slate-600 border-slate-200',
        default => 'bg-blue-50 text-blue-700 border-blue-200',
    };

    $statusName = $lead->status?->name ?? 'New';
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('leads.index') }}" class="hover:text-blue-600">
                    Leads
                </a>
                <span>/</span>
                <span>{{ $lead->name }}</span>
            </div>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                Lead Details
            </h1>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($previousLead)
                <a
                    href="{{ route('leads.show', $previousLead) }}"
                    title="{{ $previousLead->name }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
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
                    class="inline-flex cursor-not-allowed items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-400"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Previous
                </button>
            @endif

            @if ($nextLead)
                <a
                    href="{{ route('leads.show', $nextLead) }}"
                    title="{{ $nextLead->name }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
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
                    class="inline-flex cursor-not-allowed items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-400"
                >
                    Next
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>
            @endif

            <a
                href="{{ route('leads.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back
            </a>

            <a
                href="{{ route('leads.edit', $lead) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                </svg>
                Edit Lead
            </a>
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
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700">
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
                    </div>
                </div>

                <div class="grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Mobile
                        </div>
                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $lead->mobile }}
                        </div>
                    </div>

                    <div class="bg-white px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Owner
                        </div>
                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $lead->assignedUser?->name ?? 'Unassigned' }}
                        </div>
                    </div>

                    <div class="bg-white px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Source
                        </div>
                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $lead->source?->name ?? '—' }}
                        </div>
                    </div>

                    <div class="bg-white px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Team
                        </div>
                        <div class="mt-1 font-semibold text-slate-900">
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
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-900">
                        Activity Timeline
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Calls aur notes ka complete history.
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
                                @if ($activity['type'] === 'call')
                                    @php $call = $activity['data']; @endphp

                                    <div class="flex gap-4">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1 border-b border-slate-100 pb-5">
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

                                    <div class="flex gap-4">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                                                <path d="M14 2v6h6M8 13h8M8 17h6"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1 border-b border-slate-100 pb-5">
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
        <aside class="space-y-5 xl:sticky xl:top-5 xl:self-start">

            {{-- Save Call --}}
            <form
                id="saveCallForm"
                method="POST"
                action="{{ route('calls.store', $lead) }}"
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf

                <div class="border-b border-slate-200 px-5 py-4">
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
                            name="call_disposition_id"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Select disposition</option>
                            @foreach ($dispositions as $disposition)
                                <option
                                    value="{{ $disposition->id }}"
                                    @selected((string) old('call_disposition_id') === (string) $disposition->id)
                                >
                                    {{ $disposition->name }}
                                </option>
                            @endforeach
                        </select>

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

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Call Remarks <span class="text-rose-500">*</span>
                        </span>

                        <textarea
                            name="remarks"
                            rows="4"
                            required
                            placeholder="Call discussion aur customer response..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >{{ old('remarks') }}</textarea>

                        @error('remarks')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Follow-up Date
                        </span>

                        <input
                            name="follow_up_at"
                            type="datetime-local"
                            value="{{ old('follow_up_at') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >

                        @error('follow_up_at')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button
                        type="button"
                        onclick="openSaveCallConfirmation()"
                        class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Save Call
                    </button>
                </div>
            </form>

            {{-- Assign --}}
            @if ($hasFullAccess)
                <form
                    method="POST"
                    action="{{ route('leads.assign', $lead) }}"
                    class="rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    @csrf

                    <div class="border-b border-slate-200 px-5 py-4">
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
                            class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Assign Lead
                        </button>
                    </div>
                </form>
            @endif

            {{-- Add Note --}}
            <form
                method="POST"
                action="{{ route('leads.notes', $lead) }}"
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                @csrf

                <div class="border-b border-slate-200 px-5 py-4">
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
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Add Note
                    </button>
                </div>
            </form>
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
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
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
