<?php

use App\Http\Controllers\Inventory\DashboardController;
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\RequisitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,office,inventory'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [DashboardController::class, 'index'])->name('home');

        Route::resource('items', InventoryItemController::class)->parameters([
            'items' => 'item',
        ]);

        Route::get('requisitions/csv', [RequisitionController::class, 'csv'])->name('requisitions.csv');
        Route::get('requisitions', [RequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('requisitions/{requisition}', [RequisitionController::class, 'show'])->name('requisitions.show');
        Route::patch('requisitions/{requisition}', [RequisitionController::class, 'update'])->name('requisitions.update');
    });
