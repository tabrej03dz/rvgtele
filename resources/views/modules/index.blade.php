@extends('layouts.crm')

@section('title', $title)

@section('content')
@php
    $pageStart = $items->firstItem() ?? 0;
    $permissionPrefix = str_starts_with($routeName, 'crm-settings.')
        ? str($routeName)->after('crm-settings.')->toString()
        : $routeName;

    $formatValue = function ($value, string $column) {
        $lastKey = last(explode('.', $column));

        if ($value === null || $value === '') {
            return ['type' => 'empty', 'value' => '—'];
        }

        if (str_starts_with($lastKey, 'is_') || is_bool($value)) {
            return ['type' => 'boolean', 'value' => (bool) $value];
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return [
                'type' => 'date',
                'value' => $value->format(
                    str_contains($lastKey, '_at')
                        ? 'd M Y, h:i A'
                        : 'd M Y'
                ),
            ];
        }

        if (
            str_contains($lastKey, 'amount')
            || str_contains($lastKey, 'price')
            || str_contains($lastKey, 'value')
            || $lastKey === 'budget'
        ) {
            return [
                'type' => 'amount',
                'value' => '₹' . number_format((float) $value, 2),
            ];
        }

        if (
            $lastKey === 'status'
            || $lastKey === 'payment_status'
            || $lastKey === 'priority'
            || $lastKey === 'category'
            || $lastKey === 'type'
        ) {
            return [
                'type' => 'badge',
                'value' => str_replace('_', ' ', (string) $value),
            ];
        }

        return ['type' => 'text', 'value' => $value];
    };
@endphp

<div class="mx-auto max-w-[1500px] space-y-5">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage {{ strtolower($title) }} records.
            </p>
        </div>

        @can($permissionPrefix . '.create')
        <a
            href="{{ route($routeName . '.create') }}"
            class="inline-flex items-center gap-2 self-start rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:self-auto"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add New
        </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form
            method="GET"
            action="{{ route($routeName . '.index') }}"
            class="flex flex-col gap-3 sm:flex-row"
        >
            <div class="relative flex-1">
                <svg
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search {{ strtolower($title) }}..."
                    class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Search
            </button>

            @if (request()->filled('search'))
                <a
                    href="{{ route($routeName . '.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>
            @endif
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">{{ $title }} List</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
                    of {{ $items->total() }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="w-16 px-4 py-3">#</th>

                        @foreach ($columns as $column)
                            <th class="px-4 py-3">
                                {{ $columnLabels[$column] ?? ucwords(str_replace(['_', '.'], ' ', $column)) }}
                            </th>
                        @endforeach

                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500">
                                {{ $pageStart + $loop->index }}
                            </td>

                            @foreach ($columns as $column)
                                @php
                                    $formatted = $formatValue(data_get($item, $column), $column);
                                @endphp

                                <td class="px-4 py-3 text-slate-700">
                                    @switch($formatted['type'])
                                        @case('boolean')
                                            @if ($formatted['value'])
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    Yes
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                    No
                                                </span>
                                            @endif
                                            @break

                                        @case('date')
                                            <span class="whitespace-nowrap">{{ $formatted['value'] }}</span>
                                            @break

                                        @case('amount')
                                            <span class="whitespace-nowrap font-semibold text-slate-800">
                                                {{ $formatted['value'] }}
                                            </span>
                                            @break

                                        @case('badge')
                                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold capitalize text-blue-700">
                                                {{ $formatted['value'] }}
                                            </span>
                                            @break

                                        @case('empty')
                                            <span class="text-slate-400">—</span>
                                            @break

                                        @default
                                            <div
                                                class="max-w-xs truncate"
                                                title="{{ is_scalar($formatted['value']) ? $formatted['value'] : '' }}"
                                            >
                                                {{ $formatted['value'] }}
                                            </div>
                                    @endswitch
                                </td>
                            @endforeach

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @can($permissionPrefix . '.update')
                                    <a
                                        href="{{ route($routeName . '.edit', ['item' => $item]) }}"
                                        class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                    @endcan

                                    @can($permissionPrefix . '.delete')
                                    <form
                                        method="POST"
                                        action="{{ route($routeName . '.destroy', ['item' => $item]) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this record?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ count($columns) + 2 }}"
                                class="px-5 py-14 text-center"
                            >
                                <div class="font-semibold text-slate-700">
                                    No records found
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Add New button se pehla record create karein.
                                </div>

                                @can($permissionPrefix . '.create')
                                <a
                                    href="{{ route($routeName . '.create') }}"
                                    class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Add New
                                </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $items->links() }}
            </div>
        @endif
    </section>
</div>
@endsection