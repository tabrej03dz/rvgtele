@extends('layouts.crm', [
    'title' => 'Import Leads',
])

@section('content')
@php
    $importColumns = [
        ['name' => 'name', 'required' => true, 'format' => 'Text', 'example' => 'Ravi Jewellers', 'description' => 'Lead ya contact person ka naam.'],
        ['name' => 'mobile', 'required' => true, 'format' => 'Text / Number', 'example' => '9876543210', 'description' => 'Primary mobile number. Duplicate isi number se check hoga.'],
        ['name' => 'alternate_mobile', 'required' => false, 'format' => 'Text / Number', 'example' => '9123456780', 'description' => 'Alternate contact number.'],
        ['name' => 'whatsapp_number', 'required' => false, 'format' => 'Text / Number', 'example' => '9876543210', 'description' => 'WhatsApp communication number.'],
        ['name' => 'email', 'required' => false, 'format' => 'Email', 'example' => 'ravi@example.com', 'description' => 'Lead email address.'],
        ['name' => 'company_name', 'required' => false, 'format' => 'Text', 'example' => 'Ravi Jewellers Pvt Ltd', 'description' => 'Company ya business name.'],
        ['name' => 'city', 'required' => false, 'format' => 'Text', 'example' => 'Kanpur', 'description' => 'Lead city.'],
        ['name' => 'district', 'required' => false, 'format' => 'Text', 'example' => 'Kanpur Nagar', 'description' => 'Lead district.'],
        ['name' => 'state', 'required' => false, 'format' => 'Text', 'example' => 'Uttar Pradesh', 'description' => 'Lead state.'],
        ['name' => 'pincode', 'required' => false, 'format' => 'Text / Number', 'example' => '208001', 'description' => 'PIN code.'],
        ['name' => 'required_product', 'required' => false, 'format' => 'Text', 'example' => 'Telecalling CRM', 'description' => 'Required product ya service.'],
        ['name' => 'estimated_budget', 'required' => false, 'format' => 'Number', 'example' => '25000', 'description' => 'Estimated customer budget.'],
        ['name' => 'expected_deal_value', 'required' => false, 'format' => 'Number', 'example' => '50000', 'description' => 'Expected deal amount.'],
        ['name' => 'expected_closing_date', 'required' => false, 'format' => 'YYYY-MM-DD', 'example' => '2026-08-30', 'description' => 'Expected closing date.'],
        ['name' => 'next_follow_up_at', 'required' => false, 'format' => 'YYYY-MM-DD HH:MM', 'example' => '2026-07-30 16:00', 'description' => 'Next follow-up date and time.'],
        ['name' => 'priority', 'required' => false, 'format' => 'low / normal / high / urgent / hot', 'example' => 'high', 'description' => 'Lead priority.'],
        ['name' => 'temperature', 'required' => false, 'format' => 'cold / warm / hot', 'example' => 'warm', 'description' => 'Lead interest level.'],
        ['name' => 'lead_source', 'required' => false, 'format' => 'Existing Source Name', 'example' => 'Website', 'description' => 'CRM source ka exact naam.'],
        ['name' => 'lead_status', 'required' => false, 'format' => 'Existing Status Name', 'example' => 'New', 'description' => 'CRM status ka exact naam.'],
        ['name' => 'assigned_employee_email', 'required' => false, 'format' => 'Employee Email', 'example' => 'telecaller@example.com', 'description' => 'Active employee email.'],
        ['name' => 'team', 'required' => false, 'format' => 'Existing Team Name', 'example' => 'Sales Team A', 'description' => 'CRM team ka exact naam.'],
    ];

    $headingLine = collect($importColumns)->pluck('name')->implode(',');
@endphp

