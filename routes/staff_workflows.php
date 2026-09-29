<?php

use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\PurchasingController;
use App\Http\Controllers\Hr\LeaveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', \App\Http\Middleware\StaffSelfService::class])->prefix('staff')->name('staff.')->group(function () {
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('leave/{leave}', [LeaveController::class, 'show'])->name('leave.show');
    Route::get('leave/{leave}/document', [LeaveController::class, 'document'])->name('leave.document');
    Route::post('leave/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/{claim}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('expenses/{claim}/document', [ExpenseController::class, 'document'])->name('expenses.document');
    Route::post('expenses/{claim}/cancel', [ExpenseController::class, 'cancel'])->name('expenses.cancel');
});
Route::middleware(['auth', \App\Http\Middleware\ManageHr::class])->prefix('hr/leave')->name('hr.leave.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('index');
    Route::get('settings', [LeaveController::class, 'settings'])->name('settings');
    Route::post('types', [LeaveController::class, 'type'])->name('types.store');
    Route::put('types/{type}', [LeaveController::class, 'type'])->name('types.update');
    Route::post('allowances', [LeaveController::class, 'allowance'])->name('allowances');
    Route::post('holidays', [LeaveController::class, 'holiday'])->name('holidays');
    Route::delete('holidays/{holiday}', [LeaveController::class, 'removeHoliday'])->name('holidays.remove');
    Route::post('{leave}/review', [LeaveController::class, 'review'])->name('review');
});
Route::middleware(['auth', \App\Http\Middleware\ActiveErpUser::class, 'role:admin,accounts_officer,headmaster'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses/{claim}/review', [ExpenseController::class, 'review'])->name('expenses.review');
    Route::post('expenses/{claim}/pay', [ExpenseController::class, 'pay'])->name('expenses.pay');
});
Route::middleware(['auth', \App\Http\Middleware\ActiveErpUser::class, 'role:admin,accounts_officer,headmaster,office,inventory'])->prefix('finance/purchasing')->name('finance.purchasing.')->group(function () {
    Route::get('/', [PurchasingController::class, 'index'])->name('index');
    Route::get('suppliers', [PurchasingController::class, 'suppliers'])->name('suppliers');
    Route::post('suppliers', [PurchasingController::class, 'saveSupplier'])->name('suppliers.store');
    Route::put('suppliers/{supplier}', [PurchasingController::class, 'saveSupplier'])->name('suppliers.update');
    Route::get('requisitions/{requisition}/create', [PurchasingController::class, 'create'])->name('create');
    Route::post('requisitions/{requisition}', [PurchasingController::class, 'store'])->name('store');
    Route::get('{order}', [PurchasingController::class, 'show'])->name('show');
    Route::get('{order}/pdf', [PurchasingController::class, 'pdf'])->name('pdf');
    Route::post('{order}/review', [PurchasingController::class, 'review'])->name('review');
    Route::post('{order}/receive', [PurchasingController::class, 'receive'])->name('receive');
    Route::post('{order}/invoice', [PurchasingController::class, 'invoice'])->name('invoice');
    Route::get('{order}/invoice', [PurchasingController::class, 'downloadInvoice'])->name('invoice.download');
    Route::post('{order}/payments', [PurchasingController::class, 'pay'])->name('pay');
});
