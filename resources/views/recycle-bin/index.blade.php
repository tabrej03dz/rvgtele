@extends('layouts.crm', ['title' => 'Recycle Bin'])

@section('content')

<style>

:root {

    --rb-primary:#5b4df7;
    --rb-primary2:#8d29ef;

    --rb-text:#172033;

    --rb-muted:#7b8497;

    --rb-border:#e4e8f1;

    --rb-background:#f5f7fb;

    --rb-card:#ffffff;

    --rb-danger:#e74355;

    --rb-success:#16965d;
}


.recycle-page {

    padding:15px;

    min-height:100vh;

    background:
        var(--rb-background);

    color:
        var(--rb-text);
}


.recycle-card {

    background:
        var(--rb-card);

    border:
        1px solid
        var(--rb-border);

    border-radius:
        13px;

    box-shadow:
        0 6px 20px
        rgba(
            30,
            40,
            80,
            .06
        );

    margin-bottom:
        12px;
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

.recycle-header {

    padding:
        17px;

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:
        15px;
}


.recycle-header-left {

    display:flex;

    align-items:center;

    gap:
        12px;
}


.recycle-icon {

    width:
        44px;

    height:
        44px;

    border-radius:
        12px;

    display:grid;

    place-items:center;

    color:#fff;

    font-size:
        21px;

    background:
        linear-gradient(
            135deg,
            var(--rb-primary),
            var(--rb-primary2)
        );
}


.recycle-title {

    margin:0;

    font-size:
        20px;

    font-weight:
        900;
}


.recycle-subtitle {

    margin-top:
        4px;

    font-size:
        11px;

    color:
        var(--rb-muted);
}


.total-badge {

    padding:
        8px 12px;

    border-radius:
        999px;

    color:
        var(--rb-primary);

    background:
        #f0edff;

    font-size:
        10px;

    font-weight:
        900;
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

.recycle-message {

    padding:
        11px 13px;

    border-radius:
        9px;

    margin-bottom:
        10px;

    font-size:
        12px;

    font-weight:
        700;
}


.recycle-success {

    background:
        #e9fff2;

    border:
        1px solid
        #bcebd0;

    color:
        #177649;
}


.recycle-error {

    background:
        #fff0f2;

    border:
        1px solid
        #f1c4cb;

    color:
        #b82e42;
}


/*
|--------------------------------------------------------------------------
| Table Tabs
|--------------------------------------------------------------------------
*/

.recycle-tabs {

    padding:
        12px;

    display:flex;

    gap:
        6px;

    flex-wrap:
        wrap;
}


.recycle-tab {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        9px 12px;

    text-decoration:
        none;

    border:
        1px solid
        #dfe3ec;

    border-radius:
        7px;

    color:
        #4d5567;

    background:
        #fff;

    font-size:
        10px;

    font-weight:
        900;
}


.recycle-tab.active {

    color:#fff;

    border-color:
        transparent;

    background:
        linear-gradient(
            105deg,
            var(--rb-primary),
            var(--rb-primary2)
        );

    box-shadow:
        0 6px 14px
        rgba(
            91,
            77,
            247,
            .20
        );
}


.recycle-count {

    min-width:
        21px;

    height:
        21px;

    padding:
        0 5px;

    display:
        inline-grid;

    place-items:
        center;

    border-radius:
        999px;

    background:
        #f0edff;

    color:
        var(--rb-primary);

    font-size:
        9px;
}


.recycle-tab.active
.recycle-count {

    color:#fff;

    background:
        rgba(
            255,
            255,
            255,
            .20
        );
}


/*
|--------------------------------------------------------------------------
| Toolbar
|--------------------------------------------------------------------------
*/

.recycle-toolbar {

    padding:
        12px;

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:
        10px;

    flex-wrap:
        wrap;

    border-bottom:
        1px solid
        var(--rb-border);
}


.recycle-search {

    flex:1;

    display:flex;

    align-items:center;

    gap:
        7px;

    min-width:
        300px;
}


.recycle-input,
.recycle-select {

    height:
        40px;

    border:
        1px solid
        #d8dde8;

    border-radius:
        8px;

    background:
        #fff;

    outline:
        none;

    font-size:
        11px;

    padding:
        0 11px;
}


.recycle-input {

    flex:1;
}


.recycle-input:focus,
.recycle-select:focus {

    border-color:
        #8a7ef8;

    box-shadow:
        0 0 0 3px
        #f0eeff;
}


.recycle-actions {

    display:flex;

    align-items:center;

    gap:
        6px;

    flex-wrap:
        wrap;
}


/*
|--------------------------------------------------------------------------
| Buttons
|--------------------------------------------------------------------------
*/

.recycle-btn {

    height:
        39px;

    padding:
        0 12px;

    display:
        inline-flex;

    align-items:center;

    justify-content:center;

    gap:
        5px;

    text-decoration:
        none;

    border:
        1px solid
        #dde2eb;

    border-radius:
        8px;

    background:
        #fff;

    color:
        #41495b;

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        900;
}


.recycle-btn-primary {

    border:
        0;

    color:
        #fff;

    background:
        linear-gradient(
            105deg,
            var(--rb-primary),
            var(--rb-primary2)
        );
}


.recycle-btn-success {

    color:
        var(--rb-success);

    background:
        #eafff3;

    border-color:
        #bcebd0;
}


.recycle-btn-danger {

    color:
        var(--rb-danger);

    background:
        #fff0f2;

    border-color:
        #f5c6cd;
}


.recycle-btn:disabled {

    opacity:
        .45;

    cursor:
        not-allowed;
}


.recycle-mini {

    height:
        30px;

    padding:
        0 8px;

    font-size:
        9px;
}


/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

.recycle-table-wrapper {

    overflow:
        auto;
}


.recycle-table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        950px;
}


.recycle-table th {

    padding:
        10px 9px;

    background:
        #fbfcfe;

    border-bottom:
        1px solid
        var(--rb-border);

    color:
        #596174;

    text-align:
        left;

    white-space:
        nowrap;

    font-size:
        9px;

    font-weight:
        900;
}


.recycle-table td {

    padding:
        11px 9px;

    border-bottom:
        1px solid
        #edf0f5;

    vertical-align:
        middle;

    font-size:
        11px;
}


.recycle-table tbody tr:hover {

    background:
        #fcfcff;
}


.recycle-record-id {

    font-weight:
        900;
}


.deleted-date {

    color:
        #d23d50;

    font-weight:
        800;
}


.recycle-value {

    max-width:
        220px;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}


.record-actions {

    display:flex;

    align-items:center;

    gap:
        5px;

    white-space:
        nowrap;
}


/*
|--------------------------------------------------------------------------
| Footer
|--------------------------------------------------------------------------
*/

.recycle-footer {

    padding:
        12px;

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:
        10px;

    flex-wrap:
        wrap;
}


.recycle-muted {

    color:
        var(--rb-muted);

    font-size:
        10px;
}


/*
|--------------------------------------------------------------------------
| Empty
|--------------------------------------------------------------------------
*/

.recycle-empty {

    padding:
        45px;

    text-align:
        center;

    color:
        var(--rb-muted);
}


.recycle-empty-icon {

    font-size:
        40px;

    margin-bottom:
        8px;
}


/*
|--------------------------------------------------------------------------
| Mobile
|--------------------------------------------------------------------------
*/

@media(
    max-width:700px
) {

    .recycle-page {

        padding:
            8px;
    }


    .recycle-header {

        flex-direction:
            column;

        align-items:
            flex-start;
    }


    .recycle-search {

        width:
            100%;

        min-width:
            100%;

        flex-wrap:
            wrap;
    }


    .recycle-input {

        flex-basis:
            100%;
    }


    .recycle-actions {

        width:
            100%;
    }


    .recycle-actions
    .recycle-btn {

        flex:1;
    }
}

</style>


@php

/*
|--------------------------------------------------------------------------
| Table Label
|--------------------------------------------------------------------------
*/

$tableLabel =
    function (
        string $table
    ): string {

        return
            \Illuminate\Support\Str::headline(
                \Illuminate\Support\Str::singular(
                    $table
                )
            );
    };


/*
|--------------------------------------------------------------------------
| Column Label
|--------------------------------------------------------------------------
*/

$columnLabel =
    function (
        string $column
    ): string {

        return
            \Illuminate\Support\Str::headline(
                $column
            );
    };


/*
|--------------------------------------------------------------------------
| Value Formatter
|--------------------------------------------------------------------------
*/

$formatValue =
    function (
        $value,
        string $column
    ) {

        if (
            $value === null
            ||
            $value === ''
        ) {

            return '—';
        }


        if (
            in_array(
                $column,
                [
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
                true
            )
        ) {

            try {

                return
                    \Carbon\Carbon::parse(
                        $value
                    )
                    ->format(
                        'd M Y, h:i A'
                    );

            } catch (
                \Throwable $e
            ) {

                return $value;
            }
        }


        if (
            is_string($value)
            &&
            strlen($value) > 100
        ) {

            return
                \Illuminate\Support\Str::limit(
                    $value,
                    100
                );
        }


        return $value;
    };

@endphp


<div class="recycle-page">


    {{-- Success --}}

    @if(
        session('success')
    )

        <div
            class="
                recycle-message
                recycle-success
            "
        >

            {{ session('success') }}

        </div>

    @endif


    {{-- Error --}}

    @if(
        session('error')
    )

        <div
            class="
                recycle-message
                recycle-error
            "
        >

            {{ session('error') }}

        </div>

    @endif


    {{-- Validation Error --}}

    @if(
        $errors->any()
    )

        <div
            class="
                recycle-message
                recycle-error
            "
        >

            {{ $errors->first() }}

        </div>

    @endif



    {{-- Header --}}

    <section
        class="
            recycle-card
            recycle-header
        "
    >

        <div
            class="
                recycle-header-left
            "
        >

            <div
                class="
                    recycle-icon
                "
            >
                ♻
            </div>


            <div>

                <h1
                    class="
                        recycle-title
                    "
                >

                    Recycle Bin

                </h1>


                <div
                    class="
                        recycle-subtitle
                    "
                >

                    Restore deleted records or permanently delete them from database.

                </div>

            </div>

        </div>


        <div
            class="
                total-badge
            "
        >

            {{ number_format($totalDeleted) }}

            DELETED RECORDS

        </div>

    </section>



    {{-- Tables Tabs --}}

    <section
        class="
            recycle-card
        "
    >

        <div
            class="
                recycle-tabs
            "
        >

            @forelse(
                $tables
                as
                $table
            )

                @php

                    $params =
                        request()
                        ->except([
                            'page',
                            'search',
                        ]);


                    $params[
                        'table'
                    ] = $table;

                @endphp


                <a
                    href="{{
                        route(
                            'recycle-bin.index',
                            $params
                        )
                    }}"

                    class="
                        recycle-tab

                        {{
                            $selectedTable === $table
                            ?
                            'active'
                            :
                            ''
                        }}
                    "
                >

                    {{
                        strtoupper(
                            $tableLabel(
                                $table
                            )
                        )
                    }}


                    <span
                        class="
                            recycle-count
                        "
                    >

                        {{
                            $tableStats[
                                $table
                            ]
                            ??
                            0
                        }}

                    </span>

                </a>


            @empty

                <div
                    class="
                        recycle-muted
                    "
                >

                    No Soft Delete table found.

                </div>

            @endforelse

        </div>

    </section>



    @if(
        $selectedTable
    )


        {{-- Main Table --}}

        <section
            class="
                recycle-card
            "
        >


            {{-- Toolbar --}}

            <div
                class="
                    recycle-toolbar
                "
            >


                {{-- Search --}}

                <form
                    method="GET"

                    action="{{
                        route(
                            'recycle-bin.index'
                        )
                    }}"

                    class="
                        recycle-search
                    "
                >


                    <input
                        type="hidden"

                        name="table"

                        value="{{
                            $selectedTable
                        }}"
                    >


                    <input
                        class="
                            recycle-input
                        "

                        type="text"

                        name="search"

                        value="{{
                            $search
                        }}"

                        placeholder="Search deleted record..."
                    >


                    <select
                        class="
                            recycle-select
                        "

                        name="per_page"
                    >

                        @foreach(
                            [
                                25,
                                50,
                                100,
                                200
                            ]
                            as
                            $size
                        )

                            <option
                                value="{{
                                    $size
                                }}"

                                @selected(
                                    (int)
                                    $perPage
                                    ===
                                    $size
                                )
                            >

                                {{
                                    $size
                                }}

                                / page

                            </option>

                        @endforeach

                    </select>


                    <button
                        type="submit"

                        class="
                            recycle-btn
                            recycle-btn-primary
                        "
                    >

                        SEARCH

                    </button>


                    @if(
                        $search !== ''
                    )

                        <a
                            class="
                                recycle-btn
                            "

                            href="{{
                                route(
                                    'recycle-bin.index',
                                    [
                                        'table' =>
                                            $selectedTable,

                                        'per_page' =>
                                            $perPage,
                                    ]
                                )
                            }}"
                        >

                            CLEAR

                        </a>

                    @endif

                </form>



                {{-- Bulk Buttons --}}

                <div
                    class="
                        recycle-actions
                    "
                >

                    <button
                        type="button"

                        class="
                            recycle-btn
                            recycle-btn-success
                        "

                        id="
                            bulkRestoreBtn
                        "

                        disabled
                    >

                        ↶ RESTORE SELECTED

                    </button>


                    <button
                        type="button"

                        class="
                            recycle-btn
                            recycle-btn-danger
                        "

                        id="
                            bulkDeleteBtn
                        "

                        disabled
                    >

                        🗑 FORCE DELETE SELECTED

                    </button>

                </div>

            </div>



            {{-- Table --}}

            <div
                class="
                    recycle-table-wrapper
                "
            >

                <table
                    class="
                        recycle-table
                    "
                >


                    <thead>

                        <tr>

                            <th>

                                <input
                                    type="checkbox"

                                    id="
                                        selectAll
                                    "
                                >

                            </th>


                            @foreach(
                                $columns
                                as
                                $column
                            )

                                <th>

                                    {{
                                        $columnLabel(
                                            $column
                                        )
                                    }}

                                </th>

                            @endforeach


                            <th>
                                ACTION
                            </th>

                        </tr>

                    </thead>



                    <tbody>

                        @forelse(
                            $records
                            as
                            $record
                        )


                            <tr>


                                {{-- Checkbox --}}

                                <td>

                                    <input
                                        type="checkbox"

                                        class="
                                            record-check
                                        "

                                        value="{{
                                            $record->id
                                        }}"
                                    >

                                </td>



                                {{-- Dynamic Columns --}}

                                @foreach(
                                    $columns
                                    as
                                    $column
                                )

                                    @php

                                        $value =
                                            $record
                                            ->{$column}
                                            ??
                                            null;

                                    @endphp


                                    <td
                                        class="
                                            {{
                                                $column
                                                ===
                                                'deleted_at'

                                                ?
                                                'deleted-date'

                                                :
                                                ''
                                            }}
                                        "

                                        title="{{
                                            is_scalar(
                                                $value
                                            )
                                            ?
                                            $value
                                            :
                                            ''
                                        }}"
                                    >


                                        <div
                                            class="
                                                recycle-value

                                                {{
                                                    $column
                                                    ===
                                                    'id'

                                                    ?
                                                    'recycle-record-id'

                                                    :
                                                    ''
                                                }}
                                            "
                                        >

                                            {{
                                                $formatValue(
                                                    $value,
                                                    $column
                                                )
                                            }}

                                        </div>

                                    </td>

                                @endforeach



                                {{-- Actions --}}

                                <td>

                                    <div
                                        class="
                                            record-actions
                                        "
                                    >


                                        {{-- Restore --}}

                                        <form
                                            method="POST"

                                            action="{{
                                                route(
                                                    'recycle-bin.restore',
                                                    [
                                                        'table'
                                                        =>
                                                        $selectedTable,

                                                        'id'
                                                        =>
                                                        $record->id,
                                                    ]
                                                )
                                            }}"

                                            onsubmit="
                                                return confirm(
                                                    'Is record ko restore karna hai?'
                                                );
                                            "
                                        >

                                            @csrf


                                            <button
                                                type="submit"

                                                class="
                                                    recycle-btn
                                                    recycle-btn-success
                                                    recycle-mini
                                                "
                                            >

                                                ↶ RESTORE

                                            </button>

                                        </form>



                                        {{-- Force Delete --}}

                                        <form
                                            method="POST"

                                            action="{{
                                                route(
                                                    'recycle-bin.force-delete',
                                                    [
                                                        'table'
                                                        =>
                                                        $selectedTable,

                                                        'id'
                                                        =>
                                                        $record->id,
                                                    ]
                                                )
                                            }}"

                                            onsubmit="
                                                return confirm(
                                                    'WARNING: Ye record database se permanently delete ho jayega. Undo nahi hoga. Continue?'
                                                );
                                            "
                                        >

                                            @csrf

                                            @method('DELETE')


                                            <button
                                                type="submit"

                                                class="
                                                    recycle-btn
                                                    recycle-btn-danger
                                                    recycle-mini
                                                "
                                            >

                                                🗑 DELETE

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>



                        @empty


                            <tr>

                                <td
                                    colspan="{{
                                        count(
                                            $columns
                                        )
                                        +
                                        2
                                    }}"
                                >

                                    <div
                                        class="
                                            recycle-empty
                                        "
                                    >

                                        <div
                                            class="
                                                recycle-empty-icon
                                            "
                                        >
                                            ♻
                                        </div>


                                        <strong>

                                            Recycle Bin Empty

                                        </strong>


                                        <div
                                            class="
                                                recycle-muted
                                            "
                                        >

                                            Is table me koi deleted record nahi hai.

                                        </div>

                                    </div>

                                </td>

                            </tr>


                        @endforelse

                    </tbody>

                </table>

            </div>



            {{-- Pagination --}}

            <div
                class="
                    recycle-footer
                "
            >

                <div
                    class="
                        recycle-muted
                    "
                >

                    Showing

                    {{
                        $records->firstItem()
                        ??
                        0
                    }}

                    –

                    {{
                        $records->lastItem()
                        ??
                        0
                    }}

                    of

                    {{
                        $records->total()
                    }}

                </div>


                <div>

                    {{
                        $records->links()
                    }}

                </div>

            </div>

        </section>



        {{-- Hidden Bulk Form --}}

        <form
            method="POST"

            action="{{
                route(
                    'recycle-bin.bulk-action'
                )
            }}"

            id="
                bulkActionForm
            "

            style="
                display:none;
            "
        >

            @csrf


            <input
                type="hidden"

                name="table"

                value="{{
                    $selectedTable
                }}"
            >


            <input
                type="hidden"

                name="action"

                id="
                    bulkActionInput
                "
            >


            <div
                id="
                    bulkSelectedInputs
                "
            ></div>

        </form>


    @endif


