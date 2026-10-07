<?php

use App\Http\Controllers\Admin\SubjectOptionPlanController;
use App\Http\Middleware\ActiveErpUser;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin', ActiveErpUser::class])->prefix('admin/subject-option-plans')->name('admin.subject-option-plans.')->group(function () {
    Route::get('/', [SubjectOptionPlanController::class, 'index'])->name('index');
    Route::post('/', [SubjectOptionPlanController::class, 'generate'])->name('generate');
    Route::get('/{plan}', [SubjectOptionPlanController::class, 'show'])->name('show');
    Route::put('/{plan}', [SubjectOptionPlanController::class, 'update'])->name('update');
});
