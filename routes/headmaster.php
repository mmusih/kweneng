<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Headmaster\DashboardController;
use App\Http\Controllers\Headmaster\CommentController;
use App\Http\Controllers\Headmaster\ReportCardController;
use App\Http\Controllers\Headmaster\ExamSummaryController;
use App\Http\Controllers\Headmaster\MarksMonitorController;
use App\Http\Controllers\Admin\EventController as HeadmasterEventController;

Route::middleware(['auth', 'role:headmaster'])->prefix('headmaster')->name('headmaster.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/bulk-store', [CommentController::class, 'bulkStore'])->name('comments.bulk-store');

    Route::get('/reports', [ReportCardController::class, 'index'])->name('reports.index');
    Route::get('/reports/student/{student}', [ReportCardController::class, 'show'])->name('reports.show');
    Route::get('/reports/student/{student}/pdf', [ReportCardController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/bulk/pdf', [ReportCardController::class, 'bulkPdf'])->name('reports.bulk-pdf');

    Route::get('/exam-summaries', [ExamSummaryController::class, 'index'])->name('exam-summaries.index');
    Route::get('/exam-summaries/preview', [ExamSummaryController::class, 'preview'])->name('exam-summaries.preview');
    Route::get('/exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])->name('exam-summaries.pdf');

    Route::get('/marks-monitor', [MarksMonitorController::class, 'index'])->name('marks.index');

    Route::get('/marks-monitor/detail', [MarksMonitorController::class, 'show'])
        ->name('marks.detail');
    Route::get('/marks-monitor/detail', [MarksMonitorController::class, 'show'])->name('marks.detail');

    // Calendar and holidays. Holiday events automatically mark attendance register days as holidays.
    Route::get('/events/calendar', [HeadmasterEventController::class, 'calendar'])->name('events.calendar');
    Route::get('/events/get-events', [HeadmasterEventController::class, 'getEvents'])->name('events.get-events');
    Route::delete('/events/comments/{comment}', [HeadmasterEventController::class, 'deleteComment'])->name('events.delete-comment');
    Route::resource('events', HeadmasterEventController::class);
    Route::post('/events/{event}/comments', [HeadmasterEventController::class, 'addComment'])->name('events.add-comment');
});
