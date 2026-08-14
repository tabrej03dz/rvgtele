@extends('layouts.crm', ['title' => 'Leads'])

@section('content')
    @once
        <style>
            [x-cloak] {
                display: none !important
            }

            .software-ui {
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                color: #1e293b;
                font-size: 12px
            }

            .software-ui::before {
                content: "";
                position: fixed;
                inset: 0;
                z-index: -1;
                background: #f5f7fb
            }

            .software-panel,
            .software-toolbar {
                border: 1px solid #d8dee8;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 8px rgba(15, 23, 42, .05)
            }

            .software-toolbar {
                border-top: 3px solid #2563eb
            }

            .software-panel-title {
                min-height: 40px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 9px 12px;
                border-bottom: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #0f172a;
                font-size: 12px;
                font-weight: 800;
                border-radius: 10px 10px 0 0
            }

            .software-btn {
                display: inline-flex;
                min-height: 32px;
                align-items: center;
                justify-content: center;
                gap: 6px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                background: #fff;
                padding: 0 11px;
                color: #334155;
                font-size: 10px;
                font-weight: 800;
                line-height: 1;
                white-space: nowrap;
                transition: .15s
            }

            .software-btn:hover {
                border-color: #94a3b8;
                background: #f8fafc;
                color: #0f172a
            }

            .software-btn-primary {
                border-color: #1d4ed8;
                background: #2563eb;
                color: #fff
            }

            .software-btn-primary:hover {
                background: #1d4ed8;
                color: #fff
            }

            .software-label {
                margin-bottom: 5px;
                display: block;
                color: #475569;
                font-size: 9px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .055em
            }

            .software-ui input[type=text],
            .software-ui input[type=date],
            .software-ui select {
                min-height: 34px;
                border: 1px solid #cbd5e1 !important;
                border-radius: 6px !important;
                background: #fff;
                font-size: 11px !important;
                color: #0f172a
            }

            .software-ui table {
                border-collapse: separate;
                border-spacing: 0
            }

            .software-ui thead th {
                border-bottom: 1px solid #cbd5e1 !important;
                background: #f8fafc;
                color: #475569 !important;
                font-size: 9px !important;
                font-weight: 800 !important;
                letter-spacing: .055em !important;
                position: sticky;
                top: 0;
                z-index: 2
            }

            .software-ui tbody td {
                border-bottom: 1px solid #eef2f7;
                vertical-align: middle
            }

            .software-ui tbody tr:hover {
                background: #f8fbff !important
            }

            .crm-scrollbar {
                scrollbar-width: thin;
                scrollbar-color: #b8c2cf #eef2f7
            }

            .crm-scrollbar::-webkit-scrollbar {
                height: 8px;
                width: 8px
            }

            .crm-scrollbar::-webkit-scrollbar-track {
                background: #eef2f7
            }

            .crm-scrollbar::-webkit-scrollbar-thumb {
                background: #b8c2cf;
                border-radius: 8px
            }
        </style>
    @endonce

    <div class="software-ui mx-auto max-w-[1720px] space-y-3 px-1 pb-5" x-data="{
        selected: [],
        selectAllPage: false,
        showFilters: @js(request()->hasAny(['source', 'assigned_to', 'team_id', 'priority', 'temperature', 'date_from', 'date_to'])),
        showBulkModal: false,
        bulkAction: 'assign',
        assignmentScope: 'selected',
        showLabelModal: false,
        showCreateLabelModal: false,
        labelAction: 'add',
        togglePage(ids) {
            if (this.selectAllPage) { this.selected = [...new Set([...this.selected, ...ids])]; } else { this.selected = this.selected.filter(id => !ids.includes(id)); }
        }
    }">

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <div class="font-bold">Please correct the following:</div>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="software-toolbar">
            <div class="flex flex-col gap-3 px-3 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-[18px] font-bold uppercase text-slate-900">Lead Management</h1>
                        <span
                            class="border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ number_format($leads->total()) }}
                            RECORDS</span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        @if ($hasFullAccess)
                            Company CRM leads — search, label, assign and manage.
                        @elseif($isTeamLeader)
                            Your leads and your team employees' leads.
                        @else
                            Leads assigned to your account.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="showFilters=!showFilters" class="software-btn"
                        :class="showFilters ? 'border-blue-500 bg-blue-50 text-blue-700' : ''">FILTERS</button>
                    @can('leads.labels.manage')
                    <button type="button" @click="showCreateLabelModal=true" class="software-btn">+ CREATE LABEL</button>
                    <button type="button"
                        @click="if(selected.length===0){alert('Please select at least one lead.')}else{labelAction='add';showLabelModal=true}"
                        class="software-btn border-violet-300 text-violet-700">LABEL SELECTED <span x-show="selected.length"
                            x-text="'('+selected.length+')'"></span></button>
                    @endcan
                    
                        @can('leads.assign')
                        <button type="button" @click="bulkAction='assign';assignmentScope='selected';showBulkModal=true"
                            class="software-btn">BULK ASSIGN</button>
                            
                                
                        <button type="button" @click="bulkAction='unassign';assignmentScope='selected';showBulkModal=true"
                            class="software-btn border-rose-300 text-rose-700">BULK UNASSIGN</button>
                            @endcan

                            @can('leads.import') 
                                <a href="{{ route('leads.import.create') }}" class="software-btn">IMPORT</a>
                            @endcan

                    
                    @can('leads.create')
                    <a href="{{ route('leads.create') }}" class="software-btn software-btn-primary">+ NEW LEAD</a>
                    @endcan
                </div>
            </div>
        </section>

        {{-- Search - Always Visible --}}
        <section class="software-panel">
            <form method="GET" action="{{ route('leads.index') }}" class="p-3">
                @foreach (request()->except(['page', 'search']) as $key => $value)
                    @if (is_scalar($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="w-full !pl-10"
                            placeholder="Search by name, mobile, company, email or city..."
                        >
                    </div>

                    <button type="submit" class="software-btn software-btn-primary">
                        SEARCH
                    </button>

                    @if (request()->filled('search'))
                        <a
                            href="{{ route('leads.index', request()->except(['page', 'search'])) }}"
                            class="software-btn"
                        >
                            CLEAR SEARCH
                        </a>
                    @endif
                </div>
            </form>
        </section>

        {{-- Demo Send Tab --}}
        @php
            $demoBaseQuery = request()->except(['page', 'demo_send']);
            $isDemoSendTab = request()->boolean('demo_send');
        @endphp

        <section class="software-panel">
            <div class="software-panel-title">
                <span>Lead Type</span>

                @if ($isDemoSendTab)
                    <span class="text-[10px] font-bold text-emerald-700">
                        DEMO SEND LEADS
                    </span>
                @endif
            </div>

            <div class="crm-scrollbar flex gap-1 overflow-x-auto p-2">
                <a
                    href="{{ route('leads.index', $demoBaseQuery) }}"
                    class="software-btn {{ !$isDemoSendTab ? 'border-slate-800 bg-slate-800 text-white' : '' }}"
                >
                    ALL LEADS
                </a>

                <a
                    href="{{ route('leads.index', array_merge($demoBaseQuery, ['demo_send' => 1])) }}"
                    class="software-btn {{ $isDemoSendTab ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : '' }}"
                >
                    DEMO SEND
                </a>
            </div>
        </section>

        {{-- Labels --}}
        <section class="software-panel">
            <div class="software-panel-title">
                <span>Lead Labels</span>
                @if (request('label_id'))
                    <a href="{{ route('leads.index', request()->except(['page', 'label_id'])) }}"
                        class="text-[10px] font-bold text-rose-600">CLEAR LABEL FILTER</a>
                @endif
            </div>
            <div class="crm-scrollbar flex gap-2 overflow-x-auto p-3">
                <a href="{{ route('leads.index', request()->except(['page', 'label_id'])) }}"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md border px-3 py-2 text-[11px] font-bold {{ !request('label_id') ? 'border-slate-800 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-700' }}">ALL
                    LEADS</a>
                @foreach ($labels as $label)
                    <div class="flex shrink-0 items-center overflow-hidden rounded-md border border-slate-200 bg-white">
                        <a href="{{ route('leads.index', array_merge(request()->except(['page', 'label_id']), ['label_id' => $label->id])) }}"
                            class="inline-flex items-center gap-2 px-3 py-2 text-[11px] font-bold {{ (string) request('label_id') === (string) $label->id ? 'bg-slate-100' : '' }}">
                            <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $label->color }}"></span>
                            {{ $label->name }} <span class="text-slate-400">{{ $label->leads_count }}</span>
                        </a>
                        @can('leads.labels.manage')
                            <form method="POST" action="{{ route('lead-labels.destroy', $label) }}"
                                onsubmit="return confirm('Delete this label? Leads will not be deleted.')"
                                class="border-l border-slate-200">@csrf @method('DELETE')<button
                                    class="px-2 py-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600">×</button></form>
                        @endcan
                    </div>
                @endforeach
                @if ($labels->isEmpty())
                    <div class="text-[11px] text-slate-500">No labels yet. Create your first label.</div>
                @endif
            </div>
        </section>

        {{-- Call Disposition tabs --}}
        @php
            $dispositionBaseQuery = request()->except(['page', 'call_disposition']);
            $currentDisposition = (string) request('call_disposition', '');
        @endphp
        <section class="software-panel">
            <div class="software-panel-title"><span>Latest Call Disposition</span>
                @if ($currentDisposition !== '')
                    <a href="{{ route('leads.index', $dispositionBaseQuery) }}"
                        class="text-[10px] font-bold text-rose-600">RESET</a>
                @endif
            </div>
            <div class="crm-scrollbar flex gap-1 overflow-x-auto p-2">
                <a href="{{ route('leads.index', $dispositionBaseQuery) }}"
                    class="software-btn {{ $currentDisposition === '' ? 'border-slate-800 bg-slate-800 text-white' : '' }}">ALL</a>
                <a href="{{ route('leads.index', array_merge($dispositionBaseQuery, ['call_disposition' => 'no_call'])) }}"
                    class="software-btn {{ $currentDisposition === 'no_call' ? 'border-amber-400 bg-amber-50 text-amber-700' : '' }}">NO
                    CALL YET</a>
                @foreach ($dispositions as $disposition)
                    <a href="{{ route('leads.index', array_merge($dispositionBaseQuery, ['call_disposition' => $disposition->id])) }}"
                        class="software-btn {{ $currentDisposition === (string) $disposition->id ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : '' }}">{{ strtoupper($disposition->name) }}</a>
                @endforeach
            </div>
        </section>

        {{-- Filters --}}
        <section x-show="showFilters" x-cloak class="software-panel">
            <div class="software-panel-title"><span>Filters</span><a href="{{ route('leads.index') }}"
                    class="text-[10px] font-bold text-rose-600">RESET ALL</a></div>
            <form method="GET" action="{{ route('leads.index') }}"
                class="grid gap-3 p-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="demo_send" value="{{ request('demo_send') }}">
                <input type="hidden" name="call_disposition" value="{{ request('call_disposition') }}">
                <input type="hidden" name="label_id" value="{{ request('label_id') }}">
                <div><label class="software-label">Source</label><select name="source" class="w-full">
                        <option value="">All Sources</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected((string) request('source') === (string) $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($canFilterByEmployee)
                    <div><label class="software-label">Employee</label><select name="assigned_to" class="w-full">
                            <option value="">All Employees</option>
                            @if ($hasFullAccess)
                                <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
                                @endif @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}
                                    </option>
                                @endforeach
                        </select>
                    </div>
                @endif
                @if ($canFilterByTeam)
                    <div><label class="software-label">Team</label><select name="team_id" class="w-full">
                            <option value="">All Teams</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>{{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div><label class="software-label">Priority</label><select name="priority" class="w-full">
                        <option value="">All</option>
                        @foreach (['low', 'normal', 'high', 'urgent', 'hot'] as $p)
                            <option value="{{ $p }}" @selected(request('priority') === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="software-label">Temperature</label><select name="temperature" class="w-full">
                        <option value="">All</option>
                        @foreach (['cold', 'warm', 'hot'] as $t)
                            <option value="{{ $t }}" @selected(request('temperature') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="software-label">From Date</label><input type="date" name="date_from"
                        value="{{ request('date_from') }}" class="w-full"></div>
                <div><label class="software-label">To Date</label><input type="date" name="date_to"
                        value="{{ request('date_to') }}" class="w-full"></div>
                <div><label class="software-label">Per Page</label><select name="per_page" class="w-full">
                        @foreach ([25, 50, 100, 200] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end"><button class="software-btn software-btn-primary w-full">APPLY
                        FILTERS</button></div>
            </form>
        </section>

        {{-- Selected bar --}}
        <div x-show="selected.length>0" x-cloak
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2">
            <div class="font-bold text-violet-800"><span x-text="selected.length"></span> lead(s) selected</div>
            <div class="flex gap-2"><button type="button" @click="labelAction='add';showLabelModal=true"
                    class="software-btn border-violet-300 text-violet-700">ADD TO LABEL</button><button type="button"
                    @click="labelAction='remove';showLabelModal=true"
                    class="software-btn border-rose-300 text-rose-700">REMOVE LABEL</button><button type="button"
                    @click="selected=[];selectAllPage=false" class="software-btn">CLEAR</button></div>
        </div>

        {{-- Table --}}
        <section class="software-panel overflow-hidden">
            <div class="software-panel-title">
                <span>Lead Register</span>
                <form method="GET" action="{{ route('leads.index') }}" class="flex items-center gap-2">
                    @foreach (request()->except(['page', 'per_page']) as $key => $value)
                        @if (!is_array($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <span class="text-[10px] text-slate-500">Per page</span><select name="per_page"
                        onchange="this.form.submit()" class="!min-h-[28px] !py-1">
                        @foreach ([25, 50, 100, 200] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="crm-scrollbar max-h-[68vh] overflow-auto">
                <table class="w-full min-w-[1450px] text-sm">
                    <thead>
                        <tr class="text-left uppercase">
                            <th class="w-10 px-3 py-2"><input type="checkbox" x-model="selectAllPage"
                                    @change="togglePage(@js($leads->pluck('id')->map(fn($id) => (int) $id)->values()))"
                                    class="rounded border-slate-300 text-blue-600"></th>
                            <th class="px-3 py-2">Lead</th>
                            <th class="px-3 py-2">Mobile</th>
                            <th class="px-3 py-2">Labels</th>
                            <th class="px-3 py-2">Source</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Priority</th>
                            <th class="px-3 py-2">Temp.</th>
                            <th class="px-3 py-2">Team</th>
                            <th class="px-3 py-2">Owner</th>
                            <th class="px-3 py-2">Demo Send</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($leads as $lead)
                            @php
                                $priorityClass = match ($lead->priority) {
                                    'hot', 'urgent' => 'bg-rose-50 text-rose-700',
                                    'high' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $temperatureClass = match ($lead->temperature) {
                                    'hot' => 'text-rose-700',
                                    'warm' => 'text-amber-700',
                                    default => 'text-blue-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-3 py-2.5"><input type="checkbox" value="{{ $lead->id }}"
                                        x-model.number="selected" class="rounded border-slate-300 text-blue-600"></td>
                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-900">{{ $lead->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $lead->company_name ?: 'Individual Lead' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-800">{{ $lead->mobile }}</div>
                                    @if ($lead->city)
                                        <div class="text-xs text-slate-500">{{ $lead->city }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex max-w-[260px] flex-wrap gap-1">
                                        @forelse($lead->labels as $label)
                                            <a href="{{ route('leads.index', array_merge(request()->except(['page', 'label_id']), ['label_id' => $label->id])) }}"
                                                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                                style="border-color:{{ $label->color }}55;background:{{ $label->color }}12;color:{{ $label->color }}"><span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                style="background:{{ $label->color }}"></span>{{ $label->name }}</a>@empty<span
                                                class="text-xs text-slate-400">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">{{ $lead->source?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5"><span
                                        class="rounded-full border border-blue-100 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{{ $lead->status?->name ?? 'New' }}</span>
                                </td>
                                <td class="px-3 py-2.5"><span
                                        class="rounded-full px-2 py-1 text-xs font-semibold capitalize {{ $priorityClass }}">{{ $lead->priority }}</span>
                                </td>
                                <td class="px-3 py-2.5"><span
                                        class="font-medium capitalize {{ $temperatureClass }}">{{ $lead->temperature }}</span>
                                </td>
                                <td class="px-3 py-2.5">{{ $lead->team?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</td>
                                                                <td class="px-3 py-2">
                                    @if ($lead->demo_send)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Sent
                                        </span>
                                    @else
                                        <span class="text-xs font-medium text-slate-400">
                                            Not Sent
                                        </span>
                                    @endif
                                </td>

<td class="px-3 py-2.5 text-right"><a
                                        href="{{ route('leads.show', array_merge(['lead' => $lead->id], request()->except('page'))) }}"
                                        class="software-btn !min-h-[26px] !px-2.5 text-blue-700">OPEN</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-14 text-center text-slate-500">No leads found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($leads->hasPages())
                <div class="border-t border-slate-200 bg-slate-50 px-3 py-2">{{ $leads->links() }}</div>
            @endif
        </section>

        {{-- Create label modal --}}
        <div x-show="showCreateLabelModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            @keydown.escape.window="showCreateLabelModal=false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl" @click.outside="showCreateLabelModal=false">
                @can('leads.labels.manage')
                <form method="POST" action="{{ route('lead-labels.store') }}">@csrf
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-bold">Create Lead Label</h2>
                        <p class="mt-1 text-sm text-slate-500">WhatsApp list ki tarah custom group banaiye.</p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div><label class="software-label">Label Name</label><input type="text" name="name"
                                required maxlength="100" class="w-full" placeholder="e.g. VIP Customer"></div>
                        <div><label class="software-label">Label Color</label>
                            <div class="flex items-center gap-3"><input type="color" name="color" value="#7C3AED"
                                    class="h-10 w-16 cursor-pointer rounded border"><span
                                    class="text-xs text-slate-500">Choose any color</span></div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 p-4"><button type="button"
                            @click="showCreateLabelModal=false" class="software-btn">CANCEL</button><button
                            class="software-btn software-btn-primary">CREATE LABEL</button></div>
                </form>
                @endcan
            </div>
        </div>

        {{-- Bulk label modal --}}
        <div x-show="showLabelModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
            @keydown.escape.window="showLabelModal=false">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl" @click.outside="showLabelModal=false">
                @can('leads.labels.manage')
                <form method="POST" action="{{ route('leads.bulk-label') }}"
                    @submit="if(selected.length===0){$event.preventDefault();alert('Please select at least one lead.');}">
                    @csrf
                    <template x-for="leadId in selected" :key="leadId"><input type="hidden" name="lead_ids[]"
                            :value="leadId"></template>
                    <input type="hidden" name="label_action" :value="labelAction">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-bold"
                            x-text="labelAction==='add' ? 'Add Leads to Label' : 'Remove Leads from Label'"></h2>
                        <p class="mt-1 text-sm text-slate-500"><span x-text="selected.length"></span> selected lead(s).
                        </p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div><label class="software-label">Action</label>
                            <div class="grid grid-cols-2 gap-2"><button type="button" @click="labelAction='add'"
                                    class="software-btn"
                                    :class="labelAction === 'add' ? 'border-violet-400 bg-violet-50 text-violet-700' : ''">ADD</button><button
                                    type="button" @click="labelAction='remove'" class="software-btn"
                                    :class="labelAction === 'remove' ? 'border-rose-400 bg-rose-50 text-rose-700' : ''">REMOVE</button>
                            </div>
                        </div>
                        <div><label class="software-label">Select Label</label><select name="label_id" required
                                class="w-full">
                                <option value="">Choose label...</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 p-4"><button type="button"
                            @click="showLabelModal=false" class="software-btn">CANCEL</button><button
                            class="software-btn software-btn-primary"
                            :class="labelAction === 'remove' ? '!border-rose-600 !bg-rose-600' : ''"
                            x-text="labelAction==='add'?'ADD TO LABEL':'REMOVE LABEL'"></button></div>
                </form>
                @endcan
            </div>
        </div>

        {{-- Existing bulk assign/unassign --}}
        @if ($hasFullAccess)
            <div x-show="showBulkModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                @keydown.escape.window="showBulkModal=false">
                <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-2xl"
                    @click.outside="showBulkModal=false">
                    @can('leads.assign')
                    <form method="POST" action="{{ route('leads.bulk-assign') }}"
                        @submit="if(assignmentScope==='selected'&&selected.length===0){$event.preventDefault();alert('Please select at least one lead.');return;} if(bulkAction==='unassign'&&!confirm('Selected scope ko unassign karna hai?')){$event.preventDefault();}">
                        @csrf
                        <input type="hidden" name="bulk_action" :value="bulkAction"><input type="hidden"
                            name="assignment_scope" :value="assignmentScope">
                        <template x-for="leadId in selected" :key="leadId"><input type="hidden"
                                name="lead_ids[]" :value="leadId"></template>
                        <input type="hidden" name="search" value="{{ request('search') }}"><input type="hidden"
                            name="status" value="{{ request('status') }}"><input type="hidden" name="source"
                            value="{{ request('source') }}"><input type="hidden" name="filter_assigned_to"
                            value="{{ request('assigned_to') }}"><input type="hidden" name="team_id"
                            value="{{ request('team_id') }}"><input type="hidden" name="priority"
                            value="{{ request('priority') }}"><input type="hidden" name="temperature"
                            value="{{ request('temperature') }}"><input type="hidden" name="call_disposition"
                            value="{{ request('call_disposition') }}"><input type="hidden" name="demo_send"
                            value="{{ request('demo_send') }}"><input type="hidden" name="per_page"
                            value="{{ request('per_page') }}"><input type="hidden" name="date_from"
                            value="{{ request('date_from') }}"><input type="hidden" name="date_to"
                            value="{{ request('date_to') }}"><input type="hidden" name="label_id_filter"
                            value="{{ request('label_id') }}">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-lg font-bold"
                                x-text="bulkAction==='unassign'?'Bulk Unassign Leads':'Bulk Assign Leads'"></h2>
                        </div>
                        <div class="space-y-4 p-5">
                            <div><label class="software-label">Action</label>
                                <div class="grid grid-cols-2 gap-2"><button type="button" @click="bulkAction='assign'"
                                        class="software-btn"
                                        :class="bulkAction === 'assign' ? 'border-blue-400 bg-blue-50 text-blue-700' : ''">ASSIGN</button><button
                                        type="button" @click="bulkAction='unassign'" class="software-btn"
                                        :class="bulkAction === 'unassign' ? 'border-rose-400 bg-rose-50 text-rose-700' : ''">UNASSIGN</button>
                                </div>
                            </div>
                            <div><label class="software-label">Scope</label><select x-model="assignmentScope"
                                    name="assignment_scope_ui" class="w-full">
                                    <option value="selected">Selected leads only</option>
                                    <option value="filtered">All current filtered leads</option>
                                </select></div>
                            <div x-show="bulkAction==='assign'"><label class="software-label">Employee</label><select
                                    name="assigned_to" class="w-full" :required="bulkAction === 'assign'">
                                    <option value="">Select employee</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} @if ($user->employee_code)
                                                ({{ $user->employee_code }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select></div>
                            <div><label class="software-label">Reason</label><input type="text" name="reason"
                                    required maxlength="500" class="w-full" placeholder="Reason..."></div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 p-4"><button type="button"
                                @click="showBulkModal=false" class="software-btn">CANCEL</button><button
                                class="software-btn software-btn-primary"
                                :class="bulkAction === 'unassign' ? '!border-rose-600 !bg-rose-600' : ''"
                                x-text="bulkAction==='unassign'?'UNASSIGN LEADS':'ASSIGN LEADS'"></button></div>
                    </form>
                    @endcan
                </div>
            </div>
        @endif
    </div>
@endsection