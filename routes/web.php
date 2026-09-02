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
use App\Http\Controllers\SuperAdminBusinessController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\WhatsappMessageTemplateController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {





        /*
        |--------------------------------------------------------------------------
        | Data List
        |--------------------------------------------------------------------------
        */
        Route::get('/data', [DataController::class, 'index'])
            ->name('data.index');

        /*
        |--------------------------------------------------------------------------
        | Create / Store
        |--------------------------------------------------------------------------
        */
        Route::get('/data/create', [DataController::class, 'create'])
            ->name('data.create');

        Route::post('/data', [DataController::class, 'store'])
            ->name('data.store');



        Route::get(
            '/data/import',
            [DataController::class, 'importCreate']
        )->name('data.import.create');

        Route::post(
            '/data/import',
            [DataController::class, 'importStore']
        )->name('data.import.store');

        /*
        |--------------------------------------------------------------------------
        | Bulk Actions
        |--------------------------------------------------------------------------
        */
        Route::delete('/data/bulk-delete', [DataController::class, 'bulkDelete'])
            ->name('data.bulk-delete');

        Route::post('/data/bulk-convert-to-lead', [DataController::class, 'bulkConvertToLead'])
            ->name('data.bulk-convert-to-lead');

        /*
        |--------------------------------------------------------------------------
        | Restore / Force Delete
        |--------------------------------------------------------------------------
        */
        Route::post('/data/{id}/restore', [DataController::class, 'restore'])
            ->name('data.restore');

        Route::delete('/data/{id}/force-delete', [DataController::class, 'forceDelete'])
            ->name('data.force-delete');

        /*
        |--------------------------------------------------------------------------
        | Lead Conversion
        |--------------------------------------------------------------------------
        */
        Route::post('/data/{data}/convert-to-lead', [DataController::class, 'convertToLead'])
            ->name('data.convert-to-lead');

        Route::post('/data/{data}/mark-unconverted', [DataController::class, 'markUnconverted'])
            ->name('data.mark-unconverted');

        /*
        |--------------------------------------------------------------------------
        | Show / Edit / Update / Delete
        |--------------------------------------------------------------------------
        */
        Route::get('/data/{data}', [DataController::class, 'show'])
            ->name('data.show');

        Route::get('/data/{data}/edit', [DataController::class, 'edit'])
            ->name('data.edit');

        Route::put('/data/{data}', [DataController::class, 'update'])
            ->name('data.update');

        Route::delete('/data/{data}', [DataController::class, 'destroy'])
            ->name('data.destroy');

    Route::post(
        '/leads/{lead}/call-on-mobile',
        [LeadController::class, 'callOnMobile']
    )->name('leads.call-on-mobile');

});

