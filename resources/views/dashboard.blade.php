@extends('layouts.crm', [
    'title' => 'Dashboard',
])

@section('content')
@php
    $stats = [
        [
            'label' => 'Total Leads',
            'value' => number_format($totalLeads),
            'accent' => 'text-blue-700',
            'bg' => 'bg-blue-50',
        ],
        [
            'label' => 'New Today',
            'value' => number_format($newToday),
            'accent' => 'text-violet-700',
            'bg' => 'bg-violet-50',
        ],
        [
            'label' => 'Calls Today',
            'value' => number_format($callsToday),
            'accent' => 'text-sky-700',
            'bg' => 'bg-sky-50',
        ],
        [
            'label' => 'Connected',
            'value' => number_format($connectedToday),
            'accent' => 'text-emerald-700',
            'bg' => 'bg-emerald-50',
        ],
        [
            'label' => 'Due Follow-ups',
            'value' => number_format($followUpsDue),
            'accent' => 'text-amber-700',
            'bg' => 'bg-amber-50',
        ],
        [
            'label' => 'Overdue',
            'value' => number_format($overdue),
            'accent' => 'text-rose-700',
            'bg' => 'bg-rose-50',
        ],
        [
            'label' => 'Active Employees',
            'value' => number_format($activeUsers),
            'accent' => 'text-slate-700',
            'bg' => 'bg-slate-100',
        ],
        [
            'label' => 'Sales Value',
            'value' => '₹' . number_format($sales, 2),
            'accent' => 'text-indigo-700',
            'bg' => 'bg-indigo-50',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Dashboard
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                CRM activity, leads aur follow-ups ka quick overview.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('leads.import')
            <a
                href="{{ route('leads.import.create') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Import Leads
            </a>
            @endcan

            @can('leads.create')
            <a
                href="{{ route('leads.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Lead
            </a>
            @endcan
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            {{ $stat['label'] }}
                        </div>

                        <div class="mt-2 text-2xl font-bold {{ $stat['accent'] }}">
                            {{ $stat['value'] }}
                        </div>
                    </div>

                    <div class="h-10 w-10 rounded-lg {{ $stat['bg'] }}"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quick Links --}}
    <div class="grid gap-4 md:grid-cols-3">
        @can('leads.view')
        <a
            href="{{ route('leads.index') }}"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/30"
        >
            <div class="font-semibold text-slate-900">
                View All Leads
            </div>

            <div class="mt-1 text-sm text-slate-500">
                Search, filter aur assign leads.
            </div>
        </a>
        @endcan

        @can('followups.view')
        <a
            href="{{ route('followups.index') }}"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-amber-300 hover:bg-amber-50/30"
        >
            <div class="font-semibold text-slate-900">
                Manage Follow-ups
            </div>

            <div class="mt-1 text-sm text-slate-500">
                Pending aur overdue follow-ups dekhein.
            </div>
        </a>
        @endcan

        @can('calls.view')
        <a
            href="{{ route('calls.index') }}"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50/30"
        >
            <div class="font-semibold text-slate-900">
                View Call Logs
            </div>

            <div class="mt-1 text-sm text-slate-500">
                Team ki call activity review karein.
            </div>
        </a>
        @endcan
    </div>

    {{-- Recent Leads --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
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
                class="inline-flex self-start text-sm font-semibold text-blue-600 hover:text-blue-700 sm:self-auto"
            >
                View All
            </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">Mobile</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentLeads as $lead)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="font-semibold text-blue-600 hover:text-blue-700"
                                >
                                    {{ $lead->name }}
                                </a>

                                @if ($lead->company_name)
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->company_name }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">
                                    {{ $lead->mobile }}
                                </div>

                                @if ($lead->city)
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $lead->city }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ $lead->status?->name ?? 'New' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @if ($lead->assignedUser)
                                    <div class="font-medium text-slate-800">
                                        {{ $lead->assignedUser->name }}
                                    </div>

                                    @if ($lead->assignedUser->employee_code)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $lead->assignedUser->employee_code }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm font-medium text-amber-600">
                                        Unassigned
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                @can('leads.view')
                                <a
                                    href="{{ route('leads.show', $lead) }}"
                                    class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Open
                                </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="font-semibold text-slate-700">
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
@endsection