<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('leads')->controller(LeadApiController::class)->group(function () {
        Route::get('/options', 'options');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{lead}', 'show');
        Route::put('/{lead}', 'update');
        Route::patch('/{lead}', 'update');
        Route::delete('/{lead}', 'destroy');

        Route::post('/{lead}/assign', 'assign');
        Route::post('/bulk-assign', 'bulkAssign');
        Route::post('/{lead}/notes', 'addNote');

        Route::post('/labels', 'storeLabel');
        Route::delete('/labels/{label}', 'destroyLabel');
        Route::post('/{lead}/labels', 'addLabel');
        Route::delete('/{lead}/labels/{label}', 'removeLabel');
    });
});
