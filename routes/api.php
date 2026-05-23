<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentDashboardController;
use App\Http\Controllers\Api\ParentEventsController;
use App\Http\Controllers\Api\ParentMarksController;
use App\Http\Controllers\Api\ParentLibraryController;

/*
|--------------------------------------------------------------------------
| Public routes (no auth required)
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

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

        // Events
        Route::get('/events', [ParentEventsController::class, 'index']);
        Route::get('/events/calendar', [ParentEventsController::class, 'calendar']);
        Route::get('/events/{event}', [ParentEventsController::class, 'show']);

        // Announcements
        Route::get('/announcements', [ParentEventsController::class, 'announcements']);
        Route::get('/announcements/{announcement}', [ParentEventsController::class, 'showAnnouncement']);

        // Marks
        Route::get('/marks', [ParentMarksController::class, 'index']);
        Route::get('/marks/{student}/{academicYearId}/{termId}', [ParentMarksController::class, 'show']);

        // Library
        Route::get('/library', [ParentLibraryController::class, 'index']);
        Route::get('/library/history/{studentId}', [ParentLibraryController::class, 'history']);
    });
});
