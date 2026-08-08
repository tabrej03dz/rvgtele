@extends('layouts.crm', ['title' => 'Reports & Analytics'])

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

            <div>
                <h1 class="text-2xl font-bold">
                    Reports & Analytics
                </h1>

                <p class="text-indigo-100 mt-1 text-sm">
                    Monitor leads, employee calling performance and sales.
                </p>
            </div>

            {{-- Date Filter --}}
            <form method="GET"
                  action="{{ route('reports.index') }}"
                  class="flex flex-col sm:flex-row gap-3 bg-white/10 p-3 rounded-xl">

                <div>
                    <label class="block text-xs text-indigo-100 mb-1">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from"
                        value="{{ $from->format('Y-m-d') }}"
                        class="w-full rounded-lg border-0 text-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-white"
                    >
                </div>

                <div>
                    <label class="block text-xs text-indigo-100 mb-1">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to"
                        value="{{ $to->format('Y-m-d') }}"
                        class="w-full rounded-lg border-0 text-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-white"
                    >
                </div>

                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="bg-white text-indigo-700 font-semibold px-5 py-2 rounded-lg hover:bg-indigo-50 transition">
                        Filter
                    </button>

                    <a
                        href="{{ route('reports.index') }}"
                        class="bg-white/20 text-white font-semibold px-4 py-2 rounded-lg hover:bg-white/30 transition">
                        Reset
                    </a>

                </div>

            </form>

        </div>
    </div>


    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Leads --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 font-medium">
                        Total Leads
                    </p>

                    <h2 class="text-3xl font-bold text-gray-900 mt-2">
                        {{ number_format($totalLeads) }}
                    </h2>
                </div>

                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Calls --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 font-medium">
                        Total Calls
                    </p>

                    <h2 class="text-3xl font-bold text-gray-900 mt-2">
                        {{ number_format($totalCalls) }}
                    </h2>
                </div>

                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a2 2 0 011.897 1.368l1.498 4.493a2 2 0 01-.502 2.047l-2.257 2.257a16.001 16.001 0 006.586 6.586l2.257-2.257a2 2 0 012.047-.502l4.493 1.498A2 2 0 0121 20.72V24a2 2 0 01-2 2h-1C9.716 26 3 19.284 3 11V5z" />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Call Duration --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 font-medium">
                        Calling Duration
                    </p>

                    <h2 class="text-3xl font-bold text-gray-900 mt-2">
                        {{ number_format(round($totalDuration / 60)) }}
                        <span class="text-base text-gray-400 font-medium">min</span>
                    </h2>
                </div>

                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Sales --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 font-medium">
                        Total Sales
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mt-2">
                        ₹{{ number_format($sales, 2) }}
                    </h2>
                </div>

                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

            </div>
        </div>

    </div>


    {{-- Main Reports --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Leads Status --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-lg">
                    Leads by Status
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Lead distribution for selected date range.
                </p>
            </div>

            <div class="p-6">

                @forelse ($status as $x)

                    @php
                        $percentage = $totalLeads > 0
                            ? round(($x->total / $totalLeads) * 100)
                            : 0;
                    @endphp

                    <div class="mb-5 last:mb-0">

                        <div class="flex items-center justify-between mb-2">

                            <div>
                                <span class="font-medium text-gray-700">
                                    {{ $x->status?->name ?? 'Unspecified' }}
                                </span>
                            </div>

                            <div class="text-right">
                                <span class="font-bold text-gray-900">
                                    {{ $x->total }}
                                </span>

                                <span class="text-xs text-gray-400 ml-1">
                                    {{ $percentage }}%
                                </span>
                            </div>

                        </div>

                        <div class="w-full bg-gray-100 rounded-full h-2">

                            <div
                                class="bg-indigo-600 h-2 rounded-full"
                                style="width: {{ min($percentage, 100) }}%">
                            </div>

                        </div>

                    </div>

                @empty

                    <div class="text-center py-10 text-gray-400">
                        No lead data found for selected date range.
                    </div>

                @endforelse

            </div>

        </div>


        {{-- Calling Performance --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100">

                <h3 class="font-bold text-gray-900 text-lg">
                    Employee Calling Performance
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Calls and total talk-time by employee.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-gray-50 text-gray-500">

                        <tr>
                            <th class="px-6 py-3 text-left font-semibold">
                                Employee
                            </th>

                            <th class="px-4 py-3 text-center font-semibold">
                                Calls
                            </th>

                            <th class="px-6 py-3 text-right font-semibold">
                                Duration
                            </th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($calls as $x)

                            <tr class="hover:bg-gray-50 transition">

                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                            {{ strtoupper(substr($x->user?->name ?? 'U', 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="font-semibold text-gray-800">
                                                {{ $x->user?->name ?? 'Unknown User' }}
                                            </div>
                                        </div>

                                    </div>

                                </td>

                                <td class="px-4 py-4 text-center">

                                    <span class="inline-flex items-center bg-blue-50 text-blue-700 font-semibold px-3 py-1 rounded-full">
                                        {{ number_format($x->calls) }}
                                    </span>

                                </td>

                                <td class="px-6 py-4 text-right font-medium text-gray-700">
                                    {{ number_format(round($x->duration / 60)) }} min
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3"
                                    class="px-6 py-10 text-center text-gray-400">

                                    No calling activity found.

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- Employee Lead Performance --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-gray-100">

            <div class="flex items-center justify-between">

                <div>
                    <h3 class="font-bold text-gray-900 text-lg">
                        Employee Lead Assignment
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Leads assigned to each employee during selected period.
                    </p>
                </div>

                <span class="text-sm bg-gray-100 px-3 py-1 rounded-full text-gray-600">
                    {{ $totalEmployees }} Employees
                </span>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                    <tr>

                        <th class="px-6 py-3 text-left font-semibold">
                            Employee
                        </th>

                        <th class="px-6 py-3 text-right font-semibold">
                            Assigned Leads
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($users as $user)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center font-bold">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </div>

                                    <span class="font-semibold text-gray-800">
                                        {{ $user->name }}
                                    </span>

                                </div>

                            </td>

                            <td class="px-6 py-4 text-right">

                                <span class="inline-flex bg-indigo-50 text-indigo-700 font-bold px-3 py-1 rounded-lg">
                                    {{ number_format($user->assigned_leads_count) }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="2"
                                class="px-6 py-10 text-center text-gray-400">

                                No employees found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
