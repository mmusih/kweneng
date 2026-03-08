<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Mark;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'parent') {
            return redirect()->route('login')->withErrors(['error' => 'Unauthorized access']);
        }
        
        // Get parent's children
        $children = $user->parent->students ?? collect();
        
        if ($children->isEmpty()) {
            return view('parent.marks.index', compact('children'))->with('warning', 'No children linked to your account.');
        }
        
        // Get distinct academic years where any child has marks
        $studentIds = $children->pluck('id')->toArray();
        
        $academicYears = AcademicYear::whereHas('terms.marks', function($query) use ($studentIds) {
            $query->whereIn('student_id', $studentIds);
        })->with('terms')->get();
        
        return view('parent.marks.index', compact('children', 'academicYears'));
    }
    
    public function show($studentId, $academicYearId, $termId)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'parent') {
            return redirect()->route('login')->withErrors(['error' => 'Unauthorized access']);
        }
        
        // Verify the child belongs to this parent
        $children = $user->parent->students ?? collect();
        $child = $children->firstWhere('id', $studentId);
        
        if (!$child) {
            return redirect()->route('parent.children.marks.index')
                ->withErrors(['error' => 'Child not found or not linked to your account.']);
        }
        
        // Validate academic year and term
        $academicYear = AcademicYear::findOrFail($academicYearId);
        $term = Term::findOrFail($termId);
        
        // Get child's subjects for this term
        $studentSubjects = StudentSubject::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->with('subject')
            ->get();
        
        // Get marks for this term
        $marks = Mark::where('student_id', $studentId)
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
        
        return view('parent.marks.show', compact('child', 'academicYear', 'term', 'studentSubjects', 'marks', 'averages'));
    }
}
