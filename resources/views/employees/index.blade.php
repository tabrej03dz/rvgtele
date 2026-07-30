@extends('layouts.crm', [
    'title' => 'Employees',
])

@section('content')
<div class="mx-auto max-w-7xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Employees
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Team members, roles, branches aur status manage karein.
            </p>
        </div>

        <a
            href="{{ route('employees.create') }}"
            class="inline-flex items-center gap-2 self-start rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:self-auto"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Employee
        </a>
    </div>

    {{-- Alerts --}}
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

    {{-- Employees Table --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">
                    Team Members
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }}
                    of {{ $employees->total() }}
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3">Team</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($employees as $employee)
                        @php
                            $roles = $employee->getRoleNames();
                            $initials = collect(explode(' ', trim($employee->name)))
                                ->filter()
                                ->map(fn ($part) => mb_substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold uppercase text-blue-700">
                                        {{ $initials ?: 'E' }}
                                    </div>

                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-slate-900">
                                            {{ $employee->name }}
                                        </div>

                                        <div class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ $employee->email ?: 'No email' }}
                                        </div>

                                        @if ($employee->employee_code)
                                            <div class="mt-0.5 text-xs text-slate-400">
                                                {{ $employee->employee_code }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if ($roles->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($roles as $role)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
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

                            <td class="px-4 py-3 text-slate-700">
                                {{ $employee->branch?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-slate-700">
                                {{ $employee->team?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($employee->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('employees.edit', $employee) }}"
                                    class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="font-semibold text-slate-700">
                                    No employees found
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    New employee add karke team setup karein.
                                </div>

                                <a
                                    href="{{ route('employees.create') }}"
                                    class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Add Employee
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $employees->links() }}
            </div>
        @endif
    </section>
</div>
@endsection