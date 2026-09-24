@extends('layouts.crm', [
    'title' => $employee->name . ' - Performance',
])

@section('content')

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ================================================================ --}}
    {{-- Header --}}
    {{-- ================================================================ --}}

    <div
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
    >

        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >

            <div
                class="flex items-center gap-4"
            >

                <a
                    href="{{ route('dashboard') }}"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                >
                    ←
                </a>


                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-100 text-lg font-black text-violet-700"
                >
                    {{
                        strtoupper(
                            substr(
                                $employee->name,
                                0,
                                1
                            )
                        )
                    }}
                </div>


                <div>

                    <h1
                        class="text-xl font-black text-slate-900"
                    >
                        {{ $employee->name }}
                    </h1>

                    <div
                        class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500"
                    >

                        @if($employee->employee_code)

                            <span>
                                {{ $employee->employee_code }}
                            </span>

                        @endif

                        <span
                            class="rounded-full bg-emerald-50 px-2 py-0.5 font-bold text-emerald-600"
                        >
                            Active
                        </span>

                    </div>

                </div>

            </div>


            <div
                class="rounded-xl bg-violet-50 px-4 py-2"
            >

                <div
                    class="text-[10px] font-bold uppercase tracking-wide text-violet-500"
                >
                    Report Period
                </div>

                <div
                    class="font-bold text-violet-800"
                >
                    {{ $periodLabel }}
                </div>

            </div>

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- Filters --}}
    {{-- ================================================================ --}}

    <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
    >

        <form
            method="GET"
            action="{{ route(
                'dashboard.employee',
                $employee
            ) }}"
            class="space-y-4"
        >

            {{-- Quick Filter --}}

            <div
                class="flex flex-wrap gap-2"
            >

                @foreach([
                    'today' => 'Today',
                    'month' => 'This Month',
                    'all' => 'All Time'
                ] as $key => $label)

                    <button
                        type="submit"
                        name="period"
                        value="{{ $key }}"
                        class="rounded-lg px-4 py-2 text-sm font-bold transition
                        {{
                            $period === $key
                                ? 'bg-violet-600 text-white'
                                : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                        }}"
                    >
                        {{ $label }}
                    </button>

                @endforeach

            </div>


            <div
                class="grid gap-3 md:grid-cols-4"
            >

                {{-- Search --}}

                <div>

                    <label
                        class="mb-1 block text-xs font-bold text-slate-600"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Name, mobile, company..."
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                    >

                </div>


                {{-- From --}}

                <div>

                    <label
                        class="mb-1 block text-xs font-bold text-slate-600"
                    >
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from"
                        value="{{ $from }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                    >

                </div>


                {{-- To --}}

                <div>

                    <label
                        class="mb-1 block text-xs font-bold text-slate-600"
                    >
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to"
                        value="{{ $to }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                    >

                </div>


                <div
                    class="flex items-end gap-2"
                >

                    <button
                        type="submit"
                        name="period"
                        value="custom"
                        class="flex-1 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-700"
                    >
                        Apply
                    </button>


                    <a
                        href="{{ route(
                            'dashboard.employee',
                            $employee
                        ) }}"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- ================================================================ --}}
    {{-- KPI --}}
    {{-- ================================================================ --}}

    @php

        $cards = [

            [
                'label' => 'Assigned Leads',
                'value' => $totalAssignedLeads,
                'class' => 'text-slate-800 bg-slate-100',
            ],

            [
                'label' => 'Total Calls',
                'value' => $totalCalls,
                'class' => 'text-blue-700 bg-blue-50',
            ],

            [
                'label' => 'Connected',
                'value' => $connectedCalls,
                'class' => 'text-emerald-700 bg-emerald-50',
            ],

            [
                'label' => 'Demo Sent',
                'value' => $demoSent,
                'class' => 'text-violet-700 bg-violet-50',
            ],

            [
                'label' => 'Follow-ups',
                'value' => $totalFollowUps,
                'class' => 'text-amber-700 bg-amber-50',
            ],

            [
                'label' => 'Pending',
                'value' => $pendingFollowUps,
                'class' => 'text-orange-700 bg-orange-50',
            ],

            [
                'label' => 'Completed',
                'value' => $completedFollowUps,
                'class' => 'text-cyan-700 bg-cyan-50',
            ],

            [
                'label' => 'Overdue',
                'value' => $overdueFollowUps,
                'class' => 'text-rose-700 bg-rose-50',
            ],

        ];

    @endphp


    <div
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
    >

        @foreach($cards as $card)

            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >

                <div
                    class="text-sm font-semibold text-slate-500"
                >
                    {{ $card['label'] }}
                </div>

                <div
                    class="mt-3 inline-flex rounded-xl px-3 py-2 text-2xl font-black {{ $card['class'] }}"
                >
                    {{ number_format(
                        $card['value']
                    ) }}
                </div>

            </div>

        @endforeach

    </div>


    {{-- ================================================================ --}}
    {{-- Dispositions --}}
    {{-- ================================================================ --}}

    <section
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
    >

        <div>

            <h2
                class="font-bold text-slate-900"
            >
                Call Disposition Breakdown
            </h2>

            <p
                class="mt-1 text-sm text-slate-500"
            >
                {{ $periodLabel }} call result.
            </p>

        </div>


        <div
            class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >

            @foreach($dispositions as $disposition)

                @php

                    $percentage =
                        $totalCalls > 0
                            ? (
                                $disposition['total']
                                / $totalCalls
                            ) * 100
                            : 0;

                @endphp

                <div
                    class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                >

                    <div
                        class="text-sm font-bold text-slate-700"
                    >
                        {{ $disposition['name'] }}
                    </div>


                    <div
                        class="mt-3 text-2xl font-black text-slate-900"
                    >
                        {{ number_format(
                            $disposition['total']
                        ) }}
                    </div>


                    <div
                        class="mt-1 text-xs text-slate-500"
                    >
                        {{
                            number_format(
                                $percentage,
                                1
                            )
                        }}%
                    </div>


                    <div
                        class="mt-3 h-1.5 overflow-hidden rounded-full bg-white"
                    >

                        <div
                            class="h-full rounded-full bg-violet-500"
                            style="width: {{
                                min(
                                    100,
                                    $percentage
                                )
                            }}%"
                        ></div>

                    </div>

                </div>

            @endforeach


            @if($withoutDisposition > 0)

                <div
                    class="rounded-xl border border-slate-200 bg-white p-4"
                >

                    <div
                        class="text-sm font-bold text-slate-600"
                    >
                        No Disposition
                    </div>

                    <div
                        class="mt-3 text-2xl font-black text-slate-700"
                    >
                        {{ number_format(
                            $withoutDisposition
                        ) }}
                    </div>

                </div>

            @endif

        </div>

    </section>


    {{-- ================================================================ --}}
    {{-- Call History --}}
    {{-- ================================================================ --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        <div
            class="border-b border-slate-200 px-5 py-4"
        >

            <h2
                class="font-bold text-slate-900"
            >
                Call History
            </h2>

            <p
                class="mt-1 text-sm text-slate-500"
            >
                Employee ke filtered call records.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table
                class="w-full min-w-[900px] text-sm"
            >

                <thead class="bg-slate-50">

                    <tr
                        class="text-left text-xs font-bold uppercase tracking-wide text-slate-500"
                    >

                        <th class="px-5 py-3">
                            Date / Time
                        </th>

                        <th class="px-4 py-3">
                            Lead
                        </th>

                        <th class="px-4 py-3">
                            Mobile
                        </th>

                        <th class="px-4 py-3">
                            Company
                        </th>

                        <th class="px-4 py-3">
                            Disposition
                        </th>

                        <th class="px-5 py-3 text-right">
                            Lead
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="divide-y divide-slate-100"
                >

                    @forelse(
                        $callLogs
                        as $call
                    )

                        @php

                            $lead =
                                $callLeads[
                                    $call->lead_id
                                ] ?? null;

                        @endphp

                        <tr
                            class="hover:bg-slate-50"
                        >

                            <td
                                class="px-5 py-3"
                            >

                                <div
                                    class="font-semibold text-slate-800"
                                >
                                    {{
                                        $call
                                            ->created_at
                                            ?->format(
                                                'd M Y'
                                            )
                                    }}
                                </div>

                                <div
                                    class="text-xs text-slate-500"
                                >
                                    {{
                                        $call
                                            ->created_at
                                            ?->format(
                                                'h:i A'
                                            )
                                    }}
                                </div>

                            </td>


                            <td
                                class="px-4 py-3 font-semibold text-slate-800"
                            >
                                {{
                                    $lead?->name
                                    ?? '-'
                                }}
                            </td>


                            <td
                                class="px-4 py-3"
                            >
                                {{
                                    $lead?->mobile
                                    ?? '-'
                                }}
                            </td>


                            <td
                                class="px-4 py-3 text-slate-500"
                            >
                                {{
                                    $lead?->company_name
                                    ?? '-'
                                }}
                            </td>


                            <td
                                class="px-4 py-3"
                            >

                                @if($call->disposition)

                                    <span
                                        class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700"
                                    >
                                        {{
                                            $call
                                                ->disposition
                                                ->name
                                        }}
                                    </span>

                                @else

                                    <span
                                        class="text-xs text-slate-400"
                                    >
                                        No Disposition
                                    </span>

                                @endif

                            </td>


                            <td
                                class="px-5 py-3 text-right"
                            >

                                @if($lead)

                                    <a
                                        href="{{ route(
                                            'leads.show',
                                            $lead->id
                                        ) }}"
                                        class="text-xs font-bold text-blue-600 hover:text-blue-700"
                                    >
                                        Open →
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >

                                <div
                                    class="font-semibold text-slate-700"
                                >
                                    No call records found
                                </div>

                                <div
                                    class="mt-1 text-sm text-slate-500"
                                >
                                    Selected date/filter me koi call nahi mila.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($callLogs->hasPages())

            <div
                class="border-t border-slate-200 px-5 py-4"
            >
                {{ $callLogs->links() }}
            </div>

        @endif

    </section>

</div>

@endsection
