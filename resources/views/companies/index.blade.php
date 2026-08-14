@extends('layouts.crm')

@section('title', 'Companies')

@section('content')

<div class="mx-auto max-w-[1600px] space-y-6">

    {{-- ===================================================== --}}
    {{-- Header --}}
    {{-- ===================================================== --}}

    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Companies
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Manage all businesses using TeleCRM.
            </p>

        </div>


        @can('companies.create')
        <a
            href="{{ route('companies.create') }}"
            class="
                inline-flex
                items-center
                justify-center
                rounded-lg
                bg-indigo-600
                px-5
                py-2.5
                text-sm
                font-semibold
                text-white
                hover:bg-indigo-700
            "
        >
            + Add Company
        </a>
        @endcan

    </div>


    {{-- ===================================================== --}}
    {{-- Success Message --}}
    {{-- ===================================================== --}}

    @if(session('success'))

        <div
            class="
                rounded-lg
                border
                border-emerald-200
                bg-emerald-50
                px-4
                py-3
                text-sm
                text-emerald-700
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- Error Message --}}
    {{-- ===================================================== --}}

    @if(session('error'))

        <div
            class="
                rounded-lg
                border
                border-rose-200
                bg-rose-50
                px-4
                py-3
                text-sm
                text-rose-700
            "
        >
            {{ session('error') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- Search --}}
    {{-- ===================================================== --}}

    <div
        class="
            rounded-xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        "
    >

        <form
            method="GET"
            action="{{ route('companies.index') }}"
            class="grid gap-3 md:grid-cols-[1fr_200px_auto]"
        >

            {{-- Search --}}

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search company, code, email or phone..."
                class="
                    rounded-lg
                    border
                    border-slate-300
                    px-4
                    py-2.5
                    text-sm
                    focus:border-indigo-500
                    focus:ring-indigo-500
                "
            >


            {{-- Status --}}

            <select
                name="status"
                class="
                    rounded-lg
                    border
                    border-slate-300
                    px-4
                    py-2.5
                    text-sm
                "
            >

                <option value="">
                    All Status
                </option>

                <option
                    value="active"
                    @selected(request('status') === 'active')
                >
                    Active
                </option>

                <option
                    value="inactive"
                    @selected(request('status') === 'inactive')
                >
                    Inactive
                </option>

            </select>


            {{-- Search Button --}}

            <button
                type="submit"
                class="
                    rounded-lg
                    bg-slate-900
                    px-5
                    py-2.5
                    text-sm
                    font-semibold
                    text-white
                    hover:bg-slate-800
                "
            >
                Search
            </button>

        </form>

    </div>


    {{-- ===================================================== --}}
    {{-- Company Table --}}
    {{-- ===================================================== --}}

    <div
        class="
            overflow-hidden
            rounded-xl
            border
            border-slate-200
            bg-white
            shadow-sm
        "
    >

        {{-- Table Heading --}}

        <div class="border-b border-slate-200 px-5 py-4">

            <h2 class="font-bold text-slate-900">
                Company List
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Total {{ $companies->total() }} companies
            </p>

        </div>


        {{-- ===================================================== --}}
        {{-- Table --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1250px] text-sm">

                {{-- ===================================================== --}}
                {{-- Head --}}
                {{-- ===================================================== --}}

                <thead class="bg-slate-50">

                    <tr
                        class="
                            border-b
                            text-left
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >

                        <th class="px-4 py-3">
                            #
                        </th>

                        <th class="px-4 py-3">
                            Company
                        </th>

                        <th class="px-4 py-3">
                            Contact
                        </th>

                        <th class="px-4 py-3">
                            Branches
                        </th>

                        <th class="px-4 py-3">
                            Teams
                        </th>

                        <th class="px-4 py-3">
                            Employees
                        </th>

                        <th class="px-4 py-3">
                            Leads
                        </th>

                        <th class="px-4 py-3">
                            Status
                        </th>

                        <th class="px-4 py-3 text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                {{-- ===================================================== --}}
                {{-- Body --}}
                {{-- ===================================================== --}}

                <tbody class="divide-y divide-slate-100">

                    @forelse($companies as $company)

                        <tr class="hover:bg-slate-50">

                            {{-- Number --}}

                            <td class="px-4 py-4 text-slate-500">

                                {{ $companies->firstItem() + $loop->index }}

                            </td>


                            {{-- Company --}}

                            <td class="px-4 py-4">

                                <div
                                    class="
                                        font-semibold
                                        text-slate-900
                                    "
                                >
                                    {{ $company->name }}
                                </div>


                                <div class="mt-1">

                                    <span
                                        class="
                                            rounded
                                            bg-slate-100
                                            px-2
                                            py-1
                                            text-xs
                                            font-medium
                                            text-slate-600
                                        "
                                    >
                                        {{ $company->code }}
                                    </span>

                                </div>

                            </td>


                            {{-- Contact --}}

                            <td class="px-4 py-4">

                                <div>
                                    {{ $company->email ?: '—' }}
                                </div>

                                <div class="mt-1 text-slate-500">
                                    {{ $company->phone ?: '—' }}
                                </div>

                            </td>


                            {{-- Branches --}}

                            <td class="px-4 py-4">

                                <span
                                    class="
                                        inline-flex
                                        min-w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-slate-100
                                        px-2
                                        py-1
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    {{ $company->branches_count }}
                                </span>

                            </td>


                            {{-- Teams --}}

                            <td class="px-4 py-4">

                                <span
                                    class="
                                        inline-flex
                                        min-w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-slate-100
                                        px-2
                                        py-1
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    {{ $company->teams_count }}
                                </span>

                            </td>


                            {{-- Employees --}}

                            <td class="px-4 py-4">

                                <span
                                    class="
                                        inline-flex
                                        min-w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-blue-50
                                        px-2
                                        py-1
                                        font-semibold
                                        text-blue-700
                                    "
                                >
                                    {{ $company->users_count }}
                                </span>

                            </td>


                            {{-- Leads --}}

                            <td class="px-4 py-4">

                                <span
                                    class="
                                        inline-flex
                                        min-w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-indigo-50
                                        px-2
                                        py-1
                                        font-semibold
                                        text-indigo-700
                                    "
                                >
                                    {{ $company->leads_count }}
                                </span>

                            </td>


                            {{-- Status --}}

                            <td class="px-4 py-4">

                                @if($company->is_active)

                                    <span
                                        class="
                                            rounded-full
                                            bg-emerald-100
                                            px-3
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-emerald-700
                                        "
                                    >
                                        Active
                                    </span>

                                @else

                                    <span
                                        class="
                                            rounded-full
                                            bg-rose-100
                                            px-3
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-rose-700
                                        "
                                    >
                                        Inactive
                                    </span>

                                @endif

                            </td>


                            {{-- ===================================================== --}}
                            {{-- Actions --}}
                            {{-- ===================================================== --}}

                            <td class="px-4 py-4">

                                <div
                                    class="
                                        flex
                                        items-center
                                        justify-end
                                        gap-2
                                    "
                                >

                                    {{-- ===================================================== --}}
                                    {{-- View Complete Business --}}
                                    {{-- ===================================================== --}}

                                    @can('companies.view-business')
                                    <form
                                        method="POST"
                                        action="{{ route('companies.view-business', $company) }}"
                                    >

                                        @csrf


                                        <button
                                            type="submit"
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                whitespace-nowrap
                                                rounded-lg
                                                bg-indigo-600
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-white
                                                shadow-sm
                                                hover:bg-indigo-700
                                            "
                                        >

                                            {{-- Eye Icon --}}

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                class="h-4 w-4"
                                            >
                                                <path
                                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                                                />

                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="3"
                                                />
                                            </svg>

                                            View Business

                                        </button>

                                    </form>
                                    @endcan


                                    {{-- Edit --}}

                                    @can('companies.update')
                                    <a
                                        href="{{ route('companies.edit', $company) }}"
                                        class="
                                            rounded-lg
                                            border
                                            border-slate-300
                                            bg-white
                                            px-3
                                            py-2
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                            hover:bg-slate-50
                                        "
                                    >
                                        Edit
                                    </a>
                                    @endcan


                                    {{-- Delete --}}

                                    @if(
                                        (int) auth()->user()->company_id
                                        !==
                                        (int) $company->id
                                    )

                                    @can('companies.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('companies.destroy', $company) }}"
                                            onsubmit="
                                                return confirm(
                                                    'Is company ko remove karna hai?'
                                                );
                                            "
                                        >

                                            @csrf

                                            @method('DELETE')


                                            <button
                                                type="submit"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-rose-200
                                                    bg-rose-50
                                                    px-3
                                                    py-2
                                                    text-xs
                                                    font-semibold
                                                    text-rose-600
                                                    hover:bg-rose-100
                                                "
                                            >
                                                Delete
                                            </button>

                                        </form>
                                    @endcan

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        {{-- ===================================================== --}}
                        {{-- Empty --}}
                        {{-- ===================================================== --}}

                        <tr>

                            <td
                                colspan="9"
                                class="
                                    px-5
                                    py-12
                                    text-center
                                    text-slate-500
                                "
                            >
                                No company found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================================================== --}}
        {{-- Pagination --}}
        {{-- ===================================================== --}}

        @if($companies->hasPages())

            <div class="border-t border-slate-200 p-4">

                {{ $companies->links() }}

            </div>

        @endif

    </div>

</div>

@endsection