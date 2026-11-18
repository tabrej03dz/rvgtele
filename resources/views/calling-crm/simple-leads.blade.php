
@extends('layouts.crm')

@section('title', 'Simple Leads')

@section('content')

@php
    $unassignedTab = request('employee') === 'unassigned';
    $perPage = $leads->perPage();

    $filterKeys = [
        'search',
        'category_id',
        'employee',
        'call_status',
        'state',
        'city',
    ];

    $tabFilters = request()->only([
        'search',
        'category_id',
        'call_status',
        'state',
        'city',
    ]);

    $tabFilters['per_page'] = $perPage;

    $allUrl = route('simple-leads.index', $tabFilters);

    $unassignedUrl = route(
        'simple-leads.index',
        array_merge(
            $tabFilters,
            ['employee' => 'unassigned']
        )
    );

    $resetUrl = route(
        'simple-leads.index',
        array_merge(
            ['per_page' => $perPage],
            $unassignedTab
                ? ['employee' => 'unassigned']
                : []
        )
    );

    $paginationFilters = request()->only($filterKeys);

    $currentPage = $leads->currentPage();
    $lastPage = $leads->lastPage();

    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);

    /*
     | Table columns
     | Each key must match data-col in table.
     */

    $tableColumns = [
        'lead' => 'Lead Name / ID',
        'mobile' => 'Mobile',
        'email' => 'Email',
        'company' => 'Company',
        'state' => 'State',
        'city' => 'City',
        'category' => 'Category',
        'employee' => 'Employee',
        'call_status' => 'Call Status',
        'created_at' => 'Created Date',
    ];

    $cityOptions = $cityRows->map(function ($row) {
        return [
            'state' => $row->state_name,
            'city' => $row->city_name,
        ];
    })->values();
@endphp

<style>
.sl {
    --primary: #3458eb;
    --line: #e3e8f0;
    --muted: #758398;

    max-width: 1500px;
    margin: auto;
    color: #273449;
    font-size: 14px;
    line-height: 1.5;
}

.sl * {
    box-sizing: border-box;
}

.sl h1,
.sl h2,
.sl p {
    margin: 0;
}

.sl h1 {
    font-size: 25px;
    font-weight: 750;
}

.sl h2 {
    font-size: 16px;
    font-weight: 700;
}

.sl-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.sl-sub {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px !important;
}

.sl-kicker {
    color: var(--primary);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.sl-total {
    background: #eef2ff;
    color: #4057b5;
    border: 1px solid #dfe6ff;
    padding: 10px 15px;
    border-radius: 10px;
    white-space: nowrap;
}

.sl-total strong {
    font-size: 19px;
}

.sl-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    margin-bottom: 18px;
    overflow: visible;
    box-shadow: 0 3px 14px #23345808;
}

.sl-card-title {
    padding: 15px 20px;
    border-bottom: 1px solid var(--line);
}

.sl-upload {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    align-items: end;
    gap: 15px;
    padding: 20px;
}

.sl-label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 650;
    color: #536178;
}

.sl-input {
    display: block;
    width: 100%;
    min-width: 0;
    height: 41px;
    padding: 8px 11px !important;
    border: 1px solid #dbe2ed !important;
    border-radius: 9px !important;
    background: #fff;
    color: #334155;
    font-size: 13px !important;
}

.sl-file {
    width: 100%;
    padding: 8px;
    border: 1px dashed #c7d1e2;
    border-radius: 9px;
}

.sl-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    min-height: 41px;
    padding: 8px 15px;
    border: 1px solid #dce3ee;
    border-radius: 9px;
    background: #fff;
    color: #536178;
    font-size: 13px;
    font-weight: 650;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
}

.sl-btn:hover {
    background: #f4f7fb;
}

.sl-primary {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}

.sl-primary:hover {
    background: #2947ce;
    color: #fff;
}

.sl-danger {
    color: #b94b4b;
    background: #fff5f5;
    border-color: #f1d8d8;
}

.sl-btn:disabled {
    opacity: .45;
    cursor: not-allowed;
}

.sl-hint {
    padding: 0 20px 16px;
    color: var(--muted);
    font-size: 12px;
}

.sl-tabs {
    display: flex;
    gap: 25px;
    padding: 0 20px;
    border-bottom: 1px solid var(--line);
}

.sl-tab {
    padding: 15px 0;
    border-bottom: 3px solid transparent;
    text-decoration: none;
    color: #8190a4;
    font-weight: 650;
}

