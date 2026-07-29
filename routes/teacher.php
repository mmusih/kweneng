<?php

use App\Http\Controllers\Hod\SchemeDashboardController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\BehaviourController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\HomeworkController;
use App\Http\Controllers\Teacher\MarksController;
use App\Http\Controllers\Teacher\PunctualityController;
use App\Http\Controllers\Teacher\RequisitionController;
use App\Http\Controllers\Teacher\SchemeController;
use App\Http\Controllers\Teacher\TermSummaryController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,headmaster'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/timetable', [TimetableController::class, 'teacher'])->name('timetable');

    // Marks routes
    Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
    Route::post('/marks/class-subjects', [MarksController::class, 'showClassSubjects'])->name('marks.class-subjects');
    Route::post('/marks/students', [MarksController::class, 'showStudents'])->name('marks.students');
    Route::post('/marks', [MarksController::class, 'store'])->name('marks.store');
    Route::post('/marks/import', [MarksController::class, 'import'])->name('marks.import');
    Route::get('/marks/print', [MarksController::class, 'printResults'])->name('marks.print');
    Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
    Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
    Route::get('/marks/terms/{academicYearId}', [MarksController::class, 'loadTerms'])->name('marks.load-terms');

    // Requisition routes
    Route::get('/requisitions', [RequisitionController::class, 'index'])->name('requisitions.index');
    Route::get('/requisitions/create', [RequisitionController::class, 'create'])->name('requisitions.create');
    Route::post('/requisitions', [RequisitionController::class, 'store'])->name('requisitions.store');
    Route::get('/requisitions/{requisition}', [RequisitionController::class, 'show'])->name('requisitions.show');
    Route::patch('/requisitions/{requisition}/cancel', [RequisitionController::class, 'cancel'])->name('requisitions.cancel');

    // Homework routes
    Route::get('/homeworks', [HomeworkController::class, 'index'])->name('homeworks.index');
    Route::post('/homeworks', [HomeworkController::class, 'store'])->name('homeworks.store');
    Route::delete('/homeworks/{homework}', [HomeworkController::class, 'destroy'])->name('homeworks.destroy');
    Route::get('/homeworks/{homework}/attachment', [HomeworkController::class, 'downloadAttachment'])->name('homeworks.attachment');
    Route::get('/homeworks/{homework}/marks', [HomeworkController::class, 'marks'])->name('homeworks.marks');
    Route::post('/homeworks/{homework}/marks', [HomeworkController::class, 'storeMarks'])->name('homeworks.store-marks');

    // Attendance routes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
    Route::get('/attendance/print', [AttendanceController::class, 'print'])->name('attendance.print');
    Route::get('/attendance/pdf', [AttendanceController::class, 'pdf'])->name('attendance.pdf');
    Route::get('/attendance/csv', [AttendanceController::class, 'csv'])->name('attendance.csv');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // Punctuality routes
    Route::get('/punctuality', [PunctualityController::class, 'index'])->name('punctuality.index');
    Route::post('/punctuality', [PunctualityController::class, 'store'])->name('punctuality.store');

    // Behaviour routes
    Route::get('/behaviour', [BehaviourController::class, 'index'])->name('behaviour.index');
    Route::post('/behaviour', [BehaviourController::class, 'store'])->name('behaviour.store');

    // Term summary routes
    Route::get('/term-summary', [TermSummaryController::class, 'index'])->name('term-summary.index');
    Route::post('/term-summary', [TermSummaryController::class, 'store'])->name('term-summary.store');

    // Schemes of Work routes
    Route::get('/schemes', [SchemeController::class, 'index'])->name('schemes.index');
    Route::get('/schemes/create', [SchemeController::class, 'create'])->name('schemes.create');
    Route::post('/schemes', [SchemeController::class, 'store'])->name('schemes.store');
    Route::get('/schemes/{scheme}', [SchemeController::class, 'show'])->name('schemes.show');
    Route::post('/schemes/{scheme}/import-text', [SchemeController::class, 'importText'])->name('schemes.import-text');
    Route::post('/schemes/{scheme}/topics', [SchemeController::class, 'addTopic'])->name('schemes.topics.store');
    Route::delete('/schemes/{scheme}/topics/{item}', [SchemeController::class, 'destroyItem'])->name('schemes.topics.destroy');
    Route::put('/schemes/{scheme}/plan', [SchemeController::class, 'savePlan'])->name('schemes.plan.save');
    Route::patch('/schemes/{scheme}/items/{item}/status', [SchemeController::class, 'updateItemStatus'])->name('schemes.items.status');
    Route::patch('/schemes/{scheme}/items/{item}/subtopics/{subtopic}/toggle', [SchemeController::class, 'toggleSubtopic'])->name('schemes.items.subtopics.toggle');
    Route::post('/schemes/{scheme}/submit', [SchemeController::class, 'submit'])->name('schemes.submit');

    // HOD scheme monitoring routes. HOD is a department responsibility, not a replacement for the teacher role.
    Route::get('/hod/schemes', [SchemeDashboardController::class, 'index'])->name('hod.schemes.dashboard');
    Route::get('/hod/schemes/{scheme}', [SchemeDashboardController::class, 'show'])->name('hod.schemes.show');
    Route::patch('/hod/schemes/{scheme}/approve', [SchemeDashboardController::class, 'approve'])->name('hod.schemes.approve');
    Route::patch('/hod/schemes/{scheme}/request-changes', [SchemeDashboardController::class, 'requestChanges'])->name('hod.schemes.request-changes');

});
