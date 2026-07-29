<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\ParentRegistrationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------------------
// Guest routes
// -------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    // Standard login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Forgotten password
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');

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
