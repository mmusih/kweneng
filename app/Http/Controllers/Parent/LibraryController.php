<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\LibraryBorrowing;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    public function index()
    {
        $parent = Auth::user()?->parent;

        abort_unless($parent, 404, 'Parent record not found.');

        $studentIds = $parent->students()->pluck('students.id');

        $borrowings = LibraryBorrowing::with([
            'bookCopy.book',
            'student.user',
        ])
            ->whereIn('student_id', $studentIds)
            ->latest('issued_at')
            ->latest('id')
            ->paginate(30);

        return view('parent.library.index', compact('borrowings'));
    }
}
