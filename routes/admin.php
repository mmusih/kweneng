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
use App\Http\Controllers\Admin\UserManagementController;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('students/bulk-delete', [StudentController::class, 'bulkDestroy'])
            ->name('students.bulk-delete');

        Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->name('students.reset-password');

        /*
        |--------------------------------------------------------------------------
        | Core Resources
        |--------------------------------------------------------------------------
        */
        Route::resource('students', StudentController::class);
        Route::resource('classes', ClassController::class);
        Route::resource('teachers', TeacherController::class)->except(['show']);
        Route::resource('parents', ParentController::class)->except(['show']);
        Route::resource('librarians', LibrarianController::class)->except(['show']);
        Route::resource('accounts-officers', AccountsOfficerController::class)->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | Class Student Management
        |--------------------------------------------------------------------------
        */
        Route::delete('classes/{class}/students/{student}', [ClassController::class, 'removeStudent'])
            ->name('classes.remove-student');

        Route::delete('classes/{class}/students', [ClassController::class, 'bulkRemoveStudents'])
            ->name('classes.bulk-remove-students');

        /*
        |--------------------------------------------------------------------------
        | Academic Years
        |--------------------------------------------------------------------------
        */
        Route::resource('academic-years', AcademicYearController::class);
        Route::post('academic-years/{academicYear}/close', [AcademicYearController::class, 'close'])->name('academic-years.close');
        Route::post('academic-years/{academicYear}/lock', [AcademicYearController::class, 'lock'])->name('academic-years.lock');

        /*
        |--------------------------------------------------------------------------
        | Terms
        |--------------------------------------------------------------------------
        */
        Route::resource('terms', TermController::class);
        Route::post('terms/{term}/finalize', [TermController::class, 'finalize'])->name('terms.finalize');
        Route::post('terms/{term}/lock', [TermController::class, 'lock'])->name('terms.lock');
        Route::post('terms/{term}/activate', [TermController::class, 'activate'])->name('terms.activate');
        Route::post('terms/{term}/lock-midterm', [TermController::class, 'lockMidterm'])->name('terms.lock-midterm');
        Route::post('terms/{term}/unlock-midterm', [TermController::class, 'unlockMidterm'])->name('terms.unlock-midterm');
        Route::post('terms/{term}/lock-endterm', [TermController::class, 'lockEndterm'])->name('terms.lock-endterm');
        Route::post('terms/{term}/unlock-endterm', [TermController::class, 'unlockEndterm'])->name('terms.unlock-endterm');

        /*
        |--------------------------------------------------------------------------
        | Subjects
        |--------------------------------------------------------------------------
        */
        Route::resource('subjects', SubjectController::class);

        // Assign subjects to classes
        Route::get('subjects/manage/classes', [SubjectController::class, 'manageClassAssignments'])->name('subjects.manage-classes');
        Route::post('subjects/manage/classes/bulk-save', [SubjectController::class, 'bulkSaveClassAssignments'])->name('subjects.bulk-save-classes');

        // Assign teachers to subjects/classes
        Route::get('subjects/manage/teachers', [SubjectController::class, 'manageTeacherAssignments'])->name('subjects.manage-teachers');
        Route::post('subjects/manage/teachers/bulk-save', [SubjectController::class, 'bulkSaveTeacherAssignments'])->name('subjects.bulk-save-teachers');
        Route::delete('subjects/remove/teacher', [SubjectController::class, 'removeTeacherFromSubject'])->name('subjects.remove-teacher');

        /*
        |--------------------------------------------------------------------------
        | Promotions
        |--------------------------------------------------------------------------
        */
        Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('promotions/promote-student', [PromotionController::class, 'promoteStudent'])->name('promotions.promote-student');
        Route::post('promotions/bulk-promote', [PromotionController::class, 'bulkPromote'])->name('promotions.bulk-promote');
        Route::post('promotions/reverse-promotion', [PromotionController::class, 'reversePromotion'])->name('promotions.reverse-promotion');

        /*
        |--------------------------------------------------------------------------
        | Alumni
        |--------------------------------------------------------------------------
        */
        Route::get('alumni/interests', [AlumniController::class, 'interests'])->name('alumni.interests');
        Route::patch('alumni/interests/{interest}', [AlumniController::class, 'processInterest'])->name('alumni.process-interest');
        Route::post('alumni/interests/{interest}/convert', [AlumniController::class, 'convertInterestToAlumni'])->name('alumni.convert-interest');
        Route::resource('alumni', AlumniController::class);

        /*
        |--------------------------------------------------------------------------
        | Marks
        |--------------------------------------------------------------------------
        */
        Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
        Route::get('/marks/{id}', [MarksController::class, 'show'])->name('marks.show');
        Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
        Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
        Route::delete('/marks/{id}', [MarksController::class, 'destroy'])->name('marks.destroy');

        // Marks utilities
        Route::get('/marks/student-averages', [MarksController::class, 'getStudentAverages'])->name('marks.student-averages');
        Route::post('/marks/import-preview', [MarksController::class, 'importPreview'])->name('marks.import-preview');
        Route::post('/marks/import-apply', [MarksController::class, 'importApply'])->name('marks.import-apply');

        /*
        |--------------------------------------------------------------------------
        | Student Subject Assignments
        |--------------------------------------------------------------------------
        */
        Route::get('/student-subjects', [MarksController::class, 'studentSubjectsIndex'])->name('student-subjects.index');
        Route::get('/student-subjects/create', [MarksController::class, 'studentSubjectsCreate'])->name('student-subjects.create');
        Route::post('/student-subjects', [MarksController::class, 'studentSubjectsStore'])->name('student-subjects.store');

        // CSV preview/apply for student subject assignments
        Route::post('/student-subjects/import-preview', [MarksController::class, 'studentSubjectsImportPreview'])->name('student-subjects.import-preview');
        Route::post('/student-subjects/import-apply', [MarksController::class, 'studentSubjectsImportApply'])->name('student-subjects.import-apply');

        // Bulk delete must come BEFORE the single {id} delete route
        Route::delete('/student-subjects/bulk-remove', [MarksController::class, 'studentSubjectsBulkDestroy'])->name('student-subjects.bulk-destroy');

        // Single delete
        Route::delete('/student-subjects/{id}', [MarksController::class, 'studentSubjectsDestroy'])->name('student-subjects.destroy');

        // Dynamic helper endpoints for dependent dropdowns / AJAX loading
        Route::get('/student-subjects/classes/{academicYearId}', [MarksController::class, 'getClassesByAcademicYear']);
        Route::get('/student-subjects/students/{classId}/{academicYearId}', [MarksController::class, 'getStudentsByClass'])->name('student-subjects.students');
        Route::get('/student-subjects/subjects/{classId}/{academicYearId}', [MarksController::class, 'getSubjectsByClass']);
        Route::get('/student-subjects/teachers/{classId}/{subjectId}/{academicYearId}', [MarksController::class, 'getTeachersBySubject'])->name('student-subjects.teachers');
        Route::get('/student-subjects/assignment-students/{classId}/{academicYearId}/{subjectId}/{teacherId}', [MarksController::class, 'getStudentsForSubjectTeacher'])->name('student-subjects.assignment-students');

        /*
        |--------------------------------------------------------------------------
        | Shared Academic Helpers
        |--------------------------------------------------------------------------
        */
        Route::get('/terms/by-academic-year/{academicYearId}', [MarksController::class, 'getTermsByAcademicYear'])->name('terms.by-academic-year');

        /*
        |--------------------------------------------------------------------------
        | Exam Summaries
        |--------------------------------------------------------------------------
        */
        Route::get('/exam-summaries', [ExamSummaryController::class, 'index'])->name('exam-summaries.index');
        Route::get('/exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])->name('exam-summaries.pdf');

        /*
        |--------------------------------------------------------------------------
        | Report Cards
        |--------------------------------------------------------------------------
        */
        Route::get('/reports', [ReportCardController::class, 'index'])->name('reports.index');
        Route::get('/reports/student/{student}', [ReportCardController::class, 'show'])->name('reports.show');
        Route::get('/reports/student/{student}/pdf', [ReportCardController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/bulk/pdf', [ReportCardController::class, 'bulkPdf'])->name('reports.bulk-pdf');

        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
