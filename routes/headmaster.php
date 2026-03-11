<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Headmaster\DashboardController;
use App\Http\Controllers\Headmaster\CommentController;
use App\Http\Controllers\Headmaster\ReportCardController;
use App\Http\Controllers\Headmaster\ExamSummaryController;
use App\Http\Controllers\Headmaster\MarksMonitorController;

Route::middleware(['auth', 'role:headmaster'])->prefix('headmaster')->name('headmaster.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');

    Route::get('/reports', [ReportCardController::class, 'index'])->name('reports.index');
    Route::get('/reports/student/{student}', [ReportCardController::class, 'show'])->name('reports.show');

    Route::get('/exam-summaries', [ExamSummaryController::class, 'index'])->name('exam-summaries.index');
    Route::get('/exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])->name('exam-summaries.pdf');

    Route::get('/marks-monitor', [MarksMonitorController::class, 'index'])->name('marks.index');
});