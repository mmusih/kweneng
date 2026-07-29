<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\MarksController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('results.access')
        ->name('dashboard');
    Route::get('/timetable', [TimetableController::class, 'student'])->name('timetable');

    Route::middleware('results.access')->group(function () {
        Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
        Route::get('/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])->name('marks.show');
    });

    Route::get('/library', [\App\Http\Controllers\Student\LibraryController::class, 'index'])
        ->name('library.index');

    Route::get('/library', [\App\Http\Controllers\Student\LibraryController::class, 'index'])
        ->name('library.index');
});
