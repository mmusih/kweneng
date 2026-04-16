<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Librarian\DashboardController;
use App\Http\Controllers\Librarian\BookController;
use App\Http\Controllers\Librarian\BorrowingController;

Route::middleware(['auth', 'role:librarian'])->prefix('librarian')->name('librarian.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Books
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::post('/books/{book}/copies', [BookController::class, 'storeCopy'])->name('books.copies.store');

    // ISBN lookup
    Route::get('/books/lookup/isbn', [BookController::class, 'lookupIsbn'])->name('books.lookup-isbn');

    // Borrowings
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/create', [BorrowingController::class, 'create'])->name('borrowings.create');
    Route::post('/borrowings/issue', [BorrowingController::class, 'issue'])->name('borrowings.issue');
    Route::post('/borrowings/return', [BorrowingController::class, 'returnBook'])->name('borrowings.return');
    Route::post('/borrowings/mark-overdue', [BorrowingController::class, 'markOverdue'])->name('borrowings.mark-overdue');

    // AJAX helpers
    Route::get('/borrowings/classes/{class}/students', [BorrowingController::class, 'studentsByClass'])
        ->name('borrowings.students-by-class');

    Route::get('/borrowings/search-teachers', [BorrowingController::class, 'searchTeachers'])
        ->name('borrowings.search-teachers');

    Route::get('/borrowings/lookup-book-copy', [BorrowingController::class, 'lookupBookCopy'])
        ->name('borrowings.lookup-book-copy');
});
