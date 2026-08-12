@extends('layouts.crm')

@section('title', 'Edit Company')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex items-center justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Edit Company
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $company->name }}
            </p>

        </div>

        <a
            href="{{ route('companies.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700"
        >
            Back
        </a>

    </div>


    @if($errors->any())

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">

            <ul class="list-disc space-y-1 pl-5 text-sm text-rose-700">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- Company Information --}}
    <form
        method="POST"
        action="{{ route('companies.update', $company) }}"
        class="space-y-6"
    >

        @csrf
        @method('PUT')


        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    Company Information
                </h2>

            </div>


            <div class="grid gap-5 p-5 md:grid-cols-2">

                <div>

                    <label class="mb-1.5 block text-sm font-medium">
                        Company Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $company->name) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium">
                        Company Code *
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ old('code', $company->code) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 uppercase"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $company->email) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $company->phone) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-1.5 block text-sm font-medium">
                        Address
                    </label>

                    <textarea
                        name="address"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >{{ old('address', $company->address) }}</textarea>

                </div>


                <div class="md:col-span-2">

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-4">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(
                                old(
                                    'is_active',
                                    $company->is_active
                                )
                            )
                            class="h-4 w-4"
                        >

                        <div>

                            <div class="font-medium text-slate-900">
                                Active Company
                            </div>

                            <div class="text-xs text-slate-500">
                                Inactive karne par company ko CRM use nahi karne dena chahiye.
                            </div>

                        </div>

                    </label>

                </div>

            </div>

        </section>


        <div class="flex justify-end">

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                Update Company
            </button>

        </div>

    </form>


    {{-- Company Summary --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h2 class="font-bold text-slate-900">
                Company Structure
            </h2>

        </div>


        <div class="grid gap-4 p-5 md:grid-cols-3">

            <div class="rounded-lg bg-slate-50 p-4">

                <div class="text-sm text-slate-500">
                    Branches
                </div>

                <div class="mt-1 text-2xl font-bold">
                    {{ $company->branches->count() }}
                </div>

            </div>


            <div class="rounded-lg bg-slate-50 p-4">

                <div class="text-sm text-slate-500">
                    Employees
                </div>

                <div class="mt-1 text-2xl font-bold">
                    {{ $company->users->count() }}
                </div>

            </div>


            <div class="rounded-lg bg-slate-50 p-4">

                <div class="text-sm text-slate-500">
                    Owner
                </div>

                @php

                    $owner = $company->users
                        ->first(
                            fn($user) =>
                            $user->hasRole(
                                'company_owner'
                            )
                        );

                @endphp

                <div class="mt-1 font-bold text-slate-900">
                    {{ $owner?->name ?? 'Not Assigned' }}
                </div>

                <div class="text-xs text-slate-500">
                    {{ $owner?->email }}
                </div>

            </div>

        </div>

    </section>

</div>

@endsection