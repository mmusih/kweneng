<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\RegisterOfficer\DashboardController;
use App\Http\Controllers\RegisterOfficer\RegisterMonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,register_officer'])
    ->prefix('register-officer')
    ->name('register-officer.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/registers', [RegisterMonitorController::class, 'index'])->name('registers.index');
        Route::get('/registers/csv', [RegisterMonitorController::class, 'csv'])->name('registers.csv');

        Route::get('events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
        Route::get('events/get-events', [EventController::class, 'getEvents'])->name('events.get-events');
        Route::delete('events/comments/{comment}', [EventController::class, 'deleteComment'])->name('events.delete-comment');
        Route::resource('events', EventController::class);
        Route::post('events/{event}/comments', [EventController::class, 'addComment'])->name('events.add-comment');
    });
