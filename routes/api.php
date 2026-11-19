<?php

use App\Http\Controllers\Api\V1\NodaApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [NodaApiController::class, 'login']);
    Route::post('/mpesa/voda-lipa-callback', [NodaApiController::class, 'vodaLipaCallback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [NodaApiController::class, 'dashboard']);

        Route::middleware('staff')->group(function () {
            Route::post('/groups', [NodaApiController::class, 'createGroup'])->middleware('permission:groups.create');
            Route::post('/groups/{group}/assign-officer', [NodaApiController::class, 'assignGroupOfficer'])->middleware('permission:groups.assign_officer');
            Route::post('/groups/{group}/members', [NodaApiController::class, 'addMember'])->middleware('permission:members.add');
            Route::put('/members/{member}', [NodaApiController::class, 'updateMember'])->middleware('permission:members.edit');
            Route::post('/loans', [NodaApiController::class, 'createLoan'])->middleware('permission:loans.create');
            Route::post('/payments', [NodaApiController::class, 'recordPayment'])->middleware('permission:payments.record');
            Route::post('/penalties/sweep', [NodaApiController::class, 'runPenaltySweep'])->middleware('permission:penalties.sweep');
            Route::post('/mpesa/simulate', [NodaApiController::class, 'vodaLipaCallback'])->middleware('permission:mpesa.simulate');
            Route::get('/templates', [NodaApiController::class, 'templates'])->middleware('permission:templates.view');
            Route::post('/templates', [NodaApiController::class, 'saveTemplate'])->middleware('permission:templates.manage');
            Route::put('/templates/{template}', [NodaApiController::class, 'updateTemplate'])->middleware('permission:templates.manage');
            Route::delete('/templates/{template}', [NodaApiController::class, 'deleteTemplate'])->middleware('permission:templates.manage');
            Route::post('/contacts/message', [NodaApiController::class, 'sendContactMessage'])->middleware('permission:notifications.dispatch');
            Route::get('/reports', [NodaApiController::class, 'reports'])->middleware('permission:reports.view');
            Route::post('/reports/budgets', [NodaApiController::class, 'saveBudget'])->middleware('permission:reports.manage');
            Route::post('/reports/entries', [NodaApiController::class, 'saveFinancialEntry'])->middleware('permission:reports.manage');
        });
    });
});
