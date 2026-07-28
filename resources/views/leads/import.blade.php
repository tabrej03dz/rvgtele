@extends('layouts.crm', [
    'title' => 'Import Leads',
])

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Excel Import Columns
    |--------------------------------------------------------------------------
    |
    | Column names exactly same rehne chahiye.
    | Required columns: name, mobile
    |
    */

    $importColumns = [
        [
            'name' => 'name',
            'required' => true,
            'format' => 'Text',
            'example' => 'Ravi Jewellers',
            'description' => 'Lead ya contact person ka naam.',
        ],
        [
            'name' => 'mobile',
            'required' => true,
            'format' => 'Text / Number',
            'example' => '9876543210',
            'description' => 'Primary mobile number. Duplicate checking isi number se hogi.',
        ],
        [
            'name' => 'alternate_mobile',
            'required' => false,
            'format' => 'Text / Number',
            'example' => '9123456780',
            'description' => 'Lead ka alternate mobile number.',
        ],
        [
            'name' => 'whatsapp_number',
            'required' => false,
            'format' => 'Text / Number',
            'example' => '9876543210',
            'description' => 'WhatsApp communication ke liye number.',
        ],
        [
            'name' => 'email',
            'required' => false,
            'format' => 'Valid Email',
            'example' => 'ravi@example.com',
            'description' => 'Lead ki email address.',
        ],
        [
            'name' => 'company_name',
            'required' => false,
            'format' => 'Text',
            'example' => 'Ravi Jewellers Pvt Ltd',
            'description' => 'Business ya company ka naam.',
        ],
        [
            'name' => 'city',
            'required' => false,
            'format' => 'Text',
            'example' => 'Kanpur',
            'description' => 'Lead ka city.',
        ],
        [
            'name' => 'district',
            'required' => false,
            'format' => 'Text',
            'example' => 'Kanpur Nagar',
            'description' => 'Lead ka district.',
        ],
        [
            'name' => 'state',
            'required' => false,
            'format' => 'Text',
            'example' => 'Uttar Pradesh',
            'description' => 'Lead ka state.',
        ],
        [
            'name' => 'pincode',
            'required' => false,
            'format' => 'Text / Number',
            'example' => '208001',
            'description' => '6 digit PIN code.',
        ],
        [
            'name' => 'required_product',
            'required' => false,
            'format' => 'Text',
            'example' => 'Telecalling CRM',
            'description' => 'Lead ko kis product/service ki requirement hai.',
        ],
        [
            'name' => 'estimated_budget',
            'required' => false,
            'format' => 'Number',
            'example' => '25000',
            'description' => 'Customer ka estimated budget.',
        ],
        [
            'name' => 'expected_deal_value',
            'required' => false,
            'format' => 'Number',
            'example' => '50000',
            'description' => 'Expected deal amount.',
        ],
        [
            'name' => 'expected_closing_date',
            'required' => false,
            'format' => 'YYYY-MM-DD',
            'example' => '2026-08-30',
            'description' => 'Deal close hone ki expected date.',
        ],
        [
            'name' => 'next_follow_up_at',
            'required' => false,
            'format' => 'YYYY-MM-DD HH:MM',
            'example' => '2026-07-30 16:00',
            'description' => 'Next follow-up ki date aur time.',
        ],
        [
            'name' => 'priority',
            'required' => false,
            'format' => 'Fixed Value',
            'example' => 'high',
            'description' => 'Allowed: low, normal, high, urgent, hot.',
        ],
        [
            'name' => 'temperature',
            'required' => false,
            'format' => 'Fixed Value',
            'example' => 'warm',
            'description' => 'Allowed: cold, warm, hot.',
        ],
        [
            'name' => 'lead_source',
            'required' => false,
            'format' => 'Existing Source Name',
            'example' => 'Website',
            'description' => 'CRM me existing Lead Source ka exact naam.',
        ],
        [
            'name' => 'lead_status',
            'required' => false,
            'format' => 'Existing Status Name',
            'example' => 'New',
            'description' => 'CRM me existing Lead Status ka exact naam.',
        ],
        [
            'name' => 'assigned_employee_email',
            'required' => false,
            'format' => 'Employee Email',
            'example' => 'telecaller@example.com',
            'description' => 'Lead assign karne ke liye active employee ki email.',
        ],
        [
            'name' => 'team',
            'required' => false,
            'format' => 'Existing Team Name',
            'example' => 'Sales Team A',
            'description' => 'CRM me existing Team ka exact naam.',
        ],
    ];

    $headingLine = collect($importColumns)
        ->pluck('name')
        ->implode(',');
