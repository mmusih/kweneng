<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LibraryBorrowing;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    public function index()
    {
        $student = Auth::user()?->student;

        abort_unless($student, 404, 'Student record not found.');

        $borrowings = LibraryBorrowing::with(['bookCopy.book'])
            ->where('student_id', $student->id)
            ->latest('issued_at')
            ->latest('id')
            ->paginate(20);

        return view('student.library.index', compact('borrowings'));
    }
}
