@extends('layouts.crm')

@section('title', 'Add Company')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">

        <div>

            <h1 class="text-2xl font-bold text-slate-900">
                Add New Company
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Company, Head Office aur Company Owner ek saath create hoga.
            </p>

        </div>

        <a
            href="{{ route('companies.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Back
        </a>

    </div>


    {{-- Errors --}}
    @if($errors->any())

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">

            <div class="font-semibold text-rose-800">
                Form submit nahi hua
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('companies.store') }}"
        class="space-y-6"
    >

        @csrf


        {{-- Company Details --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    1. Company Details
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Main business information.
                </p>

            </div>


            <div class="grid gap-5 p-5 md:grid-cols-2">

                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Name
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Example: ABC Jewellery"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Code
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        required
                        placeholder="Example: ABC"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 uppercase"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Unique code. Example RVG, NEEL, ABC.
                    </p>

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="company@example.com"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="9876543210"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Company Address
                    </label>

                    <textarea
                        name="address"
                        rows="3"
                        placeholder="Complete address"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >{{ old('address') }}</textarea>

                </div>

            </div>

        </section>


        {{-- Head Office --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    2. Head Office / Default Branch
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Company create hote hi ye branch automatically create hogi.
                </p>

            </div>


            <div class="grid gap-5 p-5 md:grid-cols-2">

                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Branch Name
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="branch_name"
                        value="{{ old('branch_name', 'Head Office') }}"
                        required
                        placeholder="Head Office"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Branch Code
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="branch_code"
                        value="{{ old('branch_code', 'HEAD') }}"
                        required
                        placeholder="HEAD"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 uppercase"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Branch Phone
                    </label>

                    <input
                        type="text"
                        name="branch_phone"
                        value="{{ old('branch_phone') }}"
                        placeholder="Optional"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Branch Address
                    </label>

                    <textarea
                        name="branch_address"
                        rows="3"
                        placeholder="Leave blank to use company address"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >{{ old('branch_address') }}</textarea>

                </div>

            </div>

        </section>


        {{-- Owner --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-bold text-slate-900">
                    3. Company Owner Login
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Is email aur password se company ka admin login karega.
                </p>

            </div>


            <div class="grid gap-5 p-5 md:grid-cols-2">

                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Owner Name
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="owner_name"
                        value="{{ old('owner_name') }}"
                        required
                        placeholder="Owner name"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Owner Phone
                    </label>

                    <input
                        type="text"
                        name="owner_phone"
                        value="{{ old('owner_phone') }}"
                        placeholder="9876543210"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Owner Login Email
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="email"
                        name="owner_email"
                        value="{{ old('owner_email') }}"
                        required
                        placeholder="owner@example.com"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Password
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="password"
                        name="owner_password"
                        required
                        minlength="8"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>


                <div>

                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Confirm Password
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="password"
                        name="owner_password_confirmation"
                        required
                        minlength="8"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5"
                    >

                </div>

            </div>

        </section>


        {{-- Auto Setup --}}
        <section class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">

            <h3 class="font-bold text-indigo-900">
                Automatically Create Hoga
            </h3>

            <div class="mt-3 grid gap-2 text-sm text-indigo-800 md:grid-cols-2">

                <div>✓ Head Office Branch</div>
                <div>✓ Company Owner</div>

                <div>✓ Sales Team A</div>
                <div>✓ Default Lead Sources</div>

                <div>✓ Lead Statuses</div>
                <div>✓ Call Dispositions</div>

                <div>✓ Sales Pipeline</div>
                <div>✓ Pipeline Stages</div>

                <div>✓ Default Product</div>
                <div>✓ Default Campaign</div>

            </div>

        </section>


        {{-- Save --}}
        <div class="flex justify-end gap-3">

            <a
                href="{{ route('companies.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                Create Company
            </button>

        </div>

    </form>

</div>

@endsection