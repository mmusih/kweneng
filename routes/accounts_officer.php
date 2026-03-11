<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountsOfficer\DashboardController;
use App\Http\Controllers\AccountsOfficer\StudentFeesBlockController;

Route::middleware(['auth', 'role:accounts_officer'])->prefix('accounts-officer')->name('accounts-officer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/students', [StudentFeesBlockController::class, 'index'])->name('students.index');
    Route::patch('/students/{student}/toggle-fees-block', [StudentFeesBlockController::class, 'toggle'])->name('students.toggle-fees-block');
});
