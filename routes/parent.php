<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\MarksController;
use App\Http\Controllers\Parent\EventsController;

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/children/marks', [MarksController::class, 'index'])->name('children.marks.index');

    Route::middleware('results.access')->group(function () {
        Route::get('/children/{student}/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])
            ->name('children.marks.show');
    });

    Route::get('/children/library', [\App\Http\Controllers\Parent\LibraryController::class, 'index'])
        ->name('children.library.index');

    /*
    |--------------------------------------------------------------------------
    | Events & Announcements
    |--------------------------------------------------------------------------
    */
    Route::get('/events', [EventsController::class, 'index'])->name('events.index');
    Route::get('/events/calendar-data', [EventsController::class, 'getEvents'])->name('events.calendar-data');
    Route::get('/announcements', [EventsController::class, 'announcements'])->name('announcements.index');
});
