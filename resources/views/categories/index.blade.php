@extends('layouts.crm')

@section('title', $title)

@section('content')

<div class="mx-auto max-w-[1500px] space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Categories
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage your business categories.
            </p>
        </div>

        @can('categories.create')
            <a
                href="{{ route('categories.create') }}"
                class="inline-flex items-center gap-2 self-start rounded-lg
                       bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                       transition hover:bg-blue-700 sm:self-auto"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M12 5v14M5 12h14"/>
                </svg>

                Add Category
            </a>
        @endcan
    </div>


    {{-- Success Message --}}
    @if(session('success'))
        <div
            class="rounded-lg border border-emerald-200
                   bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ session('success') }}
        </div>
    @endif


    {{-- Error Message --}}
    @if(session('error'))
        <div
            class="rounded-lg border border-rose-200
                   bg-rose-50 px-4 py-3 text-sm text-rose-800"
        >
            {{ session('error') }}
        </div>
    @endif


    {{-- Search --}}
    <section
        class="rounded-xl border border-slate-200
               bg-white p-4 shadow-sm"
    >

        <form
            method="GET"
            action="{{ route('categories.index') }}"
            class="flex flex-col gap-3 sm:flex-row"
        >

            <div class="relative flex-1">

                <svg
                    class="pointer-events-none absolute left-3 top-1/2
                           h-4 w-4 -translate-y-1/2 text-slate-400"
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
                    placeholder="Search category..."
                    class="w-full rounded-lg border-slate-300
                           py-2.5 pl-10 pr-3 text-sm
                           focus:border-blue-500 focus:ring-blue-500"
                >

            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center
                       rounded-lg bg-slate-900 px-5 py-2.5
                       text-sm font-semibold text-white
                       hover:bg-slate-800"
            >
                Search
            </button>

            @if(request()->filled('search'))
                <a
                    href="{{ route('categories.index') }}"
                    class="inline-flex items-center justify-center
                           rounded-lg border border-slate-300
                           bg-white px-5 py-2.5
                           text-sm font-semibold text-slate-700
                           hover:bg-slate-50"
                >
                    Reset
                </a>
            @endif

        </form>

    </section>


    {{-- Table --}}
    <section
        class="overflow-hidden rounded-xl border
               border-slate-200 bg-white shadow-sm"
    >

        <div
            class="flex items-center justify-between
                   border-b border-slate-200 px-5 py-4"
        >
            <div>
                <h2 class="font-bold text-slate-900">
                    Category List
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $categories->firstItem() ?? 0 }}
                    –
                    {{ $categories->lastItem() ?? 0 }}
                    of
                    {{ $categories->total() }}
                </p>
            </div>
        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[750px] text-sm">

                <thead class="bg-slate-50">

                    <tr
                        class="border-b border-slate-200
                               text-left text-xs font-semibold
                               uppercase tracking-wide text-slate-500"
                    >

                        <th class="w-16 px-4 py-3">
                            #
                        </th>

                        <th class="px-4 py-3">
                            Category Name
                        </th>

                        <th class="px-4 py-3">
                            Description
                        </th>

                        <th class="px-4 py-3">
                            Created
                        </th>

                        <th class="px-4 py-3 text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($categories as $category)

                        <tr class="transition hover:bg-slate-50">

                            <td class="px-4 py-4 text-slate-500">
                                {{ $categories->firstItem() + $loop->index }}
                            </td>


                            <td class="px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-10 w-10 shrink-0
                                               items-center justify-center
                                               rounded-lg bg-blue-50
                                               font-bold text-blue-600"
                                    >
                                        {{ strtoupper(substr($category->name, 0, 1)) }}
                                    </div>

                                    <div>
                                        <div class="font-semibold text-slate-900">
                                            {{ $category->name }}
                                        </div>

                                        <div class="text-xs text-slate-400">
                                            ID: {{ $category->id }}
                                        </div>
                                    </div>

                                </div>

                            </td>


                            <td class="px-4 py-4 text-slate-600">

                                <div
                                    class="max-w-md truncate"
                                    title="{{ $category->description }}"
                                >
                                    {{ $category->description ?: '—' }}
                                </div>

                            </td>


                            <td class="px-4 py-4 text-slate-600">

                                <div class="whitespace-nowrap">
                                    {{ $category->created_at?->format('d M Y') }}
                                </div>

                                <div class="mt-0.5 text-xs text-slate-400">
                                    {{ $category->created_at?->format('h:i A') }}
                                </div>

                            </td>


                            <td class="px-4 py-4">

                                <div class="flex justify-end gap-2">

                                    @can('categories.view')
                                        <a
                                            href="{{ route('categories.show', $category) }}"
                                            class="inline-flex items-center
                                                   rounded-lg border border-blue-200
                                                   bg-blue-50 px-3 py-1.5
                                                   text-xs font-semibold text-blue-700
                                                   hover:bg-blue-100"
                                        >
                                            View
                                        </a>
                                    @endcan


                                    @can('categories.update')
                                        <a
                                            href="{{ route('categories.edit', $category) }}"
                                            class="inline-flex items-center
                                                   rounded-lg border border-slate-300
                                                   bg-white px-3 py-1.5
                                                   text-xs font-semibold text-slate-700
                                                   hover:bg-slate-50"
                                        >
                                            Edit
                                        </a>
                                    @endcan


                                    @can('categories.delete')

                                        <form
                                            method="POST"
                                            action="{{ route('categories.destroy', $category) }}"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this category?'
                                                )
                                            "
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center
                                                       rounded-lg border border-rose-200
                                                       bg-rose-50 px-3 py-1.5
                                                       text-xs font-semibold text-rose-700
                                                       hover:bg-rose-100"
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
                                colspan="5"
                                class="px-5 py-16 text-center"
                            >

                                <div
                                    class="mx-auto flex h-14 w-14
                                           items-center justify-center
                                           rounded-full bg-slate-100"
                                >

                                    <svg
                                        class="h-6 w-6 text-slate-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path d="M4 6h16M4 12h16M4 18h10"/>
                                    </svg>

                                </div>

                                <div
                                    class="mt-4 font-semibold
                                           text-slate-700"
                                >
                                    No categories found
                                </div>

                                <div
                                    class="mt-1 text-sm
                                           text-slate-500"
                                >
                                    Create your first category.
                                </div>

                                @can('categories.create')
                                    <a
                                        href="{{ route('categories.create') }}"
                                        class="mt-4 inline-flex
                                               rounded-lg bg-blue-600
                                               px-4 py-2 text-sm
                                               font-semibold text-white
                                               hover:bg-blue-700"
                                    >
                                        Add Category
                                    </a>
                                @endcan

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($categories->hasPages())

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $categories->links() }}
            </div>

        @endif

    </section>

</div>

@endsection