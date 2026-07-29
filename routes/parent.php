<?php

use App\Http\Controllers\Parent\AnnouncementController;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\EventsController;
use App\Http\Controllers\Parent\HomeworkController;
use App\Http\Controllers\Parent\MarksController;
use App\Http\Controllers\Parent\MessageController;
use App\Http\Controllers\Parent\ReportCardController;
use App\Http\Controllers\Parent\SchoolDocumentController;
use App\Http\Controllers\Parent\StudentProfileController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/timetable', [TimetableController::class, 'parent'])->name('timetable');

    /*
    |--------------------------------------------------------------------------
    | Marks
    |--------------------------------------------------------------------------
    */
    Route::get('/children/marks', [MarksController::class, 'index'])->name('children.marks.index');

    Route::middleware('results.access')->group(function () {
        Route::get('/children/{student}/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])
            ->name('children.marks.show');

        Route::get('/children/{student}/report-card/{termId}/download', [ReportCardController::class, 'download'])
            ->name('children.report-card.download');
    });

    /*
    |--------------------------------------------------------------------------
    | Library
    |--------------------------------------------------------------------------
    */
    Route::get('/children/library', [\App\Http\Controllers\Parent\LibraryController::class, 'index'])
        ->name('children.library.index');

    /*
    |--------------------------------------------------------------------------
    | Events & Announcements
    |--------------------------------------------------------------------------
    */
    Route::get('/events', [EventsController::class, 'index'])->name('events.index');
    Route::get('/events/calendar-data', [EventsController::class, 'getEvents'])->name('events.calendar-data');

    // Visiting the announcements page marks all as read
    Route::get('/announcements', [EventsController::class, 'announcements'])->name('announcements.index');

    // AJAX dismiss a single announcement from the dashboard
    Route::post('/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])
        ->name('announcements.dismiss');

    /*
    |--------------------------------------------------------------------------
    | Messaging
    |--------------------------------------------------------------------------
    */
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/new', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');

    /*
    |--------------------------------------------------------------------------
    | Homework
    |--------------------------------------------------------------------------
    */
    Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::get('/homework/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])
        ->name('homework.attachment');

    /*
    |--------------------------------------------------------------------------
    | Student Profile Updates
    |--------------------------------------------------------------------------
    */
    Route::get('/children/{student}/profile/edit', [StudentProfileController::class, 'edit'])
        ->name('children.profile.edit');
    Route::put('/children/{student}/profile', [StudentProfileController::class, 'update'])
        ->name('children.profile.update');

    /*
    |--------------------------------------------------------------------------
    | School Documents
    |--------------------------------------------------------------------------
    */
    Route::get('/documents', [SchoolDocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/download', [SchoolDocumentController::class, 'download'])
        ->name('documents.download');
});
