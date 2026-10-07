<?php

use App\Http\Controllers\Admin\EventController as HeadmasterEventController;
use App\Http\Controllers\Headmaster\CommentController;
use App\Http\Controllers\Headmaster\DashboardController;
use App\Http\Controllers\Headmaster\ExamSummaryController;
use App\Http\Controllers\Headmaster\MarksMonitorController;
use App\Http\Controllers\Headmaster\ReportCardController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\PrefectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:headmaster'])->prefix('headmaster')->name('headmaster.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/timetable', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'timetable'])->name('timetable.index');
    Route::get('/timetable/teacher-loads', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'index'])->name('timetable.teacher-loads');
    Route::get('/timetable/teacher-loads/download', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'download'])->name('timetable.teacher-loads.download');
    Route::get('/timetable/teaching-summary', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'teachingSummary'])->name('timetable.teaching-summary');
    Route::get('/timetable/teaching-summary/download', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'teachingSummaryDownload'])->name('timetable.teaching-summary.download');
    Route::get('/timetable/teaching-summary/csv', [\App\Http\Controllers\Admin\Timetable\TeacherLoadController::class, 'csv'])->name('timetable.teaching-summary.csv');
    Route::get('/students', [\App\Http\Controllers\Headmaster\StudentProfileController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [\App\Http\Controllers\Headmaster\StudentProfileController::class, 'show'])->name('students.show');

    Route::get('/awards', [AwardController::class, 'index'])->name('awards.index');
    Route::get('/awards/create', [AwardController::class, 'create'])->name('awards.create');
    Route::post('/awards', [AwardController::class, 'store'])->name('awards.store');
    Route::get('/awards/{award}/edit', [AwardController::class, 'edit'])->name('awards.edit');
    Route::put('/awards/{award}', [AwardController::class, 'update'])->name('awards.update');
    Route::get('/awards/{award}', [AwardController::class, 'show'])->name('awards.show');
    Route::post('/awards/{award}/publish', [AwardController::class, 'publish'])->name('awards.publish');
    Route::delete('/awards/{award}', [AwardController::class, 'destroy'])->name('awards.destroy');
    Route::post('/awards/{award}/recipients', [AwardController::class, 'addRecipient'])->name('awards.recipients.store');
    Route::delete('/awards/{award}/recipients/{studentAward}', [AwardController::class, 'removeRecipient'])->name('awards.recipients.destroy');
    Route::get('/awards/{award}/print', [AwardController::class, 'printRun'])->name('awards.print');
    Route::get('/awards/{award}/excel', [AwardController::class, 'exportExcel'])->name('awards.excel');
    Route::get('/awards/{award}/certificates', [AwardController::class, 'bulkCertificates'])->name('awards.certificates');
    Route::get('/awards/{award}/certificates/pdf', [AwardController::class, 'bulkCertificatesPdf'])->name('awards.certificates-pdf');
    Route::get('/awards/{award}/certificates/{studentAward}', [AwardController::class, 'certificate'])->name('awards.certificate');

    Route::get('/prefects', [PrefectController::class, 'index'])->name('prefects.index');
    Route::get('/prefects/create', [PrefectController::class, 'create'])->name('prefects.create');
    Route::get('/prefects/certificates/pdf', [PrefectController::class, 'bulkCertificatesPdf'])->name('prefects.certificates-pdf');
    Route::post('/prefects', [PrefectController::class, 'store'])->name('prefects.store');
    Route::get('/prefects/{prefect}/edit', [PrefectController::class, 'edit'])->name('prefects.edit');
    Route::put('/prefects/{prefect}', [PrefectController::class, 'update'])->name('prefects.update');
    Route::delete('/prefects/{prefect}', [PrefectController::class, 'destroy'])->name('prefects.destroy');
    Route::get('/prefects/{prefect}/certificate', [PrefectController::class, 'certificate'])->name('prefects.certificate');

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

    // Calendar and holidays. Holiday events automatically mark attendance register days as holidays.
    Route::get('/events/calendar', [HeadmasterEventController::class, 'calendar'])->name('events.calendar');
    Route::get('/events/get-events', [HeadmasterEventController::class, 'getEvents'])->name('events.get-events');
    Route::delete('/events/comments/{comment}', [HeadmasterEventController::class, 'deleteComment'])->name('events.delete-comment');
    Route::resource('events', HeadmasterEventController::class);
    Route::post('/events/{event}/comments', [HeadmasterEventController::class, 'addComment'])->name('events.add-comment');
});
