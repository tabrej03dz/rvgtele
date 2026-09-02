@extends('layouts.crm')

@section('title', $title)

@section('content')

<div class="mx-auto max-w-5xl space-y-5">

    {{-- Header --}}
    <div
        class="flex flex-col gap-4
               sm:flex-row sm:items-center
               sm:justify-between"
    >

        <div>

            <div class="mb-2 flex items-center gap-2 text-sm">

                <a
                    href="{{ route('categories.index') }}"
                    class="font-medium text-slate-500 hover:text-blue-600"
                >
                    Categories
                </a>

                <span class="text-slate-300">
                    /
                </span>

                <span class="text-slate-700">
                    Details
                </span>

            </div>


            <h1 class="text-2xl font-bold text-slate-900">
                Category Details
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                View complete category information.
            </p>

        </div>


        <div class="flex gap-2">

            <a
                href="{{ route('categories.index') }}"
                class="inline-flex items-center gap-2
                       rounded-lg border border-slate-300
                       bg-white px-4 py-2.5
                       text-sm font-semibold text-slate-700
                       hover:bg-slate-50"
            >
                Back
            </a>


            @can('categories.update')

                <a
                    href="{{ route('categories.edit', $category) }}"
                    class="inline-flex items-center gap-2
                           rounded-lg bg-blue-600
                           px-4 py-2.5 text-sm
                           font-semibold text-white
                           hover:bg-blue-700"
                >

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                    </svg>

                    Edit Category

                </a>

            @endcan

        </div>

    </div>


    {{-- Main Card --}}
    <section
        class="overflow-hidden rounded-xl
               border border-slate-200
               bg-white shadow-sm"
    >

        {{-- Category Top --}}
        <div
            class="flex items-center gap-4
                   border-b border-slate-200
                   px-6 py-6"
        >

            <div
                class="flex h-16 w-16 shrink-0
                       items-center justify-center
                       rounded-xl bg-blue-50
                       text-2xl font-bold text-blue-600"
            >
                {{ strtoupper(substr($category->name, 0, 1)) }}
            </div>


            <div>

                <h2 class="text-xl font-bold text-slate-900">
                    {{ $category->name }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Category ID #{{ $category->id }}
                </p>

            </div>

        </div>


        {{-- Information --}}
        <div class="grid gap-0 md:grid-cols-2">

            <div
                class="border-b border-slate-100
                       px-6 py-5 md:border-r"
            >

                <div
                    class="text-xs font-semibold uppercase
                           tracking-wide text-slate-400"
                >
                    Category Name
                </div>

                <div class="mt-2 font-semibold text-slate-900">
                    {{ $category->name }}
                </div>

            </div>


            <div
                class="border-b border-slate-100
                       px-6 py-5"
            >

                <div
                    class="text-xs font-semibold uppercase
                           tracking-wide text-slate-400"
                >
                    Created At
                </div>

                <div class="mt-2 text-slate-700">
                    {{ $category->created_at?->format('d M Y, h:i A') }}
                </div>

            </div>


            <div
                class="border-b border-slate-100
                       px-6 py-5 md:border-r"
            >

                <div
                    class="text-xs font-semibold uppercase
                           tracking-wide text-slate-400"
                >
                    Last Updated
                </div>

                <div class="mt-2 text-slate-700">
                    {{ $category->updated_at?->format('d M Y, h:i A') }}
                </div>

            </div>


            <div
                class="border-b border-slate-100
                       px-6 py-5"
            >

                <div
                    class="text-xs font-semibold uppercase
                           tracking-wide text-slate-400"
                >
                    Company ID
                </div>

                <div class="mt-2 text-slate-700">
                    {{ $category->company_id }}
                </div>

            </div>

        </div>


        {{-- Description --}}
        <div class="px-6 py-6">

            <div
                class="text-xs font-semibold uppercase
                       tracking-wide text-slate-400"
            >
                Description
            </div>


            @if($category->description)

                <p
                    class="mt-3 whitespace-pre-line
                           leading-7 text-slate-700"
                >
                    {{ $category->description }}
                </p>

            @else

                <p class="mt-3 text-sm italic text-slate-400">
                    No description added.
                </p>

            @endif

        </div>

    </section>


    {{-- Bottom Delete --}}
    @can('categories.delete')

        <section
            class="rounded-xl border border-rose-200
                   bg-rose-50 p-5"
        >

            <div
                class="flex flex-col gap-4
                       sm:flex-row sm:items-center
                       sm:justify-between"
            >

                <div>

                    <h3 class="font-bold text-rose-800">
                        Delete Category
                    </h3>

                    <p class="mt-1 text-sm text-rose-600">
                        Once deleted, this category cannot be recovered.
                    </p>

                </div>


                <form
                    action="{{ route('categories.destroy', $category) }}"
                    method="POST"
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
                        class="rounded-lg bg-rose-600
                               px-4 py-2.5 text-sm
                               font-semibold text-white
                               hover:bg-rose-700"
                    >
                        Delete Category
                    </button>

                </form>

            </div>

        </section>

    @endcan

</div>

@endsection