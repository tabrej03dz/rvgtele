@extends('layouts.crm', ['title' => 'Data'])

@section('content')

@once
<style>
    [x-cloak] { display: none !important; }

    .data-page {
        --navy: #0f2744;
        --navy-2: #18395f;
        --yellow: #f6b800;
        --yellow-soft: #fff8dc;
        --line: #e6ebf2;
        --muted: #6b778c;
        --bg: #f6f8fb;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #172033;
        font-size: 12px;
    }

    .data-page::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background: var(--bg);
    }

    .data-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 5px 18px rgba(15, 39, 68, .05);
    }

    .data-btn {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 13px;
        border: 1px solid #dfe5ed;
        border-radius: 9px;
        background: #fff;
        color: #314057;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
        transition: .16s ease;
    }

    .data-btn:hover {
        border-color: #c6d0dc;
        background: #f9fafb;
    }

    .data-btn svg { width: 14px; height: 14px; }

    .data-btn-primary {
        border-color: #e2a900;
        background: linear-gradient(180deg, #ffc928 0%, #f6b800 100%);
        color: #172033;
        box-shadow: 0 7px 14px rgba(246, 184, 0, .18);
    }

    .data-btn-primary:hover {
        border-color: #d99f00;
        background: #f4b500;
    }

    .data-btn-success {
        border-color: #b6ead2;
        background: #effcf6;
        color: #087a4f;
    }

    .data-btn-danger {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .data-page input[type=text],
    .data-page select {
        width: 100%;
        min-height: 38px;
        border: 1px solid #d7dee8 !important;
        border-radius: 9px !important;
        background: #fff;
        padding: 0 11px;
        color: #172033;
        font-size: 11px !important;
        outline: none;
    }

    .data-page input:focus,
    .data-page select:focus {
        border-color: #e5ae00 !important;
        box-shadow: 0 0 0 3px rgba(246, 184, 0, .13);
    }

    .data-label {
        display: block;
        margin-bottom: 5px;
        color: #687589;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .category-strip {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 10px;
        scrollbar-width: thin;
    }

    .category-tab {
        flex: 0 0 auto;
        min-height: 39px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        border: 1px solid #e2e7ee;
        border-radius: 10px;
        background: #fff;
        color: #4b5870;
        font-size: 10px;
        font-weight: 800;
        transition: .16s ease;
    }

    .category-tab:hover {
        border-color: #e8bf43;
        background: #fffdf4;
    }

    .category-tab.active {
        border-color: #efb500;
        background: var(--yellow-soft);
        color: #172033;
        box-shadow: inset 0 0 0 1px rgba(246, 184, 0, .08);
    }

    .category-count {
        min-width: 24px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 7px;
        border-radius: 999px;
        background: #f0f3f7;
        color: #536176;
        font-size: 9px;
        font-weight: 900;
    }

    .category-tab.active .category-count {
        background: #f6b800;
        color: #14233a;
    }

    .status-chip {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        padding: 0 10px;
        border: 1px solid #e2e7ee;
        border-radius: 999px;
        background: #fff;
        color: #56637a;
        font-size: 9px;
        font-weight: 800;
    }

    .status-chip.active {
        border-color: var(--navy);
        background: var(--navy);
        color: #fff;
    }

    .status-chip.converted.active {
        border-color: #0f8b5f;
        background: #0f8b5f;
    }

    .status-chip.deleted.active {
        border-color: #be123c;
        background: #be123c;
    }

    .table-wrap { overflow-x: auto; }

    .data-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table thead th {
        height: 42px;
        padding: 8px 10px;
        border-bottom: 1px solid #e8edf3;
        background: #f9fafc;
        color: #445169;
        text-align: left;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .025em;
    }

    .data-table tbody td {
        padding: 11px 10px;
        border-bottom: 1px solid #edf1f5;
        vertical-align: top;
        font-size: 11px;
    }

    .data-table tbody tr:hover { background: #fffdf7; }

    .data-name {
        color: #172033;
        font-weight: 800;
    }

    .data-name:hover { color: #b98200; text-decoration: underline; }

    .muted { margin-top: 3px; color: #7b8798; font-size: 10px; }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
    }

    .pill-category { background: #fff6cf; color: #8a6400; }
    .pill-success { background: #eafaf3; color: #087a4f; }
    .pill-pending { background: #fff7e6; color: #9a6500; }

    @media (max-width: 640px) {
        .data-card { border-radius: 11px; }
        .data-page { font-size: 11px; }
        .category-strip { padding: 8px; }
    }
</style>
@endonce

<div
    class="data-page mx-auto w-full max-w-none space-y-3 px-1 pb-5"
    x-data="{
        selected: [],
        selectAll: false,
        showFilters: @js(request()->hasAny(['company_id', 'converted'])),
        toggleAll(ids) {
            this.selected = this.selectAll ? [...ids] : [];
        }
    }"
>

    {{-- TOP HEADER + SEARCH --}}
    <section class="data-card overflow-hidden">
        <div class="flex flex-col gap-3 p-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0f2744] text-white">
                    <i data-lucide="database" class="h-5 w-5"></i>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-[18px] font-extrabold text-slate-900">Data Management</h1>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-extrabold text-slate-600">
                            {{ number_format($data->total()) }} RESULTS
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Customer data ko category-wise manage, filter aur lead me convert karein.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showFilters = !showFilters" class="data-btn">
                    <i data-lucide="sliders-horizontal"></i>
                    FILTERS
                </button>

                @can('data.import')
                    <a href="{{ route('data.import.create') }}" class="data-btn">
                        <i data-lucide="upload"></i>
                        IMPORT
                    </a>
                @endcan

                @can('data.create')
                    <a href="{{ route('data.create') }}" class="data-btn data-btn-primary">
                        <i data-lucide="plus"></i>
                        NEW DATA
                    </a>
                @endcan
            </div>
        </div>

        <div class="border-t border-slate-100 p-3">
            <form method="GET" action="{{ route('data.index') }}" class="flex flex-col gap-2 sm:flex-row">
                @foreach(request()->except(['page', 'q']) as $key => $value)
                    @if(is_scalar($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by name, mobile, business, city..."
                        class="!pl-10"
                    >
                </div>

                <button type="submit" class="data-btn data-btn-primary">
                    <i data-lucide="search"></i>
                    SEARCH
                </button>

                @if(request()->filled('q'))
                    <a href="{{ route('data.index', request()->except(['page', 'q'])) }}" class="data-btn">
                        CLEAR
                    </a>
                @endif
            </form>
        </div>
    </section>

    {{-- DYNAMIC CATEGORY TABS: category control sirf yahin hai, filter me repeat nahi hai --}}
    <section class="data-card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#fff4c3] text-[#9b7100]">
                    <i data-lucide="tags" class="h-4 w-4"></i>
                </span>
                <div>
                    <div class="text-[11px] font-extrabold text-slate-800">Categories</div>
                    <div class="text-[9px] text-slate-500">Dynamic tabs with live counts</div>
                </div>
            </div>
        </div>

        <div class="category-strip">
            <a
                href="{{ route('data.index', request()->except(['page', 'category_id'])) }}"
                class="category-tab {{ !request()->filled('category_id') ? 'active' : '' }}"
            >
                <i data-lucide="layout-grid" class="h-4 w-4"></i>
                ALL
                <span class="category-count">{{ number_format($allCategoryCount) }}</span>
            </a>

            @foreach($categories as $category)
                @php
                    $count = (int) ($categoryCounts[$category->id] ?? 0);
                    $isActive = (string) request('category_id') === (string) $category->id;
                @endphp

                <a
                    href="{{ route('data.index', array_merge(request()->except(['page', 'category_id']), ['category_id' => $category->id])) }}"
                    class="category-tab {{ $isActive ? 'active' : '' }}"
                >
                    <i data-lucide="folder" class="h-4 w-4"></i>
                    {{ strtoupper($category->name) }}
                    <span class="category-count">{{ number_format($count) }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- COMPACT STATUS FILTERS --}}
    <section class="data-card p-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[9px] font-extrabold uppercase tracking-wide text-slate-400">Status</span>

            <a
                href="{{ route('data.index', request()->except(['page', 'converted', 'show'])) }}"
                class="status-chip {{ !request()->has('converted') && request('show') !== 'deleted' ? 'active' : '' }}"
            >
                ALL
            </a>

            <a
                href="{{ route('data.index', array_merge(request()->except(['page', 'converted', 'show']), ['converted' => 0])) }}"
                class="status-chip {{ request('converted') === '0' && request('show') !== 'deleted' ? 'active' : '' }}"
            >
                UNCONVERTED
            </a>

            <a
                href="{{ route('data.index', array_merge(request()->except(['page', 'converted', 'show']), ['converted' => 1])) }}"
                class="status-chip converted {{ request('converted') === '1' && request('show') !== 'deleted' ? 'active' : '' }}"
            >
                CONVERTED
            </a>

            <a
                href="{{ route('data.index', array_merge(request()->except(['page', 'converted', 'show']), ['show' => 'deleted'])) }}"
                class="status-chip deleted {{ request('show') === 'deleted' ? 'active' : '' }}"
            >
                DELETED
            </a>
        </div>
    </section>

    {{-- EXTRA FILTERS: category dropdown intentionally removed to avoid duplicate category UI --}}
    <section x-show="showFilters" x-cloak class="data-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
            <div class="flex items-center gap-2 font-extrabold text-slate-800">
                <i data-lucide="filter" class="h-4 w-4 text-[#b98500]"></i>
                Additional Filters
            </div>

            <a href="{{ route('data.index') }}" class="text-[9px] font-extrabold text-rose-600">RESET ALL</a>
        </div>

        <form method="GET" action="{{ route('data.index') }}" class="grid gap-3 p-3 md:grid-cols-3">
            <input type="hidden" name="q" value="{{ request('q') }}">

            @if(request()->filled('category_id'))
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
            @endif

            @if(request('show') === 'deleted')
                <input type="hidden" name="show" value="deleted">
            @endif

            <div>
                <label class="data-label">Company</label>
                <select name="company_id">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="data-label">Conversion</label>
                <select name="converted">
                    <option value="">All</option>
                    <option value="0" @selected(request('converted') === '0')>Not Converted</option>
                    <option value="1" @selected(request('converted') === '1')>Converted</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="data-btn data-btn-primary w-full">
                    <i data-lucide="check"></i>
                    APPLY FILTERS
                </button>
            </div>
        </form>
    </section>

    {{-- BULK ACTION BAR --}}
    @if(request('show') !== 'deleted')
        <div
            x-show="selected.length > 0"
            x-cloak
            class="data-card flex flex-wrap items-center justify-between gap-3 border-[#f0cf67] bg-[#fffaf0] px-4 py-3"
        >
            <div class="font-extrabold text-[#765700]">
                <span x-text="selected.length"></span> record(s) selected
            </div>

            <div class="flex flex-wrap gap-2">
                <form
                    method="POST"
                    action="{{ route('data.bulk-convert-to-lead') }}"
                    @submit="if (!confirm('Selected data ko leads me convert karna hai?')) $event.preventDefault()"
                >
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="data-btn data-btn-success">
                        <i data-lucide="user-plus"></i>
                        CONVERT TO LEADS
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('data.bulk-delete') }}"
                    @submit="if (!confirm('Selected records delete karna hai?')) $event.preventDefault()"
                >
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="data-btn data-btn-danger">
                        <i data-lucide="trash-2"></i>
                        DELETE
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- DATA TABLE --}}
    <section class="data-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <i data-lucide="table-2" class="h-4 w-4"></i>
                </span>
                <div>
                    <div class="text-[11px] font-extrabold text-slate-800">Data Register</div>
                    <div class="text-[9px] text-slate-500">
                        Showing {{ $data->count() }} on this page
                    </div>
                </div>
            </div>

            @if(request()->filled('category_id'))
                @php($selectedCategory = $categories->firstWhere('id', (int) request('category_id')))
                @if($selectedCategory)
                    <span class="pill pill-category">
                        <i data-lucide="tag" class="h-3 w-3"></i>
                        {{ $selectedCategory->name }}
                    </span>
                @endif
            @endif
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @if(request('show') !== 'deleted')
                            <th style="width:40px">
                                <input
                                    type="checkbox"
                                    x-model="selectAll"
                                    @change="toggleAll(@js($data->pluck('id')->map(fn($id) => (int) $id)->values()))"
                                >
                            </th>
                        @endif
                        <th>Customer / Business</th>
                        <th>Mobile</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Company</th>
                        <th>Source</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($data as $row)
                        <tr>
                            @if(request('show') !== 'deleted')
                                <td>
                                    <input type="checkbox" value="{{ $row->id }}" x-model.number="selected">
                                </td>
                            @endif

                            <td>
                                <a href="{{ route('data.show', $row) }}" class="data-name">
                                    {{ $row->name ?: 'Unnamed' }}
                                </a>
                                @if($row->company_name)
                                    <div class="muted">{{ $row->company_name }}</div>
                                @endif
                                @if($row->email)
                                    <div class="muted">{{ $row->email }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="font-extrabold text-slate-700">{{ $row->mobile ?: '—' }}</div>
                                @if($row->whatsapp_number && $row->whatsapp_number !== $row->mobile)
                                    <div class="muted">WA: {{ $row->whatsapp_number }}</div>
                                @endif
                            </td>

                            <td>
                                @if($row->categoryInfo)
                                    <span class="pill pill-category">{{ $row->categoryInfo->name }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td>
                                {{ $row->city ?: '—' }}
                                @if($row->district)
                                    <div class="muted">{{ $row->district }}</div>
                                @endif
                                @if($row->state)
                                    <div class="muted">{{ $row->state }}</div>
                                @endif
                            </td>

                            <td>{{ $row->company?->name ?? '—' }}</td>

                            <td>
                                {{ $row->lead_source ?: '—' }}
                                @if($row->campaign)
                                    <div class="muted">{{ $row->campaign }}</div>
                                @endif
                            </td>

                            <td>
                                @if($row->estimated_budget !== null)
                                    ₹{{ number_format((float) $row->estimated_budget, 2) }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if($row->converted)
                                    <span class="pill pill-success">
                                        <i data-lucide="circle-check" class="h-3 w-3"></i>
                                        Converted
                                    </span>
                                @else
                                    <span class="pill pill-pending">Pending</span>
                                @endif
                            </td>

                            <td>
                                {{ $row->created_at?->format('d M Y') }}
                                <div class="muted">{{ $row->created_at?->format('h:i A') }}</div>
                            </td>

                            <td>
                                <div class="flex justify-end gap-1">
                                    @if(request('show') === 'deleted')
                                        <form method="POST" action="{{ route('data.restore', $row->id) }}">
                                            @csrf
                                            <button class="data-btn data-btn-success !min-h-[29px] !px-2">RESTORE</button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('data.force-delete', $row->id) }}"
                                            onsubmit="return confirm('Permanently delete karna hai?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="data-btn data-btn-danger !min-h-[29px] !px-2">DELETE</button>
                                        </form>
                                    @else
                                        <a href="{{ route('data.show', $row) }}" class="data-btn !min-h-[29px] !px-2">OPEN</a>

                                        @if(!$row->converted)
                                            <form
                                                method="POST"
                                                action="{{ route('data.convert-to-lead', $row) }}"
                                                onsubmit="return confirm('Is data ko lead me convert karna hai?')"
                                            >
                                                @csrf
                                                <button class="data-btn data-btn-success !min-h-[29px] !px-2">CONVERT</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-16 text-center text-slate-500">
                                No data found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col justify-between gap-3 border-t border-slate-100 bg-white px-4 py-3 sm:flex-row sm:items-center">
            <div class="text-[10px] text-slate-500">
                Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ number_format($data->total()) }} results
            </div>

            @if($data->hasPages())
                <div>{{ $data->links() }}</div>
            @endif
        </div>
    </section>

</div>
@endsection
