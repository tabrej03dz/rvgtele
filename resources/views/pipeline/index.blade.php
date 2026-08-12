@extends('layouts.crm', [
    'title' => 'Sales Pipeline',
])

@section('content')
<div class="mx-auto max-w-[1800px] space-y-5">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex items-center gap-3">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 6h16"/>
                            <path d="M4 12h10"/>
                            <path d="M4 18h7"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">
                            Sales Pipeline
                        </h1>

                        <p class="mt-1 text-sm text-slate-500">
                            Lead ko uski current sales stage ke according manage karein.
                        </p>
                    </div>

                </div>
            </div>

            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('leads.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    View All Leads
                </a>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- NO PIPELINE --}}
    {{-- ========================================================= --}}

    @if (!$pipeline)

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">

            <div class="flex gap-4">

                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-amber-900">
                        Pipeline not configured
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-amber-800">
                        Sales Pipeline use karne ke liye pehle pipeline aur uske
                        stages configure karein.
                    </p>
                </div>

            </div>

        </div>

    @else

        {{-- ===================================================== --}}
        {{-- HELP / HOW TO USE --}}
        {{-- ===================================================== --}}

        <details
            class="group rounded-2xl border border-blue-200 bg-blue-50"
        >
            <summary
                class="flex cursor-pointer list-none items-center justify-between gap-4 p-4"
            >

                <div class="flex items-center gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M9.5 9a2.5 2.5 0 0 1 4.8 1c0 2-2.3 2-2.3 4"/>
                            <path d="M12 17h.01"/>
                        </svg>
                    </div>

                    <div>
                        <div class="font-semibold text-blue-950">
                            Pipeline kaise use karein?
                        </div>

                        <div class="mt-0.5 text-xs text-blue-700">
                            Example aur recommended workflow dekhne ke liye click karein.
                        </div>
                    </div>

                </div>

                <svg
                    class="h-5 w-5 text-blue-700 transition group-open:rotate-180"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="m6 9 6 6 6-6"/>
                </svg>

            </summary>

            <div class="border-t border-blue-200 px-5 py-4">

                <div class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-xl bg-white p-4">
                        <div class="font-semibold text-slate-900">
                            1. New Lead
                        </div>

                        <p class="mt-1 leading-5 text-slate-600">
                            Nayi enquiry ko starting stage me rakhein.
                        </p>
                    </div>

                    <div class="rounded-xl bg-white p-4">
                        <div class="font-semibold text-slate-900">
                            2. Contact Karein
                        </div>

                        <p class="mt-1 leading-5 text-slate-600">
                            Customer se call hone ke baad lead ko next relevant
                            stage me move karein.
                        </p>
                    </div>

                    <div class="rounded-xl bg-white p-4">
                        <div class="font-semibold text-slate-900">
                            3. Follow-up
                        </div>

                        <p class="mt-1 leading-5 text-slate-600">
                            Interested customer ke liye next follow-up date set
                            karein.
                        </p>
                    </div>

                    <div class="rounded-xl bg-white p-4">
                        <div class="font-semibold text-slate-900">
                            4. Close
                        </div>

                        <p class="mt-1 leading-5 text-slate-600">
                            Deal complete hone par final Won/Sale stage me
                            shift karein.
                        </p>
                    </div>

                </div>

                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-100/60 p-4 text-sm text-blue-950">

                    <span class="font-semibold">Example:</span>

                    New Lead
                    <span class="mx-1">→</span>
                    Contacted
                    <span class="mx-1">→</span>
                    Interested
                    <span class="mx-1">→</span>
                    Follow-up
                    <span class="mx-1">→</span>
                    Won

                </div>

            </div>

        </details>


        {{-- ===================================================== --}}
        {{-- SUMMARY CARDS --}}
        {{-- ===================================================== --}}

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Pipeline
                </div>

                <div class="mt-2 truncate text-xl font-bold text-slate-900">
                    {{ $pipeline->name }}
                </div>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Total Leads
                </div>

                <div class="mt-2 text-xl font-bold text-slate-900">
                    {{ number_format($pipelineTotalLeads ?? 0) }}
                </div>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Expected Pipeline Value
                </div>

                <div class="mt-2 text-xl font-bold text-slate-900">
                    ₹{{ number_format($pipelineTotalValue ?? 0, 2) }}
                </div>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Showing
                </div>

                <div class="mt-2 text-xl font-bold text-slate-900">

                    @if($paginatedLeads && $paginatedLeads->total())
                        {{ number_format($paginatedLeads->firstItem()) }}
                        -
                        {{ number_format($paginatedLeads->lastItem()) }}
                    @else
                        0
                    @endif

                </div>

                <div class="mt-1 text-xs text-slate-500">
                    of {{ number_format($paginatedLeads?->total() ?? 0) }} filtered leads
                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- FILTERS --}}
        {{-- ===================================================== --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="space-y-4"
            >

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Filters
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Required leads ko quickly find karein.
                        </p>
                    </div>

                    @if(request()->hasAny([
                        'search',
                        'stage',
                        'assigned_to',
                        'priority',
                        'status',
                        'sort'
                    ]))
                        <a
                            href="{{ url()->current() }}"
                            class="text-sm font-semibold text-rose-600 hover:text-rose-700"
                        >
                            Clear All
                        </a>
                    @endif

                </div>


                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">

                    {{-- Search --}}

                    <div class="xl:col-span-2">

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Search Lead
                        </label>

                        <div class="relative">

                            <svg
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>

                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Name, phone, company..."
                                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >

                        </div>

                    </div>


                    {{-- Stage --}}

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Stage
                        </label>

                        <select
                            name="stage"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">All Stages</option>

                            @foreach($pipeline->stages as $stage)
                                <option
                                    value="{{ $stage->id }}"
                                    @selected((string) request('stage') === (string) $stage->id)
                                >
                                    {{ $stage->name }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- Assigned Employee --}}

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Assigned To
                        </label>

                        <select
                            name="assigned_to"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >

                            <option value="">
                                Everyone
                            </option>

                            <option
                                value="unassigned"
                                @selected(request('assigned_to') === 'unassigned')
                            >
                                Unassigned
                            </option>

                            @foreach($employees as $employee)

                                <option
                                    value="{{ $employee->id }}"
                                    @selected(
                                        (string) request('assigned_to') ===
                                        (string) $employee->id
                                    )
                                >
                                    {{ $employee->name }}

                                    @if($employee->employee_code)
                                        ({{ $employee->employee_code }})
                                    @endif
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Priority --}}

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Priority
                        </label>

                        <select
                            name="priority"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >

                            <option value="">
                                All Priority
                            </option>

                            <option
                                value="urgent"
                                @selected(request('priority') === 'urgent')
                            >
                                Urgent
                            </option>

                            <option
                                value="hot"
                                @selected(request('priority') === 'hot')
                            >
                                Hot
                            </option>

                            <option
                                value="high"
                                @selected(request('priority') === 'high')
                            >
                                High
                            </option>

                            <option
                                value="medium"
                                @selected(request('priority') === 'medium')
                            >
                                Medium
                            </option>

                            <option
                                value="low"
                                @selected(request('priority') === 'low')
                            >
                                Low
                            </option>

                        </select>

                    </div>


                    {{-- Status --}}

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >

                            <option value="">
                                All Status
                            </option>

                            @foreach($statuses as $status)

                                <option
                                    value="{{ $status->id }}"
                                    @selected(
                                        (string) request('status') ===
                                        (string) $status->id
                                    )
                                >
                                    {{ $status->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-end sm:justify-between">

                    {{-- Sorting --}}

                    <div class="w-full sm:max-w-xs">

                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Sort Leads
                        </label>

                        <select
                            name="sort"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >

                            <option
                                value=""
                                @selected(!request('sort'))
                            >
                                Newest First
                            </option>

                            <option
                                value="oldest"
                                @selected(request('sort') === 'oldest')
                            >
                                Oldest First
                            </option>

                            <option
                                value="value_high"
                                @selected(request('sort') === 'value_high')
                            >
                                Deal Value: High to Low
                            </option>

                            <option
                                value="value_low"
                                @selected(request('sort') === 'value_low')
                            >
                                Deal Value: Low to High
                            </option>

                            <option
                                value="follow_up"
                                @selected(request('sort') === 'follow_up')
                            >
                                Upcoming Follow-up
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 6h16"/>
                            <path d="M7 12h10"/>
                            <path d="M10 18h4"/>
                        </svg>

                        Apply Filters

                    </button>

                </div>

            </form>

        </div>


        {{-- ===================================================== --}}
        {{-- FILTERED EMPTY RESULT --}}
        {{-- ===================================================== --}}

        @if(!$paginatedLeads || $paginatedLeads->count() === 0)

            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">

                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>

                </div>

                <h3 class="mt-4 font-semibold text-slate-900">
                    No matching leads found
                </h3>

                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    Search ya filters ko change karke dobara try karein.
                </p>

                <a
                    href="{{ url()->current() }}"
                    class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Clear Filters
                </a>

            </div>

        @else

            {{-- ================================================= --}}
            {{-- PIPELINE BOARD --}}
            {{-- ================================================= --}}

            <div class="overflow-x-auto pb-4">

                <div class="flex min-w-max items-start gap-4">

                    @foreach ($pipeline->stages as $stage)

                        @php
                            $stageLeads = collect($leads[$stage->id] ?? []);

                            $stageValue = $stageLeads->sum(
                                fn ($lead) =>
                                    (float) ($lead->expected_deal_value ?? 0)
                            );
                        @endphp


                        <section
                            class="w-[340px] shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm"
                        >

                            {{-- ================================= --}}
                            {{-- STAGE HEADER --}}
                            {{-- ================================= --}}

                            <div class="border-b border-slate-200 bg-white p-4">

                                <div class="flex items-start justify-between gap-3">

                                    <div class="min-w-0">

                                        <div class="flex items-center gap-2">

                                            <span
                                                class="h-3 w-3 shrink-0 rounded-full"
                                                style="
                                                    background-color:
                                                    {{ $stage->color ?: '#64748b' }}
                                                "
                                            ></span>

                                            <h2 class="truncate font-bold text-slate-900">
                                                {{ $stage->name }}
                                            </h2>

                                        </div>

                                        <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">

                                            <span>
                                                {{ number_format($stageLeads->count()) }}
                                                on this page
                                            </span>

                                            <span>•</span>

                                            <span>
                                                ₹{{ number_format($stageValue, 2) }}
                                            </span>

                                        </div>

                                    </div>


                                    <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                        {{ $stageLeads->count() }}
                                    </span>

                                </div>

                            </div>


                            {{-- ================================= --}}
                            {{-- LEADS --}}
                            {{-- ================================= --}}

                            <div class="max-h-[65vh] space-y-3 overflow-y-auto p-3">

                                @forelse ($stageLeads as $lead)

                                    @php
                                        $priorityClass = match ($lead->priority) {
                                            'urgent' =>
                                                'border-rose-200 bg-rose-50 text-rose-700',

                                            'hot' =>
                                                'border-orange-200 bg-orange-50 text-orange-700',

                                            'high' =>
                                                'border-amber-200 bg-amber-50 text-amber-700',

                                            'medium' =>
                                                'border-blue-200 bg-blue-50 text-blue-700',

                                            'low' =>
                                                'border-slate-200 bg-slate-100 text-slate-600',

                                            default =>
                                                'border-slate-200 bg-slate-100 text-slate-600',
                                        };
                                    @endphp


                                    <a
                                        href="{{ route('leads.show', $lead) }}"
                                        class="group block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                                    >

                                        {{-- Lead Heading --}}

                                        <div class="flex items-start justify-between gap-3">

                                            <div class="min-w-0">

                                                <h3 class="truncate font-bold text-slate-900 group-hover:text-blue-700">
                                                    {{ $lead->name }}
                                                </h3>

                                                <p class="mt-1 truncate text-xs text-slate-500">
                                                    {{ $lead->company_name ?: 'Individual Lead' }}
                                                </p>

                                            </div>


                                            @if($lead->priority)

                                                <span
                                                    class="shrink-0 rounded-full border px-2 py-1 text-[10px] font-bold uppercase {{ $priorityClass }}"
                                                >
                                                    {{ $lead->priority }}
                                                </span>

                                            @endif

                                        </div>


                                        {{-- Contact --}}

                                        @if($lead->phone)

                                            <div class="mt-3 flex items-center gap-2 text-xs text-slate-600">

                                                <svg
                                                    class="h-3.5 w-3.5 shrink-0 text-slate-400"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/>
                                                </svg>

                                                <span class="truncate">
                                                    {{ $lead->phone }}
                                                </span>

                                            </div>

                                        @endif


                                        {{-- Status --}}

                                        @if($lead->status)

                                            <div class="mt-3">

                                                <span
                                                    class="inline-flex rounded-full px-2 py-1 text-[10px] font-semibold"
                                                    style="
                                                        background-color:
                                                        {{ $lead->status->color ?? '#e2e8f0' }}20;

                                                        color:
                                                        {{ $lead->status->color ?? '#475569' }};
                                                    "
                                                >
                                                    {{ $lead->status->name }}
                                                </span>

                                            </div>

                                        @endif


                                        <div class="my-3 border-t border-slate-100"></div>


                                        {{-- Deal --}}

                                        <div class="space-y-2 text-xs">

                                            <div class="flex items-center justify-between gap-3">

                                                <span class="text-slate-500">
                                                    Deal Value
                                                </span>

                                                <span class="font-bold text-slate-900">
                                                    ₹{{ number_format(
                                                        $lead->expected_deal_value ?? 0,
                                                        2
                                                    ) }}
                                                </span>

                                            </div>


                                            <div class="flex items-center justify-between gap-3">

                                                <span class="text-slate-500">
                                                    Assigned
                                                </span>

                                                <span
                                                    class="max-w-[180px] truncate font-semibold {{ $lead->assignedUser ? 'text-slate-700' : 'text-rose-600' }}"
                                                >
                                                    {{ $lead->assignedUser?->name ?? 'Unassigned' }}
                                                </span>

                                            </div>


                                            @if ($lead->next_follow_up_at)

                                                <div class="flex items-center justify-between gap-3">

                                                    <span class="text-slate-500">
                                                        Follow-up
                                                    </span>

                                                    <span class="font-semibold text-blue-700">
                                                        {{ $lead->next_follow_up_at->format('d M, h:i A') }}
                                                    </span>

                                                </div>

                                            @endif

                                        </div>

                                    </a>

                                @empty

                                    <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center">

                                        <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-slate-100">

                                            <svg
                                                class="h-4 w-4 text-slate-400"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M12 5v14M5 12h14"/>
                                            </svg>

                                        </div>

                                        <div class="mt-3 text-sm font-semibold text-slate-600">
                                            No leads
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            Is page par is stage ki koi lead nahi hai.
                                        </div>

                                    </div>

                                @endforelse

                            </div>

                        </section>

                    @endforeach

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- PAGINATION --}}
            {{-- ================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div class="text-sm text-slate-500">

                        @if($paginatedLeads->total())

                            Showing

                            <span class="font-semibold text-slate-800">
                                {{ number_format($paginatedLeads->firstItem()) }}
                            </span>

                            to

                            <span class="font-semibold text-slate-800">
                                {{ number_format($paginatedLeads->lastItem()) }}
                            </span>

                            of

                            <span class="font-semibold text-slate-800">
                                {{ number_format($paginatedLeads->total()) }}
                            </span>

                            leads

                        @endif

                    </div>


                    <div>
                        {{ $paginatedLeads->onEachSide(1)->links() }}
                    </div>

                </div>

            </div>

        @endif

    @endif

</div>
@endsection
