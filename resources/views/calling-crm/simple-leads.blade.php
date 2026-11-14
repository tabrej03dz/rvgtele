@extends('layouts.crm')
@section('title', 'Simple Leads')
@section('content')
@php
    $unassignedTab = request('employee') === 'unassigned';
    $tabFilters = request()->only(['search', 'category_id', 'call_status']);
    $allUrl = route('simple-leads.index', $tabFilters);
    $unassignedUrl = route('simple-leads.index', array_merge($tabFilters, ['employee' => 'unassigned']));
    $resetUrl = route('simple-leads.index', $unassignedTab ? ['employee' => 'unassigned'] : []);
@endphp
<style>
.sl{--blue:#3458eb;--line:#e5eaf2;--muted:#718096;color:#1e293b;max-width:1440px;margin:auto;font-size:14px;line-height:1.5}
.sl *{box-sizing:border-box}.sl h1,.sl h2,.sl p{margin:0}.sl-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:22px}.sl h1{font-size:26px;letter-spacing:-.8px;font-weight:750}.sl-sub{color:var(--muted);font-size:13px;margin-top:4px!important}.sl-kicker{font-size:11px;color:var(--blue);font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:5px!important}.sl-count{padding:9px 14px;background:#eef2ff;border:1px solid #dfe6ff;color:#3450be;border-radius:12px;font-size:13px;white-space:nowrap}.sl-count strong{font-size:18px;margin-right:5px}
.sl-card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 3px 14px #23345805;margin-bottom:18px;overflow:hidden}.sl-card-head{padding:17px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px}.sl-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#eef2ff;color:var(--blue);font-size:21px;flex-shrink:0}.sl h2{font-size:15px;font-weight:700}.sl-upload{padding:18px 20px;display:grid;grid-template-columns:minmax(200px,1.5fr) minmax(170px,1fr) auto;gap:16px;align-items:end}.sl label.sl-label{display:block;font-size:12px;font-weight:650;margin-bottom:7px;color:#4b5870}.sl-input{display:block;width:100%;height:42px;border:1px solid #dbe2ed!important;background:#fff;border-radius:9px!important;padding:9px 12px!important;color:#334155;font-size:13px!important;min-width:0}.sl-input:focus{outline:2px solid #c7d2fe;outline-offset:1px;border-color:#6b83ee!important}.sl input[type=file]{height:42px;width:100%;border:1px dashed #c5cfe1;border-radius:9px;background:#f8faff;padding:5px;font-size:12px;color:#64748b}.sl input[type=file]::file-selector-button{background:#e9efff;border:0;border-radius:5px;padding:6px 10px;color:#3450be;font-weight:600;margin-right:10px;cursor:pointer}.sl-hint{padding:0 20px 17px;font-size:12px;color:var(--muted)}
.sl-btn{display:inline-flex;justify-content:center;align-items:center;gap:7px;min-height:42px;padding:9px 16px;border:1px solid #dce3ee;border-radius:9px;background:#fff;color:#475569;text-decoration:none;font-size:13px;font-weight:650;cursor:pointer;white-space:nowrap}.sl-btn:hover{background:#f5f7fb}.sl-primary{background:var(--blue);border-color:var(--blue);color:#fff}.sl-primary:hover{background:#2846ce}.sl-danger{background:#fff5f5;border-color:#f7dada;color:#c04444}.sl-danger:hover{background:#ffe9e9}.sl-btn:disabled{opacity:.45;cursor:not-allowed}.sl-btn:focus-visible,.sl-tab:focus-visible{outline:3px solid #bbcaff;outline-offset:3px}
.sl-tabs{display:flex;gap:24px;border-bottom:1px solid var(--line);padding:0 20px}.sl-tab{padding:17px 2px 14px;text-decoration:none;font-weight:650;color:#7a869b;border-bottom:3px solid transparent;display:inline-flex;align-items:center;gap:8px}.sl-tab.active{color:var(--blue);border-bottom-color:var(--blue)}.sl-tab-dot{width:7px;height:7px;background:#f5ad45;border-radius:50%}.sl-filters{padding:18px 20px;display:grid;grid-template-columns:minmax(180px,1.4fr) repeat(3,minmax(125px,1fr)) auto;gap:12px;align-items:end}.sl-filter-buttons{display:flex;gap:8px}.sl-toolbar{padding:15px 20px;background:#fafbfe;border-top:1px solid var(--line);border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.sl-selected{font-size:12px;color:#77849b}.sl-selected strong{color:#334155}.sl-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.sl-actions select{width:190px}.sl-table-wrap{overflow:auto}.sl table{width:100%;border-collapse:collapse;white-space:nowrap;text-align:left}.sl th{padding:13px 18px;background:#f8fafc;border-bottom:1px solid var(--line);color:#8792a5;font-size:10px;font-weight:700;letter-spacing:.65px;text-transform:uppercase}.sl td{padding:16px 18px;border-bottom:1px solid #eff2f7;font-size:13px}.sl tbody tr:hover{background:#fafcff}.sl tbody tr.is-selected{background:#f0f4ff}.sl input[type=checkbox]{width:16px;height:16px;accent-color:var(--blue);cursor:pointer}.sl-lead{display:flex;align-items:center;gap:11px}.sl-avatar{height:35px;width:35px;border-radius:10px;display:grid;place-items:center;background:#eef2ff;color:#5265bb;font-size:13px;font-weight:700;flex-shrink:0}.sl-name{font-weight:650;max-width:240px;overflow:hidden;text-overflow:ellipsis}.sl-id{font-size:11px;color:#94a0b3;margin-top:2px}.sl-mobile{font-variant-numeric:tabular-nums;color:#475569;text-decoration:none}.sl-tag{font-size:11px;background:#f1f5f9;color:#64748b;padding:5px 9px;border-radius:6px}.sl-badge{font-size:11px;font-weight:600;padding:5px 9px;border-radius:6px;display:inline-block}.sl-badge.pending{background:#eef2ff;color:#5465c0}.sl-badge.warm{background:#fff4de;color:#a77620}.sl-badge.good{background:#eaf8f0;color:#32815b}.sl-badge.bad{background:#fff0f0;color:#b85d5d}.sl-empty{padding:50px 20px!important;text-align:center;color:#8b97a8}.sl-empty strong{display:block;color:#475569;font-size:15px;margin-bottom:5px}.sl-footer{padding:17px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;color:#8b97a8;font-size:12px}.sl-pagination{padding:0 20px 18px}.sl-alert{border:1px solid #d9eadf;background:#f0faf4;color:#27714b;padding:13px 17px;border-radius:11px;margin-bottom:15px}.sl-alert.error{background:#fff3f3;border-color:#f2dada;color:#ae4646}.sl-errors{padding:14px 18px;margin-bottom:16px;border:1px solid var(--line);border-radius:12px;background:#fff}.sl-errors pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px;margin-top:10px}.sl-errors summary{cursor:pointer}
@media(max-width:1100px){.sl-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.sl-filter-buttons{align-self:end}.sl-upload{grid-template-columns:1fr 1fr}.sl-upload .sl-btn{grid-column:1/-1}}
@media(max-width:640px){.sl-head{align-items:flex-start}.sl h1{font-size:23px}.sl-count{padding:7px 10px}.sl-count strong{font-size:16px}.sl-upload,.sl-filters{grid-template-columns:1fr}.sl-head{gap:8px}.sl-card-head,.sl-upload,.sl-filters,.sl-toolbar{padding-left:15px;padding-right:15px}.sl-tabs{gap:20px;padding:0 15px}.sl-actions{width:100%}.sl-actions select{width:100%}.sl-actions .sl-btn{flex:1}.sl-filter-buttons .sl-btn{flex:1}.sl-hint{padding-left:15px}.sl-sub{font-size:12px}.sl-footer{padding:15px}.sl-pagination{padding:0 15px 15px}}
</style>
<div class="sl" id="simple-leads-page">
    <header class="sl-head">
        <div><p class="sl-kicker">Calling CRM</p><h1>Simple Leads</h1><p class="sl-sub">Upload, organise and assign your leads.</p></div>
        <div class="sl-count"><strong>{{ number_format($leads->total()) }}</strong> {{ $unassignedTab ? 'unassigned' : 'matching leads' }}</div>
    </header>
    @if(session('success'))<div class="sl-alert" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="sl-alert error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="sl-alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @if(session('import_errors'))
        <details class="sl-errors"><summary>View upload errors</summary><pre>{{ json_encode(session('import_errors'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
    @endif

    @can('leads.import')
    <section class="sl-card">
        <div class="sl-card-head"><span class="sl-icon" aria-hidden="true">↥</span><div><h2>Upload leads</h2><p class="sl-sub">Add a CSV or Excel file to get started.</p></div></div>
        <form method="POST" action="{{ route('simple-leads.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="sl-upload">
                <div><label class="sl-label" for="upload_file">Choose file</label><input id="upload_file" type="file" name="file" accept=".csv,.xlsx,.xls" required></div>
                <div><label class="sl-label" for="upload_category">Category</label><select id="upload_category" name="category_id" class="sl-input" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                <button type="submit" class="sl-btn sl-primary">Upload leads</button>
            </div>
            <p class="sl-hint">Required: <strong>name, mobile</strong> &nbsp;·&nbsp; Optional: email, company_name &nbsp;·&nbsp; Duplicate numbers are skipped.</p>
        </form>
    </section>
    @endcan

    <section class="sl-card">
        <nav class="sl-tabs" aria-label="Lead lists">
            <a class="sl-tab {{ !$unassignedTab ? 'active' : '' }}" href="{{ $allUrl }}" @if(!$unassignedTab) aria-current="page" @endif>All Leads</a>
            <a class="sl-tab {{ $unassignedTab ? 'active' : '' }}" href="{{ $unassignedUrl }}" @if($unassignedTab) aria-current="page" @endif><span class="sl-tab-dot" aria-hidden="true"></span>Unassigned Leads</a>
        </nav>
        <form method="GET" action="{{ route('simple-leads.index') }}" class="sl-filters">
            @if($unassignedTab)<input type="hidden" name="employee" value="unassigned">@endif
            <div><label class="sl-label" for="sl-search">Search leads</label><input class="sl-input" id="sl-search" name="search" type="search" value="{{ request('search') }}" placeholder="Name or mobile number"></div>
            <div><label class="sl-label" for="sl-category">Category</label><select class="sl-input" id="sl-category" name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div><label class="sl-label" for="sl-employee">Employee</label>
                @if($unassignedTab)<select class="sl-input" id="sl-employee" disabled><option>Unassigned only</option></select>
                @else<select class="sl-input" id="sl-employee" name="employee"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee') == $employee->id)>{{ $employee->name }}</option>@endforeach</select>@endif
            </div>
            <div><label class="sl-label" for="sl-status">Call status</label><select class="sl-input" id="sl-status" name="call_status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('call_status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div class="sl-filter-buttons"><button class="sl-btn sl-primary" type="submit">Filter</button><a class="sl-btn" href="{{ $resetUrl }}">Reset</a></div>
        </form>

        <form id="lead-actions" method="POST" action="{{ route('simple-leads.assign') }}">
            @csrf
            <div class="sl-toolbar">
                <div class="sl-selected"><strong id="sl-selected-count">0</strong> selected <span aria-hidden="true">·</span> Select leads below</div>
                <div class="sl-actions">
                    @can('leads.assign')
                    <select name="assigned_to" class="sl-input" id="sl-assign-employee" aria-label="Assign to employee"><option value="">Assign to employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select>
                    <button type="submit" data-action="assign" class="sl-btn sl-primary sl-bulk-button" formaction="{{ route('simple-leads.assign') }}" disabled>Assign selected</button>
                    @endcan
                    @can('leads.delete')<button type="submit" data-action="delete" class="sl-btn sl-danger sl-bulk-button" formaction="{{ route('simple-leads.delete') }}" disabled>Delete selected</button>@endcan
                </div>
            </div>
            <div class="sl-table-wrap"><table>
                <thead><tr><th scope="col"><input type="checkbox" id="select-all-leads" aria-label="Select all leads on this page" @disabled($leads->isEmpty())></th><th scope="col">Lead</th><th scope="col">Mobile</th><th scope="col">Category</th><th scope="col">Employee</th><th scope="col">Call status</th></tr></thead>
                <tbody>
                    @forelse($leads as $lead)
                        @php
                            $statusKey = strtolower(str_replace(['_', '-'], ' ', trim($lead->call_status ?? '')));
                            $statusTone = match ($statusKey) {
                                'answered', 'converted' => 'good',
                                'not answer', 'not answered', 'no answer', 'busy' => 'warm',
                                'not interested' => 'bad',
                                default => 'pending',
                            };
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" class="lead-check" aria-label="Select lead {{ $lead->id }}"></td>
                            <td><div class="sl-lead"><span class="sl-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($lead->name ?: 'L', 0, 1)) }}</span><div><div class="sl-name">{{ $lead->name ?: 'Unnamed lead' }}</div><div class="sl-id">Lead #{{ $lead->id }}</div></div></div></td>
                            <td><span class="sl-mobile">{{ $lead->mobile ?: '—' }}</span></td>
                            <td><span class="sl-tag">{{ $lead->leadCategory?->name ?? $lead->category ?? '—' }}</span></td>
                            <td>@if($lead->assigned_to)<span>{{ $lead->assignedUser?->name ?? 'Employee unavailable' }}</span>@else<span class="sl-badge warm">Unassigned</span>@endif</td>
                            <td><span class="sl-badge {{ $statusTone }}">{{ ucwords(str_replace('_', ' ', $lead->call_status ?? '')) ?: '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sl-empty"><strong>{{ $unassignedTab ? 'No unassigned leads' : 'No leads found' }}</strong>{{ $unassignedTab ? 'New unassigned leads will appear here. Try resetting your filters.' : 'Upload a file or try changing your filters.' }}</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </form>
        <div class="sl-footer"><span>Showing {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} of {{ number_format($leads->total()) }} leads</span><span>Select all applies to this page only.</span></div>
        @if($leads->hasPages())<div class="sl-pagination">{{ $leads->links() }}</div>@endif
    </section>
</div>
<script>
(() => {
    const root = document.getElementById('simple-leads-page');
    const form = root.querySelector('#lead-actions');
    const selectAll = root.querySelector('#select-all-leads');
    const checks = [...root.querySelectorAll('.lead-check')];
    const buttons = root.querySelectorAll('.sl-bulk-button');
    const refresh = () => {
        const count = checks.filter(box => box.checked).length;
        root.querySelector('#sl-selected-count').textContent = count;
        selectAll.checked = checks.length > 0 && count === checks.length;
        selectAll.indeterminate = count > 0 && count < checks.length;
        buttons.forEach(button => button.disabled = count === 0);
        checks.forEach(box => box.closest('tr').classList.toggle('is-selected', box.checked));
    };
    selectAll.addEventListener('change', () => {
        checks.forEach(box => box.checked = selectAll.checked);
        refresh();
    });
    checks.forEach(box => box.addEventListener('change', refresh));
    form.addEventListener('submit', event => {
        const count = checks.filter(box => box.checked).length;
        if (!count) { event.preventDefault(); alert('Pehle lead select karein.'); return; }
        const action = event.submitter?.dataset.action || 'assign';
        if (action === 'assign') {
            const employee = root.querySelector('#sl-assign-employee');
            if (!employee?.value) { event.preventDefault(); alert('Assign karne ke liye employee select karein.'); employee?.focus(); }
        } else if (!confirm(`${count} selected leads ko Recycle Bin mein bhejna hai?`)) {
            event.preventDefault();
        }
    });
    window.addEventListener('pageshow', refresh);
    refresh();
})();
</script>
@endsection
