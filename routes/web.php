<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TokenCategoryController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('companies', CompanyController::class)->except('show')->middleware('role:super-admin,administrator');
    Route::resource('agencies', AgencyController::class)->except('show')->middleware('role:super-admin,administrator');
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration')->middleware('role:super-admin');
    Route::post('/configuration/desks', [ConfigurationController::class, 'desk'])->name('configuration.desks')->middleware('role:super-admin');
    Route::resource('token-categories', TokenCategoryController::class)->except('show')->middleware('role:super-admin,administrator');
    Route::resource('tokens', TokenController::class)->only('index');
    Route::get('/tokens/{token}/modal', [TokenController::class, 'modal'])->name('tokens.modal');
    Route::get('/tokens/{token}/workers/modal', [TokenController::class, 'workersModal'])->name('tokens.workers.modal');
    Route::get('/tokens/{token}/pdf', [TokenController::class, 'pdf'])->name('tokens.pdf');
    Route::middleware('role:super-admin,administrator')->group(function () {
        Route::resource('tokens', TokenController::class)->only('create', 'store', 'edit', 'update');
        Route::post('/tokens/{token}/cancel', [TokenController::class, 'cancel'])->name('tokens.cancel');
        Route::post('/tokens/{token}/documents', [DocumentController::class, 'storeToken'])->name('tokens.documents.store');
        Route::put('/tokens/{token}/documents/{document}', [DocumentController::class, 'updateToken'])->name('tokens.documents.update');
    });
    Route::resource('tokens', TokenController::class)->only('show');
    Route::resource('workers', WorkerController::class)->only('index');
    Route::middleware('role:super-admin,administrator,data-entry')->group(function () {
        Route::resource('workers', WorkerController::class)->only('create', 'store', 'edit', 'update');
        Route::post('/workers/{worker}/documents', [DocumentController::class, 'store'])->name('documents.store');
    });
    Route::resource('workers', WorkerController::class)->only('show');
    Route::get('/workers/{worker}/letters/{type}', [WorkerController::class, 'letter'])->name('workers.letter');
    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/tokens/{format}', [ReportController::class, 'tokens'])->whereIn('format', ['pdf', 'excel'])->name('reports.tokens');
    Route::get('/reports/workers/{format}', [ReportController::class, 'workers'])->whereIn('format', ['pdf', 'excel'])->name('reports.workers');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index')->middleware('role:super-admin,viewer');
    Route::middleware('role:super-admin')->group(function () {
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
        Route::resource('users', UserController::class)->except('show', 'destroy');
    });
});
