@extends('layouts.crm', [
    'title' => 'Employees',
])

@section('content')

<div class="mx-auto max-w-7xl space-y-5">

    @php

        $loggedInUser = auth()->user();


        /*
        |--------------------------------------------------------------------------
        | Role Hierarchy
        |--------------------------------------------------------------------------
        */

        $roleHierarchy = [

            'employee'    => 1,

            'team_leader' => 2,

            'admin'       => 3,

            'owner'       => 4,

            'super_admin' => 5,

        ];


        /*
        |--------------------------------------------------------------------------
        | Logged In User Highest Role Level
        |--------------------------------------------------------------------------
        */

        $loggedInLevel = 0;


        foreach (
            $loggedInUser->getRoleNames()
            as $roleName
        ) {

            $level =
                $roleHierarchy[
                    $roleName
                ] ?? 0;


            if (
                $level >
                $loggedInLevel
            ) {

                $loggedInLevel =
                    $level;
            }
        }

    @endphp


    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">

                Employees

            </h1>


            <p class="mt-1 text-sm text-slate-500">

                Apni branch ke team members, roles aur status manage karein.

            </p>

        </div>


        <a
            href="{{ route('employees.create') }}"
            class="inline-flex items-center gap-2 self-start rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:self-auto"
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


            Add Employee

        </a>

    </div>


    {{-- Success Alert --}}
    @if (session('success'))

        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">

            <svg
                class="mt-0.5 h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >

                <path d="M20 6 9 17l-5-5"/>

            </svg>


            <span>

                {{ session('success') }}

            </span>

        </div>

    @endif


    {{-- Error Alert --}}
    @if (session('error'))

        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

            <svg
                class="mt-0.5 h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >

                <circle
                    cx="12"
                    cy="12"
                    r="10"
                />

                <path d="M12 8v4M12 16h.01"/>

            </svg>


            <span>

                {{ session('error') }}

            </span>

        </div>

    @endif


    {{-- Employee Table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">


        {{-- Table Header --}}
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="font-bold text-slate-900">

                    Team Members

                </h2>


                <p class="mt-0.5 text-sm text-slate-500">

                    {{ $employees->firstItem() ?? 0 }}

                    –

                    {{ $employees->lastItem() ?? 0 }}

                    of

                    {{ $employees->total() }}

                </p>

            </div>


            <div class="flex flex-col items-start gap-1 text-xs text-slate-400 sm:items-end">

                <div>

                    Logged in as:

                    <span class="font-semibold text-slate-600">

                        {{ $loggedInUser->name }}

                    </span>

                </div>


                @if ($loggedInUser->branch)

                    <div>

                        Branch:

                        <span class="font-semibold text-slate-600">

                            {{ $loggedInUser->branch->name }}

                        </span>

                    </div>

                @endif

            </div>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">


                {{-- Table Head --}}
                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                        <th class="px-4 py-3">

                            Employee

                        </th>


                        <th class="px-4 py-3">

                            Role

                        </th>


                        <th class="px-4 py-3">

                            Branch

                        </th>


                        <th class="px-4 py-3">

                            Team

                        </th>


                        <th class="px-4 py-3">

                            Status

                        </th>


                        <th class="px-4 py-3 text-right">

                            Action

                        </th>

                    </tr>

                </thead>


                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-100">


                    @forelse ($employees as $employee)

                        @php

                            /*
                            |--------------------------------------------------------------------------
                            | Roles
                            |--------------------------------------------------------------------------
                            */

                            $roles =
                                $employee->getRoleNames();


                            /*
                            |--------------------------------------------------------------------------
                            | Employee Highest Role Level
                            |--------------------------------------------------------------------------
                            */

                            $employeeLevel = 0;


                            foreach (
                                $roles
                                as $roleName
                            ) {

                                $level =
                                    $roleHierarchy[
                                        $roleName
                                    ] ?? 0;


                                if (
                                    $level >
                                    $employeeLevel
                                ) {

                                    $employeeLevel =
                                        $level;
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Employee Initials
                            |--------------------------------------------------------------------------
                            */

                            $initials = collect(
                                explode(
                                    ' ',
                                    trim(
                                        $employee->name
                                    )
                                )
                            )
                                ->filter()
                                ->map(
                                    fn ($part) =>
                                        mb_substr(
                                            $part,
                                            0,
                                            1
                                        )
                                )
                                ->take(2)
                                ->implode('');


                            /*
                            |--------------------------------------------------------------------------
                            | Same Branch
                            |--------------------------------------------------------------------------
                            */

                            $sameBranch = false;


                            if (
                                empty(
                                    $loggedInUser->branch_id
                                )
                                &&
                                empty(
                                    $employee->branch_id
                                )
                            ) {

                                $sameBranch = true;

                            } elseif (
                                !empty(
                                    $loggedInUser->branch_id
                                )
                                &&
                                !empty(
                                    $employee->branch_id
                                )
                                &&
                                (int) $loggedInUser->branch_id ===
                                (int) $employee->branch_id
                            ) {

                                $sameBranch = true;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Dashboard Access
                            |--------------------------------------------------------------------------
                            */

                            $canViewDashboard = false;


                            /*
                            |--------------------------------------------------------------------------
                            | Basic Requirement
                            |--------------------------------------------------------------------------
                            |
                            | Same Branch
                            | Different User
                            | Target Lower Role
                            |
                            */

                            if (
                                $sameBranch
                                &&
                                (int) $loggedInUser->id !==
                                (int) $employee->id
                                &&
                                $employeeLevel <
                                $loggedInLevel
                            ) {

                                /*
                                |--------------------------------------------------------------------------
                                | Normal Employee
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $loggedInUser->hasRole(
                                        'employee'
                                    )
                                ) {

                                    $canViewDashboard =
                                        false;

                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Team Leader
                                |--------------------------------------------------------------------------
                                */

                                elseif (
                                    $loggedInUser->hasRole(
                                        'team_leader'
                                    )
                                ) {

                                    $canViewDashboard =

                                        !empty(
                                            $loggedInUser->team_id
                                        )

                                        &&

                                        !empty(
                                            $employee->team_id
                                        )

                                        &&

                                        (int) $loggedInUser->team_id ===
                                        (int) $employee->team_id

                                        &&

                                        $employee->hasRole(
                                            'employee'
                                        );

                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Admin / Owner / Super Admin
                                |--------------------------------------------------------------------------
                                */

                                else {

                                    $canViewDashboard =
                                        true;
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Edit Permission
                            |--------------------------------------------------------------------------
                            |
                            | Same branch
                            | Same or lower role
                            |
                            */

                            $canEdit =

                                $sameBranch

                                &&

                                $employeeLevel <=
                                $loggedInLevel;

                        @endphp


                        <tr class="transition hover:bg-slate-50/80">


                            {{-- Employee --}}
                            <td class="px-4 py-3">

                                <div class="flex items-center gap-3">


                                    {{-- Avatar --}}
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold uppercase text-blue-700">

                                        {{ $initials ?: 'E' }}

                                    </div>


                                    {{-- Employee Details --}}
                                    <div class="min-w-0">


                                        {{-- Name --}}
                                        <div class="truncate font-semibold text-slate-900">

                                            {{ $employee->name }}


                                            @if (
                                                (int) $loggedInUser->id ===
                                                (int) $employee->id
                                            )

                                                <span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-blue-600">

                                                    You

                                                </span>

                                            @endif

                                        </div>


                                        {{-- Email --}}
                                        <div class="mt-0.5 truncate text-xs text-slate-500">

                                            {{ $employee->email ?: 'No email' }}

                                        </div>


                                        {{-- Phone --}}
                                        @if ($employee->phone)

                                            <div class="mt-0.5 truncate text-xs text-slate-400">

                                                {{ $employee->phone }}

                                            </div>

                                        @endif


                                        {{-- Employee Code --}}
                                        @if ($employee->employee_code)

                                            <div class="mt-1">

                                                <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">

                                                    {{ $employee->employee_code }}

                                                </span>

                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- Role --}}
                            <td class="px-4 py-3">


                                @if ($roles->isNotEmpty())

                                    <div class="flex flex-wrap gap-1.5">


                                        @foreach ($roles as $role)

                                            @php

                                                $roleClass = match ($role) {

                                                    'super_admin' =>
                                                        'bg-purple-50 text-purple-700 ring-purple-200',

                                                    'owner' =>
                                                        'bg-amber-50 text-amber-700 ring-amber-200',

                                                    'admin' =>
                                                        'bg-indigo-50 text-indigo-700 ring-indigo-200',

                                                    'team_leader' =>
                                                        'bg-cyan-50 text-cyan-700 ring-cyan-200',

                                                    'employee' =>
                                                        'bg-slate-100 text-slate-700 ring-slate-200',

                                                    default =>
                                                        'bg-slate-100 text-slate-700 ring-slate-200',
                                                };

                                            @endphp


                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $roleClass }}"
                                            >

                                                {{ \Illuminate\Support\Str::headline($role) }}

                                            </span>

                                        @endforeach


                                    </div>

                                @else

                                    <span class="text-slate-400">

                                        No role

                                    </span>

                                @endif

                            </td>


                            {{-- Branch --}}
                            <td class="px-4 py-3">


                                @if ($employee->branch)

                                    <div class="flex items-center gap-2 text-slate-700">

                                        <svg
                                            class="h-4 w-4 text-slate-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >

                                            <path d="M3 21h18"/>

                                            <path d="M6 21V7l6-4 6 4v14"/>

                                            <path d="M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>

                                        </svg>


                                        {{ $employee->branch->name }}

                                    </div>

                                @else

                                    <span class="text-slate-400">

                                        —

                                    </span>

                                @endif

                            </td>


                            {{-- Team --}}
                            <td class="px-4 py-3">


                                @if ($employee->team)

                                    <div class="flex items-center gap-2">


                                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-50 text-indigo-600">

                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >

                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>

                                                <circle
                                                    cx="9"
                                                    cy="7"
                                                    r="4"
                                                />

                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>

                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>

                                            </svg>

                                        </span>


                                        <span class="font-medium text-slate-700">

                                            {{ $employee->team->name }}

                                        </span>

                                    </div>

                                @else

                                    <span class="text-slate-400">

                                        —

                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td class="px-4 py-3">


                                @if ($employee->is_active)

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">

                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500">

                                        </span>

                                        Active

                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">

                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400">

                                        </span>

                                        Inactive

                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">


                                <div class="flex items-center justify-end gap-2">


                                    {{-- Employee View --}}
                                    @if ($canViewDashboard)

                                        <form
                                            method="POST"
                                            action="{{ route('employees.impersonate', $employee) }}"
                                            onsubmit="return confirm('Are you sure you want to open {{ addslashes($employee->name) }} account?')"
                                        >

                                            @csrf


                                            <button
                                                type="submit"
                                                title="Open employee account"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                            >

                                                <svg
                                                    class="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >

                                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>

                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3"
                                                    />

                                                </svg>


                                                Employee View

                                            </button>

                                        </form>

                                    @endif


                                    {{-- Edit --}}
                                    @if ($canEdit)

                                        <a
                                            href="{{ route('employees.edit', $employee) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
                                        >

                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >

                                                <path d="M12 20h9"/>

                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>

                                            </svg>


                                            Edit

                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                                    <svg
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >

                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>

                                        <circle
                                            cx="9"
                                            cy="7"
                                            r="4"
                                        />

                                        <path d="M19 8v6M22 11h-6"/>

                                    </svg>

                                </div>


                                <div class="mt-3 font-semibold text-slate-700">

                                    No employees found

                                </div>


                                <div class="mt-1 text-sm text-slate-500">

                                    Aapki branch aur role permission ke according koi employee available nahi hai.

                                </div>


                                <a
                                    href="{{ route('employees.create') }}"
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
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


                                    Add Employee

                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if ($employees->hasPages())

            <div class="border-t border-slate-200 px-5 py-4">

                {{ $employees->links() }}

            </div>

        @endif

    </section>

</div>

@endsection