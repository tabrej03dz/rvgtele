@extends('layouts.crm')

@section('title', $title)

@section('content')

<div class="mx-auto max-w-4xl space-y-5">

    {{-- Page Header --}}
    <div
        class="flex flex-col gap-3
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
                    Add New
                </span>

            </div>


            <h1 class="text-2xl font-bold text-slate-900">
                Add Category
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Create a new category for your company.
            </p>

        </div>


        <a
            href="{{ route('categories.index') }}"
            class="inline-flex self-start items-center
                   gap-2 rounded-lg border border-slate-300
                   bg-white px-4 py-2.5
                   text-sm font-semibold text-slate-700
                   hover:bg-slate-50"
        >

            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="m15 18-6-6 6-6"/>
            </svg>

            Back

        </a>

    </div>


    {{-- Validation Errors --}}
    @if($errors->any())

        <div
            class="rounded-xl border border-rose-200
                   bg-rose-50 p-4"
        >

            <div class="font-semibold text-rose-800">
                Please fix the following errors:
            </div>

            <ul
                class="mt-2 list-inside list-disc
                       space-y-1 text-sm text-rose-700"
            >

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- Form Card --}}
    <section
        class="overflow-hidden rounded-xl
               border border-slate-200
               bg-white shadow-sm"
    >

        <div class="border-b border-slate-200 px-6 py-5">

            <h2 class="font-bold text-slate-900">
                Category Information
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Enter basic details of the category.
            </p>

        </div>


        <form
            action="{{ route('categories.store') }}"
            method="POST"
            class="p-6"
        >

            @csrf

            @include('categories._form')

        </form>

    </section>

</div>

@endsection