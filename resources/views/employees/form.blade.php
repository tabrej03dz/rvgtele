@extends('layouts.crm', [
    'title' => $employee->exists ? 'Edit Employee' : 'Create Employee',
])

@section('content')
@php
    $isEdit = $employee->exists;

    $fieldClass = function (string $name) use ($errors) {
        return $errors->has($name)
            ? 'border-rose-300 bg-rose-50 focus:border-rose-500 focus:ring-rose-500'
            : 'border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500';
    };
@endphp

<div class="mx-auto max-w-5xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a
                    href="{{ route('employees.index') }}"
                    class="hover:text-blue-600"
                >
                    Employees
                </a>
                <span>/</span>
                <span>{{ $isEdit ? 'Edit' : 'Create' }}</span>
            </div>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                {{ $isEdit ? 'Edit Employee' : 'Create Employee' }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Employee account, role aur team details manage karein.
            </p>
        </div>

        <a
            href="{{ route('employees.index') }}"
            class="inline-flex self-start rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto"
        >
            Back
        </a>
    </div>

    {{-- Validation Summary --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
            <div class="font-semibold text-rose-800">
                Form submit nahi hua
            </div>

            <p class="mt-1 text-sm text-rose-700">
                Highlighted fields ko correct karke dobara save karein.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $isEdit ? route('employees.update', $employee) : route('employees.store') }}"
        class="space-y-5"
    >
        @csrf

        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- Account Details --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Account Details
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Employee ki login aur contact information.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Employee Name <span class="text-rose-500">*</span>
                    </span>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $employee->name) }}"
                        placeholder="Example: Rahul Sharma"
                        autocomplete="name"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('name') }}"
                    >

                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Email Address <span class="text-rose-500">*</span>
                    </span>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $employee->email) }}"
                        placeholder="employee@example.com"
                        autocomplete="email"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('email') }}"
                    >

                    @error('email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Phone Number
                    </span>

                    <input
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $employee->phone) }}"
                        placeholder="Example: 9876543210"
                        inputmode="numeric"
                        autocomplete="tel"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('phone') }}"
                    >

                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Employee Code
                    </span>

                    <input
                        type="text"
                        name="employee_code"
                        value="{{ old('employee_code', $employee->employee_code) }}"
                        placeholder="Example: EMP-001"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('employee_code') }}"
                    >

                    @error('employee_code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Password
                        @unless ($isEdit)
                            <span class="text-rose-500">*</span>
                        @endunless
                    </span>

                    <input
                        type="password"
                        name="password"
                        placeholder="{{ $isEdit ? 'Leave blank to keep existing password' : 'Enter secure password' }}"
                        autocomplete="new-password"
                        {{ $isEdit ? '' : 'required' }}
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('password') }}"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $isEdit
                            ? 'Password change nahi karna ho to field blank chhod dein.'
                            : 'Strong password use karein.' }}
                    </p>

                    @error('password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- Work Details --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Work Details
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Role, branch aur team assignment.
                </p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Role <span class="text-rose-500">*</span>
                    </span>

                    <select
                        name="role"
                        required
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('role') }}"
                    >
                        <option value="">Select role</option>

                        @foreach ($roles as $role)
                            <option
                                value="{{ $role->name }}"
                                @selected(
                                    old('role', $employee->roles->first()?->name) === $role->name
                                )
                            >
                                {{ \Illuminate\Support\Str::headline($role->name) }}
                            </option>
                        @endforeach
                    </select>

                    @if ($roles->isEmpty())
                        <p class="mt-1 text-xs font-medium text-amber-600">
                            Aapke role ke niche koi role available nahi hai.
                        </p>
                    @else
                        <p class="mt-1 text-xs text-slate-500">
                            Super Admin ko database ke saare roles dikhte hain. Baaki users ko sirf apne se chhote roles dikhte hain.
                        </p>
                    @endif

                    @error('role')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Branch
                    </span>

                    <select
                        name="branch_id"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('branch_id') }}"
                    >
                        <option value="">Select branch</option>

                        @foreach ($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                @selected(
                                    (string) old('branch_id', $employee->branch_id) ===
                                    (string) $branch->id
                                )
                            >
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('branch_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">
                        Team
                    </span>

                    <select
                        name="team_id"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm {{ $fieldClass('team_id') }}"
                    >
                        <option value="">Select team</option>

                        @foreach ($teams as $team)
                            <option
                                value="{{ $team->id }}"
                                @selected(
                                    (string) old('team_id', $employee->team_id) ===
                                    (string) $team->id
                                )
                            >
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('team_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </section>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-2 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:justify-end">
            <a
                href="{{ route('employees.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            @can($isEdit ? 'employees.update' : 'employees.create')
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                {{ $isEdit ? 'Update Employee' : 'Save Employee' }}
            </button>
            @endcan
        </div>
    </form>
</div>
@endsection