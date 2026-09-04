@extends('layouts.crm', ['title' => 'Leads'])

@section('content')

<style>
:root{--p:#5b4df7;--p2:#8d29ef;--ink:#172033;--muted:#7b8497;--line:#e6e9f1;--bg:#f5f7fb;--card:#fff;--shadow:0 5px 18px rgba(33,44,84,.07)}
.lead-page{padding:14px;background:var(--bg);min-height:100vh;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,sans-serif}.panel{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);margin-bottom:10px}.search-panel{padding:10px}.search-form{display:flex;gap:8px;align-items:center}.search-box{height:42px;border:1px solid #ced4e0;border-radius:7px;display:flex;align-items:center;gap:10px;padding:0 12px;flex:1;background:#fff}.search-box input{border:0;outline:0;width:100%;font-size:13px}.search-icon{color:#9aa2b1;font-size:20px}.btn{border:1px solid #dfe3ec;background:#fff;border-radius:7px;padding:10px 14px;font-weight:700;font-size:11px;cursor:pointer;text-decoration:none;color:#394157;display:inline-flex;align-items:center;justify-content:center;gap:5px;white-space:nowrap}.btn:hover{transform:translateY(-1px)}.btn-primary{border:0;color:#fff;background:linear-gradient(100deg,var(--p),var(--p2));box-shadow:0 5px 12px rgba(97,75,246,.22)}.btn-soft{background:#fff}.btn-danger{background:#e74355;color:#fff;border-color:#e74355}.btn-tiny{padding:7px 10px;font-size:10px}.top-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.mini-panel{padding:12px 14px;min-height:90px}.panel-head,.table-head,.modal-title-row{display:flex;align-items:center;justify-content:space-between;gap:10px}.section-title{font-size:13px;font-weight:800;display:flex;align-items:center;gap:7px}.title-icon{width:25px;height:25px;border-radius:7px;display:inline-grid;place-items:center;color:var(--p);background:#f1efff}.chips{display:flex;align-items:center;flex-wrap:wrap;gap:5px;margin-top:12px}.chip{font-size:10px;font-weight:800;color:#4e5668;border:1px solid #dfe3eb;padding:8px 12px;border-radius:6px;text-decoration:none;background:#fff;transition:.15s}.chip:hover{border-color:#a99efc;color:var(--p)}.chip.active{color:#fff;border-color:transparent;background:linear-gradient(105deg,var(--p),var(--p2));box-shadow:0 6px 14px rgba(86,70,230,.22)}.label-chip .dot{display:inline-block;width:7px;height:7px;background:var(--label);border-radius:50%;margin-right:5px}.label-chip b{margin-left:5px;font-size:9px}.empty-inline{font-size:11px;color:var(--muted)}.management-panel{padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:14px}.management-info{display:flex;align-items:center;gap:10px;min-width:270px}.big-icon{width:36px;height:36px;border-radius:9px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,var(--p),var(--p2));box-shadow:0 7px 14px rgba(96,75,246,.25)}.management-title{font-size:15px;font-weight:800}.record-badge{font-size:9px;color:#6d5dfb;background:#f0edff;border-radius:999px;padding:4px 7px;margin-left:5px}.subtext,.muted{color:var(--muted);font-size:10px}.toolbar{display:flex;align-items:center;justify-content:flex-end;gap:5px;flex-wrap:wrap}.disposition-panel{padding:10px 12px}.disposition-chips{margin-top:9px}.table-panel{overflow:hidden}.table-head{padding:10px 12px;border-bottom:1px solid var(--line)}.per-page-form{display:flex;align-items:center;gap:7px;font-size:10px}.per-page-form select{border:1px solid #d6dae5;border-radius:6px;padding:6px}.table-scroll{overflow:auto}.lead-table{width:100%;border-collapse:collapse;min-width:1250px}.lead-table th{background:#fbfcfe;border-bottom:1px solid var(--line);color:#5b6374;font-size:9px;text-align:left;padding:10px 8px;white-space:nowrap}.lead-table td{padding:11px 8px;border-bottom:1px solid #edf0f5;font-size:11px;vertical-align:middle}.lead-table tbody tr:hover{background:#fbfbff}.check-col{width:28px}.lead-name{font-size:12px;font-weight:800;color:#20283a}.mobile-link{font-weight:800;color:#222a3d;text-decoration:none}.note-cell{max-width:210px}.note-preview{display:block;border:0;background:none;padding:0;color:#4f586a;font-size:10px;cursor:pointer;text-align:left;max-width:190px}.link-btn{border:0;background:none;color:var(--p);font-size:9px;font-weight:800;padding:4px 0;cursor:pointer}.row-actions{display:flex;align-items:center;gap:4px}.open-btn{background:linear-gradient(100deg,var(--p),var(--p2));color:#fff;text-decoration:none;padding:6px 11px;border-radius:6px;font-size:9px;font-weight:800}.icon-action{width:27px;height:27px;border:1px solid #e0e4ec;background:#fff;border-radius:7px;display:grid;place-items:center;text-decoration:none;cursor:pointer;color:#3f4657}.icon-action.whatsapp{color:#15a867}.status-pill,.priority,.temperature{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:9px;font-weight:800;background:#eef3ff;color:#4e67a8}.demo-sent{background:#e9fff1;color:#1b9850}.priority-normal{background:#f0f2f6;color:#596274}.priority-high,.priority-urgent,.priority-hot{background:#fff0f2;color:#d63e50}.priority-low{background:#eef8ff;color:#3f7da7}.temp-hot{color:#d74444;background:#fff0f0}.temp-warm{color:#b86c24;background:#fff6e9}.temp-cold{color:#3978ad;background:#edf7ff}.label-stack{display:flex;flex-wrap:wrap;gap:3px}.mini-label{font-size:8px;font-weight:800;padding:3px 6px;border-radius:999px;color:var(--label);border:1px solid color-mix(in srgb,var(--label) 35%, white);background:color-mix(in srgb,var(--label) 9%, white)}.pagination-wrap{padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px}.empty-state{text-align:center;padding:35px;color:var(--muted)}.flash{padding:10px 12px;border-radius:8px;margin-bottom:10px;font-size:12px;font-weight:700}.flash-success{background:#eaffeF;color:#177647;border:1px solid #bcebd0}.flash-error{background:#fff0f2;color:#b92e42;border:1px solid #f2c4ca}
.modal{position:fixed;inset:0;background:rgba(15,23,42,.46);display:none;align-items:center;justify-content:center;padding:18px;z-index:9999}.modal.open{display:flex}.modal-card{width:min(480px,100%);background:#fff;border-radius:14px;padding:17px;box-shadow:0 25px 60px rgba(15,23,42,.22);max-height:90vh;overflow:auto}.modal-wide{width:min(780px,100%)}.modal-title-row{margin-bottom:15px}.modal-title-row h3{margin:0;font-size:16px}.modal-close{border:0;background:#f2f3f7;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:20px}.field,.form-grid label{display:flex;flex-direction:column;gap:6px;font-size:11px;font-weight:800;margin-bottom:12px}.field input,.field select,.field textarea,.form-grid input,.form-grid select{border:1px solid #d9deea;border-radius:8px;padding:10px;outline:none;background:#fff;font:inherit;font-weight:500}.field input:focus,.field select:focus,.field textarea:focus,.form-grid input:focus,.form-grid select:focus{border-color:#8a7df8;box-shadow:0 0 0 3px #f0eeff}.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.modal-actions{display:flex;justify-content:flex-end;gap:7px;margin-top:10px}.note-full{white-space:pre-wrap;font-size:13px;line-height:1.6;background:#fafbff;border:1px solid var(--line);padding:12px;border-radius:9px}.note-meta{margin-top:8px}
@media(max-width:1000px){.top-grid{grid-template-columns:1fr}.management-panel{align-items:flex-start;flex-direction:column}.toolbar{justify-content:flex-start}.form-grid{grid-template-columns:1fr 1fr}}
@media(max-width:620px){.lead-page{padding:8px}.search-form{flex-wrap:wrap}.search-box{width:100%;flex-basis:100%}.form-grid{grid-template-columns:1fr}.toolbar{width:100%}.toolbar .btn{flex:1}.pagination-wrap{align-items:flex-start;flex-direction:column}}
</style>
@php
    $query = request()->query();
    $currentDisposition = (string) request('call_disposition', 'no_call');
    $currentLabel = request('label_id');
    $demoOnly = request()->boolean('demo_send');

    $urlWith = function (array $changes = [], array $remove = ['page']) use ($query) {
        $params = $query;
        foreach ($remove as $key) unset($params[$key]);
        foreach ($changes as $key => $value) {
            if ($value === null || $value === '') unset($params[$key]);
            else $params[$key] = $value;
        }
        return route('manage.leads.index', $params);
    };
@endphp

<div class="lead-page" id="leadPage">
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="flash flash-error">
            <strong>Please fix:</strong> {{ $errors->first() }}
        </div>
    @endif

    {{-- Search --}}
    <section class="panel search-panel">
        <form method="GET" action="{{ route('manage.leads.index') }}" class="search-form">
            @foreach(request()->except(['search','page']) as $key => $value)
                @if(!is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, mobile, company, email or city...">
            </div>
            <button class="btn btn-primary" type="submit">⌕ SEARCH</button>
            @if(request()->filled('search'))
                <a class="btn btn-soft" href="{{ $urlWith(['search' => null]) }}">CLEAR</a>
            @endif
        </form>
    </section>

    {{-- Lead type + labels --}}
    <div class="top-grid">
        <section class="panel mini-panel">
            <div class="section-title"><span class="title-icon">◈</span> Lead Type</div>
            <div class="chips">
                <a href="{{ $urlWith(['demo_send' => null]) }}" class="chip {{ !$demoOnly ? 'active' : '' }}">ALL LEADS</a>
                <a href="{{ $urlWith(['demo_send' => 1]) }}" class="chip {{ $demoOnly ? 'active' : '' }}">DEMO SEND</a>
            </div>
        </section>

        <section class="panel mini-panel">
            <div class="panel-head">
                <div class="section-title"><span class="title-icon">◇</span> Lead Labels</div>
                @can('leads.labels.manage')
                    <button type="button" class="btn btn-tiny" data-open-modal="createLabelModal">＋ CREATE LABEL</button>
                @endcan
            </div>
            <div class="chips label-chips">
                <a href="{{ $urlWith(['label_id' => null]) }}" class="chip {{ !$currentLabel ? 'active' : '' }}">ALL LEADS</a>
                @forelse($labels as $label)
                    <a href="{{ $urlWith(['label_id' => $label->id]) }}"
                       class="chip label-chip {{ (string)$currentLabel === (string)$label->id ? 'active' : '' }}"
                       style="--label: {{ $label->color }}">
                        <span class="dot"></span>{{ strtoupper($label->name) }}
                        <b>{{ $label->leads_count ?? 0 }}</b>
                    </a>
                @empty
                    <span class="empty-inline">No labels yet. Create your first label.</span>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Management toolbar --}}
    <section class="panel management-panel">
        <div class="management-info">
            <div class="big-icon">♙</div>
            <div>
                <div class="management-title">Lead Management <span class="record-badge">{{ $leads->total() }} RECORDS</span></div>
                <div class="subtext">Company CRM leads — search, label, assign and manage.</div>
            </div>
        </div>
        <div class="toolbar">
            <button class="btn btn-soft" type="button" onclick="toggleFullScreen()">↗ FULL SCREEN</button>
            <button class="btn btn-soft" type="button" data-open-modal="filterModal">☰ FILTERS</button>
            @can('leads.labels.manage')
                <button class="btn btn-soft" type="button" data-open-modal="createLabelModal">＋ CREATE LABEL</button>
                <button class="btn btn-soft selection-action" type="button" data-open-modal="bulkLabelModal" disabled>◇ LABEL SELECTED</button>
            @endcan
            @can('leads.assign')
                <button class="btn btn-soft selection-action" type="button" data-open-modal="bulkAssignModal" disabled>♙ BULK ASSIGN</button>
                <button class="btn btn-soft selection-action" type="button" data-open-modal="bulkUnassignModal" disabled>♧ BULK UNASSIGN</button>
            @endcan
            @can('leads.import')
                <a class="btn btn-soft" href="{{ route('leads.import.create') }}">⇧ IMPORT</a>
            @endcan
            @can('leads.create')
                <a class="btn btn-primary" href="{{ route('leads.create') }}">＋ NEW LEAD</a>
            @endcan
        </div>
    </section>

    {{-- Latest disposition --}}
    <section class="panel disposition-panel">
        <div class="section-title"><span class="title-icon">⌕</span> Latest Call Disposition</div>
        <div class="chips disposition-chips">
            <a href="{{ $urlWith(['call_disposition' => 'all']) }}" class="chip {{ $currentDisposition === 'all' ? 'active' : '' }}">ALL</a>
            <a href="{{ $urlWith(['call_disposition' => 'no_call']) }}" class="chip {{ $currentDisposition === 'no_call' ? 'active' : '' }}">NO CALL YET</a>
            @foreach($dispositions as $disposition)
                <a href="{{ $urlWith(['call_disposition' => $disposition->id]) }}"
                   class="chip {{ $currentDisposition === (string)$disposition->id ? 'active' : '' }}">
                    {{ strtoupper($disposition->name) }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- Lead register --}}
    <section class="panel table-panel">
        <div class="table-head">
            <div class="section-title"><span class="title-icon">▣</span> Lead Register <span class="record-badge">{{ $leads->count() }} SHOWING</span></div>
            <form method="GET" action="{{ route('manage.leads.index') }}" class="per-page-form">
                @foreach(request()->except(['per_page','page']) as $key => $value)
                    @if(!is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label>Per page</label>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach([25,50,100,200] as $size)
                        <option value="{{ $size }}" @selected((int)$perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="table-scroll">
            <table class="lead-table">
                <thead>
                    <tr>
                        <th class="check-col"><input type="checkbox" id="selectAll"></th>
                        <th>LEAD / BUSINESS</th>
                        <th>MOBILE</th>
                        <th>LATEST NOTE</th>
                        <th>DEMO SEND</th>
                        <th>ACTION</th>
                        <th>LABELS</th>
                        <th>SOURCE</th>
                        <th>STATUS</th>
                        <th>PRIORITY</th>
                        <th>TEMP.</th>
                        <th>TEAM</th>
                        <th>OWNER</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    @php
                        $displayName = $lead->company_name ?: $lead->name ?: 'Unnamed Lead';
                        $wa = preg_replace('/\D+/', '', $lead->whatsapp_number ?: $lead->mobile ?: '');
                        if ($wa && strlen($wa) === 10) $wa = '91'.$wa;
                        $priority = strtolower($lead->priority ?: 'normal');
                        $temp = strtolower($lead->temperature ?: 'cold');
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="lead-check" value="{{ $lead->id }}"></td>
                        <td>
                            <div class="lead-name">{{ $displayName }}</div>
                            <div class="muted">{{ $lead->name && $lead->company_name ? $lead->name : ($lead->city ?: '—') }}</div>
                        </td>
                        <td>
                            <a class="mobile-link" href="tel:{{ $lead->mobile }}">{{ $lead->mobile ?: '—' }}</a>
                            <div class="muted">{{ $lead->city ?: '' }}</div>
                        </td>
                        <td class="note-cell">
                            @if($lead->latest_note_body)
                                <button type="button" class="note-preview"
                                        data-note="{{ e($lead->latest_note_body) }}"
                                        data-note-user="{{ e($lead->latest_note_user_name ?: '') }}"
                                        data-note-date="{{ $lead->latest_note_created_at ? \Carbon\Carbon::parse($lead->latest_note_created_at)->format('d M Y, h:i A') : '' }}">
                                    {{ \Illuminate\Support\Str::limit($lead->latest_note_body, 52) }}
                                </button>
                            @else
                                <span class="muted">No note</span>
                            @endif
                            @can('leads.notes.create')
                                <button type="button" class="link-btn add-note-btn" data-lead-id="{{ $lead->id }}" data-lead-name="{{ e($displayName) }}">＋ Add note</button>
                            @endcan
                        </td>
                        <td>
                            @if($lead->demo_send)
                                <span class="status-pill demo-sent">✓ Sent</span>
                                @if($lead->demo_sent_at)<div class="muted">{{ $lead->demo_sent_at->format('d M, h:i A') }}</div>@endif
                            @else
                                <span class="muted">Not Sent</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="open-btn" href="{{ route('leads.show', array_merge(['lead' => $lead->id], request()->query())) }}">OPEN</a>
                                @if($lead->mobile)
                                    <button type="button" class="icon-action call-btn" title="Call on registered mobile" data-call-url="{{ route('leads.call-on-mobile', $lead) }}">☎</button>
                                @endif
                                @if($wa)
                                    <a class="icon-action whatsapp" title="WhatsApp" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">◉</a>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="label-stack">
                                @forelse($lead->labels as $label)
                                    <span class="mini-label" style="--label:{{ $label->color }}">{{ $label->name }}</span>
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>{{ $lead->source?->name ?: '—' }}</td>
                        <td><span class="status-pill">{{ $lead->status?->name ?: 'New' }}</span></td>
                        <td><span class="priority priority-{{ $priority }}">{{ ucfirst($priority) }}</span></td>
                        <td><span class="temperature temp-{{ $temp }}">{{ ucfirst($temp) }}</span></td>
                        <td>{{ $lead->team?->name ?: '—' }}</td>
                        <td>{{ $lead->assignedUser?->name ?: 'Unassigned' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13"><div class="empty-state">No leads found for the selected filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            <div class="muted">Showing {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }}</div>
            <div>{{ $leads->links() }}</div>
        </div>
    </section>
</div>

{{-- Filter modal --}}
<div class="modal" id="filterModal" aria-hidden="true">
    <div class="modal-card modal-wide">
        <div class="modal-title-row"><h3>Filter Leads</h3><button type="button" class="modal-close">×</button></div>
        <form method="GET" action="{{ route('manage.leads.index') }}">
            <input type="hidden" name="call_disposition" value="{{ $currentDisposition }}">
            @if($demoOnly)<input type="hidden" name="demo_send" value="1">@endif
            @if($currentLabel)<input type="hidden" name="label_id" value="{{ $currentLabel }}">@endif
            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            <div class="form-grid">
                <label>Status<select name="status"><option value="">All statuses</option>@foreach($statuses as $s)<option value="{{ $s->id }}" @selected((string)request('status')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></label>
                <label>Source<select name="source"><option value="">All sources</option>@foreach($sources as $s)<option value="{{ $s->id }}" @selected((string)request('source')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></label>
                <label>
                    Category

                    <select name="category_id">
                        <option value="">
                            All categories
                        </option>

                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (string) request('category_id')
                                    ===
                                    (string) $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>City<select name="city"><option value="">All cities</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(request('city')===$city)>{{ $city }}</option>@endforeach</select></label>
                @if($canFilterByEmployee)
                    <label>Employee<select name="assigned_to"><option value="">All employees</option>@if($hasFullAccess)<option value="unassigned" @selected(request('assigned_to')==='unassigned')>Unassigned</option>@endif @foreach($users as $u)<option value="{{ $u->id }}" @selected((string)request('assigned_to')===(string)$u->id)>{{ $u->name }}{{ $u->employee_code ? ' ('.$u->employee_code.')' : '' }}</option>@endforeach</select></label>
                @endif
                @if($canFilterByTeam)
                    <label>Team<select name="team_id"><option value="">All teams</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((string)request('team_id')===(string)$team->id)>{{ $team->name }}</option>@endforeach</select></label>
                @endif
                <label>Priority<select name="priority"><option value="">All priorities</option>@foreach(['low','normal','high','urgent','hot'] as $p)<option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>@endforeach</select></label>
                <label>Temperature<select name="temperature"><option value="">All temperatures</option>@foreach(['cold','warm','hot'] as $t)<option value="{{ $t }}" @selected(request('temperature')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></label>
                <label>From date<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
                <label>To date<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            </div>
            <div class="modal-actions">
                <a href="{{ route('manage.leads.index', ['call_disposition' => 'no_call']) }}" class="btn btn-soft">RESET</a>
                <button type="submit" class="btn btn-primary">APPLY FILTERS</button>
            </div>
        </form>
    </div>
</div>

{{-- Create label modal --}}
@can('leads.labels.manage')
<div class="modal" id="createLabelModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-title-row"><h3>Create Lead Label</h3><button type="button" class="modal-close">×</button></div>
        <form method="POST" action="{{ route('lead-labels.store') }}">@csrf
            <label class="field">Label name<input type="text" name="name" maxlength="100" required placeholder="e.g. Hot Prospect"></label>
            <label class="field">Color<input type="color" name="color" value="#6D5DFB" required></label>
            <div class="modal-actions"><button type="button" class="btn btn-soft modal-close">CANCEL</button><button class="btn btn-primary">CREATE LABEL</button></div>
        </form>
    </div>
</div>

<div class="modal" id="bulkLabelModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-title-row"><h3>Label Selected Leads</h3><button type="button" class="modal-close">×</button></div>
        <form method="POST" action="{{ route('leads.bulk-label') }}" class="selected-form">@csrf
            <div class="selected-inputs"></div>
            <label class="field">Label<select name="label_id" required><option value="">Select label</option>@foreach($labels as $label)<option value="{{ $label->id }}">{{ $label->name }}</option>@endforeach</select></label>
            <label class="field">Action<select name="label_action" required><option value="add">Add label</option><option value="remove">Remove label</option></select></label>
            <div class="modal-actions"><button type="button" class="btn btn-soft modal-close">CANCEL</button><button class="btn btn-primary">APPLY</button></div>
        </form>
    </div>
</div>
@endcan

{{-- Bulk assignment modals --}}
@can('leads.assign')
<div class="modal" id="bulkAssignModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-title-row"><h3>Bulk Assign</h3><button type="button" class="modal-close">×</button></div>
        <form method="POST" action="{{ route('manage.leads.bulk-assign') }}" class="selected-form bulk-selection-form" id="bulkAssignForm">@csrf
            <input type="hidden" name="bulk_action" value="assign">
            <input type="hidden" name="assignment_scope" value="selected">
            <div class="selected-inputs"></div>
            <label class="field">Assign to<select name="assigned_to" required><option value="">Choose employee</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}{{ $u->employee_code ? ' ('.$u->employee_code.')' : '' }}</option>@endforeach</select></label>
            <label class="field">Reason<textarea name="reason" maxlength="500" required placeholder="Reason for assignment">Bulk assignment from leads index</textarea></label>
            <div class="modal-actions"><button type="button" class="btn btn-soft modal-close">CANCEL</button><button class="btn btn-primary">ASSIGN SELECTED</button></div>
        </form>
    </div>
</div>

<div class="modal" id="bulkUnassignModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-title-row"><h3>Bulk Unassign</h3><button type="button" class="modal-close">×</button></div>
        <form method="POST" action="{{ route('manage.leads.bulk-assign') }}" class="selected-form bulk-selection-form" id="bulkUnassignForm">@csrf
            <input type="hidden" name="bulk_action" value="unassign">
            <input type="hidden" name="assignment_scope" value="selected">
            <div class="selected-inputs"></div>
            <label class="field">Reason<textarea name="reason" maxlength="500" required>Bulk unassignment from leads index</textarea></label>
            <div class="modal-actions"><button type="button" class="btn btn-soft modal-close">CANCEL</button><button class="btn btn-danger">UNASSIGN SELECTED</button></div>
        </form>
    </div>
</div>
@endcan

{{-- Note modal --}}
@can('leads.notes.create')
<div class="modal" id="noteModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-title-row"><div><h3>Add Note</h3><div class="muted" id="noteLeadName"></div></div><button type="button" class="modal-close">×</button></div>
        <form method="POST" id="noteForm" action="">@csrf
            <label class="field">Note<textarea name="body" maxlength="3000" required rows="5" placeholder="Customer discussion, follow-up, requirement or important note..."></textarea></label>
            <div class="modal-actions"><button type="button" class="btn btn-soft modal-close">CANCEL</button><button class="btn btn-primary">SAVE NOTE</button></div>
        </form>
    </div>
</div>
@endcan

{{-- Note preview modal --}}
<div class="modal" id="notePreviewModal" aria-hidden="true">
    <div class="modal-card"><div class="modal-title-row"><h3>Latest Note</h3><button type="button" class="modal-close">×</button></div><div id="notePreviewBody" class="note-full"></div><div id="notePreviewMeta" class="muted note-meta"></div></div>
</div>



<script>
(() => {
    const csrf = '{{ csrf_token() }}';
    const selectAll = document.getElementById('selectAll');

    const leadChecks = () => Array.from(document.querySelectorAll('.lead-check'));
    const selectedIds = () => leadChecks()
        .filter(check => check.checked)
        .map(check => String(check.value));

    function populateSelectedInputs(form) {
        if (!form) return [];

        const ids = selectedIds();
        const box = form.querySelector('.selected-inputs');

        if (box) {
            box.innerHTML = '';

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'lead_ids[]';
                input.value = id;
                box.appendChild(input);
            });
        }

        return ids;
    }

    function updateSelection() {
        const ids = selectedIds();

        document.querySelectorAll('.selection-action').forEach(button => {
            button.disabled = ids.length === 0;
        });

        document.querySelectorAll('.selected-form').forEach(form => {
            populateSelectedInputs(form);
        });

        if (selectAll) {
            const all = leadChecks();
            selectAll.indeterminate = ids.length > 0 && ids.length < all.length;
            selectAll.checked = all.length > 0 && ids.length === all.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', event => {
            leadChecks().forEach(check => {
                check.checked = event.target.checked;
            });
            updateSelection();
        });
    }

    leadChecks().forEach(check => {
        check.addEventListener('change', updateSelection);
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-open-modal]').forEach(button => {
        button.addEventListener('click', () => {
            if (button.classList.contains('selection-action') && selectedIds().length === 0) {
                alert('Please select at least one lead.');
                return;
            }

            updateSelection();
            openModal(button.dataset.openModal);
        });
    });

    document.querySelectorAll('.modal-close').forEach(button => {
        button.addEventListener('click', () => closeModal(button.closest('.modal')));
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.open').forEach(closeModal);
        }
    });

    // IMPORTANT: selected lead ids are rebuilt immediately before submit.
    // This prevents bulk assign/unassign from posting an empty lead_ids array.
    document.querySelectorAll('.bulk-selection-form').forEach(form => {
        form.addEventListener('submit', event => {
            const ids = populateSelectedInputs(form);

            if (ids.length === 0) {
                event.preventDefault();
                alert('Please select at least one lead.');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"], button:not([type])');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = 'PLEASE WAIT...';
            }
        });
    });

    document.querySelectorAll('.add-note-btn').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.getElementById('noteForm');
            if (!form) return;

            form.action = `{{ url('/leads') }}/${button.dataset.leadId}/notes`;
            document.getElementById('noteLeadName').textContent = button.dataset.leadName || '';
            openModal('noteModal');
        });
    });

    document.querySelectorAll('.note-preview').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('notePreviewBody').textContent = button.dataset.note || '';
            document.getElementById('notePreviewMeta').textContent = [
                button.dataset.noteUser,
                button.dataset.noteDate
            ].filter(Boolean).join(' • ');

            openModal('notePreviewModal');
        });
    });

    document.querySelectorAll('.call-btn').forEach(button => {
        button.addEventListener('click', async () => {
            if (button.disabled) return;

            const oldText = button.textContent;
            button.disabled = true;
            button.textContent = '…';

            try {
                const response = await fetch(button.dataset.callUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Call request failed.');
                }

                button.textContent = '✓';
                setTimeout(() => {
                    button.textContent = oldText;
                }, 1400);
            } catch (error) {
                alert(error.message || 'Call request failed.');
                button.textContent = oldText;
            } finally {
                button.disabled = false;
            }
        });
    });

    window.toggleFullScreen = async function () {
        try {
            if (!document.fullscreenElement) {
                await document.getElementById('leadPage').requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (error) {
            console.warn(error);
        }
    };

    updateSelection();
})();
</script>
@endsection