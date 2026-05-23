<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ParentRegistrationController;

// -------------------------------------------------------------------------
// Guest routes
// -------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    // Standard login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Parent self-registration (uses invite code from child's login slip)
    Route::get('parent/register', [ParentRegistrationController::class, 'create'])
        ->name('parent.register');
    Route::post('parent/register', [ParentRegistrationController::class, 'store']);
});

// -------------------------------------------------------------------------
// Authenticated routes
// -------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
