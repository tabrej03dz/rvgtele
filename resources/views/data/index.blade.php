@extends('layouts.crm', ['title' => 'Data'])

@section('content')

@once
<style>
    [x-cloak] {
        display: none !important;
    }

    .software-ui {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system,
            BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #18213a;
        font-size: 12px;
    }

    .software-ui::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background: #f8faff;
    }

    .software-panel,
    .software-toolbar {
        border: 1px solid #e5e9f2;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(31, 42, 80, .055);
    }

    .software-panel-title {
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 16px;
        border-bottom: 1px solid #edf0f5;
        background: #fff;
        color: #17203a;
        font-size: 13px;
        font-weight: 800;
        border-radius: 12px 12px 0 0;
    }

    .panel-heading-label {
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .panel-heading-icon {
        display: inline-flex;
        width: 27px;
        height: 27px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .panel-heading-icon svg {
        width: 15px;
        height: 15px;
    }

    .software-btn {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #e1e5ed;
        border-radius: 8px;
        background: #fff;
        padding: 0 14px;
        color: #33405b;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
        transition: .15s;
    }

    .software-btn:hover {
        border-color: #c5bcff;
        background: #faf9ff;
        color: #6237e8;
    }

    .software-btn-primary {
        border-color: transparent;
        background: linear-gradient(
            100deg,
            #2563eb 0%,
            #6338ef 58%,
            #8b2de9 100%
        );
        color: #fff;
        box-shadow: 0 7px 16px rgba(77, 61, 232, .22);
    }

    .software-btn-primary:hover {
        color: #fff;
        transform: translateY(-1px);
    }

    .software-btn-danger {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .software-btn-success {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    .software-btn svg {
        width: 14px;
        height: 14px;
    }

    .software-label {
        margin-bottom: 5px;
        display: block;
        color: #475569;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .software-ui input[type=text],
    .software-ui select {
        min-height: 38px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        background: #fff;
        padding-left: 10px;
        padding-right: 10px;
        font-size: 11px !important;
        color: #0f172a;
    }

    .software-ui input:focus,
    .software-ui select:focus {
        border-color: #3b82f6 !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .10);
    }

    .data-toolbar-icon {
        display: inline-flex;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: linear-gradient(145deg, #2864fa, #842de8);
        color: #fff;
        box-shadow: 0 7px 15px rgba(78, 54, 229, .23);
    }

    .data-count-badge {
        border-radius: 6px;
        background: #f1f0ff;
        color: #6251d8;
        padding: 3px 8px;
        font-size: 9px;
        font-weight: 800;
    }

    .data-table-wrap {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        min-width: 1300px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table thead th {
        height: 43px;
        border-bottom: 1px solid #e8ebf2;
        background: #fbfcfe;
        color: #313b54;
        padding: 8px 10px;
        text-align: left;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .data-table tbody td {
        border-bottom: 1px solid #eef2f7;
        padding: 11px 10px;
        vertical-align: top;
        font-size: 11px;
    }

    .data-table tbody tr:hover {
        background: #f8fbff;
    }

    .data-link {
        color: #172033;
        font-weight: 700;
    }

    .data-link:hover {
        color: #2563eb;
        text-decoration: underline;
    }

    .data-muted {
        margin-top: 3px;
        color: #64748b;
        font-size: 10px;
    }
</style>
@endonce


<div
    class="software-ui mx-auto w-full max-w-none space-y-3 px-1 pb-5"
    x-data="{
        selected: [],
        selectAll: false,
        showFilters: @js(request()->hasAny(['company_id','category_id','converted','show'])),
        toggleAll(ids) {
            if (this.selectAll) {
                this.selected = [...ids];
            } else {
                this.selected = [];
            }
        }
    }"
>

    {{-- ========================================================= --}}
    {{-- SEARCH --}}
    {{-- ========================================================= --}}

    <section class="software-panel">

        <form
            method="GET"
            action="{{ route('data.index') }}"
            class="p-3"
        >

            @foreach(request()->except(['page', 'q']) as $key => $value)
                @if(is_scalar($value) && $value !== '')
                    <input
                        type="hidden"
                        name="{{ $key }}"
                        value="{{ $value }}"
                    >
                @endif
            @endforeach

            <div class="flex flex-col gap-2 sm:flex-row">

                <div class="relative flex-1">

                    <i
                        data-lucide="search"
                        class="
                            pointer-events-none
                            absolute
                            left-3
                            top-1/2
                            h-4
                            w-4
                            -translate-y-1/2
                            text-slate-400
                        "
                    ></i>

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search name, mobile, company, category, city..."
                        class="w-full !pl-10"
                    >

                </div>

                <button
                    type="submit"
                    class="software-btn software-btn-primary"
                >
                    <i data-lucide="search"></i>
                    SEARCH
                </button>

                @if(request()->filled('q'))
                    <a
                        href="{{ route(
                            'data.index',
                            request()->except(['page', 'q'])
                        ) }}"
                        class="software-btn"
                    >
                        CLEAR
                    </a>
                @endif

            </div>

        </form>

    </section>


    {{-- ========================================================= --}}
    {{-- HEADER TOOLBAR --}}
    {{-- ========================================================= --}}

    <section class="software-toolbar">

        <div
            class="
                flex
                flex-col
                gap-3
                px-3
                py-3
                lg:flex-row
                lg:items-center
                lg:justify-between
            "
        >

            <div class="flex items-center gap-3">

                <span class="data-toolbar-icon">
                    <i data-lucide="database"></i>
                </span>

                <div>

                    <div class="flex items-center gap-2">

                        <h1 class="text-[18px] font-bold text-slate-900">
                            Data Management
                        </h1>

                        <span class="data-count-badge">
                            {{ number_format($data->total()) }} RECORDS
                        </span>

                    </div>

                    <p class="mt-1 text-[11px] text-slate-500">
                        Raw customer data manage karein aur Leads me convert karein.
                    </p>

                </div>

            </div>


            <div class="flex flex-wrap items-center gap-2">

                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="software-btn"
                >
                    <i data-lucide="list-filter"></i>
                    FILTERS
                </button>

                @can('data.import')
                    <a
                        href="{{ route('data.import.create') }}"
                        class="software-btn"
                    >
                        <i data-lucide="upload"></i>
                        IMPORT
                    </a>
                @endcan
                @can('data.create')
                    <a
                        href="{{ route('data.create') }}"
                        class="software-btn software-btn-primary"
                    >
                        <i data-lucide="plus"></i>
                        NEW DATA
                    </a>
                @endcan

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- STATUS TABS --}}
    {{-- ========================================================= --}}

    <section class="software-panel">

        <div class="software-panel-title">

            <span class="panel-heading-label">

                <span class="panel-heading-icon">
                    <i data-lucide="list-filter"></i>
                </span>

                Conversion Status

            </span>

        </div>

        <div class="flex flex-wrap gap-2 p-3">

            <a
                href="{{ route(
                    'data.index',
                    request()->except(['converted', 'page'])
                ) }}"
                class="
                    software-btn
                    {{ !request()->has('converted')
                        ? 'software-btn-primary'
                        : ''
                    }}
                "
            >
                ALL
            </a>

            <a
                href="{{ route(
                    'data.index',
                    array_merge(
                        request()->except(['page', 'converted']),
                        ['converted' => 0]
                    )
                ) }}"
                class="
                    software-btn
                    {{ request('converted') === '0'
                        ? 'software-btn-primary'
                        : ''
                    }}
                "
            >
                UNCONVERTED
            </a>

            <a
                href="{{ route(
                    'data.index',
                    array_merge(
                        request()->except(['page', 'converted']),
                        ['converted' => 1]
                    )
                ) }}"
                class="
                    software-btn
                    {{ request('converted') === '1'
                        ? 'software-btn-success'
                        : ''
                    }}
                "
            >
                CONVERTED
            </a>

            <a
                href="{{ route('data.index', ['show' => 'deleted']) }}"
                class="
                    software-btn
                    {{ request('show') === 'deleted'
                        ? 'software-btn-danger'
                        : ''
                    }}
                "
            >
                DELETED
            </a>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- FILTERS --}}
    {{-- ========================================================= --}}

    <section
        x-show="showFilters"
        x-cloak
        class="software-panel"
    >

        <div class="software-panel-title">

            <span class="panel-heading-label">
                <span class="panel-heading-icon">
                    <i data-lucide="filter"></i>
                </span>
                Filters
            </span>

            <a
                href="{{ route('data.index') }}"
                class="text-[10px] font-bold text-rose-600"
            >
                RESET ALL
            </a>

        </div>


        <form
            method="GET"
            action="{{ route('data.index') }}"
            class="
                grid
                gap-3
                p-3
                md:grid-cols-2
                lg:grid-cols-4
            "
        >

            <input
                type="hidden"
                name="q"
                value="{{ request('q') }}"
            >


            {{-- Company --}}

            <div>

                <label class="software-label">
                    Company
                </label>

                <select
                    name="company_id"
                    class="w-full"
                >

                    <option value="">
                        All Companies
                    </option>

                    @foreach($companies as $company)

                        <option
                            value="{{ $company->id }}"
                            @selected(
                                (string) request('company_id')
                                ===
                                (string) $company->id
                            )
                        >
                            {{ $company->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Category --}}

            <div>

                <label class="software-label">
                    Category
                </label>

                <select
                    name="category_id"
                    class="w-full"
                >

                    <option value="">
                        All Categories
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

            </div>
            {{-- Converted --}}
            <div>
                <label class="software-label">
                    Conversion
                </label>
                <select
                    name="converted"
                    class="w-full"
                >
                    <option value="">
                        All
                    </option>
                    <option
                        value="0"
                        @selected(request('converted') === '0')
                    >
                        Not Converted
                    </option>

                    <option
                        value="1"
                        @selected(request('converted') === '1')
                    >
                        Converted
                    </option>

                </select>

            </div>


            <div class="flex items-end">

                <button
                    type="submit"
                    class="software-btn software-btn-primary w-full"
                >
                    APPLY FILTERS
                </button>

            </div>

        </form>

    </section>


    {{-- ========================================================= --}}
    {{-- SELECTED ACTION BAR --}}
    {{-- ========================================================= --}}

    @if(request('show') !== 'deleted')

        <div
            x-show="selected.length > 0"
            x-cloak
            class="
                flex
                flex-wrap
                items-center
                justify-between
                gap-3
                rounded-xl
                border
                border-violet-200
                bg-violet-50
                px-4
                py-3
            "
        >

            <div class="font-bold text-violet-800">

                <span x-text="selected.length"></span>
                record(s) selected

            </div>


            <div class="flex flex-wrap gap-2">

                <form
                    method="POST"
                    action="{{ route('data.bulk-convert-to-lead') }}"
                    @submit="
                        if (
                            !confirm(
                                'Selected data ko leads me convert karna hai?'
                            )
                        ) {
                            $event.preventDefault()
                        }
                    "
                >

                    @csrf

                    <template
                        x-for="id in selected"
                        :key="id"
                    >
                        <input
                            type="hidden"
                            name="ids[]"
                            :value="id"
                        >
                    </template>

                    <button
                        type="submit"
                        class="software-btn software-btn-success"
                    >
                        <i data-lucide="user-plus"></i>
                        CONVERT TO LEADS
                    </button>

                </form>


                <form
                    method="POST"
                    action="{{ route('data.bulk-delete') }}"
                    @submit="
                        if (
                            !confirm(
                                'Selected records delete karna hai?'
                            )
                        ) {
                            $event.preventDefault()
                        }
                    "
                >

                    @csrf
                    @method('DELETE')

                    <template
                        x-for="id in selected"
                        :key="id"
                    >
                        <input
                            type="hidden"
                            name="ids[]"
                            :value="id"
                        >
                    </template>

                    <button
                        type="submit"
                        class="software-btn software-btn-danger"
                    >
                        <i data-lucide="trash-2"></i>
                        DELETE
                    </button>

                </form>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- TABLE --}}
    {{-- ========================================================= --}}

    <section class="software-panel overflow-hidden">

        <div class="software-panel-title">

            <div class="flex items-center gap-2">

                <span class="panel-heading-label">

                    <span class="panel-heading-icon">
                        <i data-lucide="table-2"></i>
                    </span>

                    Data Register

                </span>

                <span class="data-count-badge">
                    {{ $data->count() }} SHOWING
                </span>

            </div>

        </div>


        <div class="data-table-wrap">

            <table class="data-table">

                <thead>

                    <tr>

                        @if(request('show') !== 'deleted')
                            <th style="width:40px">

                                <input
                                    type="checkbox"
                                    x-model="selectAll"
                                    @change="
                                        toggleAll(
                                            @js(
                                                $data
                                                    ->pluck('id')
                                                    ->map(fn($id) => (int) $id)
                                                    ->values()
                                            )
                                        )
                                    "
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

                                    <input
                                        type="checkbox"
                                        value="{{ $row->id }}"
                                        x-model.number="selected"
                                    >

                                </td>

                            @endif


                            <td>

                                <a
                                    href="{{ route('data.show', $row) }}"
                                    class="data-link"
                                >
                                    {{ $row->name ?: 'Unnamed' }}
                                </a>

                                @if($row->company_name)

                                    <div class="data-muted">
                                        {{ $row->company_name }}
                                    </div>

                                @endif

                                @if($row->email)

                                    <div class="data-muted">
                                        {{ $row->email }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                <div class="font-bold text-slate-700">
                                    {{ $row->mobile ?: '—' }}
                                </div>

                                @if($row->whatsapp_number)

                                    <div class="data-muted">
                                        WA: {{ $row->whatsapp_number }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                @if($row->categoryInfo)

                                    <span
                                        class="
                                            inline-flex
                                            rounded-full
                                            bg-indigo-50
                                            px-2
                                            py-1
                                            font-semibold
                                            text-indigo-700
                                        "
                                    >
                                        {{ $row->categoryInfo->name }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td>

                                {{ $row->city ?: '—' }}

                                @if($row->district)

                                    <div class="data-muted">
                                        {{ $row->district }}
                                    </div>

                                @endif

                                @if($row->state)

                                    <div class="data-muted">
                                        {{ $row->state }}
                                    </div>

                                @endif

                            </td>


                            <td>
                                {{ $row->company?->name ?? '—' }}
                            </td>


                            <td>

                                {{ $row->lead_source ?: '—' }}

                                @if($row->campaign)

                                    <div class="data-muted">
                                        {{ $row->campaign }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                @if($row->estimated_budget !== null)

                                    ₹{{ number_format(
                                        (float) $row->estimated_budget,
                                        2
                                    ) }}

                                @else
                                    —
                                @endif

                            </td>


                            <td>

                                @if($row->converted)

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1
                                            rounded-full
                                            bg-emerald-50
                                            px-2
                                            py-1
                                            font-bold
                                            text-emerald-700
                                        "
                                    >
                                        <i
                                            data-lucide="circle-check"
                                            class="h-3 w-3"
                                        ></i>

                                        Converted

                                    </span>

                                @else

                                    <span
                                        class="
                                            inline-flex
                                            rounded-full
                                            bg-amber-50
                                            px-2
                                            py-1
                                            font-bold
                                            text-amber-700
                                        "
                                    >
                                        Pending
                                    </span>

                                @endif

                            </td>


                            <td>

                                {{ $row->created_at?->format('d M Y') }}

                                <div class="data-muted">
                                    {{ $row->created_at?->format('h:i A') }}
                                </div>

                            </td>


                            <td>

                                <div
                                    class="
                                        flex
                                        justify-end
                                        gap-1
                                    "
                                >

                                    @if(request('show') === 'deleted')

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'data.restore',
                                                $row->id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                class="
                                                    software-btn
                                                    software-btn-success
                                                    !min-h-[29px]
                                                    !px-2
                                                "
                                            >
                                                RESTORE
                                            </button>

                                        </form>


                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'data.force-delete',
                                                $row->id
                                            ) }}"
                                            onsubmit="
                                                return confirm(
                                                    'Permanently delete karna hai?'
                                                )
                                            "
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                class="
                                                    software-btn
                                                    software-btn-danger
                                                    !min-h-[29px]
                                                    !px-2
                                                "
                                            >
                                                DELETE
                                            </button>

                                        </form>

                                    @else

                                        <a
                                            href="{{ route(
                                                'data.show',
                                                $row
                                            ) }}"
                                            class="
                                                software-btn
                                                !min-h-[29px]
                                                !px-2
                                            "
                                        >
                                            OPEN
                                        </a>


                                        @if(!$row->converted)

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'data.convert-to-lead',
                                                    $row
                                                ) }}"
                                                onsubmit="
                                                    return confirm(
                                                        'Is data ko lead me convert karna hai?'
                                                    )
                                                "
                                            >

                                                @csrf

                                                <button
                                                    class="
                                                        software-btn
                                                        software-btn-success
                                                        !min-h-[29px]
                                                        !px-2
                                                    "
                                                >
                                                    CONVERT
                                                </button>

                                            </form>

                                        @endif

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="11"
                                class="py-16 text-center text-slate-500"
                            >
                                No data found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div
            class="
                flex
                flex-col
                justify-between
                gap-3
                border-t
                border-slate-200
                bg-white
                px-4
                py-3
                sm:flex-row
                sm:items-center
            "
        >

            <div class="text-[10px] text-slate-500">

                Showing
                {{ $data->firstItem() ?? 0 }}
                to
                {{ $data->lastItem() ?? 0 }}
                of
                {{ number_format($data->total()) }}
                results

            </div>

            @if($data->hasPages())

                <div>
                    {{ $data->links() }}
                </div>

            @endif

        </div>

    </section>

</div>

@endsection