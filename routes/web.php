<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\TrackingController;
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

    // Tracking
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::post('/tracking/batch', [TrackingController::class, 'trackBatch'])->name('tracking.batch');
    Route::post('/tracking/{nim}', [TrackingController::class, 'trackSingle'])->name('tracking.single');
    Route::get('/tracking/{nim}/result', [TrackingController::class, 'result'])->name('tracking.result');
});
