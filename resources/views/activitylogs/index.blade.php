@extends('layouts.crm', [
    'title' => 'Activity Logs',
])

@section('content')

<div class="mx-auto max-w-7xl space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Activity Logs
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Users CRM me kya activity kar rahe hain uska complete record.
            </p>
        </div>

        <div class="text-sm text-slate-500">
            Last updated:
            <span class="font-semibold text-slate-700">
                {{ now()->format('d M Y h:i A') }}
            </span>
        </div>
    </div>


    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Total Activities
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format($totalCount) }}
            </p>
        </div>


        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Today's Activities
            </p>

            <p class="mt-2 text-3xl font-bold text-blue-600">
                {{ number_format($todayCount) }}
            </p>
        </div>


        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Active Users Today
            </p>

            <p class="mt-2 text-3xl font-bold text-emerald-600">
                {{ number_format($todayUsers) }}
            </p>
        </div>

    </div>


    {{-- Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('activity-logs.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6"
        >

            {{-- Search --}}
            <div class="lg:col-span-2">

                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="User, email, route, IP..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>


            {{-- User --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    User
                </label>

                <select
                    name="user_id"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">
                        All Users
                    </option>

                    @foreach($users as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected(request('user_id') == $user->id)
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>


            {{-- Method --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Action Type
                </label>

                <select
                    name="method"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    <option value="">
                        All Actions
                    </option>

                    <option
                        value="GET"
                        @selected(request('method') === 'GET')
                    >
                        View
                    </option>

                    <option
                        value="POST"
                        @selected(request('method') === 'POST')
                    >
                        Create / Action
                    </option>

                    <option
                        value="PUT"
                        @selected(request('method') === 'PUT')
                    >
                        Update
                    </option>

                    <option
                        value="PATCH"
                        @selected(request('method') === 'PATCH')
                    >
                        Update
                    </option>

                    <option
                        value="DELETE"
                        @selected(request('method') === 'DELETE')
                    >
                        Delete
                    </option>
                </select>
            </div>


            {{-- From Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    From Date
                </label>

                <input
                    type="date"
                    name="from_date"
                    value="{{ request('from_date') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
            </div>


            {{-- To Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    To Date
                </label>

                <input
                    type="date"
                    name="to_date"
                    value="{{ request('to_date') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
            </div>


            <div class="lg:col-span-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Apply Filters
                </button>

                <a
                    href="{{ route('activity-logs.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- Activity Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            User
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Activity
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Method
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Route
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            IP
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Status
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Time
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Details
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse($activities as $activity)

                        @php
                            $properties = $activity->properties ?? collect();

                            $method = $properties->get('method');

                            $statusCode = $properties->get('status_code');

                            $methodClass = match($method) {
                                'GET' => 'bg-blue-100 text-blue-700',
                                'POST' => 'bg-green-100 text-green-700',
                                'PUT', 'PATCH' => 'bg-amber-100 text-amber-700',
                                'DELETE' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">

                            {{-- User --}}
                            <td class="whitespace-nowrap px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700">
                                        {{ strtoupper(substr($activity->causer?->name ?? 'U', 0, 1)) }}
                                    </div>

                                    <div>

                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $activity->causer?->name ?? 'Unknown User' }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $activity->causer?->email }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- Activity --}}
                            <td class="px-4 py-4">

                                <div class="max-w-xs text-sm font-medium text-slate-800">
                                    {{ $activity->description }}
                                </div>

                            </td>


                            {{-- Method --}}
                            <td class="whitespace-nowrap px-4 py-4">

                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $methodClass }}"
                                >
                                    {{ $method ?? '-' }}
                                </span>

                            </td>


                            {{-- Route --}}
                            <td class="px-4 py-4">

                                <div class="max-w-xs truncate text-sm text-slate-700">
                                    {{ $properties->get('route') ?? '-' }}
                                </div>

                                <div class="mt-1 max-w-xs truncate text-xs text-slate-400">
                                    {{ $properties->get('path') }}
                                </div>

                            </td>


                            {{-- IP --}}
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">
                                {{ $properties->get('ip_address') ?? '-' }}
                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-4 py-4">

                                @if($statusCode >= 200 && $statusCode < 300)

                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        {{ $statusCode }}
                                    </span>

                                @elseif($statusCode >= 400)

                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        {{ $statusCode }}
                                    </span>

                                @else

                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $statusCode }}
                                    </span>

                                @endif

                            </td>


                            {{-- Date --}}
                            <td class="whitespace-nowrap px-4 py-4">

                                <div class="text-sm font-medium text-slate-700">
                                    {{ $activity->created_at->format('d M Y') }}
                                </div>

                                <div class="text-xs text-slate-400">
                                    {{ $activity->created_at->format('h:i:s A') }}
                                </div>

                            </td>


                            {{-- Details --}}
                            <td class="whitespace-nowrap px-4 py-4 text-right">

                                <a
                                    href="{{ route('activity-logs.show', $activity) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="8"
                                class="px-6 py-12 text-center"
                            >
                                <div class="text-sm font-medium text-slate-500">
                                    No activity found.
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($activities->hasPages())
            <div class="border-t border-slate-200 px-4 py-4">
                {{ $activities->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
