<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AccountsOfficerController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExamSummaryController;
use App\Http\Controllers\Admin\LibrarianController;
use App\Http\Controllers\Admin\LoginSlipController;
use App\Http\Controllers\Admin\MarksController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ParentAbsenceNoticeController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Admin\SchoolDocumentController;
use App\Http\Controllers\Admin\StudentAcademicRecordController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudyRetentionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Admin\Timetable\GridController;
use App\Http\Controllers\Admin\Timetable\PeriodController as TimetablePeriodController;
use App\Http\Controllers\Admin\Timetable\PrintController as TimetablePrintController;
use App\Http\Controllers\Admin\Timetable\RoomController as TimetableRoomGridController;
use App\Http\Controllers\Admin\Timetable\SettingController as TimetableSettingController;
use App\Http\Controllers\Admin\Timetable\TeacherLoadController as TimetableTeacherLoadController;
use App\Http\Controllers\Admin\Timetable\TimetableDivisionGridController;
use App\Http\Controllers\Admin\Timetable\VerificationController as TimetableVerificationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\PrefectController;
use App\Http\Controllers\Hod\SchemeDashboardController as SchemeOversightController;
use Illuminate\Support\Facades\Route;

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

        Route::put('students/{student}/photo', [StudentController::class, 'updatePhoto'])
            ->name('students.photo.update');

        Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->name('students.reset-password');

        Route::get('students/{student}/slip', [LoginSlipController::class, 'show'])
            ->name('students.slip');
        Route::get('students/{student}/academic-record', [StudentAcademicRecordController::class, 'show'])->name('students.academic-record.show');
        Route::get('students/{student}/academic-record/download', [StudentAcademicRecordController::class, 'download'])->name('students.academic-record.download');
        Route::post('students/slips/bulk', [LoginSlipController::class, 'bulk'])
            ->name('students.slips.bulk');

        /*
        |--------------------------------------------------------------------------
        | Core Resources
        |--------------------------------------------------------------------------
        */
        Route::resource('students', StudentController::class);
        Route::resource('classes', ClassController::class)->except(['show']);
        Route::resource('teachers', TeacherController::class)->except(['show', 'destroy']);
        Route::resource('parents', ParentController::class)->except(['show']);
        Route::resource('librarians', LibrarianController::class)->except(['show']);
        Route::resource('accounts-officers', AccountsOfficerController::class)->except(['show']);

        Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
        Route::post('departments/{department}/assign', [DepartmentController::class, 'assign'])->name('departments.assign');
        Route::delete('departments/{department}/assignments/{assignment}', [DepartmentController::class, 'removeAssignment'])->name('departments.assignments.destroy');

        Route::get('schemes', [SchemeOversightController::class, 'index'])->name('schemes.index');
        Route::get('schemes/{scheme}', [SchemeOversightController::class, 'show'])->name('schemes.show');
        Route::patch('schemes/{scheme}/approve', [SchemeOversightController::class, 'approve'])->name('schemes.approve');
        Route::patch('schemes/{scheme}/request-changes', [SchemeOversightController::class, 'requestChanges'])->name('schemes.request-changes');

        /*
        |--------------------------------------------------------------------------
        | Timetable
        |--------------------------------------------------------------------------
        */
        // One timetable editor; old bookmarks open the current grid.
        Route::get('timetable', [GridController::class, 'index'])->name('timetable.index');
        Route::redirect('timetable/legacy', '/admin/timetable');
        Route::get('timetable/grid', [GridController::class, 'index'])->name('timetable.grid');
        Route::get('timetable/settings/{setting}/prepare', [\App\Http\Controllers\Admin\Timetable\PreparationController::class, 'index'])->name('timetable.prepare');
        Route::post('timetable/settings/{setting}/prepare', [\App\Http\Controllers\Admin\Timetable\PreparationController::class, 'store'])->name('timetable.prepare.store');
        Route::post('timetable/settings/{setting}/publish', [TimetableSettingController::class, 'publish'])->name('timetable.settings.publish');
        Route::post('timetable/settings', [TimetableSettingController::class, 'store'])->name('timetable.settings.store');
        Route::get('timetable/teacher-loads', [TimetableTeacherLoadController::class, 'index'])->name('timetable.teacher-loads');
        Route::get('timetable/teacher-loads/download', [TimetableTeacherLoadController::class, 'download'])->name('timetable.teacher-loads.download');
        Route::get('timetable/teaching-summary', [TimetableTeacherLoadController::class, 'teachingSummary'])->name('timetable.teaching-summary');
        Route::get('timetable/teaching-summary/download', [TimetableTeacherLoadController::class, 'teachingSummaryDownload'])->name('timetable.teaching-summary.download');
        Route::get('timetable/teaching-summary/csv', [TimetableTeacherLoadController::class, 'csv'])->name('timetable.teaching-summary.csv');
        Route::get('timetable/settings/{setting}/verification', TimetableVerificationController::class)->name('timetable.settings.verification');
        Route::get('timetable/settings/{setting}/print/{type}/{id}', TimetablePrintController::class)->name('timetable.settings.print');
        Route::get('timetable/grid/candidates', [GridController::class, 'candidates'])->name('timetable.grid.candidates');
        Route::post('timetable/grid/move', [GridController::class, 'move'])->name('timetable.grid.move');
        Route::post('timetable/grid/card-attendance', [GridController::class, 'changeCardAttendance'])->name('timetable.grid.card-attendance');
        Route::post('timetable/grid/card-room', [GridController::class, 'changeRoom'])->name('timetable.grid.card-room');
        Route::post('timetable/grid/unplace', [GridController::class, 'unplace'])->name('timetable.grid.unplace');
        Route::put('timetable/grid/required-count', [GridController::class, 'updateRequiredCount'])->name('timetable.grid.required-count');
        Route::post('timetable/grid/lock', [GridController::class, 'lock'])->name('timetable.grid.lock');
        Route::post('timetable/grid/lessons', [GridController::class, 'storeLesson'])->name('timetable.grid.lesson.store');
        Route::post('timetable/grid/lesson', [GridController::class, 'updateLesson'])->name('timetable.grid.lesson.update');
        Route::post('timetable/grid/lesson/duplicate', [GridController::class, 'duplicateLesson'])->name('timetable.grid.lesson.duplicate');
        Route::delete('timetable/grid/lesson', [GridController::class, 'destroyLesson'])->name('timetable.grid.lesson.destroy');
        Route::post('timetable/grid/divisions', [TimetableDivisionGridController::class, 'store'])->name('timetable.grid.divisions.store');
        Route::put('timetable/grid/divisions/{division}', [TimetableDivisionGridController::class, 'update'])->name('timetable.grid.divisions.update');
        Route::delete('timetable/grid/divisions/{division}', [TimetableDivisionGridController::class, 'destroy'])->name('timetable.grid.divisions.destroy');
        Route::post('timetable/grid/rooms', [TimetableRoomGridController::class, 'store'])->name('timetable.grid.rooms.store');
        Route::put('timetable/grid/rooms/{room}', [TimetableRoomGridController::class, 'update'])->name('timetable.grid.rooms.update');
        Route::delete('timetable/grid/rooms/{room}', [TimetableRoomGridController::class, 'destroy'])->name('timetable.grid.rooms.destroy');
        Route::put('timetable/grid/base-rooms', [TimetableRoomGridController::class, 'updateBaseRooms'])->name('timetable.grid.base-rooms.update');
        Route::post('timetable/day-structure', [TimetablePeriodController::class, 'update'])->name('timetable.day-structure');

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
        Route::resource('academic-years', AcademicYearController::class)->except(['show']);
        Route::post('academic-years/{academicYear}/close', [AcademicYearController::class, 'close'])->name('academic-years.close');
        Route::post('academic-years/{academicYear}/lock', [AcademicYearController::class, 'lock'])->name('academic-years.lock');

        /*
        |--------------------------------------------------------------------------
        | Terms
        |--------------------------------------------------------------------------
        */
        Route::resource('terms', TermController::class)->except(['show']);
        Route::get('study-retention', [StudyRetentionController::class, 'index'])->name('study-retention.index');
        Route::post('study-retention', [StudyRetentionController::class, 'store'])->name('study-retention.store');
        Route::post('study-retention/selection', [StudyRetentionController::class, 'selection'])->name('study-retention.selection');
        Route::post('study-retention/enrolments', [StudyRetentionController::class, 'enrol'])->name('study-retention.enrol');
        Route::delete('study-retention/enrolments/{studyEnrolment}', [StudyRetentionController::class, 'unenrol'])->name('study-retention.unenrol');
        Route::get('study-retention/print', [StudyRetentionController::class, 'print'])->name('study-retention.print');
        Route::delete('study-retention/{studyRule}', [StudyRetentionController::class, 'destroy'])->name('study-retention.destroy');
        Route::post('terms/{term}/finalize', [TermController::class, 'finalize'])->name('terms.finalize');
        Route::post('terms/{term}/lock', [TermController::class, 'lock'])->name('terms.lock');
        Route::post('terms/{term}/unlock', [TermController::class, 'unlock'])->name('terms.unlock');
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
        Route::get('subjects/manage/classes', [SubjectController::class, 'manageClassAssignments'])->name('subjects.manage-classes');
        Route::post('subjects/manage/classes/bulk-save', [SubjectController::class, 'bulkSaveClassAssignments'])->name('subjects.bulk-save-classes');
        Route::get('subjects/manage/teachers', [SubjectController::class, 'manageTeacherAssignments'])->name('subjects.manage-teachers');
        Route::post('subjects/manage/teachers/bulk-save', [SubjectController::class, 'bulkSaveTeacherAssignments'])->name('subjects.bulk-save-teachers');
        Route::delete('subjects/remove/teacher', [SubjectController::class, 'removeTeacherFromSubject'])->name('subjects.remove-teacher');

        Route::resource('subjects', SubjectController::class)->except(['show']);

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
        Route::get('/marks/student-averages', [MarksController::class, 'getStudentAverages'])->name('marks.student-averages');
        Route::post('/marks/import-preview', [MarksController::class, 'importPreview'])->name('marks.import-preview');
        Route::post('/marks/import-apply', [MarksController::class, 'importApply'])->name('marks.import-apply');

        Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
        Route::get('/marks/group/edit', [MarksController::class, 'editGroup'])->name('marks.group.edit');
        Route::put('/marks/group', [MarksController::class, 'updateGroup'])->name('marks.group.update');
        Route::get('/marks/{id}', [MarksController::class, 'show'])->name('marks.show');
        Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
        Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
        Route::delete('/marks/{id}', [MarksController::class, 'destroy'])->name('marks.destroy');

        /*
        |--------------------------------------------------------------------------
        | Student Subject Assignments
        |--------------------------------------------------------------------------
        */
        Route::get('/student-subjects', [MarksController::class, 'studentSubjectsIndex'])->name('student-subjects.index');
        Route::get('/student-subjects/create', [MarksController::class, 'studentSubjectsCreate'])->name('student-subjects.create');
        Route::post('/student-subjects', [MarksController::class, 'studentSubjectsStore'])->name('student-subjects.store');

        Route::post('/student-subjects/import-preview', [MarksController::class, 'studentSubjectsImportPreview'])->name('student-subjects.import-preview');
        Route::post('/student-subjects/import-apply', [MarksController::class, 'studentSubjectsImportApply'])->name('student-subjects.import-apply');

        Route::delete('/student-subjects/bulk-remove', [MarksController::class, 'studentSubjectsBulkDestroy'])->name('student-subjects.bulk-destroy');
        Route::delete('/student-subjects/{id}', [MarksController::class, 'studentSubjectsDestroy'])->name('student-subjects.destroy');

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
        Route::get('/exam-summaries/preview', [ExamSummaryController::class, 'preview'])->name('exam-summaries.preview');
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

        /*
        |--------------------------------------------------------------------------
        | Login Details
        |--------------------------------------------------------------------------
        */
        Route::post('students/print-logins', [StudentController::class, 'printLogins'])
            ->name('students.print-logins');

        Route::post('parents/{parent}/reset-password', [ParentController::class, 'resetPassword'])
            ->name('parents.reset-password');

        /*
        |--------------------------------------------------------------------------
        | Events
        |--------------------------------------------------------------------------
        */
        Route::get('events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
        Route::get('events/get-events', [EventController::class, 'getEvents'])->name('events.get-events');
        Route::delete('events/comments/{comment}', [EventController::class, 'deleteComment'])->name('events.delete-comment');

        Route::resource('events', EventController::class);

        Route::post('events/{event}/comments', [EventController::class, 'addComment'])->name('events.add-comment');

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */
        Route::resource('announcements', AnnouncementController::class);

        /*
        |--------------------------------------------------------------------------
        | Parent Messages (inbox)
        |--------------------------------------------------------------------------
        */
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');

        /*
        |--------------------------------------------------------------------------
        | School Documents
        |--------------------------------------------------------------------------
        */
        Route::get('documents', [SchoolDocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [SchoolDocumentController::class, 'store'])->name('documents.store');
        Route::patch('documents/{document}/toggle', [SchoolDocumentController::class, 'toggleActive'])->name('documents.toggle');
        Route::delete('documents/{document}', [SchoolDocumentController::class, 'destroy'])->name('documents.destroy');

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */

        Route::get('/announcements/{announcement}/tracking', [AnnouncementController::class, 'tracking'])
            ->name('announcements.tracking');
        Route::get('/announcements/{announcement}/tracking/export', [AnnouncementController::class, 'exportTrackingCsv'])
            ->name('announcements.tracking.export');

        Route::post('/announcements/{announcement}/tracking/reminder', [AnnouncementController::class, 'sendTrackingReminder'])
            ->name('announcements.tracking.reminder');

        /*
        |--------------------------------------------------------------------------
        | Absense Notices
        |--------------------------------------------------------------------------
        */
        Route::get('/absence-notices', [ParentAbsenceNoticeController::class, 'index'])
            ->name('absence-notices.index');

        Route::get('/absence-notices/{absenceNotice}', [ParentAbsenceNoticeController::class, 'show'])
            ->name('absence-notices.show');

        Route::patch('/absence-notices/{absenceNotice}/seen', [ParentAbsenceNoticeController::class, 'markSeen'])
            ->name('absence-notices.seen');

        Route::patch('/absence-notices/{absenceNotice}/resolved', [ParentAbsenceNoticeController::class, 'markResolved'])
            ->name('absence-notices.resolved');
    });
