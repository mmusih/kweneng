<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\MarksController;
use App\Http\Controllers\Student\DashboardController;

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('results.access')
        ->name('dashboard');

    Route::middleware('results.access')->group(function () {
        Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
        Route::get('/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])->name('marks.show');
    });
});
