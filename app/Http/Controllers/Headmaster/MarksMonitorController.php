<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Http\Request;

class MarksMonitorController extends Controller
{
    public function index(Request $request)
    {
        $query = Mark::with(['student.user', 'subject', 'class', 'teacher.user', 'academicYear', 'term']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $marks = $query->latest()->paginate(50);

        $academicYears = AcademicYear::all();
        $classes = ClassModel::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();
        $terms = collect();

        if ($request->filled('academic_year_id')) {
            $terms = Term::where('academic_year_id', $request->academic_year_id)
                ->orderBy('start_date')
                ->get();
        }

        return view('headmaster.marks.index', compact(
            'marks',
            'academicYears',
            'classes',
            'subjects',
            'teachers',
            'terms'
        ));
    }
}
