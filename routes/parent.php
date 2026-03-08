<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\MarksController;

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    // Parent dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Children marks routes
    Route::get('/children/marks', [MarksController::class, 'index'])->name('children.marks.index');
    Route::get('/children/{studentId}/marks/{academicYearId}/{termId}', [MarksController::class, 'show'])->name('children.marks.show');
});