<div
    class="mx-auto max-w-6xl space-y-5"
    x-data="{
        fileName: '',
        fileSize: '',
        copied: false,
        showColumns: false,

        onFileChange(event) {
            const file = event.target.files[0];

            if (!file) {
                this.fileName = '';
                this.fileSize = '';
                return;
            }

            this.fileName = file.name;
            this.fileSize = this.formatSize(file.size);
        },

        formatSize(bytes) {
            if (!bytes) return '0 KB';

            const kb = bytes / 1024;

            if (kb < 1024) {
                return kb.toFixed(1) + ' KB';
            }

            return (kb / 1024).toFixed(1) + ' MB';
        },

        async copyHeadings() {
            try {
                await navigator.clipboard.writeText(@js($headingLine));
                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 1800);
            } catch (error) {
                alert('Headings copy nahi ho saki.');
            }
        }
    }"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('leads.index') }}" class="hover:text-blue-600">
                    Leads
                </a>
                <span>/</span>
                <span>Import</span>
            </div>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                Import Leads
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Excel ya CSV file se multiple leads upload karein.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('leads.import')
            <a
                href="{{ route('leads.import.template') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v12"/>
                    <path d="m7 10 5 5 5-5"/>
                    <path d="M5 21h14"/>
                </svg>
                Download Template
            </a>
            @endcan

            <a
                href="{{ route('leads.index') }}"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                Back to Leads
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <div class="font-semibold">Import completed</div>
            <div class="mt-0.5">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
            <div class="font-semibold text-rose-800">
                Import start nahi ho saka
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Import Result --}}
    @if (session('import_result'))
        @php
            $result = session('import_result');
        @endphp

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-900">Import Result</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Uploaded file ka processing summary.
                    </p>
                </div>

                <a
                    href="{{ route('leads.index') }}"
                    class="inline-flex self-start rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 sm:self-auto"
                >
                    View Leads
                </a>
            </div>

            <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4">
                <div class="bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Imported
                    </div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ $result['imported'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Updated
                    </div>
                    <div class="mt-1 text-2xl font-bold text-blue-600">
                        {{ $result['updated'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Duplicates
                    </div>
                    <div class="mt-1 text-2xl font-bold text-amber-600">
                        {{ $result['duplicates'] ?? 0 }}
                    </div>
                </div>

                <div class="bg-white p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Failed
                    </div>
                    <div class="mt-1 text-2xl font-bold text-rose-600">
                        {{ $result['failed'] ?? 0 }}
                    </div>
                </div>
            </div>

            @if (!empty($result['errors']))
                <div class="border-t border-slate-200 p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-900">
                                Failed Rows
                            </h3>
                            <p class="text-xs text-slate-500">
                                In rows ko file me correct karke dobara import karein.
                            </p>
                        </div>

                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">
                            {{ count($result['errors']) }}
                        </span>
                    </div>

                    <div class="max-h-72 overflow-auto rounded-lg border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50">
                                <tr class="border-b border-slate-200">
                                    <th class="w-24 px-4 py-3 text-left font-semibold text-slate-600">
                                        Row
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                        Error
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($result['errors'] as $error)
                                    @php
                                        $rowNumber = '-';
                                        $errorMessage = 'Unknown import error.';

                                        if (is_array($error)) {
                                            $rowNumber = $error['row'] ?? '-';
                                            $errorMessage = $error['message'] ?? 'Unknown import error.';
                                        } else {
                                            $errorString = (string) $error;

                                            if (preg_match('/^Row\s+(\d+):\s*(.*)$/s', $errorString, $matches)) {
                                                $rowNumber = $matches[1] ?? '-';
                                                $errorMessage = $matches[2] ?? $errorString;
                                            } else {
                                                $errorMessage = $errorString;
                                            }
                                        }
                                    @endphp

                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            {{ $rowNumber }}
                                        </td>
                                        <td class="px-4 py-3 text-rose-600">
                                            {{ $errorMessage }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
        {{-- Main Import Form --}}
        <form
            method="POST"
            action="{{ route('leads.import.store') }}"
            enctype="multipart/form-data"
            class="rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold text-slate-900">
                    Upload File
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Supported: XLSX, XLS, CSV · Maximum size: 10 MB
                </p>
            </div>

            <div class="space-y-6 p-5">
                {{-- File --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Excel / CSV File
                        <span class="text-rose-500">*</span>
                    </label>

                    <label
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center transition hover:border-blue-400 hover:bg-blue-50/50"
                    >
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,.xls,.csv"
                            required
                            class="sr-only"
                            @change="onFileChange($event)"
                        >

                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 16V4"/>
                                <path d="m7 9 5-5 5 5"/>
                                <path d="M5 20h14"/>
                            </svg>
                        </div>

                        <div class="mt-3 text-sm font-semibold text-slate-800">
                            Click to choose file
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            Template format use karna recommended hai.
                        </div>
                    </label>

                    @error('file')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <div
                        x-show="fileName"
                        x-cloak
                        class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5"
                    >
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-blue-900" x-text="fileName"></div>
                            <div class="text-xs text-blue-600" x-text="fileSize"></div>
                        </div>

                        <span class="shrink-0 rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                            Selected
                        </span>
                    </div>
                </div>

                {{-- Default Mapping --}}
                <div>
                    <div class="mb-4">
                        <h3 class="font-semibold text-slate-900">
                            Default Mapping
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Excel me value blank ya match na hone par ye defaults use honge.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Default Source
                                <span class="text-rose-500">*</span>
                            </span>

                            <select
                                name="default_lead_source_id"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select source</option>
                                @foreach ($sources as $source)
                                    <option
                                        value="{{ $source->id }}"
                                        @selected((string) old('default_lead_source_id') === (string) $source->id)
                                    >
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('default_lead_source_id')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Default Status
                                <span class="text-rose-500">*</span>
                            </span>

                            <select
                                name="default_lead_status_id"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select status</option>
                                @foreach ($statuses as $status)
                                    <option
                                        value="{{ $status->id }}"
                                        @selected((string) old('default_lead_status_id') === (string) $status->id)
                                    >
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('default_lead_status_id')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Default Employee
                            </span>

                            <select
                                name="default_assigned_to"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Keep unassigned</option>
                                @foreach ($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) old('default_assigned_to') === (string) $user->id)
                                    >
                                        {{ $user->name }}
                                        @if ($user->employee_code)
                                            ({{ $user->employee_code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('default_assigned_to')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                Default Team
                            </span>

                            <select
                                name="default_team_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">No default team</option>
                                @foreach ($teams as $team)
                                    <option
                                        value="{{ $team->id }}"
                                        @selected((string) old('default_team_id') === (string) $team->id)
                                    >
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('default_team_id')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>
                </div>

                {{-- Duplicate Action --}}
                <div>
                    <div class="mb-3">
                        <h3 class="font-semibold text-slate-900">
                            Duplicate Mobile
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Existing mobile number milne par kya karna hai?
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                            <input
                                type="radio"
                                name="duplicate_action"
                                value="skip"
                                @checked(old('duplicate_action', 'skip') === 'skip')
                                class="mt-1 border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <div>
                                <div class="font-semibold text-slate-800">
                                    Skip duplicate
                                </div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">
                                    Existing lead me koi change nahi hoga.
                                </div>
                            </div>
                        </label>

                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                            <input
                                type="radio"
                                name="duplicate_action"
                                value="update"
                                @checked(old('duplicate_action') === 'update')
                                class="mt-1 border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <div>
                                <div class="font-semibold text-slate-800">
                                    Update existing
                                </div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">
                                    Existing lead Excel data se update hoga.
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('duplicate_action')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                <a
                    href="{{ route('leads.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </a>

                @can('leads.import')
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Import Leads
                </button>
                @endcan
            </div>
        </form>

        {{-- Right Help --}}
        <aside class="space-y-4">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold text-slate-900">
                    Before Import
                </h3>

                <ol class="mt-4 space-y-4">
                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                            1
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">
                                Download template
                            </div>
                            <div class="mt-0.5 text-xs leading-5 text-slate-500">
                                Official Excel template use karein.
                            </div>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                            2
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">
                                Add lead data
                            </div>
                            <div class="mt-0.5 text-xs leading-5 text-slate-500">
                                Headings change na karein.
                            </div>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                            3
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">
                                Upload and import
                            </div>
                            <div class="mt-0.5 text-xs leading-5 text-slate-500">
                                Source, status aur duplicate action select karein.
                            </div>
                        </div>
                    </li>
                </ol>
            </section>

            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="font-bold text-amber-900">
                    Required Columns
                </h3>

                <div class="mt-3 flex flex-wrap gap-2">
                    <code class="rounded-md bg-white px-2.5 py-1.5 text-sm font-bold text-amber-800">
                        name
                    </code>
                    <code class="rounded-md bg-white px-2.5 py-1.5 text-sm font-bold text-amber-800">
                        mobile
                    </code>
                </div>

                <p class="mt-3 text-xs leading-5 text-amber-800">
                    First row me headings aur second row se lead data hona chahiye.
                </p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold text-slate-900">
                    File Rules
                </h3>

                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <span class="text-emerald-500">✓</span>
                        Mobile ko Text format me rakhein.
                    </li>
                    <li class="flex gap-2">
                        <span class="text-emerald-500">✓</span>
                        Date format YYYY-MM-DD use karein.
                    </li>
                    <li class="flex gap-2">
                        <span class="text-emerald-500">✓</span>
                        Employee mapping email se hoti hai.
                    </li>
                    <li class="flex gap-2">
                        <span class="text-emerald-500">✓</span>
                        Source aur Team ka exact CRM name use karein.
                    </li>
                </ul>
            </section>
        </aside>
    </div>

    {{-- Column Guide --}}
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <button
            type="button"
            @click="showColumns = !showColumns"
            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50"
        >
            <div>
                <h2 class="font-bold text-slate-900">
                    Excel Column Guide
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Exact headings, formats aur examples dekhein.
                </p>
            </div>

            <svg
                class="h-5 w-5 shrink-0 text-slate-400 transition"
                :class="showColumns ? 'rotate-180' : ''"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>

        <div x-show="showColumns" x-cloak>
            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 overflow-x-auto rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <code class="whitespace-nowrap text-xs text-slate-600">
                            {{ $headingLine }}
                        </code>
                    </div>

                    <button
                        type="button"
                        @click="copyHeadings()"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <span x-show="!copied">Copy Headings</span>
                        <span x-show="copied" x-cloak>Copied ✓</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border-t border-slate-200">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Column</th>
                            <th class="px-4 py-3">Required</th>
                            <th class="px-4 py-3">Format</th>
                            <th class="px-4 py-3">Example</th>
                            <th class="px-4 py-3">Use</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($importColumns as $column)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <code class="font-semibold text-blue-700">
                                        {{ $column['name'] }}
                                    </code>
                                </td>

                                <td class="px-4 py-3">
                                    @if ($column['required'])
                                        <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">
                                            Required
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">
                                            Optional
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-slate-600">
                                    {{ $column['format'] }}
                                </td>

                                <td class="px-4 py-3">
                                    <code class="text-xs text-slate-700">
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
    </section>
</div>
@endsection