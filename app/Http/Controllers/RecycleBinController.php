<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class RecycleBinController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Allowed Roles
    |--------------------------------------------------------------------------
    */

    private array $allowedRoles = [
        'super_admin',
        'admin',
    ];


    /*
    |--------------------------------------------------------------------------
    | Searchable Columns
    |--------------------------------------------------------------------------
    */

    private array $searchableColumns = [
        'name',
        'company_name',
        'business_name',
        'title',
        'code',
        'mobile',
        'phone',
        'email',
        'city',
        'category',
        'remarks',
    ];


    /*
    |--------------------------------------------------------------------------
    | Columns shown in table
    |--------------------------------------------------------------------------
    */

    private array $displayColumns = [
        'id',
        'name',
        'company_name',
        'business_name',
        'title',
        'code',
        'mobile',
        'phone',
        'email',
        'city',
        'category',
        'company_id',
        'created_at',
        'deleted_at',
    ];


    /*
    |--------------------------------------------------------------------------
    | Recycle Bin Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {

        $this->ensureAccess($request);

        /*
        |--------------------------------------------------------------------------
        | Get all Soft Delete tables
        |--------------------------------------------------------------------------
        */

        $tables = $this->softDeleteTables(
            $request
        );


        /*
        |--------------------------------------------------------------------------
        | Selected Table
        |--------------------------------------------------------------------------
        */

        $selectedTable = (string) $request->input(
            'table',
            ''
        );


        if (
            $selectedTable === ''
            ||
            !in_array(
                $selectedTable,
                $tables,
                true
            )
        ) {

            $selectedTable =
                $tables[0] ?? '';
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Per Page
        |--------------------------------------------------------------------------
        */

        $perPage = (int) $request->input(
            'per_page',
            25
        );

        if (
            !in_array(
                $perPage,
                [
                    25,
                    50,
                    100,
                    200,
                ],
                true
            )
        ) {

            $perPage = 25;
        }


        /*
        |--------------------------------------------------------------------------
        | Count Deleted Records Per Table
        |--------------------------------------------------------------------------
        */

        $tableStats = [];

        foreach ($tables as $table) {

            $query = DB::table($table)
                ->whereNotNull(
                    'deleted_at'
                );

            $this->applyCompanyScope(
                $request,
                $table,
                $query
            );

            $tableStats[$table] =
                (int) $query->count();
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Pagination
        |--------------------------------------------------------------------------
        */

        $records =
            new LengthAwarePaginator(
                [],
                0,
                $perPage,
                1,
                [
                    'path' =>
                        $request->url(),

                    'query' =>
                        $request->query(),
                ]
            );


        $columns = [];


        /*
        |--------------------------------------------------------------------------
        | Selected Table Query
        |--------------------------------------------------------------------------
        */

        if ($selectedTable !== '') {

            $columns =
                $this->availableDisplayColumns(
                    $selectedTable
                );


            $query =
                DB::table(
                    $selectedTable
                )
                ->whereNotNull(
                    'deleted_at'
                );


            /*
            |--------------------------------------------------------------------------
            | Company Security
            |--------------------------------------------------------------------------
            */

            $this->applyCompanyScope(
                $request,
                $selectedTable,
                $query
            );


            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if ($search !== '') {

                $searchColumns =
                    $this->availableSearchColumns(
                        $selectedTable
                    );


                if (!empty($searchColumns)) {

                    $query->where(
                        function (
                            $searchQuery
                        ) use (
                            $searchColumns,
                            $search
                        ) {

                            foreach (
                                $searchColumns
                                as
                                $column
                            ) {

                                $searchQuery
                                    ->orWhere(
                                        $column,
                                        'like',
                                        '%' .
                                        $search .
                                        '%'
                                    );
                            }
                        }
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $query
                ->orderByDesc(
                    'deleted_at'
                );


            if (
                Schema::hasColumn(
                    $selectedTable,
                    'id'
                )
            ) {

                $query->orderByDesc(
                    'id'
                );
            }


            $records =
                $query
                    ->paginate(
                        $perPage
                    )
                    ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | Total Deleted Records
        |--------------------------------------------------------------------------
        */

        $totalDeleted =
            array_sum(
                $tableStats
            );


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'recycle-bin.index',
            [
                'tables' =>
                    $tables,

                'selectedTable' =>
                    $selectedTable,

                'tableStats' =>
                    $tableStats,

                'records' =>
                    $records,

                'columns' =>
                    $columns,

                'search' =>
                    $search,

                'perPage' =>
                    $perPage,

                'totalDeleted' =>
                    $totalDeleted,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restore Single Record
    |--------------------------------------------------------------------------
    */

    public function restore(
        Request $request,
        string $table,
        int $id
    ): RedirectResponse {

        $this->ensureAccess(
            $request
        );


        $this->ensureValidTable(
            $request,
            $table
        );


        /*
        |--------------------------------------------------------------------------
        | Check Record
        |--------------------------------------------------------------------------
        */

        $record =
            $this->deletedRecordQuery(
                $request,
                $table
            )
            ->where(
                'id',
                $id
            )
            ->first();


        abort_if(
            !$record,
            404,
            'Deleted record not found.'
        );


        try {

            DB::transaction(
                function () use (
                    $table,
                    $id
                ) {

                    $updateData = [
                        'deleted_at' => null,
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | updated_at if column exists
                    |--------------------------------------------------------------------------
                    */

                    if (
                        Schema::hasColumn(
                            $table,
                            'updated_at'
                        )
                    ) {

                        $updateData[
                            'updated_at'
                        ] = now();
                    }


                    DB::table($table)
                        ->where(
                            'id',
                            $id
                        )
                        ->whereNotNull(
                            'deleted_at'
                        )
                        ->update(
                            $updateData
                        );
                }
            );

        } catch (Throwable $e) {

            report($e);


            return back()->with(
                'error',
                'Restore failed: ' .
                $e->getMessage()
            );
        }


        return back()->with(
            'success',
            'Record restored successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Force Delete Single Record
    |--------------------------------------------------------------------------
    */

    public function forceDelete(
        Request $request,
        string $table,
        int $id
    ): RedirectResponse {

        $this->ensureAccess(
            $request
        );


        $this->ensureValidTable(
            $request,
            $table
        );


        /*
        |--------------------------------------------------------------------------
        | Check Deleted Record
        |--------------------------------------------------------------------------
        */

        $record =
            $this->deletedRecordQuery(
                $request,
                $table
            )
            ->where(
                'id',
                $id
            )
            ->first();


        abort_if(
            !$record,
            404,
            'Deleted record not found.'
        );


        try {

            DB::transaction(
                function () use (
                    $table,
                    $id
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | REAL DATABASE DELETE
                    |--------------------------------------------------------------------------
                    */

                    DB::table($table)
                        ->where(
                            'id',
                            $id
                        )
                        ->whereNotNull(
                            'deleted_at'
                        )
                        ->delete();
                }
            );

        } catch (Throwable $e) {

            report($e);


            return back()->with(
                'error',
                'Permanent delete failed. Record kisi dusre table se linked ho sakta hai. Error: '
                .
                $e->getMessage()
            );
        }


        return back()->with(
            'success',
            'Record permanently deleted from database.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Restore / Bulk Force Delete
    |--------------------------------------------------------------------------
    */

    public function bulkAction(
        Request $request
    ): RedirectResponse {

        $this->ensureAccess(
            $request
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate(
                [
                    'table' => [
                        'required',
                        'string',
                    ],

                    'action' => [
                        'required',

                        Rule::in(
                            [
                                'restore',
                                'force_delete',
                            ]
                        ),
                    ],

                    'ids' => [
                        'required',
                        'array',
                        'min:1',
                    ],

                    'ids.*' => [
                        'required',
                        'integer',
                    ],
                ]
            );


        $table =
            (string) $validated['table'];


        /*
        |--------------------------------------------------------------------------
        | Validate Table
        |--------------------------------------------------------------------------
        */

        $this->ensureValidTable(
            $request,
            $table
        );


        /*
        |--------------------------------------------------------------------------
        | Selected IDs
        |--------------------------------------------------------------------------
        */

        $ids =
            collect(
                $validated['ids']
            )
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Only allow accessible deleted IDs
        |--------------------------------------------------------------------------
        */

        $allowedIds =
            $this->deletedRecordQuery(
                $request,
                $table
            )
            ->whereIn(
                'id',
                $ids
            )
            ->pluck(
                'id'
            )
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->values()
            ->all();


        if (empty($allowedIds)) {

            return back()->with(
                'error',
                'No valid deleted records selected.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Bulk Action
        |--------------------------------------------------------------------------
        */

        try {

            DB::transaction(
                function () use (
                    $validated,
                    $table,
                    $allowedIds
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | RESTORE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $validated['action']
                        ===
                        'restore'
                    ) {

                        $updateData = [
                            'deleted_at' =>
                                null,
                        ];


                        if (
                            Schema::hasColumn(
                                $table,
                                'updated_at'
                            )
                        ) {

                            $updateData[
                                'updated_at'
                            ] = now();
                        }


                        DB::table($table)
                            ->whereIn(
                                'id',
                                $allowedIds
                            )
                            ->whereNotNull(
                                'deleted_at'
                            )
                            ->update(
                                $updateData
                            );


                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | FORCE DELETE
                    |--------------------------------------------------------------------------
                    */

                    DB::table($table)
                        ->whereIn(
                            'id',
                            $allowedIds
                        )
                        ->whereNotNull(
                            'deleted_at'
                        )
                        ->delete();
                }
            );

        } catch (Throwable $e) {

            report($e);


            return back()->with(
                'error',
                'Bulk action failed: '
                .
                $e->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        $count =
            count(
                $allowedIds
            );


        return back()->with(
            'success',

            $validated['action']
            ===
            'restore'

                ? "{$count} record(s) restored successfully."

                : "{$count} record(s) permanently deleted."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Find All Soft Delete Tables
    |--------------------------------------------------------------------------
    |
    | Jis table me deleted_at column hoga,
    | wo automatically Recycle Bin me show hoga.
    |
    */

    private function softDeleteTables(
        Request $request
    ): array {

        $databaseName =
            DB::connection()
                ->getDatabaseName();


        $cacheKey =
            'recycle_bin_soft_delete_tables_'
            .
            $databaseName;


        $tables =
            Cache::remember(
                $cacheKey,
                now()->addMinutes(10),

                function () use (
                    $databaseName
                ) {

                    $rows =
                        DB::select(
                            '
                            SELECT TABLE_NAME

                            FROM INFORMATION_SCHEMA.COLUMNS

                            WHERE TABLE_SCHEMA = ?

                            AND COLUMN_NAME = ?

                            ORDER BY TABLE_NAME
                            ',
                            [
                                $databaseName,
                                'deleted_at',
                            ]
                        );


                    return collect($rows)
                        ->map(
                            function ($row) {

                                return (string) (
                                    $row->TABLE_NAME
                                    ??
                                    $row->table_name
                                    ??
                                    ''
                                );
                            }
                        )
                        ->filter()
                        ->values()
                        ->all();
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Admin Security
        |--------------------------------------------------------------------------
        |
        | Admin ko sirf wahi tables dikhenge
        | jisme company_id column available ho.
        |
        | Super Admin sab dekh sakta hai.
        |
        */

        if (
            !$this->isSuperAdmin(
                $request
            )
        ) {

            $tables =
                array_values(
                    array_filter(
                        $tables,

                        function (
                            string $table
                        ) {

                            return
                                $table !==
                                'companies'
                                &&
                                Schema::hasColumn(
                                    $table,
                                    'company_id'
                                );
                        }
                    )
                );
        }


        return $tables;
    }


    /*
    |--------------------------------------------------------------------------
    | Deleted Record Query
    |--------------------------------------------------------------------------
    */

    private function deletedRecordQuery(
        Request $request,
        string $table
    ) {

        $query =
            DB::table($table)
                ->whereNotNull(
                    'deleted_at'
                );


        $this->applyCompanyScope(
            $request,
            $table,
            $query
        );


        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | Company Scope
    |--------------------------------------------------------------------------
    */

    private function applyCompanyScope(
        Request $request,
        string $table,
        $query
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        if (
            $this->isSuperAdmin(
                $request
            )
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                $table,
                'company_id'
            )
        ) {

            $query->where(
                $table .
                '.company_id',

                (int)
                $request
                    ->user()
                    ->company_id
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Safety
        |--------------------------------------------------------------------------
        |
        | Table company scoped nahi hai to normal admin
        | ko access nahi dena.
        |
        */

        $query->whereRaw(
            '1 = 0'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Available Search Columns
    |--------------------------------------------------------------------------
    */

    private function availableSearchColumns(
        string $table
    ): array {

        return array_values(
            array_filter(
                $this->searchableColumns,

                fn ($column) =>
                    Schema::hasColumn(
                        $table,
                        $column
                    )
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Available Display Columns
    |--------------------------------------------------------------------------
    */

    private function availableDisplayColumns(
        string $table
    ): array {

        $columns =
            array_values(
                array_filter(
                    $this->displayColumns,

                    fn ($column) =>
                        Schema::hasColumn(
                            $table,
                            $column
                        )
                )
            );


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                $table,
                'id'
            )
            &&
            !in_array(
                'id',
                $columns,
                true
            )
        ) {

            array_unshift(
                $columns,
                'id'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Deleted At
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                $table,
                'deleted_at'
            )
            &&
            !in_array(
                'deleted_at',
                $columns,
                true
            )
        ) {

            $columns[] =
                'deleted_at';
        }


        return $columns;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Table
    |--------------------------------------------------------------------------
    */

    private function ensureValidTable(
        Request $request,
        string $table
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Must be allowed Soft Delete table
        |--------------------------------------------------------------------------
        */

        abort_unless(
            in_array(
                $table,
                $this->softDeleteTables(
                    $request
                ),
                true
            ),
            404,
            'Recycle table not found.'
        );


        /*
        |--------------------------------------------------------------------------
        | Check database table
        |--------------------------------------------------------------------------
        */

        abort_unless(
            Schema::hasTable(
                $table
            ),
            404,
            'Table not found.'
        );


        /*
        |--------------------------------------------------------------------------
        | Required Columns
        |--------------------------------------------------------------------------
        */

        abort_unless(
            Schema::hasColumn(
                $table,
                'deleted_at'
            )
            &&
            Schema::hasColumn(
                $table,
                'id'
            ),
            404,
            'Invalid recycle table.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Access Permission
    |--------------------------------------------------------------------------
    */

    private function ensureAccess(
        Request $request
    ): void {

        abort_unless(
            $request->user()
            &&
            $request
                ->user()
                ->hasAnyRole(
                    $this->allowedRoles
                ),
            403,
            'You do not have permission to access Recycle Bin.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Super Admin Check
    |--------------------------------------------------------------------------
    */

    private function isSuperAdmin(
        Request $request
    ): bool {

        return
            $request
                ->user()
                ->hasRole(
                    'super_admin'
                );
    }
}