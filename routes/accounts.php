<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounts\DashboardController;

Route::middleware(['auth', 'role:accounts'])->prefix('accounts')->name('accounts.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
