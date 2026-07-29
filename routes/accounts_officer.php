<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ExamSummaryController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\AccountsOfficer\DashboardController;
use App\Http\Controllers\AccountsOfficer\StudentFeesBlockController;
use App\Http\Controllers\AccountsOfficer\FeeImportController;

Route::middleware(['auth', 'role:accounts_officer'])
    ->prefix('accounts-officer')
    ->name('accounts-officer.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Student fee blocking
        |--------------------------------------------------------------------------
        */

        Route::get('/students', [StudentFeesBlockController::class, 'index'])
            ->name('students.index');

        Route::patch('/students/{student}/toggle-fees-block', [StudentFeesBlockController::class, 'toggle'])
            ->name('students.toggle-fees-block');

        Route::post('/students/bulk-fees-block', [StudentFeesBlockController::class, 'bulk'])
            ->name('students.bulk-fees-block');

        Route::post('/students/{student}/block', [StudentFeesBlockController::class, 'block'])
            ->name('students.block');

        Route::post('/students/{student}/unblock', [StudentFeesBlockController::class, 'unblock'])
            ->name('students.unblock');

        /*
        |--------------------------------------------------------------------------
        | Fee imports
        |--------------------------------------------------------------------------
        */

        Route::get('/fees', [FeeImportController::class, 'index'])
            ->name('fees.index');

        Route::get('/fees/import', [FeeImportController::class, 'create'])
            ->name('fees.import');

        Route::post('/fees/import', [FeeImportController::class, 'preview'])
            ->name('fees.import.store');

        Route::get('/fees/imports/{batch}/preview', [FeeImportController::class, 'showPreview'])
            ->name('fees.preview');

        Route::post('/fees/imports/{batch}/confirm', [FeeImportController::class, 'confirm'])
            ->name('fees.imports.confirm');

        Route::get('/fees/imports/{batch}', [FeeImportController::class, 'show'])
            ->name('fees.imports.show');

        Route::patch('/fees/import-rows/{row}/manual-match', [FeeImportController::class, 'manualMatch'])
            ->name('fees.import-rows.manual-match');

        /*
        |--------------------------------------------------------------------------
        | Result summaries and report cards
        |--------------------------------------------------------------------------
        */

        Route::get('/exam-summaries', [ExamSummaryController::class, 'index'])
            ->name('exam-summaries.index');
        Route::get('/exam-summaries/preview', [ExamSummaryController::class, 'preview'])
            ->name('exam-summaries.preview');
        Route::get('/exam-summaries/pdf', [ExamSummaryController::class, 'pdf'])
            ->name('exam-summaries.pdf');

        Route::get('/reports', [ReportCardController::class, 'index'])
            ->name('reports.index');
        Route::get('/reports/student/{student}', [ReportCardController::class, 'show'])
            ->name('reports.show');
        Route::get('/reports/student/{student}/pdf', [ReportCardController::class, 'pdf'])
            ->name('reports.pdf');
        Route::get('/reports/bulk/pdf', [ReportCardController::class, 'bulkPdf'])
            ->name('reports.bulk-pdf');
    });
