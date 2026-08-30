<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
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
    Route::post('/configuration/categories', [ConfigurationController::class, 'category'])->name('configuration.categories')->middleware('role:super-admin');
    Route::post('/configuration/desks', [ConfigurationController::class, 'desk'])->name('configuration.desks')->middleware('role:super-admin');
    Route::resource('tokens', TokenController::class)->only('index', 'show');
    Route::get('/tokens/{token}/pdf', [TokenController::class, 'pdf'])->name('tokens.pdf');
    Route::middleware('role:super-admin,administrator')->group(function () {
        Route::resource('tokens', TokenController::class)->only('create', 'store', 'edit', 'update');
        Route::post('/tokens/{token}/cancel', [TokenController::class, 'cancel'])->name('tokens.cancel');
    });
    Route::resource('applicants', ApplicantController::class)->only('index', 'show');
    Route::get('/applicants/{applicant}/letters/{type}', [ApplicantController::class, 'letter'])->name('applicants.letter');
    Route::middleware('role:super-admin,administrator,data-entry')->group(function () {
        Route::resource('applicants', ApplicantController::class)->only('create', 'store', 'edit', 'update');
        Route::post('/applicants/{applicant}/documents', [DocumentController::class, 'store'])->name('documents.store');
    });
    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/tokens/{format}', [ReportController::class, 'tokens'])->whereIn('format', ['pdf', 'excel'])->name('reports.tokens');
    Route::get('/reports/applicants/{format}', [ReportController::class, 'applicants'])->whereIn('format', ['pdf', 'excel'])->name('reports.applicants');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index')->middleware('role:super-admin,viewer');
    Route::resource('users',UserController::class)->except('show','destroy')->middleware('role:super-admin');
});
