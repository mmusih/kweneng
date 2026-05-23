<?php

/**
 * Add these routes inside your existing admin.php route file,
 * within the auth + role middleware group for admins.
 *
 * Example placement:
 *
 *   Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
 *       // ... your existing admin routes ...
 *
 *       // Login slip routes (paste below)
 *       Route::get('students/{student}/slip', [LoginSlipController::class, 'show'])->name('students.slip');
 *       Route::post('students/slips/bulk', [LoginSlipController::class, 'bulk'])->name('students.slips.bulk');
 *   });
 */

use App\Http\Controllers\Admin\LoginSlipController;

// Single student slip (GET ?regenerate=1 to force-regenerate)
Route::get('students/{student}/slip', [LoginSlipController::class, 'show'])
    ->name('students.slip');

// Bulk slip generation (POST with student_ids[])
Route::post('students/slips/bulk', [LoginSlipController::class, 'bulk'])
    ->name('students.slips.bulk');
