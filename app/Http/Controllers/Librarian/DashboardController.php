<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\LibraryBorrowing;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'books' => Book::count(),
            'copies' => BookCopy::count(),
            'availableCopies' => BookCopy::where('is_available', true)->count(),
            'borrowedCopies' => BookCopy::where('status', 'borrowed')->count(),
            'overdueBorrowings' => LibraryBorrowing::where('status', 'overdue')->count(),
        ];

        $recentBorrowings = LibraryBorrowing::with([
            'bookCopy.book',
            'student.user',
            'teacher.user',
        ])
            ->latest()
            ->take(10)
            ->get();

        return view('librarian.dashboard', compact('stats', 'recentBorrowings'));
    }
}
