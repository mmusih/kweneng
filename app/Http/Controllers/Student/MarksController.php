<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Mark;
use App\Models\StudentSubject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    public function index()
    {
        // More robust way to get student
        $user = Auth::user();
        
        if (!$user || !$user->student) {
            return redirect()->route('student.dashboard')->withErrors(['error' => 'Student record not found.']);
        }
        
        $student = $user->student;
        
        // Get academic years where student has marks
        $academicYears = AcademicYear::whereHas('terms.marks', function($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with('terms')->get();
        
        return view('student.marks.index', compact('academicYears'));
    }
    
    public function show($academicYearId, $termId)
    {
        // More robust way to get student
        $user = Auth::user();
        
        if (!$user || !$user->student) {
            return redirect()->route('student.dashboard')->withErrors(['error' => 'Student record not found.']);
        }
        
        $student = $user->student;
        
        // Validate that student has marks for this term
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $term = Term::findOrFail($termId);
        
        // Get student's subjects for this term
        $studentSubjects = StudentSubject::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->with('subject')
            ->get();
        
        // Get marks for this term
        $marks = Mark::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->with('subject', 'teacher.user')
            ->get()
            ->keyBy('subject_id');
        
        // Calculate averages
        $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
        $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);
        
        $averages = [
            'midterm' => $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null,
            'endterm' => $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null
        ];
        
        return view('student.marks.show', compact('academicYear', 'term', 'studentSubjects', 'marks', 'averages'));
    }
}
