<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Headmaster\DashboardController;
use App\Http\Controllers\Headmaster\CommentController;

Route::middleware(['auth', 'role:headmaster'])->prefix('headmaster')->name('headmaster.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
});