<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\MarksController;

Route::middleware(['auth', 'role:teacher,headmaster'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Marks routes
    Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
    Route::post('/marks/class-subjects', [MarksController::class, 'showClassSubjects'])->name('marks.class-subjects');
    Route::post('/marks/students', [MarksController::class, 'showStudents'])->name('marks.students');
    Route::post('/marks', [MarksController::class, 'store'])->name('marks.store');
    Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
    Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
    Route::get('/marks/terms/{academicYearId}', [MarksController::class, 'loadTerms'])->name('marks.load-terms');
});