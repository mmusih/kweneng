<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\MarksController;
use App\Http\Controllers\Admin\LibrarianController;
use App\Http\Controllers\Admin\ExamSummaryController;
use App\Http\Controllers\Admin\AccountsOfficerController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ReportCardController;


Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Student Management
    Route::resource('students', StudentController::class);

    // Class Management
    Route::resource('classes', ClassController::class);

    // Teacher Management
    Route::resource('teachers', TeacherController::class)->except(['show']);

    // Parent Management
    Route::resource('parents', ParentController::class)->except(['show']);

    // Librarian Management
    Route::resource('librarians', LibrarianController::class)->except(['show']);

    // Academic Year Management
    Route::resource('academic-years', AcademicYearController::class);
    Route::post('academic-years/{academicYear}/close', [AcademicYearController::class, 'close'])->name('academic-years.close');
    Route::post('academic-years/{academicYear}/lock', [AcademicYearController::class, 'lock'])->name('academic-years.lock');

    // Term Management
    Route::resource('terms', TermController::class);
    Route::post('terms/{term}/finalize', [TermController::class, 'finalize'])->name('terms.finalize');
    Route::post('terms/{term}/lock', [TermController::class, 'lock'])->name('terms.lock');
    Route::post('terms/{term}/activate', [TermController::class, 'activate'])->name('terms.activate');

    Route::post('terms/{term}/lock-midterm', [TermController::class, 'lockMidterm'])->name('terms.lock-midterm');
    Route::post('terms/{term}/unlock-midterm', [TermController::class, 'unlockMidterm'])->name('terms.unlock-midterm');
    Route::post('terms/{term}/lock-endterm', [TermController::class, 'lockEndterm'])->name('terms.lock-endterm');
    Route::post('terms/{term}/unlock-endterm', [TermController::class, 'unlockEndterm'])->name('terms.unlock-endterm');

    // Subject Management Routes
    Route::resource('subjects', SubjectController::class);

    // Subject Assignment Routes
    Route::get('subjects/manage/classes', [SubjectController::class, 'manageClassAssignments'])->name('subjects.manage-classes');
    Route::post('subjects/assign/class', [SubjectController::class, 'assignSubjectToClass'])->name('subjects.assign-class');
    Route::delete('subjects/remove/class', [SubjectController::class, 'removeSubjectFromClass'])->name('subjects.remove-class');

    Route::get('subjects/manage/teachers', [SubjectController::class, 'manageTeacherAssignments'])->name('subjects.manage-teachers');
    Route::post('subjects/assign/teacher', [SubjectController::class, 'assignTeacherToSubject'])->name('subjects.assign-teacher');
    Route::delete('subjects/remove/teacher', [SubjectController::class, 'removeTeacherFromSubject'])->name('subjects.remove-teacher');

    // Promotion Management
    Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::post('promotions/promote-student', [PromotionController::class, 'promoteStudent'])->name('promotions.promote-student');
    Route::post('promotions/bulk-promote', [PromotionController::class, 'bulkPromote'])->name('promotions.bulk-promote');
    Route::post('promotions/reverse-promotion', [PromotionController::class, 'reversePromotion'])->name('promotions.reverse-promotion');

    // ALUMNI ROUTES - ORDER MATTERS!
    // Specific routes MUST come BEFORE resource routes
    Route::get('alumni/interests', [AlumniController::class, 'interests'])->name('alumni.interests');
    Route::patch('alumni/interests/{interest}', [AlumniController::class, 'processInterest'])->name('alumni.process-interest');
    Route::post('alumni/interests/{interest}/convert', [AlumniController::class, 'convertInterestToAlumni'])->name('alumni.convert-interest');

    // Resource route MUST come AFTER specific routes
    Route::resource('alumni', AlumniController::class);

    // MARKS MANAGEMENT ROUTES
    Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
    Route::get('/marks/{id}', [MarksController::class, 'show'])->name('marks.show');
    Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
    Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
    Route::delete('/marks/{id}', [MarksController::class, 'destroy'])->name('marks.destroy');
    Route::get('/marks/student-averages', [MarksController::class, 'getStudentAverages'])->name('marks.student-averages');

    // STUDENT-SUBJECT ASSIGNMENT ROUTES
    Route::get('/student-subjects', [MarksController::class, 'studentSubjectsIndex'])->name('student-subjects.index');
    Route::get('/student-subjects/create', [MarksController::class, 'studentSubjectsCreate'])->name('student-subjects.create');
    Route::post('/student-subjects', [MarksController::class, 'studentSubjectsStore'])->name('student-subjects.store');
    Route::delete('/student-subjects/{id}', [MarksController::class, 'studentSubjectsDestroy'])->name('student-subjects.destroy');

    // EXAM SUMMARY ROUTES
    Route::get('/exam-summaries', [ExamSummaryController::class, 'index'])->name('exam-summaries.index');

    Route::get('/exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])->name('exam-summaries.pdf');

    // REPORT CARD ROUTES
    Route::get('/reports', [ReportCardController::class, 'index'])->name('reports.index');
    Route::get('/reports/student/{student}', [ReportCardController::class, 'show'])->name('reports.show');
    Route::get('/reports/student/{student}/pdf', [ReportCardController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/bulk/pdf', [ReportCardController::class, 'bulkPdf'])->name('reports.bulk-pdf');

    // AJAX ENDPOINTS FOR DYNAMIC LOADING
    Route::get('/student-subjects/classes/{academicYearId}', [MarksController::class, 'getClassesByAcademicYear']);
    Route::get('/student-subjects/students/{classId}/{academicYearId}', [MarksController::class, 'getStudentsByClass'])->name('student-subjects.students');
    Route::get('/student-subjects/subjects/{classId}/{academicYearId}', [MarksController::class, 'getSubjectsByClass']);
    Route::get('/terms/by-academic-year/{academicYearId}', [MarksController::class, 'getTermsByAcademicYear'])->name('terms.by-academic-year');

    // Accounts Officer Management
    Route::resource('accounts-officers', AccountsOfficerController::class)->except(['show']);
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
