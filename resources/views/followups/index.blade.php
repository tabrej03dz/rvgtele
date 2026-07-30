@extends('layouts.crm', [
    'title' => 'Follow-ups',
])

@section('content')
@php
    $currentStatus = request('status');

    $tabClass = function (?string $status) use ($currentStatus) {
        $isActive = $status === $currentStatus
            || ($status === null && blank($currentStatus));

        return $isActive
            ? 'bg-slate-900 text-white border-slate-900'
            : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50';
    };
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Follow-ups
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Pending, overdue aur completed follow-ups manage karein.
            </p>
        </div>
    </div>

    {{-- Status Tabs --}}
    <div class="flex flex-wrap gap-2">
        <a
            href="{{ route('followups.index') }}"
            class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $tabClass(null) }}"
        >
            All
        </a>

        <a
            href="{{ route('followups.index', ['status' => 'pending']) }}"
            class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $tabClass('pending') }}"
        >
            Pending
        </a>

        <a
            href="{{ route('followups.index', ['status' => 'overdue']) }}"
            class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition
                {{ $currentStatus === 'overdue'
                    ? 'border-rose-600 bg-rose-600 text-white'
                    : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}"
        >
            Overdue
        </a>

        <a
            href="{{ route('followups.index', ['status' => 'completed']) }}"
            class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $tabClass('completed') }}"
        >
            Completed
        </a>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
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

    {{-- Follow-up Table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">
                    Follow-up List
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $followups->firstItem() ?? 0 }}–{{ $followups->lastItem() ?? 0 }}
                    of {{ $followups->total() }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">Assigned To</th>
                        <th class="px-4 py-3">Scheduled</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($followups as $followup)
                        @php
                            $isOverdue = $followup->status === 'pending'
                                && $followup->scheduled_at
                                && $followup->scheduled_at->isPast();

                            $statusClass = match ($followup->status) {
                                'completed' => 'bg-emerald-50 text-emerald-700',
                                'cancelled' => 'bg-slate-100 text-slate-600',
                                default => $isOverdue
                                    ? 'bg-rose-50 text-rose-700'
                                    : 'bg-amber-50 text-amber-700',
                            };

                            $typeClass = match ($followup->type) {
                                'call' => 'bg-blue-50 text-blue-700',
                                'meeting' => 'bg-violet-50 text-violet-700',
                                'whatsapp' => 'bg-emerald-50 text-emerald-700',
                                'email' => 'bg-sky-50 text-sky-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp

                        <tr class="{{ $isOverdue ? 'bg-rose-50/40' : 'hover:bg-slate-50' }}">
                            <td class="px-4 py-3">
                                @if ($followup->lead)
                                    <a
                                        href="{{ route('leads.show', $followup->lead) }}"
                                        class="font-semibold text-blue-600 hover:text-blue-700"
                                    >
                                        {{ $followup->lead->name }}
                                    </a>
                                @else
                                    <span class="font-semibold text-slate-700">
                                        Lead unavailable
                                    </span>
                                @endif

                                @if ($followup->notes)
                                    <div class="mt-1 max-w-md text-xs leading-5 text-slate-500">
                                        {{ \Illuminate\Support\Str::limit($followup->notes, 90) }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">
                                    {{ $followup->assignedUser?->name ?? 'Unassigned' }}
                                </div>

                                @if ($followup->assignedUser?->employee_code)
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $followup->assignedUser->employee_code }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium {{ $isOverdue ? 'text-rose-700' : 'text-slate-800' }}">
                                    {{ $followup->scheduled_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>

                                @if ($isOverdue)
                                    <div class="mt-0.5 text-xs font-medium text-rose-600">
                                        Overdue
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $typeClass }}">
                                    {{ $followup->type }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusClass }}">
                                    {{ $isOverdue ? 'overdue' : $followup->status }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                @if ($followup->status === 'pending')
                                    <form
                                        method="POST"
                                        action="{{ route('followups.complete', $followup) }}"
                                        class="inline"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                        >
                                            Complete
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">
                                        —
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="font-semibold text-slate-700">
                                    No follow-ups found
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Selected filter ke liye koi follow-up available nahi hai.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($followups->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $followups->links() }}
            </div>
        @endif
    </section>
</div>
@endsection