</div>



<script>

document.addEventListener(
    'DOMContentLoaded',

    function () {


        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const selectAll =
            document.getElementById(
                'selectAll'
            );


        const bulkRestoreBtn =
            document.getElementById(
                'bulkRestoreBtn'
            );


        const bulkDeleteBtn =
            document.getElementById(
                'bulkDeleteBtn'
            );


        /*
        |--------------------------------------------------------------------------
        | All checkboxes
        |--------------------------------------------------------------------------
        */

        function getChecks() {

            return Array.from(
                document.querySelectorAll(
                    '.record-check'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Selected IDs
        |--------------------------------------------------------------------------
        */

        function selectedIds() {

            return getChecks()
                .filter(
                    checkbox =>
                        checkbox.checked
                )
                .map(
                    checkbox =>
                        checkbox.value
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Update Bulk Buttons
        |--------------------------------------------------------------------------
        */

        function updateButtons() {

            const ids =
                selectedIds();


            const count =
                ids.length;


            if (
                bulkRestoreBtn
            ) {

                bulkRestoreBtn.disabled =
                    count === 0;


                bulkRestoreBtn.textContent =
                    count > 0

                    ?
                    `↶ RESTORE SELECTED (${count})`

                    :
                    '↶ RESTORE SELECTED';
            }


            if (
                bulkDeleteBtn
            ) {

                bulkDeleteBtn.disabled =
                    count === 0;


                bulkDeleteBtn.textContent =
                    count > 0

                    ?
                    `🗑 FORCE DELETE SELECTED (${count})`

                    :
                    '🗑 FORCE DELETE SELECTED';
            }


            /*
            |--------------------------------------------------------------------------
            | Select All status
            |--------------------------------------------------------------------------
            */

            if (
                selectAll
            ) {

                const all =
                    getChecks();


                selectAll.checked =
                    all.length > 0
                    &&
                    all.every(
                        checkbox =>
                            checkbox.checked
                    );


                selectAll.indeterminate =
                    count > 0
                    &&
                    count < all.length;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Select All
        |--------------------------------------------------------------------------
        */

        if (
            selectAll
        ) {

            selectAll.addEventListener(
                'change',

                function () {

                    getChecks()
                        .forEach(
                            function (
                                checkbox
                            ) {

                                checkbox.checked =
                                    selectAll.checked;
                            }
                        );


                    updateButtons();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Individual Checkbox
        |--------------------------------------------------------------------------
        */

        getChecks()
            .forEach(
                function (
                    checkbox
                ) {

                    checkbox.addEventListener(
                        'change',
                        updateButtons
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Submit Bulk
        |--------------------------------------------------------------------------
        */

        function submitBulk(
            action
        ) {

            const ids =
                selectedIds();


            if (
                ids.length === 0
            ) {

                alert(
                    'Please select at least one record.'
                );

                return;
            }


            let message;


            if (
                action ===
                'restore'
            ) {

                message =
                    `${ids.length} selected record(s) restore karne hain?`;

            } else {

                message =
                    `WARNING: ${ids.length} selected record(s) database se permanently delete honge. Ye action undo nahi hoga. Continue?`;
            }


            if (
                !confirm(
                    message
                )
            ) {

                return;
            }


            const form =
                document.getElementById(
                    'bulkActionForm'
                );


            const actionInput =
                document.getElementById(
                    'bulkActionInput'
                );


            const inputsHolder =
                document.getElementById(
                    'bulkSelectedInputs'
                );


            if (
                !form
                ||
                !actionInput
                ||
                !inputsHolder
            ) {

                alert(
                    'Bulk form not found.'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Clear Old Inputs
            |--------------------------------------------------------------------------
            */

            inputsHolder.innerHTML =
                '';


            /*
            |--------------------------------------------------------------------------
            | Add IDs
            |--------------------------------------------------------------------------
            */

            ids.forEach(
                function (
                    id
                ) {

                    const input =
                        document.createElement(
                            'input'
                        );


                    input.type =
                        'hidden';


                    input.name =
                        'ids[]';


                    input.value =
                        id;


                    inputsHolder
                        .appendChild(
                            input
                        );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */

            actionInput.value =
                action;


            /*
            |--------------------------------------------------------------------------
            | Submit
            |--------------------------------------------------------------------------
            */

            form.submit();
        }


        /*
        |--------------------------------------------------------------------------
        | Bulk Restore
        |--------------------------------------------------------------------------
        */

        if (
            bulkRestoreBtn
        ) {

            bulkRestoreBtn
                .addEventListener(
                    'click',

                    function () {

                        submitBulk(
                            'restore'
                        );
                    }
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Bulk Force Delete
        |--------------------------------------------------------------------------
        */

        if (
            bulkDeleteBtn
        ) {

            bulkDeleteBtn
                .addEventListener(
                    'click',

                    function () {

                        submitBulk(
                            'force_delete'
                        );
                    }
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Initial State
        |--------------------------------------------------------------------------
        */

        updateButtons();

    }
);

</script>

@endsection