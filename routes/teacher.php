<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\MarksController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\PunctualityController;
use App\Http\Controllers\Teacher\BehaviourController;
use App\Http\Controllers\Teacher\HomeworkController;

Route::middleware(['auth', 'role:teacher,headmaster'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Marks routes
    Route::get('/marks', [MarksController::class, 'index'])->name('marks.index');
    Route::post('/marks/class-subjects', [MarksController::class, 'showClassSubjects'])->name('marks.class-subjects');
    Route::post('/marks/students', [MarksController::class, 'showStudents'])->name('marks.students');
    Route::post('/marks', [MarksController::class, 'store'])->name('marks.store');
    Route::post('/marks/import', [MarksController::class, 'import'])->name('marks.import');
    Route::get('/marks/{id}/edit', [MarksController::class, 'edit'])->name('marks.edit');
    Route::put('/marks/{id}', [MarksController::class, 'update'])->name('marks.update');
    Route::get('/marks/terms/{academicYearId}', [MarksController::class, 'loadTerms'])->name('marks.load-terms');

    // Homework routes
    Route::get('/homeworks', [HomeworkController::class, 'index'])->name('homeworks.index');
    Route::post('/homeworks', [HomeworkController::class, 'store'])->name('homeworks.store');
    Route::get('/homeworks/{homework}/marks', [HomeworkController::class, 'marks'])->name('homeworks.marks');
    Route::post('/homeworks/{homework}/marks', [HomeworkController::class, 'storeMarks'])->name('homeworks.store-marks');

    // Attendance routes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // Punctuality routes
    Route::get('/punctuality', [PunctualityController::class, 'index'])->name('punctuality.index');
    Route::post('/punctuality', [PunctualityController::class, 'store'])->name('punctuality.store');

    // Behaviour routes
    Route::get('/behaviour', [BehaviourController::class, 'index'])->name('behaviour.index');
    Route::post('/behaviour', [BehaviourController::class, 'store'])->name('behaviour.store');
});
