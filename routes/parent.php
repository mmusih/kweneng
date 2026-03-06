<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parent\DashboardController;

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
