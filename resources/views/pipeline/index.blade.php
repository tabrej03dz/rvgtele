@extends('layouts.crm', [
    'title' => 'Sales Pipeline',
])

@section('content')
<div class="mx-auto max-w-[1600px] space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Sales Pipeline
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Stage-wise leads aur expected deal value track karein.
            </p>
        </div>

        <a
            href="{{ route('leads.index') }}"
            class="inline-flex self-start items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto"
        >
            View All Leads
        </a>
    </div>

    @if (!$pipeline)
        {{-- No Pipeline --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-amber-900">
                        Pipeline not configured
                    </h2>

                    <p class="mt-1 text-sm text-amber-800">
                        Sales pipeline use karne ke liye database seeder run karein ya pipeline configure karein.
                    </p>
                </div>
            </div>
        </div>
    @else
        @php
            $totalLeads = collect($leads)->flatten(1)->count();

            $totalValue = collect($leads)
                ->flatten(1)
                ->sum(fn ($lead) => (float) ($lead->expected_deal_value ?? 0));
        @endphp

        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Pipeline
                </div>

                <div class="mt-1 text-lg font-bold text-slate-900">
                    {{ $pipeline->name }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Total Leads
                </div>

                <div class="mt-1 text-lg font-bold text-slate-900">
                    {{ number_format($totalLeads) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Expected Value
                </div>

                <div class="mt-1 text-lg font-bold text-slate-900">
                    ₹{{ number_format($totalValue, 2) }}
                </div>
            </div>
        </div>

        {{-- Pipeline Board --}}
        <div class="overflow-x-auto pb-4">
            <div class="flex min-w-max items-start gap-4">
                @foreach ($pipeline->stages as $stage)
                    @php
                        $stageLeads = collect($leads[$stage->id] ?? []);
                        $stageValue = $stageLeads->sum(
                            fn ($lead) => (float) ($lead->expected_deal_value ?? 0)
                        );
                    @endphp

                    <section class="w-80 shrink-0 rounded-xl border border-slate-200 bg-slate-50">
                        {{-- Stage Header --}}
                        <div class="border-b border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            style="background-color: {{ $stage->color ?: '#64748b' }}"
                                        ></span>

                                        <h2 class="truncate font-semibold text-slate-900">
                                            {{ $stage->name }}
                                        </h2>
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        ₹{{ number_format($stageValue, 2) }}
                                    </div>
                                </div>

                                <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">
                                    {{ $stageLeads->count() }}
                                </span>
                            </div>
                        </div>

                        {{-- Stage Leads --}}
                        <div class="max-h-[68vh] space-y-3 overflow-y-auto p-3">
                            @forelse ($stageLeads as $lead)
                                @php
                                    $priorityClass = match ($lead->priority) {
                                        'hot', 'urgent' => 'bg-rose-50 text-rose-700',
                                        'high' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp

                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="truncate font-semibold text-slate-900">
                                                {{ $lead->name }}
                                            </h3>

                                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                                {{ $lead->company_name ?: 'Individual Lead' }}
                                            </p>
                                        </div>

                                        <span class="shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold capitalize {{ $priorityClass }}">
                                            {{ $lead->priority }}
                                        </span>
                                    </div>

                                    <div class="mt-4 space-y-2 text-xs text-slate-500">
                                        <div class="flex items-center justify-between gap-3">
                                            <span>Deal Value</span>
                                            <span class="font-semibold text-slate-800">
                                                ₹{{ number_format($lead->expected_deal_value ?? 0, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <span>Owner</span>
                                            <span class="max-w-[160px] truncate font-medium text-slate-700">
                                                {{ $lead->assignedUser?->name ?? 'Unassigned' }}
                                            </span>
                                        </div>

                                        @if ($lead->next_follow_up_at)
                                            <div class="flex items-center justify-between gap-3">
                                                <span>Follow-up</span>
                                                <span class="font-medium text-slate-700">
                                                    {{ $lead->next_follow_up_at->format('d M, h:i A') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
                                    <div class="text-sm font-medium text-slate-600">
                                        No leads
                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        Is stage mein abhi koi lead nahi hai.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection