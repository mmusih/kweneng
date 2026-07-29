<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExamSummaryController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Office\DashboardController;
use App\Http\Controllers\Office\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,office'])
    ->prefix('office')
    ->name('office.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('students', [StudentProfileController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [StudentProfileController::class, 'show'])->name('students.show');
        Route::get('students/{student}/edit', [StudentProfileController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentProfileController::class, 'update'])->name('students.update');
        Route::patch('students/{student}', [StudentProfileController::class, 'update']);

        Route::get('events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
        Route::get('events/get-events', [EventController::class, 'getEvents'])->name('events.get-events');
        Route::delete('events/comments/{comment}', [EventController::class, 'deleteComment'])->name('events.delete-comment');
        Route::resource('events', EventController::class);
        Route::post('events/{event}/comments', [EventController::class, 'addComment'])->name('events.add-comment');

        Route::resource('announcements', AnnouncementController::class);
        Route::get('announcements/{announcement}/tracking', [AnnouncementController::class, 'tracking'])->name('announcements.tracking');
        Route::get('announcements/{announcement}/tracking/export', [AnnouncementController::class, 'exportTrackingCsv'])->name('announcements.tracking.export');
        Route::post('announcements/{announcement}/tracking/reminder', [AnnouncementController::class, 'sendTrackingReminder'])->name('announcements.tracking.reminder');

        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');

        Route::get('exam-summaries', [ExamSummaryController::class, 'index'])->name('exam-summaries.index');
        Route::get('exam-summaries/preview', [ExamSummaryController::class, 'preview'])->name('exam-summaries.preview');
        Route::get('exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])->name('exam-summaries.pdf');

        Route::get('reports', [ReportCardController::class, 'index'])->name('reports.index');
        Route::get('reports/student/{student}', [ReportCardController::class, 'show'])->name('reports.show');
        Route::get('reports/student/{student}/pdf', [ReportCardController::class, 'pdf'])->name('reports.pdf');
        Route::get('reports/bulk/pdf', [ReportCardController::class, 'bulkPdf'])->name('reports.bulk-pdf');
    });