@endphp

<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        selectedFileName: '',
        selectedFileSize: '',
        copied: false,

        fileChanged(event) {
            const file = event.target.files[0];

            if (!file) {
                this.selectedFileName = '';
                this.selectedFileSize = '';
                return;
            }

            this.selectedFileName = file.name;
            this.selectedFileSize = this.formatBytes(file.size);
        },

        formatBytes(bytes) {
            if (!bytes) {
                return '0 Bytes';
            }

            const units = [
                'Bytes',
                'KB',
                'MB',
                'GB'
            ];

            const index = Math.floor(
                Math.log(bytes) /
                Math.log(1024)
            );

            return (
                parseFloat(
                    (
                        bytes /
                        Math.pow(1024, index)
                    ).toFixed(2)
                ) +
                ' ' +
                units[index]
            );
        },

        async copyHeadings() {
            const headings = @js($headingLine);

            try {
                await navigator.clipboard.writeText(
                    headings
                );

                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                alert(
                    'Headings copy nahi ho saki. Download Template use karein.'
                );
            }
        }
    }"
>
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Import Leads
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Excel, XLS ya CSV file se ek saath multiple leads import karein.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                @click="copyHeadings()"
                class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
            >
                <span x-show="!copied">
                    Copy All Headings
                </span>

                <span
                    x-show="copied"
                    x-cloak
                >
                    Headings Copied ✓
                </span>
            </button>

            <a
                href="{{ route('leads.import.template') }}"
                class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
            >
                Download Excel Template
            </a>

            <a
                href="{{ route('leads.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Leads
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
            <div class="font-semibold">
                Import Process Completed
            </div>

            <div class="mt-1 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <h3 class="font-semibold text-rose-700">
                Import start nahi ho saka:
            </h3>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-600">
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Import Result --}}
    @if (session('import_result'))
        @php
            $result = session('import_result');
        @endphp

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Import Result
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Excel processing ka complete result.
                    </p>
                </div>

                <a
                    href="{{ route('leads.index') }}"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    View Imported Leads
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <div class="text-sm font-medium text-emerald-600">
                        New Imported
                    </div>

                    <div class="mt-1 text-2xl font-bold text-emerald-700">
                        {{ $result['imported'] ?? 0 }}
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div class="text-sm font-medium text-blue-600">
                        Existing Updated
                    </div>

                    <div class="mt-1 text-2xl font-bold text-blue-700">
                        {{ $result['updated'] ?? 0 }}
                    </div>
                </div>

                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <div class="text-sm font-medium text-amber-600">
                        Duplicate Skipped
                    </div>

                    <div class="mt-1 text-2xl font-bold text-amber-700">
                        {{ $result['duplicates'] ?? 0 }}
                    </div>
                </div>

                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <div class="text-sm font-medium text-rose-600">
                        Failed Rows
                    </div>

                    <div class="mt-1 text-2xl font-bold text-rose-700">
                        {{ $result['failed'] ?? 0 }}
                    </div>
                </div>
            </div>

            @if (!empty($result['errors']))
                <div class="mt-5 overflow-hidden rounded-xl border border-rose-200">
                    <div class="flex items-center justify-between bg-rose-50 px-4 py-3">
                        <div>
                            <div class="font-semibold text-rose-700">
                                Failed Rows
                            </div>

                            <div class="text-xs text-rose-600">
                                Excel ki in rows me validation ya data error mila.
                            </div>
                        </div>

                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">
                            {{ count($result['errors']) }} errors
                        </span>
                    </div>

                    <div class="max-h-96 overflow-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-white shadow-sm">
                                <tr class="border-b">
                                    <th class="w-24 px-4 py-3 text-left">
                                        Excel Row
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        Error Details
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($result['errors'] as $error)
                                    <tr class="border-b last:border-0">
                                        <td class="px-4 py-3 font-bold text-slate-800">
                                            {{ $error['row'] }}
                                        </td>

                                        <td class="px-4 py-3 text-rose-600">
                                            {{ $error['message'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Quick Steps --}}
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            [
                'number' => '1',
                'title' => 'Template Download',
                'text' => 'System ka official Excel template download karein.',
            ],
            [
                'number' => '2',
                'title' => 'Data Fill',
                'text' => 'Column headings change kiye bina lead data bharein.',
            ],
            [
                'number' => '3',
                'title' => 'Defaults Select',
                'text' => 'Source, Status, Employee aur Team select karein.',
            ],
            [
                'number' => '4',
                'title' => 'Import & Verify',
                'text' => 'File upload karke result aur failed rows verify karein.',
            ],
        ] as $step)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white">
                        {{ $step['number'] }}
                    </div>

                    <div>
                        <div class="font-semibold text-slate-900">
                            {{ $step['title'] }}
                        </div>

                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            {{ $step['text'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Import Form --}}
        <form
            method="POST"
            action="{{ route('leads.import.store') }}"
            enctype="multipart/form-data"
            class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2"
        >
            @csrf

            <div class="border-b border-slate-200 pb-4">
                <h2 class="text-lg font-bold text-slate-900">
                    Upload Excel File
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Maximum file size 10 MB. Supported formats:
                    XLSX, XLS aur CSV.
                </p>
            </div>

            {{-- File Upload --}}
            <div>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">
                        Excel / CSV File
                        <span class="text-rose-500">*</span>
                    </span>

                    <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-blue-400 hover:bg-blue-50">
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,.xls,.csv"
                            required
                            @change="fileChanged($event)"
                            class="block w-full cursor-pointer text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2.5 file:font-semibold file:text-white hover:file:bg-blue-700"
                        >

                        <div class="mt-3 text-xs text-slate-500">
                            File choose karne se pehle headings aur format verify karein.
                        </div>
                    </div>
                </label>

                <div
                    x-show="selectedFileName"
                    x-cloak
                    class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="text-sm font-semibold text-blue-900">
                                Selected File
                            </div>

                            <div
                                class="mt-0.5 text-sm text-blue-700"
                                x-text="selectedFileName"
                            ></div>
                        </div>

                        <div
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"
                            x-text="selectedFileSize"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Defaults --}}
            <div>
                <div class="mb-4">
                    <h3 class="font-bold text-slate-900">
                        Default Values
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Excel me value blank ya unknown hone par ye default values use hongi.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Default Lead Source
                            <span class="text-rose-500">*</span>
                        </span>

                        <select
                            name="default_lead_source_id"
                            required
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Select Source
                            </option>

                            @foreach ($sources as $source)
                                <option
                                    value="{{ $source->id }}"
                                    @selected(
                                        old('default_lead_source_id') ==
                                        $source->id
                                    )
                                >
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-1 text-xs text-slate-500">
                            Excel ka lead_source blank ya unmatched hone par.
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Default Lead Status
                            <span class="text-rose-500">*</span>
                        </span>

                        <select
                            name="default_lead_status_id"
                            required
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Select Status
                            </option>

                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status->id }}"
                                    @selected(
                                        old('default_lead_status_id') ==
                                        $status->id
                                    )
                                >
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-1 text-xs text-slate-500">
                            Normally new import ke liye New status select karein.
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Default Employee
                        </span>

                        <select
                            name="default_assigned_to"
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                Keep Unassigned
                            </option>

                            @foreach ($users as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected(
                                        old('default_assigned_to') ==
                                        $user->id
                                    )
                                >
                                    {{ $user->name }}

                                    @if ($user->employee_code)
                                        ({{ $user->employee_code }})
                                    @endif

                                    @if ($user->email)
                                        - {{ $user->email }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-1 text-xs text-slate-500">
                            Excel me assigned_employee_email blank hone par.
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                            Default Team
                        </span>

                        <select
                            name="default_team_id"
                            class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">
                                No Default Team
                            </option>

                            @foreach ($teams as $team)
                                <option
                                    value="{{ $team->id }}"
                                    @selected(
                                        old('default_team_id') ==
                                        $team->id
                                    )
                                >
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-1 text-xs text-slate-500">
                            Excel me team blank ya unmatched hone par.
                        </div>
                    </label>
                </div>
            </div>

            {{-- Duplicate Action --}}
            <div>
                <div class="mb-3">
                    <h3 class="font-bold text-slate-900">
                        Duplicate Mobile Number
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Same company me same mobile number pehle se exist hone par kya karna hai?
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <input
                            type="radio"
                            name="duplicate_action"
                            value="skip"
                            @checked(
                                old(
                                    'duplicate_action',
                                    'skip'
                                ) === 'skip'
                            )
                            class="mt-1"
                        >

                        <div>
                            <div class="font-semibold text-slate-800">
                                Skip Duplicate
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Existing lead me koi change nahi hoga.
                                Duplicate row skip ho jayegi.
                            </div>
                        </div>
                    </label>

                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                        <input
                            type="radio"
                            name="duplicate_action"
                            value="update"
                            @checked(
                                old('duplicate_action') ===
                                'update'
                            )
                            class="mt-1"
                        >

                        <div>
                            <div class="font-semibold text-slate-800">
                                Update Existing
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Same mobile wali existing lead ka data
                                Excel values se update hoga.
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Final Checklist --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="font-semibold text-amber-900">
                    Import se pehle check karein
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
                    <li>
                        File me <strong>name</strong> aur
                        <strong>mobile</strong> columns zaroor hon.
                    </li>

                    <li>
                        Column headings me extra space ya spelling mistake na ho.
                    </li>

                    <li>
                        Mobile number ko Excel me Text format me rakhna best hai.
                    </li>

                    <li>
                        Date format YYYY-MM-DD use karein.
                    </li>

                    <li>
                        First row me headings aur second row se data hona chahiye.
                    </li>
                </ul>
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Import Leads
            </button>
        </form>

        {{-- Sidebar Help --}}
        <div class="space-y-5">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-blue-900">
                            Required Columns
                        </h3>

                        <p class="mt-1 text-xs text-blue-700">
                            Inke bina row import nahi hogi.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">
                        2 Columns
                    </span>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-lg bg-white p-3">
                        <code class="font-bold text-blue-700">
                            name
                        </code>

                        <div class="mt-1 text-xs text-slate-500">
                            Example: Ravi Jewellers
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-3">
                        <code class="font-bold text-blue-700">
                            mobile
                        </code>

                        <div class="mt-1 text-xs text-slate-500">
                            Example: 9876543210
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold text-slate-900">
                    Accepted Values
                </h3>

                <div class="mt-4 space-y-4 text-sm">
                    <div>
                        <div class="font-semibold text-slate-700">
                            Priority
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ([
                                'low',
                                'normal',
                                'high',
                                'urgent',
                                'hot',
                            ] as $value)
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                    {{ $value }}
                                </code>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-700">
                            Temperature
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ([
                                'cold',
                                'warm',
                                'hot',
                            ] as $value)
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                    {{ $value }}
                                </code>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-700">
                            Date
                        </div>

                        <code class="mt-2 block rounded bg-slate-100 px-2 py-1.5 text-xs text-slate-700">
                            2026-08-30
                        </code>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-700">
                            Date and Time
                        </div>

                        <code class="mt-2 block rounded bg-slate-100 px-2 py-1.5 text-xs text-slate-700">
                            2026-07-30 16:00
                        </code>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                <h3 class="font-bold text-rose-900">
                    Common Mistakes
                </h3>

                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-rose-700">
                    <li>
                        Name की जगह <code>lead_name</code> लिख देना।
                    </li>

                    <li>
                        Mobile column को scientific format में रखना।
                    </li>

                    <li>
                        Date में DD-MM-YYYY और YYYY-MM-DD mix करना।
                    </li>

                    <li>
                        Employee name देना जबकि mapping email से होती है।
                    </li>

                    <li>
                        CRM में मौजूद न होने वाला Source या Team नाम देना।
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Exact Column Format --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">
                    Excel Column Names and Format
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Excel ki first row me ye exact headings use karein.
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="copyHeadings()"
                    class="rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                >
                    <span x-show="!copied">
                        Copy Headings
                    </span>

                    <span
                        x-show="copied"
                        x-cloak
                    >
                        Copied ✓
                    </span>
                </button>

                <a
                    href="{{ route('leads.import.template') }}"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                    Download Template
                </a>
            </div>
        </div>

        {{-- Comma Separated Heading Line --}}
        <div class="border-b border-slate-200 bg-blue-50 p-4">
            <div class="mb-2 text-xs font-bold uppercase tracking-wide text-blue-700">
                CSV Heading Line
            </div>

            <div class="overflow-x-auto rounded-lg border border-blue-200 bg-white p-3">
                <code class="whitespace-nowrap text-xs text-slate-700">
                    {{ $headingLine }}
                </code>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-14 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            #
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Column Name
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Required
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Format
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Example
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Use / Description
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach ($importColumns as $column)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-4 py-3">
                                <code class="rounded bg-slate-100 px-2 py-1 font-bold text-blue-700">
                                    {{ $column['name'] }}
                                </code>
                            </td>

                            <td class="px-4 py-3">
                                @if ($column['required'])
                                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">
                                        Required
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        Optional
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-slate-700">
                                {{ $column['format'] }}
                            </td>

                            <td class="px-4 py-3">
                                <code class="rounded bg-emerald-50 px-2 py-1 text-xs text-emerald-700">
                                    {{ $column['example'] }}
                                </code>
                            </td>

                            <td class="px-4 py-3 text-slate-600">
                                {{ $column['description'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sample Excel Preview --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <h2 class="text-lg font-bold text-slate-900">
                Sample Excel Preview
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Excel file ka basic format kuch is tarah hona chahiye.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1500px] text-xs">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        @foreach ($importColumns as $column)
                            <th class="whitespace-nowrap border-r border-blue-500 px-3 py-3 text-left font-semibold last:border-r-0">
                                {{ $column['name'] }}

                                @if ($column['required'])
                                    <span class="text-yellow-200">
                                        *
                                    </span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    <tr class="bg-white">
                        @foreach ($importColumns as $column)
                            <td class="whitespace-nowrap border-r border-slate-200 px-3 py-3 text-slate-700 last:border-r-0">
                                {{ $column['example'] }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-amber-50 px-5 py-3 text-sm text-amber-800">
            <strong>*</strong>
            वाले columns required हैं। बाकी columns blank छोड़े जा सकते हैं।
        </div>
    </div>

    {{-- Default Mapping Explanation --}}
    <div class="rounded-xl border border-violet-200 bg-violet-50 p-5">
        <h2 class="font-bold text-violet-900">
            Default Mapping कैसे काम करती है?
        </h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-white p-4">
                <div class="font-semibold text-violet-800">
                    Lead Source
                </div>

                <div class="mt-1 text-xs leading-5 text-slate-600">
                    Excel में source blank या CRM से match नहीं हुआ तो selected Default Source लगेगा।
                </div>
            </div>

            <div class="rounded-lg bg-white p-4">
                <div class="font-semibold text-violet-800">
                    Lead Status
                </div>

                <div class="mt-1 text-xs leading-5 text-slate-600">
                    Excel में status blank या unknown हुआ तो selected Default Status लगेगा।
                </div>
            </div>

            <div class="rounded-lg bg-white p-4">
                <div class="font-semibold text-violet-800">
                    Employee
                </div>

                <div class="mt-1 text-xs leading-5 text-slate-600">
                    Employee email नहीं मिली तो Default Employee लगेगा या Lead unassigned रहेगी।
                </div>
            </div>

            <div class="rounded-lg bg-white p-4">
                <div class="font-semibold text-violet-800">
                    Team
                </div>

                <div class="mt-1 text-xs leading-5 text-slate-600">
                    Team name match नहीं हुआ तो selected Default Team use होगी।
                </div>
            </div>
        </div>
    </div>
</div>
@endsection