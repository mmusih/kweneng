<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\MarksController;

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/children/marks', [MarksController::class, 'index'])->name('children.marks.index');

    Route::middleware('results.access')->group(function () {
        Route::get('/children/{student}/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])
            ->name('children.marks.show');
    });
});
