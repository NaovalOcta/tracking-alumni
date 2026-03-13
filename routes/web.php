<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Alumni CRUD
    Route::resource('alumni', AlumniController::class);
    Route::post('alumni/import', [AlumniController::class, 'import'])->name('alumni.import');

    // Tracking
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::post('/tracking/batch', [TrackingController::class, 'trackBatch'])->name('tracking.batch');
    Route::post('/tracking/{nim}', [TrackingController::class, 'trackSingle'])->name('tracking.single');
    Route::get('/tracking/{nim}/result', [TrackingController::class, 'result'])->name('tracking.result');
    Route::get('/tracking/{nim}/progress', [TrackingController::class, 'getProgress'])->name('tracking.progress');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Audit
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::post('/audit/{nim}/verify', [AuditController::class, 'verify'])->name('audit.verify');
    Route::post('/audit/{nim}/reject', [AuditController::class, 'reject'])->name('audit.reject');

    // Export
    Route::get('/export', [ExportController::class, 'export'])->name('export');
});