.sl-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.sl-filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    padding: 20px;
}

.sl-filter-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.sl-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 15px 20px;
    background: #fafbfe;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.sl-flex {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.sl-muted {
    font-size: 12px;
    color: var(--muted);
}

/* COLUMN SELECTOR */

.sl-column-wrap {
    position: relative;
}

.sl-column-panel {
    display: none;
    position: absolute;
    z-index: 100;
    top: calc(100% + 6px);
    right: 0;
    width: 255px;
    max-width: 90vw;
    max-height: 370px;
    overflow-y: auto;
    padding: 12px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: 0 10px 35px #0000001c;
}

.sl-column-panel.open {
    display: block;
}

.sl-column-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 5px 12px;
    border-bottom: 1px solid var(--line);
    margin-bottom: 8px;
}

.sl-column-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 7px;
    border-radius: 7px;
    cursor: pointer;
}

.sl-column-option:hover {
    background: #f5f7fc;
}

.sl-column-option input {
    accent-color: var(--primary);
    width: 16px;
    height: 16px;
}

/* TABLE */

.sl-table-wrap {
    overflow-x: auto;
}

.sl table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    white-space: nowrap;
}

.sl th {
    padding: 13px 15px;
    background: #f8fafc;
    color: #78869a;
    font-size: 11px;
    font-weight: 700;
    border-bottom: 1px solid var(--line);
    text-transform: uppercase;
}

.sl td {
    padding: 13px 15px;
    font-size: 13px;
    border-bottom: 1px solid #eef2f7;
}

.sl tbody tr:hover {
    background: #fafcff;
}

.sl tbody tr.is-selected {
    background: #f0f4ff;
}

.sl table input[type=checkbox] {
    width: 16px;
    height: 16px;
    accent-color: var(--primary);
    cursor: pointer;
}

.sl-name {
    font-weight: 700;
}

.sl-id {
    color: #94a0b3;
    font-size: 11px;
    margin-top: 3px;
}

.sl-tag {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 6px;
    color: #64748b;
    background: #f1f5f9;
    font-size: 11px;
}

.sl-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.sl-badge.pending {
    background: #eef2ff;
    color: #5265bb;
}

.sl-badge.warm {
    background: #fff4de;
    color: #a77620;
}

.sl-badge.good {
    background: #eaf8f0;
    color: #32815b;
}

.sl-badge.bad {
    background: #fff0f0;
    color: #b85d5d;
}

.sl-empty {
    text-align: center;
    padding: 40px !important;
    color: var(--muted);
}

.sl-alert {
    padding: 13px 16px;
    margin-bottom: 15px;
    border-radius: 10px;
    background: #effaf3;
    color: #28714a;
}

.sl-alert.error {
    background: #fff1f1;
    color: #aa4545;
}

.sl-footer,
.sl-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding: 17px 20px;
}

.sl-pager {
    border-top: 1px solid var(--line);
}

.sl-pages,
.sl-page-form {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.sl-page {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 35px;
    height: 35px;
    padding: 0 10px;
    border: 1px solid var(--line);
    border-radius: 8px;
    text-decoration: none;
    color: #64748b;
    font-size: 12px;
}

.sl-page.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}

.sl-page-form .sl-input {
    width: 85px;
    height: 36px;
}

.sl-page-form .sl-btn {
    min-height: 36px;
}

/* Hidden table columns */

.sl [data-col].sl-col-hidden {
    display: none !important;
}

@media (max-width: 1100px) {
    .sl-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .sl-upload,
    .sl-filters {
        grid-template-columns: 1fr;
    }

    .sl-head {
        align-items: flex-start;
    }

    .sl-toolbar {
        align-items: flex-start;
    }

    .sl-filter-actions {
        justify-content: flex-start;
    }
}
</style>

