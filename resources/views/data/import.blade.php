@extends('layouts.crm', ['title' => 'Import Data'])

@section('content')

@include('data.partials.styles')

<div class="software-ui mx-auto w-full max-w-4xl space-y-3">

    <section class="software-toolbar">

        <div
            class="
                flex
                items-center
                justify-between
                gap-4
                px-4
                py-4
            "
        >

            <div class="flex items-center gap-3">

                <span class="data-toolbar-icon">
                    <i data-lucide="upload"></i>
                </span>

                <div>

                    <h1 class="text-lg font-bold text-slate-900">
                        Bulk Import Data
                    </h1>

                    <p class="mt-1 text-[11px] text-slate-500">
                        CSV file se multiple records import karein.
                    </p>

                </div>

            </div>

            <a
                href="{{ route('data.index') }}"
                class="software-btn"
            >
                <i data-lucide="arrow-left"></i>
                BACK
            </a>

        </div>

    </section>


    <form
        method="POST"
        action="{{ route('data.import.store') }}"
        enctype="multipart/form-data"
        class="space-y-3"
    >

        @csrf


        <section class="software-panel">

            <div class="software-panel-title">

                <span class="panel-heading-label">

                    <span class="panel-heading-icon">
                        <i data-lucide="file-spreadsheet"></i>
                    </span>

                    Import Settings

                </span>

            </div>


            <div class="grid gap-4 p-4 md:grid-cols-2">

                <div>

                    <label class="software-label">
                        Company *
                    </label>

                    <select
                        name="company_id"
                        required
                        class="w-full"
                    >

                        <option value="">
                            Select Company
                        </option>

                        @foreach($companies as $company)

                            <option
                                value="{{ $company->id }}"
                                @selected(
                                    old('company_id')
                                    == $company->id
                                )
                            >
                                {{ $company->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label class="software-label">
                        Default Category
                    </label>

                    <input
                        type="text"
                        name="category"
                        value="{{ old('category') }}"
                        class="w-full"
                        placeholder="Jewellery, Solar, Furniture..."
                    >

                    <div class="mt-1 text-[10px] text-slate-500">
                        Optional. Agar diya to sab imported records me ye category lagegi.
                    </div>

                </div>


                <div class="md:col-span-2">

                    <label class="software-label">
                        CSV File *
                    </label>

                    <input
                        type="file"
                        name="file"
                        accept=".csv,text/csv"
                        required
                        class="
                            block
                            w-full
                            rounded-lg
                            border
                            border-slate-300
                            bg-white
                            p-3
                            text-xs
                        "
                    >

                </div>

            </div>

        </section>


        <section class="software-panel">

            <div class="software-panel-title">

                <span class="panel-heading-label">

                    <span class="panel-heading-icon">
                        <i data-lucide="table-properties"></i>
                    </span>

                    Supported CSV Columns

                </span>

            </div>


            <div class="p-4">

                <div class="grid gap-2 md:grid-cols-3">

                    @foreach([
                        'name',
                        'company_name',
                        'mobile',
                        'alternate_mobile',
                        'whatsapp_number',
                        'email',
                        'category',
                        'lead_source',
                        'campaign',
                        'address',
                        'city',
                        'district',
                        'state',
                        'pincode',
                        'industry',
                        'required_product',
                        'preferred_language',
                        'estimated_budget',
                        'remarks',
                    ] as $column)

                        <div
                            class="
                                rounded-lg
                                border
                                border-slate-200
                                bg-slate-50
                                px-3
                                py-2
                                font-mono
                                text-[10px]
                                text-slate-700
                            "
                        >
                            {{ $column }}
                        </div>

                    @endforeach

                </div>

            </div>

        </section>


        <section class="software-panel">

            <div class="flex justify-end gap-2 p-4">

                <a
                    href="{{ route('data.index') }}"
                    class="software-btn"
                >
                    CANCEL
                </a>

                <button
                    type="submit"
                    class="software-btn software-btn-primary"
                >
                    <i data-lucide="upload"></i>
                    IMPORT DATA
                </button>

            </div>

        </section>

    </form>

</div>

@endsection