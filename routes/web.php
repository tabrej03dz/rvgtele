<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\CallDispositionController;
use App\Http\Controllers\CallLogController;
use App\Http\Controllers\CampaignController;
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
use App\Http\Controllers\CompanyController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'company.active',])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin')->group(function () {
        Route::resource(
            'companies',
            CompanyController::class
        )->except('show');
    });




    Route::get('/leads/import', [
        LeadImportController::class,
        'create',
    ])->name('leads.import.create');

    Route::post('/leads/import', [
        LeadImportController::class,
        'store',
    ])->name('leads.import.store');

    Route::get('/leads/import/template', [
        LeadImportController::class,
        'downloadTemplate',
    ])->name('leads.import.template');

    /*
    |--------------------------------------------------------------------------
    | Bulk Assignment
    |--------------------------------------------------------------------------
    */

    Route::post('/leads/bulk-assign', [
        LeadController::class,
        'bulkAssign',
    ])->name('leads.bulk-assign');

    /*
    |--------------------------------------------------------------------------
    | Existing Lead Routes
    |--------------------------------------------------------------------------
    */

    Route::resource('leads', LeadController::class);

    Route::post('/leads/{lead}/assign', [
        LeadController::class,
        'assign',
    ])->name('leads.assign');

    Route::post('/leads/{lead}/notes', [
        LeadController::class,
        'note',
    ])->name('leads.notes');




    Route::resource('leads', LeadController::class);
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('leads/{lead}/notes', [LeadController::class, 'note'])->name('leads.notes');

    


    Route::get('calls', [CallLogController::class, 'index'])->name('calls.index');
    Route::post('leads/{lead}/calls', [CallLogController::class, 'store'])->name('calls.store');

    Route::get('follow-ups', [FollowUpController::class, 'index'])->name('followups.index');
    Route::post('follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('followups.complete');
    Route::delete('follow-ups/{followUp}', [FollowUpController::class, 'destroy'])->name('followups.destroy');

    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::post('pipeline/{lead}/move', [PipelineController::class, 'move'])->name('pipeline.move');

    Route::resource('employees', EmployeeController::class)->except('show');
    Route::resource('branches', BranchController::class)->parameters(['branches' => 'item'])->except('show');
    Route::resource('teams', TeamController::class)->parameters(['teams' => 'item'])->except('show');
    Route::resource('campaigns', CampaignController::class)->parameters(['campaigns' => 'item'])->except('show');
    Route::resource('products', ProductController::class)->parameters(['products' => 'item'])->except('show');
    Route::resource('customers', CustomerController::class)->parameters(['customers' => 'item'])->except('show');
    Route::resource('tasks', TaskController::class)->parameters(['tasks' => 'item'])->except('show');
    Route::resource('orders', OrderController::class)->parameters(['orders' => 'item'])->except('show');
    Route::resource('payments', PaymentController::class)->parameters(['payments' => 'item'])->except('show');

    Route::prefix('settings')->name('crm-settings.')->group(function () {
        Route::resource('lead-sources', LeadSourceController::class)->parameters(['lead-sources' => 'item'])->except('show');
        Route::resource('lead-statuses', LeadStatusController::class)->parameters(['lead-statuses' => 'item'])->except('show');
        Route::resource('call-dispositions', CallDispositionController::class)->parameters(['call-dispositions' => 'item'])->except('show');
    });

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/settings.php';
