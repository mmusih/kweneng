<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentAbsenceNoticeController;
use App\Http\Controllers\Api\ParentDashboardController;
use App\Http\Controllers\Api\ParentDeviceTokenController;
use App\Http\Controllers\Api\ParentDocumentsController;
use App\Http\Controllers\Api\ParentEventsController;
use App\Http\Controllers\Api\ParentFeesController;
use App\Http\Controllers\Api\ParentHomeworkController;
use App\Http\Controllers\Api\ParentLibraryController;
use App\Http\Controllers\Api\ParentMarksController;
use App\Http\Controllers\Api\ParentMessagesController;
use App\Http\Controllers\Api\ParentRegistrationApiController;
use App\Http\Controllers\Api\ParentReportCardController;
use App\Http\Controllers\Api\ParentStudentProfileController;
use App\Http\Controllers\Api\PasswordResetLinkController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\TeacherDashboardController;
use App\Http\Controllers\Api\TeacherHomeworkController;
use App\Http\Controllers\Api\TeacherMarksController;
use App\Http\Controllers\Api\TeacherSchemeController;
use App\Http\Controllers\Api\TimetableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (no auth required)
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/teacher-login', [AuthController::class, 'teacherLogin']);
Route::post('/auth/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('api.password.email');
Route::post('/parent-register/verify-code', [ParentRegistrationApiController::class, 'verifyCode']);
Route::post('/parent-register/complete', [ParentRegistrationApiController::class, 'complete']);

/*
|--------------------------------------------------------------------------
| Authenticated routes (Sanctum token required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Parent-only routes
    Route::middleware('role:parent')->prefix('parent')->group(function () {

        // Dashboard
        Route::get('/dashboard', [ParentDashboardController::class, 'index']);
        Route::get('/timetable', [TimetableController::class, 'parent']);

        // Parent absence notices
        Route::get('/absence-notices', [ParentAbsenceNoticeController::class, 'index']);
        Route::post('/absence-notices', [ParentAbsenceNoticeController::class, 'store']);

        // Fees
        Route::get('/fees', [ParentFeesController::class, 'index']);

        // Homework
        Route::get('/homework', [ParentHomeworkController::class, 'index']);
        Route::get('/homework/{homework}/attachment', [ParentHomeworkController::class, 'downloadAttachment'])
            ->name('api.parent.homework.attachment');

        // Student profile and emergency contacts
        Route::put('/children/{student}/profile', [ParentStudentProfileController::class, 'update']);

        // Device token / push notifications
        Route::post('/device-token', [ParentDeviceTokenController::class, 'store']);
        Route::post('/device-token/test', [ParentDeviceTokenController::class, 'test']);

        // Events
        Route::get('/events', [ParentEventsController::class, 'index']);
        Route::get('/events/calendar', [ParentEventsController::class, 'calendar']);
        Route::get('/events/{event}', [ParentEventsController::class, 'show']);

        // Announcements
        Route::get('/announcements', [ParentEventsController::class, 'announcements']);
        Route::post('/announcements/{announcement}/read', [ParentEventsController::class, 'markAnnouncementRead']);
        Route::post('/announcements/{announcement}/dismiss', [ParentEventsController::class, 'dismissAnnouncement']);
        Route::post('/announcements/{announcement}/acknowledge', [ParentEventsController::class, 'acknowledgeAnnouncement']);
        Route::get('/announcements/{announcement}', [ParentEventsController::class, 'showAnnouncement']);

        // Marks
        Route::get('/marks', [ParentMarksController::class, 'index']);
        Route::get('/marks/{student}/{academicYearId}/{termId}', [ParentMarksController::class, 'show']);

        // Report Cards
        Route::get('/report-card/{studentId}/{termId}', [ParentReportCardController::class, 'download']);

        // Library
        Route::get('/library', [ParentLibraryController::class, 'index']);
        Route::get('/library/history/{studentId}', [ParentLibraryController::class, 'history']);

        // Messages
        Route::get('/messages', [ParentMessagesController::class, 'index']);
        Route::get('/messages/{id}', [ParentMessagesController::class, 'show']);
        Route::post('/messages', [ParentMessagesController::class, 'store']);
        Route::post('/messages/{id}/reply', [ParentMessagesController::class, 'reply']);

        // Documents
        Route::get('/documents', [ParentDocumentsController::class, 'index']);
        Route::get('/documents/{id}/download', [ParentDocumentsController::class, 'download']);
    });

    // Dedicated teacher mobile application
    Route::middleware('role:teacher,headmaster')->prefix('teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
        Route::get('/timetable', [TimetableController::class, 'teacher']);

        Route::get('/attendance/register', [TeacherAttendanceController::class, 'show']);
        Route::post('/attendance/register', [TeacherAttendanceController::class, 'store']);

        Route::get('/marks/sheet', [TeacherMarksController::class, 'show']);
        Route::post('/marks/sheet', [TeacherMarksController::class, 'store']);

        Route::get('/homeworks', [TeacherHomeworkController::class, 'index']);
        Route::post('/homeworks', [TeacherHomeworkController::class, 'store']);
        Route::get('/homeworks/{homework}/image', [TeacherHomeworkController::class, 'image'])
            ->name('api.teacher.homeworks.image');
        Route::delete('/homeworks/{homework}', [TeacherHomeworkController::class, 'destroy']);

        Route::get('/schemes', [TeacherSchemeController::class, 'index']);
        Route::get('/schemes/{scheme}', [TeacherSchemeController::class, 'show']);
        Route::patch('/scheme-items/{item}', [TeacherSchemeController::class, 'updateItem']);
        Route::patch('/scheme-subtopics/{subtopic}/toggle', [TeacherSchemeController::class, 'toggleSubtopic']);
    });

    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/timetable', [TimetableController::class, 'student']);
    });
});