Route::middleware(['auth', 'verified', 'company.active', 'activitylog'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // Companies
    Route::get('/companies', [CompanyController::class, 'index'])->middleware('permission:companies.view')->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->middleware('permission:companies.create')->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->middleware('permission:companies.create')->name('companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->middleware('permission:companies.update')->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->middleware('permission:companies.update')->name('companies.update');
    Route::patch('/companies/{company}', [CompanyController::class, 'update'])->middleware('permission:companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware('permission:companies.delete')->name('companies.destroy');
    Route::post('/companies/{company}/view-business', [SuperAdminBusinessController::class, 'viewBusiness'])
        ->middleware('permission:companies.view-business')->name('companies.view-business');

    // Activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:activity-logs.view')->name('activity-logs.index');
    Route::get('/activity-logs/{activity}', [ActivityLogController::class, 'show'])->middleware('permission:activity-logs.view')->name('activity-logs.show');

    // Lead import must stay before /leads/{lead}.
    Route::get('/leads/import', [LeadImportController::class, 'create'])->middleware('permission:leads.import')->name('leads.import.create');
    Route::post('/leads/import', [LeadImportController::class, 'store'])->middleware('permission:leads.import')->name('leads.import.store');
    Route::get('/leads/import/template', [LeadImportController::class, 'downloadTemplate'])->middleware('permission:leads.import')->name('leads.import.template');

    // Lead labels / assignment / notes
    Route::post('/lead-labels', [LeadController::class, 'storeLabel'])->middleware('permission:leads.labels.manage')->name('lead-labels.store');
    Route::delete('/lead-labels/{label}', [LeadController::class, 'destroyLabel'])->middleware('permission:leads.labels.manage')->name('lead-labels.destroy');
    Route::post('/leads/bulk-label', [LeadController::class, 'bulkLabel'])->middleware('permission:leads.labels.manage')->name('leads.bulk-label');
    Route::post('/leads/{lead}/labels', [LeadController::class, 'addLabel'])->middleware('permission:leads.labels.manage')->name('leads.labels.add');
    Route::delete('/leads/{lead}/labels/{label}', [LeadController::class, 'removeLabel'])->middleware('permission:leads.labels.manage')->name('leads.labels.remove');
    Route::post('/leads/bulk-assign', [LeadController::class, 'bulkAssign'])->middleware('permission:leads.assign')->name('leads.bulk-assign');
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->middleware('permission:leads.assign')->name('leads.assign');
    Route::post('/leads/{lead}/notes', [LeadController::class, 'note'])->middleware('permission:leads.notes.create')->name('leads.notes');

    // Leads CRUD
    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view')->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->middleware('permission:leads.create')->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create')->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view')->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->middleware('permission:leads.update')->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update')->name('leads.update');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:leads.delete')->name('leads.destroy');

    // Calls
    Route::get('/calls', [CallLogController::class, 'index'])->middleware('permission:calls.view')->name('calls.index');
    Route::post('/leads/{lead}/calls', [CallLogController::class, 'store'])->middleware('permission:calls.create')->name('calls.store');

    // Follow ups
    Route::get('/follow-ups/nearest', [FollowUpController::class, 'nearest'])
        ->name('followups.nearest');

    Route::get('/follow-ups/reminders', [FollowUpController::class, 'reminders'])
        ->name('followups.reminders');

    Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])
        ->name('followups.complete');

    Route::post('/follow-ups/{followUp}/snooze', [FollowUpController::class, 'snooze'])
        ->name('followups.snooze');

    Route::post('/follow-ups/{followUp}/reschedule', [FollowUpController::class, 'reschedule'])
        ->name('followups.reschedule');

    Route::post('/follow-ups/{followUp}/cancel', [FollowUpController::class, 'cancel'])
        ->name('followups.cancel');


    Route::get('/follow-ups', [FollowUpController::class, 'index'])->middleware('permission:followups.view')->name('followups.index');
    Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->middleware('permission:followups.complete')->name('followups.complete');
    Route::delete('/follow-ups/{followUp}', [FollowUpController::class, 'destroy'])->middleware('permission:followups.delete')->name('followups.destroy');

    // Pipeline
    Route::get('/pipeline', [PipelineController::class, 'index'])->middleware('permission:pipeline.view')->name('pipeline.index');
    Route::post('/pipeline/{lead}/move', [PipelineController::class, 'move'])->middleware('permission:pipeline.move')->name('pipeline.move');

    // Access control
    Route::prefix('access-control')->name('access-control.')->group(function () {
        Route::get('/', [AccessControlController::class, 'index'])->middleware('permission:access-control.view')->name('index');
        Route::post('/roles', [AccessControlController::class, 'storeRole'])->middleware('permission:access-control.roles.create')->name('roles.store');
        Route::post('/permissions', [AccessControlController::class, 'storePermission'])->middleware('permission:access-control.permissions.create')->name('permissions.store');
        Route::put('/assign', [AccessControlController::class, 'assignPermissions'])->middleware('permission:access-control.user-permissions.assign|access-control.role-permissions.assign')->name('assign');
        Route::delete('/permissions/{permission}', [AccessControlController::class, 'destroyPermission'])->middleware('permission:access-control.permissions.delete')->name('permissions.destroy');
        Route::delete('/roles/{role}', [AccessControlController::class, 'destroyRole'])->middleware('permission:access-control.roles.delete')->name('roles.destroy');
    });

    // Employee impersonation
    Route::post('/employees/stop-impersonating', [EmployeeController::class, 'stopImpersonating'])->name('employees.stop-impersonating');
    Route::post('/employees/{employee}/impersonate', [EmployeeController::class, 'impersonate'])
        ->middleware('permission:employees.impersonate')->name('employees.impersonate');

    // Employees CRUD
    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employees.view')->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->middleware('permission:employees.create')->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:employees.create')->name('employees.store');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('permission:employees.update')->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employees.update')->name('employees.update');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employees.delete')->name('employees.destroy');

    // Generic CRUD registrar.
    $crud = static function (string $uri, string $routeName, string $controller, string $permission, string $parameter = 'item'): void {
        Route::get("/{$uri}", [$controller, 'index'])->middleware("permission:{$permission}.view")->name("{$routeName}.index");
        Route::get("/{$uri}/create", [$controller, 'create'])->middleware("permission:{$permission}.create")->name("{$routeName}.create");
        Route::post("/{$uri}", [$controller, 'store'])->middleware("permission:{$permission}.create")->name("{$routeName}.store");
        Route::get("/{$uri}/{{$parameter}}/edit", [$controller, 'edit'])->middleware("permission:{$permission}.update")->name("{$routeName}.edit");
        Route::put("/{$uri}/{{$parameter}}", [$controller, 'update'])->middleware("permission:{$permission}.update")->name("{$routeName}.update");
        Route::patch("/{$uri}/{{$parameter}}", [$controller, 'update'])->middleware("permission:{$permission}.update");
        Route::delete("/{$uri}/{{$parameter}}", [$controller, 'destroy'])->middleware("permission:{$permission}.delete")->name("{$routeName}.destroy");
    };

    $crud('branches', 'branches', BranchController::class, 'branches');
    $crud('teams', 'teams', TeamController::class, 'teams');
    $crud('campaigns', 'campaigns', CampaignController::class, 'campaigns');
    $crud('products', 'products', ProductController::class, 'products');
    $crud('customers', 'customers', CustomerController::class, 'customers');
    $crud('tasks', 'tasks', TaskController::class, 'tasks');
    $crud('orders', 'orders', OrderController::class, 'orders');
    $crud('payments', 'payments', PaymentController::class, 'payments');

    Route::prefix('settings')->name('crm-settings.')->group(function () use ($crud) {
        $crud(
            'lead-sources',
            'lead-sources',
            LeadSourceController::class,
            'lead-sources'
        );

        $crud(
            'lead-statuses',
            'lead-statuses',
            LeadStatusController::class,
            'lead-statuses'
        );

        $crud(
            'call-dispositions',
            'call-dispositions',
            CallDispositionController::class,
            'call-dispositions'
        );
    });

    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
});



Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Message Templates
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/whatsapp-templates',
        [WhatsappMessageTemplateController::class, 'index']
    )->name('whatsapp-templates.index');

    Route::get(
        '/whatsapp-templates/create',
        [WhatsappMessageTemplateController::class, 'create']
    )->name('whatsapp-templates.create');

    Route::post(
        '/whatsapp-templates',
        [WhatsappMessageTemplateController::class, 'store']
    )->name('whatsapp-templates.store');

    Route::get(
        '/whatsapp-templates/{whatsappTemplate}/edit',
        [WhatsappMessageTemplateController::class, 'edit']
    )->name('whatsapp-templates.edit');

    Route::put(
        '/whatsapp-templates/{whatsappTemplate}',
        [WhatsappMessageTemplateController::class, 'update']
    )->name('whatsapp-templates.update');

    Route::delete(
        '/whatsapp-templates/{whatsappTemplate}',
        [WhatsappMessageTemplateController::class, 'destroy']
    )->name('whatsapp-templates.destroy');

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Lead Popup Data
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leads/{lead}/whatsapp-templates',
        [WhatsappMessageTemplateController::class, 'selectable']
    )->name('leads.whatsapp-templates.selectable');
});

require __DIR__.'/settings.php';
