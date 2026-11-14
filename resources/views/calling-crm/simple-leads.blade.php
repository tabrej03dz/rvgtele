@extends('layouts.crm')

@section('title', 'Simple Leads')

@section('content')
@php
    $unassignedTab = request('employee') === 'unassigned';
    $perPage = (int) $leads->perPage();

    $tabFilters = request()->only([
        'search',
        'category_id',
        'call_status',
    ]);

    $tabFilters['per_page'] = $perPage;

    $allUrl = route('simple-leads.index', $tabFilters);

    $unassignedUrl = route(
        'simple-leads.index',
        array_merge($tabFilters, ['employee' => 'unassigned'])
    );

    $resetUrl = route(
        'simple-leads.index',
        array_merge(
            ['per_page' => $perPage],
            $unassignedTab ? ['employee' => 'unassigned'] : []
        )
    );

    $paginationFilters = request()->only([
        'search',
        'category_id',
        'employee',
        'call_status',
    ]);

    $currentPage = $leads->currentPage();
    $lastPage = $leads->lastPage();

    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<style>
.sl {
    --blue: #3458eb;
    --line: #e5eaf2;
    --muted: #7b879a;
    max-width: 1440px;
    margin: auto;
    color: #263449;
    font-size: 14px;
    line-height: 1.5;
}
.sl * { box-sizing: border-box; }
.sl h1, .sl h2, .sl p { margin: 0; }
.sl-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
.sl h1 { font-size: 26px; font-weight: 750; letter-spacing: -.7px; }
.sl h2 { font-size: 15px; font-weight: 700; }
.sl-muted { color: var(--muted); font-size: 12px; }
.sl-sub { margin-top: 4px !important; color: var(--muted); font-size: 13px; }
.sl-kicker {
    color: var(--blue);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    margin-bottom: 5px !important;
    text-transform: uppercase;
}
.sl-total {
    padding: 9px 14px;
    border: 1px solid #dfe6ff;
    border-radius: 11px;
    background: #eef2ff;
    color: #4057b5;
    white-space: nowrap;
}
.sl-total strong { font-size: 19px; margin-right: 5px; }
.sl-card {
    background: white;
    border: 1px solid var(--line);
    border-radius: 15px;
    margin-bottom: 18px;
    overflow: hidden;
    box-shadow: 0 3px 14px #23345805;
}
.sl-card-title { padding: 16px 20px; border-bottom: 1px solid var(--line); }
.sl-upload {
    display: grid;
    grid-template-columns: minmax(180px, 1.5fr) minmax(160px, 1fr) auto;
    align-items: end;
    gap: 15px;
    padding: 18px 20px;
}
.sl-label {
    display: block;
    margin-bottom: 6px;
    color: #536178;
    font-size: 12px;
    font-weight: 650;
}
.sl-input {
    width: 100%;
    min-width: 0;
    height: 42px;
    padding: 8px 11px !important;
    border: 1px solid #dbe2ed !important;
    border-radius: 9px !important;
    background: white;
    color: #334155;
    font-size: 13px !important;
}
.sl-input:focus {
    outline: 2px solid #c7d2fe;
    outline-offset: 1px;
}
.sl-file {
    width: 100%;
    height: 42px;
    padding: 5px;
    border: 1px dashed #c7d1e2;
    border-radius: 9px;
    background: #f8faff;
    color: #64748b;
    font-size: 12px;
}
.sl-file::file-selector-button {
    border: 0;
    padding: 6px 10px;
    margin-right: 8px;
    border-radius: 5px;
    background: #e8eeff;
    color: #4057b5;
    cursor: pointer;
}
.sl-hint { padding: 0 20px 16px; color: var(--muted); font-size: 12px; }
.sl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 8px 15px;
    border: 1px solid #dce3ee;
    border-radius: 9px;
    background: white;
    color: #536178;
    font-size: 13px;
    font-weight: 650;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
}
.sl-btn:hover { background: #f4f7fb; }
.sl-primary { background: var(--blue); color: white; border-color: var(--blue); }
.sl-primary:hover { background: #2947ce; }
.sl-danger { background: #fff5f5; color: #b94b4b; border-color: #f1d8d8; }
.sl-danger:hover { background: #ffecec; }
.sl-btn:disabled, .sl-disabled { opacity: .45; cursor: not-allowed; }
.sl-btn:focus-visible, .sl-tab:focus-visible {
    outline: 3px solid #bbcaff;
    outline-offset: 2px;
}
.sl-tabs { display: flex; gap: 24px; padding: 0 20px; border-bottom: 1px solid var(--line); }
.sl-tab {
    padding: 16px 0 13px;
    border-bottom: 3px solid transparent;
    color: #8190a4;
    text-decoration: none;
    font-weight: 650;
}
.sl-tab.active { color: var(--blue); border-bottom-color: var(--blue); }
.sl-filters {
    display: grid;
    grid-template-columns: minmax(180px, 1.4fr) repeat(3, minmax(120px, 1fr)) auto;
    align-items: end;
    gap: 12px;
    padding: 18px 20px;
}
.sl-flex { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sl-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 14px 20px;
    background: #fafbfe;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}
.sl-actions select { width: 190px; }
.sl-table-wrap { overflow-x: auto; }
.sl table { width: 100%; text-align: left; border-collapse: collapse; white-space: nowrap; }
.sl th {
    padding: 13px 17px;
    background: #f8fafc;
    border-bottom: 1px solid var(--line);
    color: #8792a5;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .7px;
    text-transform: uppercase;
}
.sl td { padding: 15px 17px; border-bottom: 1px solid #eef2f7; font-size: 13px; }
.sl tbody tr:hover { background: #fafcff; }
.sl tbody tr.is-selected { background: #f0f4ff; }
.sl input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
.sl-name { font-weight: 650; }
.sl-id { margin-top: 3px; color: #94a0b3; font-size: 11px; }
.sl-tag { padding: 5px 9px; border-radius: 6px; background: #f1f5f9; color: #64748b; font-size: 11px; }
.sl-badge { display: inline-block; padding: 5px 9px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.sl-badge.pending { background: #eef2ff; color: #5265bb; }
.sl-badge.warm { background: #fff4de; color: #a77620; }
.sl-badge.good { background: #eaf8f0; color: #32815b; }
.sl-badge.bad { background: #fff0f0; color: #b85d5d; }
.sl-empty { padding: 45px 20px !important; text-align: center; color: #8792a5; }
.sl-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 17px 20px;
}
.sl-pager {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    padding: 17px 20px;
    border-top: 1px solid var(--line);
}
.sl-pages { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
.sl-page {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 35px;
    height: 35px;
    padding: 0 10px;
    border: 1px solid var(--line);
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    font-size: 12px;
}
.sl-page:hover { background: #f5f7fc; }
.sl-page.active { background: var(--blue); color: white; border-color: var(--blue); }
.sl-page-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sl-page-form .sl-input { width: 85px; height: 36px; }
.sl-page-form .sl-btn { min-height: 36px; padding: 6px 12px; }
.sl-alert { padding: 13px 16px; border-radius: 10px; margin-bottom: 15px; background: #effaf3; color: #28714a; }
.sl-alert.error { background: #fff1f1; color: #aa4545; }
.sl-errors { padding: 14px 18px; margin-bottom: 15px; border: 1px solid var(--line); border-radius: 10px; background: white; }
.sl-errors summary { cursor: pointer; }
.sl-errors pre { white-space: pre-wrap; overflow-wrap: anywhere; margin-top: 10px; font-size: 12px; }
@media(max-width:1100px) {
    .sl-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media(max-width:640px) {
    .sl-head { align-items: flex-start; }
    .sl h1 { font-size: 23px; }
    .sl-upload, .sl-filters { grid-template-columns: 1fr; }
    .sl-actions, .sl-actions select { width: 100%; }
    .sl-actions .sl-btn { flex: 1; }
    .sl-pager { align-items: flex-start; }
}
</style>

<div class="sl" id="simple-leads-page">

    <header class="sl-head">
        <div>
            <p class="sl-kicker">Calling CRM</p>
            <h1>Simple Leads</h1>
            <p class="sl-sub">Upload, organise and assign your leads.</p>
        </div>

        <div class="sl-total">
            <strong>{{ number_format($leads->total()) }}</strong>
            {{ $unassignedTab ? 'unassigned' : 'matching leads' }}
        </div>
    </header>

    @if(session('success'))
        <div class="sl-alert" role="status">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="sl-alert error" role="alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="sl-alert error" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('import_errors'))
        <details class="sl-errors">
            <summary>View upload errors</summary>
            <pre>{{ json_encode(session('import_errors'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    @endif

    @can('leads.import')
        <section class="sl-card">
            <div class="sl-card-title">
                <h2>Upload leads</h2>
                <p class="sl-sub">Add a CSV or Excel file.</p>
            </div>

            <form
                method="POST"
                action="{{ route('simple-leads.upload') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="sl-upload">
                    <div>
                        <label class="sl-label" for="upload_file">Choose file</label>
                        <input
                            id="upload_file"
                            class="sl-file"
                            type="file"
                            name="file"
                            accept=".csv,.xlsx,.xls"
                            required
                        >
                    </div>

                    <div>
                        <label class="sl-label" for="upload_category">Category</label>
                        <select
                            id="upload_category"
                            class="sl-input"
                            name="category_id"
                            required
                        >
                            <option value="">Select category</option>

                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="sl-btn sl-primary">
                        Upload leads
                    </button>
                </div>

                <p class="sl-hint">
                    Required: name, mobile.
                    Optional: email, company_name.
                    Duplicate numbers are skipped.
                </p>
            </form>
        </section>
    @endcan

    <section class="sl-card">

        <nav class="sl-tabs" aria-label="Lead lists">
            <a
                href="{{ $allUrl }}"
                class="sl-tab {{ !$unassignedTab ? 'active' : '' }}"
                @if(!$unassignedTab) aria-current="page" @endif
            >
                All Leads
            </a>

            <a
                href="{{ $unassignedUrl }}"
                class="sl-tab {{ $unassignedTab ? 'active' : '' }}"
                @if($unassignedTab) aria-current="page" @endif
            >
                Unassigned Leads
            </a>
        </nav>

        {{-- FILTER CHANGE STARTS AGAIN FROM PAGE 1 --}}
        <form
            method="GET"
            action="{{ route('simple-leads.index') }}"
            class="sl-filters"
        >
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            @if($unassignedTab)
                <input type="hidden" name="employee" value="unassigned">
            @endif

            <div>
                <label class="sl-label" for="sl-search">Search leads</label>
                <input
                    id="sl-search"
                    class="sl-input"
                    name="search"
                    type="search"
                    value="{{ request('search') }}"
                    placeholder="Name or mobile number"
                >
            </div>

            <div>
                <label class="sl-label" for="sl-category">Category</label>
                <select id="sl-category" class="sl-input" name="category_id">
                    <option value="">All categories</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(request('category_id') == $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="sl-label" for="sl-employee">Employee</label>

                @if($unassignedTab)
                    <select id="sl-employee" class="sl-input" disabled>
                        <option>Unassigned only</option>
                    </select>
                @else
                    <select id="sl-employee" class="sl-input" name="employee">
                        <option value="">All employees</option>

                        @foreach($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                @selected(request('employee') == $employee->id)
                            >
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="sl-label" for="sl-status">Call status</label>
                <select id="sl-status" class="sl-input" name="call_status">
                    <option value="">All statuses</option>

                    @foreach($statuses as $status)
                        <option
                            value="{{ $status }}"
                            @selected(request('call_status') === $status)
                        >
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sl-flex">
                <button type="submit" class="sl-btn sl-primary">Filter</button>
                <a href="{{ $resetUrl }}" class="sl-btn">Reset</a>
            </div>
        </form>

        <form
            id="lead-actions"
            method="POST"
            action="{{ route('simple-leads.assign') }}"
        >
            @csrf

            <div class="sl-toolbar">
                <div class="sl-muted" aria-live="polite">
                    <strong id="sl-selected-count">0</strong> selected
                </div>

                <div class="sl-flex sl-actions">
                    @can('leads.assign')
                        <select
                            name="assigned_to"
                            id="sl-assign-employee"
                            class="sl-input"
                            aria-label="Assign to employee"
                        >
                            <option value="">Assign to employee</option>

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
                            Assign selected
                        </button>
                    @endcan

                    @can('leads.delete')
                        <button
                            type="submit"
                            data-action="delete"
                            class="sl-btn sl-danger sl-bulk-button"
                            formaction="{{ route('simple-leads.delete') }}"
                            disabled
                        >
                            Delete selected
                        </button>
                    @endcan
                </div>
            </div>

            <div class="sl-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">
                                <input
                                    type="checkbox"
                                    id="select-all-leads"
                                    aria-label="Select all leads on this page"
                                    @disabled($leads->isEmpty())
                                >
                            </th>
                            <th scope="col">Lead</th>
                            <th scope="col">Mobile</th>
                            <th scope="col">Category</th>
                            <th scope="col">Employee</th>
                            <th scope="col">Call status</th>
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
                                    'answered', 'converted' => 'good',

                                    'not answer',
                                    'not answered',
                                    'no answer',
                                    'busy' => 'warm',

                                    'not interested' => 'bad',

                                    default => 'pending',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        name="lead_ids[]"
                                        value="{{ $lead->id }}"
                                        class="lead-check"
                                        aria-label="Select lead {{ $lead->id }}"
                                    >
                                </td>

                                <td>
                                    <div class="sl-name">
                                        {{ $lead->name ?: 'Unnamed lead' }}
                                    </div>
                                    <div class="sl-id">
                                        Lead #{{ $lead->id }}
                                    </div>
                                </td>

                                <td>{{ $lead->mobile ?: '—' }}</td>

                                <td>
                                    <span class="sl-tag">
                                        {{ $lead->leadCategory?->name ?? $lead->category ?? '—' }}
                                    </span>
                                </td>

                                <td>
                                    @if($lead->assigned_to)
                                        {{ $lead->assignedUser?->name ?? 'Employee unavailable' }}
                                    @else
                                        <span class="sl-badge warm">Unassigned</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="sl-badge {{ $statusTone }}">
                                        {{ ucwords(str_replace('_', ' ', $lead->call_status ?? '')) ?: '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="sl-empty">
                                    {{ $unassignedTab ? 'No unassigned leads found.' : 'No leads found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{-- ROW COUNT AND CUSTOM PAGE SIZE --}}
        <div class="sl-footer">
            <div class="sl-muted">
                Showing
                <strong>{{ $leads->firstItem() ?? 0 }}</strong>
                to
                <strong>{{ $leads->lastItem() ?? 0 }}</strong>
                of
                <strong>{{ number_format($leads->total()) }}</strong>
                leads
            </div>

            <form
                method="GET"
                action="{{ route('simple-leads.index') }}"
                class="sl-page-form"
            >
                @foreach($paginationFilters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <label for="sl-per-page" class="sl-muted">Leads per page</label>

                <input
                    id="sl-per-page"
                    class="sl-input"
                    type="number"
                    name="per_page"
                    value="{{ $perPage }}"
                    min="1"
                    max="500"
                    step="1"
                    required
                >

                <button type="submit" class="sl-btn sl-primary">Apply</button>
            </form>
        </div>

        {{-- PAGE LINKS AND DIRECT PAGE NUMBER --}}
        <nav class="sl-pager" aria-label="Leads pagination">

            <div class="sl-pages">

                @if($leads->onFirstPage())
                    <span class="sl-page sl-disabled" aria-disabled="true">
                        Previous
                    </span>
                @else
                    <a
                        class="sl-page"
                        href="{{ $leads->previousPageUrl() }}"
                        rel="prev"
                    >
                        Previous
                    </a>
                @endif

                @if($startPage > 1)
                    <a class="sl-page" href="{{ $leads->url(1) }}">1</a>

                    @if($startPage > 2)
                        <span class="sl-muted" aria-hidden="true">…</span>
                    @endif
                @endif

                @for($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++)
                    @if($pageNumber === $currentPage)
                        <span class="sl-page active" aria-current="page">
                            {{ $pageNumber }}
                        </span>
                    @else
                        <a
                            class="sl-page"
                            href="{{ $leads->url($pageNumber) }}"
                            aria-label="Go to page {{ $pageNumber }}"
                        >
                            {{ $pageNumber }}
                        </a>
                    @endif
                @endfor

                @if($endPage < $lastPage)
                    @if($endPage < $lastPage - 1)
                        <span class="sl-muted" aria-hidden="true">…</span>
                    @endif

                    <a class="sl-page" href="{{ $leads->url($lastPage) }}">
                        {{ $lastPage }}
                    </a>
                @endif

                @if($leads->hasMorePages())
                    <a
                        class="sl-page"
                        href="{{ $leads->nextPageUrl() }}"
                        rel="next"
                    >
                        Next
                    </a>
                @else
                    <span class="sl-page sl-disabled" aria-disabled="true">
                        Next
                    </span>
                @endif

            </div>

            <form
                method="GET"
                action="{{ route('simple-leads.index') }}"
                class="sl-page-form"
            >
                @foreach($paginationFilters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <label for="sl-page-number" class="sl-muted">Go to page</label>

                <input
                    id="sl-page-number"
                    class="sl-input"
                    type="number"
                    name="page"
                    value="{{ $currentPage }}"
                    min="1"
                    max="{{ $lastPage }}"
                    step="1"
                    required
                >

                <span class="sl-muted">of {{ $lastPage }}</span>

                <button type="submit" class="sl-btn sl-primary">Go</button>
            </form>

        </nav>

    </section>

</div>

<script>
(() => {
    const root = document.getElementById('simple-leads-page');
    const form = root.querySelector('#lead-actions');
    const selectAll = root.querySelector('#select-all-leads');
    const checks = [...root.querySelectorAll('.lead-check')];
    const buttons = root.querySelectorAll('.sl-bulk-button');

    const refreshSelection = () => {
        const count = checks.filter(checkbox => checkbox.checked).length;

        root.querySelector('#sl-selected-count').textContent = count;

        selectAll.checked = checks.length > 0 && count === checks.length;
        selectAll.indeterminate = count > 0 && count < checks.length;

        buttons.forEach(button => {
            button.disabled = count === 0;
        });

        checks.forEach(checkbox => {
            checkbox.closest('tr').classList.toggle(
                'is-selected',
                checkbox.checked
            );
        });
    };

    selectAll.addEventListener('change', () => {
        checks.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });

        refreshSelection();
    });

    checks.forEach(checkbox => {
        checkbox.addEventListener('change', refreshSelection);
    });

    form.addEventListener('submit', event => {
        const count = checks.filter(checkbox => checkbox.checked).length;

        if (count === 0) {
            event.preventDefault();
            alert('Pehle kam se kam ek lead select karein.');
            return;
        }

        const action = event.submitter?.dataset.action || 'assign';

        if (action === 'assign') {
            const employee = root.querySelector('#sl-assign-employee');

            if (!employee?.value) {
                event.preventDefault();
                alert('Employee select karein.');
                employee?.focus();
            }

            return;
        }

        if (!confirm(`${count} selected leads ko Recycle Bin mein bhejna hai?`)) {
            event.preventDefault();
        }
    });

    window.addEventListener('pageshow', refreshSelection);
    refreshSelection();
})();
</script>
@endsection