<div class="sl" id="simple-leads-page">

    <!-- HEADER -->

    <header class="sl-head">
        <div>
            <p class="sl-kicker">Calling CRM</p>
            <h1>Simple Leads</h1>
            <p class="sl-sub">
                Manage, filter and assign leads.
            </p>
        </div>

        <div class="sl-total">
            <strong>
                {{ number_format($leads->total()) }}
            </strong>
            {{ $unassignedTab
                ? 'unassigned'
                : 'matching leads' }}
        </div>
    </header>

    <!-- MESSAGES -->

    @if(session('success'))
        <div class="sl-alert" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sl-alert error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="sl-alert error" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('import_errors'))
        <details class="sl-alert">
            <summary>View upload errors</summary>
            <pre style="white-space:pre-wrap">
{{ json_encode(
    session('import_errors'),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
) }}
            </pre>
        </details>
    @endif

    <!-- UPLOAD -->

    @can('leads.import')
        <section class="sl-card">
            <div class="sl-card-title">
                <h2>Upload Leads</h2>
                <p class="sl-sub">
                    Upload your CSV or Excel file.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('simple-leads.upload') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="sl-upload">
                    <div>
                        <label class="sl-label">
                            Choose File
                        </label>

                        <input
                            type="file"
                            class="sl-file"
                            name="file"
                            accept=".csv,.xlsx,.xls"
                            required
                        >
                    </div>

                    <div>
                        <label class="sl-label">
                            Category
                        </label>

                        <select
                            name="category_id"
                            class="sl-input"
                            required
                        >
                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                        old('category_id') ==
                                        $category->id
                                    )
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="sl-btn sl-primary"
                    >
                        Upload Leads
                    </button>
                </div>

                <p class="sl-hint">
                    Required: Name, Mobile.
                    Optional: Email, Company, State, City.
                    Duplicate numbers are skipped.
                </p>
            </form>
        </section>
    @endcan

    <!-- LEAD LIST -->

    <section class="sl-card">

        <!-- TABS -->

        <nav class="sl-tabs">
            <a
                href="{{ $allUrl }}"
                class="sl-tab {{ !$unassignedTab ? 'active' : '' }}"
            >
                All Leads
            </a>

            <a
                href="{{ $unassignedUrl }}"
                class="sl-tab {{ $unassignedTab ? 'active' : '' }}"
            >
                Unassigned Leads
            </a>
        </nav>

        <!-- FILTER FORM -->

        <form
            method="GET"
            action="{{ route('simple-leads.index') }}"
            class="sl-filters"
            id="sl-filter-form"
        >
            <input
                type="hidden"
                name="per_page"
                value="{{ $perPage }}"
            >

            @if($unassignedTab)
                <input
                    type="hidden"
                    name="employee"
                    value="unassigned"
                >
            @endif

            <!-- SEARCH -->

            <div>
                <label class="sl-label">
                    Search Leads
                </label>

                <input
                    type="search"
                    name="search"
                    class="sl-input"
                    value="{{ request('search') }}"
                    placeholder="Name or Mobile"
                >
            </div>

            <!-- CATEGORY -->

            <div>
                <label class="sl-label">
                    Category
                </label>

                <select
                    name="category_id"
                    class="sl-input"
                >
                    <option value="">
                        All Categories
                    </option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(
                                request('category_id') ==
                                $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- EMPLOYEE -->

            <div>
                <label class="sl-label">
                    Employee
                </label>

                @if($unassignedTab)
                    <select class="sl-input" disabled>
                        <option>Unassigned Only</option>
                    </select>
                @else
                    <select
                        name="employee"
                        class="sl-input"
                    >
                        <option value="">
                            All Employees
                        </option>

                        @foreach($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                @selected(
                                    request('employee') ==
                                    $employee->id
                                )
                            >
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- CALL STATUS -->

            <div>
                <label class="sl-label">
                    Call Status
                </label>

                <select
                    name="call_status"
                    class="sl-input"
                >
                    <option value="">
                        All Statuses
                    </option>

                    @foreach($statuses as $status)
                        <option
                            value="{{ $status }}"
                            @selected(
                                request('call_status') === $status
                            )
                        >
                            {{ ucwords(
                                str_replace('_', ' ', $status)
                            ) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- STATE FILTER -->

            <div>
                <label
                    class="sl-label"
                    for="sl-state"
                >
                    State
                </label>

                <select
                    id="sl-state"
                    name="state"
                    class="sl-input"
                >
                    <option value="">
                        All States
                    </option>

                    @foreach($states as $state)
                        <option
                            value="{{ $state }}"
                            @selected(
                                $selectedState === $state
                            )
                        >
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- CITY FILTER -->

            <div>
                <label
                    class="sl-label"
                    for="sl-city"
                >
                    City
                </label>

                <select
                    id="sl-city"
                    name="city"
                    class="sl-input"
                >
                    <option value="">
                        All Cities
                    </option>
                </select>
            </div>

            <!-- FILTER ACTIONS -->

            <div class="sl-filter-actions">
                <button
                    type="submit"
                    class="sl-btn sl-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="{{ $resetUrl }}"
                    class="sl-btn"
                >
                    Reset Filters
                </a>
            </div>
        </form>

        <!-- LEAD BULK FORM -->

        <form
            id="lead-actions"
            method="POST"
            action="{{ route('simple-leads.assign') }}"
        >
            @csrf

            <!-- TABLE TOOLBAR -->

            <div class="sl-toolbar">

                <div class="sl-muted" aria-live="polite">
                    <strong id="sl-selected-count">0</strong>
                    selected
                </div>

                <div class="sl-flex">

                    <!-- SHOW / HIDE COLUMNS -->

                    <div class="sl-column-wrap">
                        <button
                            type="button"
                            class="sl-btn"
                            id="sl-column-toggle"
                            aria-expanded="false"
                            aria-controls="sl-column-panel"
                        >
                            Show / Hide Columns
                        </button>

                        <div
                            id="sl-column-panel"
                            class="sl-column-panel"
                        >
                            <div class="sl-column-heading">
                                <strong>Table Columns</strong>

                                <button
                                    type="button"
                                    class="sl-btn"
                                    id="sl-reset-columns"
                                    style="min-height:30px;padding:4px 8px"
                                >
                                    Reset
                                </button>
                            </div>

                            @foreach($tableColumns as $key => $label)
                                <label class="sl-column-option">
                                    <input
                                        type="checkbox"
                                        class="sl-column-check"
                                        value="{{ $key }}"
                                        checked
                                    >

                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- ASSIGN -->

                    @can('leads.assign')
                        <select
                            name="assigned_to"
                            id="sl-assign-employee"
                            class="sl-input"
                            style="width:190px"
                        >
                            <option value="">
                                Assign to Employee
                            </option>

                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>

                        <button
                            type="submit"
                            data-action="assign"
                            class="sl-btn sl-primary sl-bulk-button"
                            formaction="{{ route('simple-leads.assign') }}"
                            disabled
                        >
                            Assign Selected
                        </button>
                    @endcan

                    <!-- DELETE -->

                    @can('leads.delete')
                        <button
                            type="submit"
                            data-action="delete"
                            class="sl-btn sl-danger sl-bulk-button"
                            formaction="{{ route('simple-leads.delete') }}"
                            disabled
                        >
                            Delete Selected
                        </button>
                    @endcan

                </div>
            </div>

            <!-- TABLE -->

            <div class="sl-table-wrap">
                <table id="sl-leads-table">

                    <thead>
                        <tr>
                            <!-- Selection never hidden -->

                            <th>
                                <input
                                    type="checkbox"
                                    id="select-all-leads"
                                    aria-label="Select all leads"
                                    @disabled($leads->isEmpty())
                                >
                            </th>

                            <th data-col="lead">
                                Lead
                            </th>

                            <th data-col="mobile">
                                Mobile
                            </th>

                            <th data-col="email">
                                Email
                            </th>

                            <th data-col="company">
                                Company
                            </th>

                            <th data-col="state">
                                State
                            </th>

                            <th data-col="city">
                                City
                            </th>

                            <th data-col="category">
                                Category
                            </th>

                            <th data-col="employee">
                                Employee
                            </th>

                            <th data-col="call_status">
                                Call Status
                            </th>

                            <th data-col="created_at">
                                Created Date
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($leads as $lead)

                            @php
                                $statusKey = strtolower(
                                    str_replace(
                                        ['_', '-'],
                                        ' ',
                                        trim($lead->call_status ?? '')
                                    )
                                );

                                $statusTone = match ($statusKey) {
                                    'answered',
                                    'converted' => 'good',

                                    'not answer',
                                    'not answered',
                                    'no answer',
                                    'busy' => 'warm',

                                    'not interested' => 'bad',

                                    default => 'pending',
                                };
                            @endphp

                            <tr>

                                <!-- CHECKBOX -->

                                <td>
                                    <input
                                        type="checkbox"
                                        name="lead_ids[]"
                                        value="{{ $lead->id }}"
                                        class="lead-check"
                                        aria-label="Select lead {{ $lead->id }}"
                                    >
                                </td>

                                <!-- LEAD NAME -->

                                <td data-col="lead">
                                    <div class="sl-name">
                                        {{ $lead->name ?: 'Unnamed Lead' }}
                                    </div>

                                    <div class="sl-id">
                                        Lead #{{ $lead->id }}
                                    </div>
                                </td>

                                <!-- MOBILE -->

                                <td data-col="mobile">
                                    {{ $lead->mobile ?: '—' }}
                                </td>

                                <!-- EMAIL -->

                                <td data-col="email">
                                    {{ $lead->email ?: '—' }}
                                </td>

                                <!-- COMPANY -->

                                <td data-col="company">
                                    {{ $lead->company_name ?: '—' }}
                                </td>

                                <!-- STATE -->

                                <td data-col="state">
                                    @if(trim($lead->state ?? '') !== '')
                                        <span class="sl-tag">
                                            {{ $lead->state }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>

                                <!-- CITY -->

                                <td data-col="city">
                                    @if(trim($lead->city ?? '') !== '')
                                        <span class="sl-tag">
                                            {{ $lead->city }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>

                                <!-- CATEGORY -->

                                <td data-col="category">
                                    <span class="sl-tag">
                                        {{
                                            $lead->leadCategory?->name
                                            ?? $lead->category
                                            ?? '—'
                                        }}
                                    </span>
                                </td>

                                <!-- ASSIGNED EMPLOYEE -->

                                <td data-col="employee">
                                    @if($lead->assigned_to)
                                        {{
                                            $lead->assignedUser?->name
                                            ?? 'Employee Unavailable'
                                        }}
                                    @else
                                        <span class="sl-badge warm">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>

                                <!-- CALL STATUS -->

                                <td data-col="call_status">
                                    <span class="sl-badge {{ $statusTone }}">
                                        {{
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $lead->call_status ?? ''
                                                )
                                            ) ?: '—'
                                        }}
                                    </span>
                                </td>

                                <!-- CREATED DATE -->

                                <td data-col="created_at">
                                    {{
                                        $lead->created_at
                                            ? $lead->created_at->format('d M Y')
                                            : '—'
                                    }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="11"
                                    class="sl-empty"
                                    id="sl-empty-cell"
                                >
                                    {{
                                        $unassignedTab
                                            ? 'No unassigned leads found.'
                                            : 'No leads found.'
                                    }}
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </form>

        <!-- PAGE SIZE -->

        <div class="sl-footer">

            <div class="sl-muted">
                Showing
                <strong>{{ $leads->firstItem() ?? 0 }}</strong>
                to
                <strong>{{ $leads->lastItem() ?? 0 }}</strong>
                of
                <strong>
                    {{ number_format($leads->total()) }}
                </strong>
                leads
            </div>

            <form
                method="GET"
                action="{{ route('simple-leads.index') }}"
                class="sl-page-form"
            >

                @foreach($paginationFilters as $key => $value)
                    <input
                        type="hidden"
                        name="{{ $key }}"
                        value="{{ $value }}"
                    >
                @endforeach

                <label class="sl-muted">
                    Leads Per Page
                </label>

                <input
                    type="number"
                    name="per_page"
                    class="sl-input"
                    value="{{ $perPage }}"
                    min="1"
                    max="500"
                    required
                >

                <button
                    type="submit"
                    class="sl-btn sl-primary"
                >
                    Apply
                </button>
            </form>

        </div>

        <!-- PAGINATION -->

        <nav class="sl-pager">

            <div class="sl-pages">

                @if($leads->onFirstPage())
                    <span class="sl-page">
                        Previous
                    </span>
                @else
                    <a
                        href="{{ $leads->previousPageUrl() }}"
                        class="sl-page"
                    >
                        Previous
                    </a>
                @endif

                @if($startPage > 1)
                    <a
                        href="{{ $leads->url(1) }}"
                        class="sl-page"
                    >
                        1
                    </a>

                    @if($startPage > 2)
                        <span>…</span>
                    @endif
                @endif

                @for(
                    $pageNumber = $startPage;
                    $pageNumber <= $endPage;
                    $pageNumber++
                )

                    @if($pageNumber === $currentPage)
                        <span class="sl-page active">
                            {{ $pageNumber }}
                        </span>
                    @else
                        <a
                            href="{{ $leads->url($pageNumber) }}"
                            class="sl-page"
                        >
                            {{ $pageNumber }}
                        </a>
                    @endif

                @endfor

                @if($endPage < $lastPage)
                    @if($endPage < $lastPage - 1)
                        <span>…</span>
                    @endif

                    <a
                        href="{{ $leads->url($lastPage) }}"
                        class="sl-page"
                    >
                        {{ $lastPage }}
                    </a>
                @endif

                @if($leads->hasMorePages())
                    <a
                        href="{{ $leads->nextPageUrl() }}"
                        class="sl-page"
                    >
                        Next
                    </a>
                @else
                    <span class="sl-page">
                        Next
                    </span>
                @endif

            </div>

            <!-- DIRECT PAGE JUMP -->

            <form
                method="GET"
                action="{{ route('simple-leads.index') }}"
                class="sl-page-form"
            >

                @foreach($paginationFilters as $key => $value)
                    <input
                        type="hidden"
                        name="{{ $key }}"
                        value="{{ $value }}"
                    >
                @endforeach

                <input
                    type="hidden"
                    name="per_page"
                    value="{{ $perPage }}"
                >

                <label class="sl-muted">
                    Go to Page
                </label>

                <input
                    type="number"
                    class="sl-input"
                    name="page"
                    value="{{ $currentPage }}"
                    min="1"
                    max="{{ $lastPage }}"
                    required
                >

                <span class="sl-muted">
                    of {{ $lastPage }}
                </span>

                <button
                    type="submit"
                    class="sl-btn sl-primary"
                >
                    Go
                </button>

            </form>

        </nav>

    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const root = document.getElementById(
        'simple-leads-page'
    );

    if (!root) return;

    /*
     |===========================================
     | STATE / CITY FILTER
     |===========================================
     */

    const cityData = @json($cityOptions);

    const selectedCity = @json($selectedCity);

    const stateSelect = root.querySelector(
        '#sl-state'
    );

    const citySelect = root.querySelector(
        '#sl-city'
    );

    function loadCities(keepSelected = false) {

        if (!stateSelect || !citySelect) return;

        const selectedState = stateSelect.value;

        const previousCity = keepSelected
            ? selectedCity
            : '';

        citySelect.innerHTML = '';

        const defaultOption = document.createElement(
            'option'
        );

        defaultOption.value = '';
        defaultOption.textContent = 'All Cities';

        citySelect.appendChild(defaultOption);

        const uniqueCities = new Set();

        cityData.forEach(function (item) {

            if (
                selectedState !== '' &&
                item.state !== selectedState
            ) {
                return;
            }

            if (item.city) {
                uniqueCities.add(item.city);
            }
        });

        const sortedCities = Array.from(
            uniqueCities
        ).sort((a, b) => a.localeCompare(b));

        sortedCities.forEach(function (city) {

            const option = document.createElement(
                'option'
            );

            option.value = city;
            option.textContent = city;

            if (city === previousCity) {
                option.selected = true;
            }

            citySelect.appendChild(option);
        });
    }

    if (stateSelect) {
        stateSelect.addEventListener(
            'change',
            function () {
                loadCities(false);
            }
        );
    }

    loadCities(true);

    /*
     |===========================================
     | SHOW / HIDE COLUMNS
     |===========================================
     */

    const columnButton = root.querySelector(
        '#sl-column-toggle'
    );

    const columnPanel = root.querySelector(
        '#sl-column-panel'
    );

    const columnChecks = Array.from(
        root.querySelectorAll('.sl-column-check')
    );

    const resetColumns = root.querySelector(
        '#sl-reset-columns'
    );

    /*
     | Each logged-in user gets a separate key
     | within this browser.
     */

    const storageKey =
        'simple_leads_columns_user_' +
        @json(auth()->id());

    const defaultColumns = [
        'lead',
        'mobile',
        'state',
        'city',
        'category',
        'employee',
        'call_status'
    ];

    const allColumns = columnChecks.map(
        checkbox => checkbox.value
    );

    let visibleColumns = defaultColumns.slice();

    try {
        const saved = JSON.parse(
            localStorage.getItem(storageKey) || 'null'
        );

        if (Array.isArray(saved)) {
            const valid = saved.filter(
                key => allColumns.includes(key)
            );

            if (valid.length > 0) {
                visibleColumns = valid;
            }
        }
    } catch (error) {
        visibleColumns = defaultColumns.slice();
    }

    function renderColumns() {

        if (visibleColumns.length === 0) {
            visibleColumns = ['lead'];
        }

        columnChecks.forEach(function (checkbox) {
            checkbox.checked = visibleColumns.includes(
                checkbox.value
            );
        });

        root.querySelectorAll('[data-col]').forEach(
            function (element) {

                const columnName = element.getAttribute(
                    'data-col'
                );

                element.classList.toggle(
                    'sl-col-hidden',
                    !visibleColumns.includes(columnName)
                );
            }
        );

        /*
         | Empty-table message should use
         | the correct colspan.
         */

        const emptyCell = root.querySelector(
            '#sl-empty-cell'
        );

        if (emptyCell) {
            emptyCell.colSpan =
                visibleColumns.length + 1;
        }
    }

    function saveColumns() {

        try {
            localStorage.setItem(
                storageKey,
                JSON.stringify(visibleColumns)
            );
        } catch (error) {
            // Browser storage may be disabled.
        }

        renderColumns();
    }

    columnChecks.forEach(function (checkbox) {

        checkbox.addEventListener(
            'change',
            function () {

                const checked = columnChecks
                    .filter(item => item.checked)
                    .map(item => item.value);

                if (checked.length === 0) {
                    checkbox.checked = true;
                    alert(
                        'At least one column must remain visible.'
                    );
                    return;
                }

                visibleColumns = checked;

                saveColumns();
            }
        );
    });

    if (columnButton && columnPanel) {

        columnButton.addEventListener(
            'click',
            function () {

                const opened = columnPanel.classList
                    .toggle('open');

                columnButton.setAttribute(
                    'aria-expanded',
                    opened ? 'true' : 'false'
                );
            }
        );

        document.addEventListener(
            'click',
            function (event) {

                if (
                    !columnButton.contains(event.target) &&
                    !columnPanel.contains(event.target)
                ) {
                    columnPanel.classList.remove(
                        'open'
                    );

                    columnButton.setAttribute(
                        'aria-expanded',
                        'false'
                    );
                }
            }
        );
    }

    if (resetColumns) {

        resetColumns.addEventListener(
            'click',
            function () {

                visibleColumns = defaultColumns.slice();

                saveColumns();
            }
        );
    }

    renderColumns();

    /*
     |===========================================
     | BULK SELECT / ASSIGN / DELETE
     |===========================================
     */

    const form = root.querySelector(
        '#lead-actions'
    );

    const selectAll = root.querySelector(
        '#select-all-leads'
    );

    const checks = Array.from(
        root.querySelectorAll('.lead-check')
    );

    const buttons = Array.from(
        root.querySelectorAll('.sl-bulk-button')
    );

    const selectedCount = root.querySelector(
        '#sl-selected-count'
    );

    function refreshSelection() {

        const count = checks.filter(
            checkbox => checkbox.checked
        ).length;

        if (selectedCount) {
            selectedCount.textContent = count;
        }

        if (selectAll) {
            selectAll.checked =
                checks.length > 0 &&
                count === checks.length;

            selectAll.indeterminate =
                count > 0 &&
                count < checks.length;
        }

        buttons.forEach(function (button) {
            button.disabled = count === 0;
        });

        checks.forEach(function (checkbox) {
            checkbox.closest('tr').classList.toggle(
                'is-selected',
                checkbox.checked
            );
        });
    }

    if (selectAll) {

        selectAll.addEventListener(
            'change',
            function () {

                checks.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });

                refreshSelection();
            }
        );
    }

    checks.forEach(function (checkbox) {

        checkbox.addEventListener(
            'change',
            refreshSelection
        );
    });

    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                const count = checks.filter(
                    checkbox => checkbox.checked
                ).length;

                if (count === 0) {
                    event.preventDefault();

                    alert(
                        'Pehle kam se kam ek lead select karein.'
                    );

                    return;
                }

                const action =
                    event.submitter?.dataset.action ||
                    'assign';

                if (action === 'assign') {

                    const employee = root.querySelector(
                        '#sl-assign-employee'
                    );

                    if (!employee || !employee.value) {

                        event.preventDefault();

                        alert(
                            'Employee select karein.'
                        );

                        employee?.focus();

                        return;
                    }

                    return;
                }

                if (action === 'delete') {

                    const confirmed = confirm(
                        count +
                        ' selected leads ko Recycle Bin mein bhejna hai?'
                    );

                    if (!confirmed) {
                        event.preventDefault();
                    }
                }
            }
        );
    }

    window.addEventListener(
        'pageshow',
        refreshSelection
    );

    refreshSelection();

});
</script>

@endsection
