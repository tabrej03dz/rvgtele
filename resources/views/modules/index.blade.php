@extends('layouts.crm')

@section('title', $title)

@section('content')
<div class="space-y-6">

    {{-- Heading and Create Button --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $title }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage {{ strtolower($title) }} from this page.
            </p>
        </div>

        <a
            href="{{ route($routeName . '.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-700"
        >
            + Add New
        </a>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search {{ strtolower($title) }}..."
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >

            <button
                type="submit"
                class="rounded-lg bg-slate-800 px-5 py-2.5 font-medium text-white hover:bg-slate-900"
            >
                Search
            </button>

            @if (request()->filled('search'))
                <a
                    href="{{ route($routeName . '.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2.5 text-center font-medium text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            #
                        </th>

                        @foreach ($columns as $column)
                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $columnLabels[$column] ?? ucwords(str_replace(['_', '.'], ' ', $column)) }}
                            </th>
                        @endforeach

                        <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                {{ $items->firstItem() + $loop->index }}
                            </td>

                            @foreach ($columns as $column)
                                @php
                                    $value = data_get($item, $column);
                                    $lastKey = last(explode('.', $column));
                                @endphp

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">

                                    {{-- Boolean fields --}}
                                    @if (
                                        str_starts_with($lastKey, 'is_')
                                        || is_bool($value)
                                    )
                                        @if ($value)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Yes
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                No
                                            </span>
                                        @endif

                                    {{-- Date fields --}}
                                    @elseif ($value instanceof \Carbon\CarbonInterface)
                                        {{ $value->format(
                                            str_contains($lastKey, '_at')
                                                ? 'd M Y, h:i A'
                                                : 'd M Y'
                                        ) }}

                                    {{-- Amount fields --}}
                                    @elseif (
                                        str_contains($lastKey, 'amount')
                                        || $lastKey === 'budget'
                                    )
                                        ₹{{ number_format((float) $value, 2) }}

                                    {{-- Empty value --}}
                                    @elseif ($value === null || $value === '')
                                        <span class="text-slate-400">
                                            —
                                        </span>

                                    {{-- Status --}}
                                    @elseif (
                                        $lastKey === 'status'
                                        || $lastKey === 'payment_status'
                                        || $lastKey === 'priority'
                                        || $lastKey === 'category'
                                    )
                                        <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium capitalize text-indigo-700">
                                            {{ str_replace('_', ' ', $value) }}
                                        </span>

                                    {{-- Normal value --}}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a
                                        href="{{ route($routeName . '.edit', ['item' => $item]) }}"
                                        class="rounded-lg bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-100"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route($routeName . '.destroy', ['item' => $item]) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this record?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-100"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ count($columns) + 2 }}"
                                class="px-5 py-12 text-center"
                            >
                                <div class="text-base font-medium text-slate-600">
                                    No records found
                                </div>

                                <div class="mt-1 text-sm text-slate-400">
                                    Add your first record using the Add New button.
                                </div>
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
    </div>
</div>
@endsection