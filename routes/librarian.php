<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Librarian\DashboardController;

Route::middleware(['auth', 'role:librarian'])->prefix('librarian')->name('librarian.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});