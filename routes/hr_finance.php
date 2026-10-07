<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Hr\StaffController;
use App\Http\Controllers\Parent\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', \App\Http\Middleware\ManageHr::class])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Hr\DashboardController::class, 'index'])->name('dashboard');
    Route::get('document-types', [StaffController::class, 'types'])->name('types');
    Route::post('document-types', [StaffController::class, 'saveType'])->name('types.store');
    Route::put('document-types/{type}', [StaffController::class, 'updateType'])->name('types.update');
    Route::resource('staff', StaffController::class)->except('destroy');
    Route::post('staff/{staff}/requirements', [StaffController::class, 'requirement'])->name('requirements.store');
    Route::put('requirements/{requirement}', [StaffController::class, 'renewal'])->name('requirements.update');
    Route::post('requirements/{requirement}/documents', [StaffController::class, 'upload'])->name('documents.store');
    Route::get('documents/{document}/download', [StaffController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/verify', [StaffController::class, 'verify'])->name('documents.verify');
});
Route::middleware(['auth', 'role:admin,accounts_officer', \App\Http\Middleware\ActiveErpUser::class])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Finance\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
    Route::post('payments/{payment}/email', [PaymentController::class, 'email'])->middleware('throttle:10,1')->name('payments.email');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'download'])->name('payments.download');
    Route::post('categories', [PaymentController::class, 'category'])->name('categories.store');
    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('accounts/{account}/charges', [AccountController::class, 'charge'])->name('accounts.charge');
});
Route::middleware(['auth', 'role:parent', \App\Http\Middleware\ActiveErpUser::class])->prefix('parent')->name('parent.')->group(function () {
    Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/{payment}/download', [ReceiptController::class, 'download'])->name('receipts.download');
});
