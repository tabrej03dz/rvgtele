<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CallDispositionController;
use App\Http\Controllers\CallLogController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminBusinessController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/


Route::view('/', 'welcome')->name('home');


/*
|--------------------------------------------------------------------------
| Authenticated CRM Routes
|--------------------------------------------------------------------------
|
| activitylog     = user activity tracking
| company.active  = inactive company ko CRM access se rokega
|
*/

Route::middleware([
    'auth',
    'verified',
    'company.active',
    'activitylog',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    |
    | Sirf super admin company management access karega.
    |
    */

    Route::middleware('role:super_admin')->group(function () {

        Route::resource(
            'companies',
            CompanyController::class
        )->except('show');

         Route::post(
            '/companies/{company}/view-business',
            [SuperAdminBusinessController::class, 'viewBusiness']
        )->name('companies.view-business');

    });


    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    |
    | Super Admin / Owner / Admin activity dekh sakte hain.
    |
    */

    Route::middleware(
        'role:super_admin|owner|admin'
    )->group(function () {

        Route::get(
            '/activity-logs',
            [ActivityLogController::class, 'index']
        )->name('activity-logs.index');

        Route::get(
            '/activity-logs/{activity}',
            [ActivityLogController::class, 'show']
        )->name('activity-logs.show');

    });


    /*
    |--------------------------------------------------------------------------
    | Lead Import
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leads/import',
        [LeadImportController::class, 'create']
    )->name('leads.import.create');


    Route::post(
        '/leads/import',
        [LeadImportController::class, 'store']
    )->name('leads.import.store');


    Route::get(
        '/leads/import/template',
        [LeadImportController::class, 'downloadTemplate']
    )->name('leads.import.template');






    Route::post(
    '/lead-labels',
    [LeadController::class, 'storeLabel']
)->name('lead-labels.store');

Route::delete(
    '/lead-labels/{label}',
    [LeadController::class, 'destroyLabel']
)->name('lead-labels.destroy');

Route::post(
    '/leads/bulk-label',
    [LeadController::class, 'bulkLabel']
)->name('leads.bulk-label');

Route::post(
    '/leads/{lead}/labels',
    [LeadController::class, 'addLabel']
)->name('leads.labels.add');

Route::delete(
    '/leads/{lead}/labels/{label}',
    [LeadController::class, 'removeLabel']
)->name('leads.labels.remove');



    /*
    |--------------------------------------------------------------------------
    | Lead Bulk Assignment
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/leads/bulk-assign',
        [LeadController::class, 'bulkAssign']
    )->name('leads.bulk-assign');


    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'leads',
        LeadController::class
    );


    Route::post(
        '/leads/{lead}/assign',
        [LeadController::class, 'assign']
    )->name('leads.assign');


    Route::post(
        '/leads/{lead}/notes',
        [LeadController::class, 'note']
    )->name('leads.notes');


    /*
    |--------------------------------------------------------------------------
    | Call Logs
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/calls',
        [CallLogController::class, 'index']
    )->name('calls.index');


    Route::post(
        '/leads/{lead}/calls',
        [CallLogController::class, 'store']
    )->name('calls.store');


    /*
    |--------------------------------------------------------------------------
    | Follow Ups
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/follow-ups',
        [FollowUpController::class, 'index']
    )->name('followups.index');


    Route::post(
        '/follow-ups/{followUp}/complete',
        [FollowUpController::class, 'complete']
    )->name('followups.complete');


    Route::delete(
        '/follow-ups/{followUp}',
        [FollowUpController::class, 'destroy']
    )->name('followups.destroy');


    /*
    |--------------------------------------------------------------------------
    | Pipeline
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pipeline',
        [PipelineController::class, 'index']
    )->name('pipeline.index');


    Route::post(
        '/pipeline/{lead}/move',
        [PipelineController::class, 'move']
    )->name('pipeline.move');




    /*
    |--------------------------------------------------------------------------
    | Role & Permission Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('access-control')
        ->name('access-control.')
        ->group(function () {

            Route::get(
                '/',
                [AccessControlController::class, 'index']
            )->name('index');

            Route::post(
                '/roles',
                [AccessControlController::class, 'storeRole']
            )->name('roles.store');

            Route::post(
                '/permissions',
                [AccessControlController::class, 'storePermission']
            )->name('permissions.store');

            Route::put(
                '/roles/{role}/permissions',
                [AccessControlController::class, 'syncRolePermissions']
            )->name('roles.permissions.sync');

            Route::put(
                '/users/{user}/access',
                [AccessControlController::class, 'syncUserAccess']
            )->name('users.access.sync');
        });


    /*
    |--------------------------------------------------------------------------
    | Employee Impersonation
    |--------------------------------------------------------------------------
    |
    | Important:
    | Custom routes resource route se PEHLE rakhe gaye hain.
    |
    */

    Route::post(
        '/employees/stop-impersonating',
        [EmployeeController::class, 'stopImpersonating']
    )->name('employees.stop-impersonating');


    Route::post(
        '/employees/{employee}/impersonate',
        [EmployeeController::class, 'impersonate']
    )->name('employees.impersonate');


    /*
    |--------------------------------------------------------------------------
    | Employees
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'employees',
        EmployeeController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | Branches
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'branches',
        BranchController::class
    )
        ->parameters([
            'branches' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'teams',
        TeamController::class
    )
        ->parameters([
            'teams' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Campaigns
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'campaigns',
        CampaignController::class
    )
        ->parameters([
            'campaigns' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'products',
        ProductController::class
    )
        ->parameters([
            'products' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'customers',
        CustomerController::class
    )
        ->parameters([
            'customers' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'tasks',
        TaskController::class
    )
        ->parameters([
            'tasks' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'orders',
        OrderController::class
    )
        ->parameters([
            'orders' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'payments',
        PaymentController::class
    )
        ->parameters([
            'payments' => 'item',
        ])
        ->except('show');


    /*
    |--------------------------------------------------------------------------
    | CRM Settings
    |--------------------------------------------------------------------------
    */

    Route::prefix('settings')
        ->name('crm-settings.')
        ->group(function () {

            Route::resource(
                'lead-sources',
                LeadSourceController::class
            )
                ->parameters([
                    'lead-sources' => 'item',
                ])
                ->except('show');


            Route::resource(
                'lead-statuses',
                LeadStatusController::class
            )
                ->parameters([
                    'lead-statuses' => 'item',
                ])
                ->except('show');


            Route::resource(
                'call-dispositions',
                CallDispositionController::class
            )
                ->parameters([
                    'call-dispositions' => 'item',
                ])
                ->except('show');

        });


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports',
        [ReportController::class, 'index']
    )->name('reports.index');

});


/*
|--------------------------------------------------------------------------
| Laravel Settings Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';