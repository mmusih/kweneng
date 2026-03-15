<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountsOfficer\DashboardController;

Route::middleware(['auth', 'role:accounts_officer'])
    ->prefix('accounts-officer')
    ->name('accounts-officer.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('/students/{student}/block', [DashboardController::class, 'block'])->name('students.block');
        Route::post('/students/{student}/unblock', [DashboardController::class, 'unblock'])->name('students.unblock');
    });
