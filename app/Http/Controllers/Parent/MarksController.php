<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'parent') {
            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized access',
            ]);
        }

        $children = $user->parent?->students()
            ->with(['user', 'currentClass'])
            ->get() ?? collect();

        if ($children->isEmpty()) {
            return view('parent.marks.index', [
                'children' => collect(),
                'accessibleChildren' => collect(),
                'blockedChildren' => collect(),
                'academicYears' => collect(),
                'marksMatrix' => collect(),
            ])->with('warning', 'No children linked to your account.');
        }

        $accessibleChildren = $children->filter(fn($child) => !(bool) $child->fees_blocked)->values();
        $blockedChildren = $children->filter(fn($child) => (bool) $child->fees_blocked)->values();

        $academicYears = collect();
        $marksMatrix = collect();

        if ($accessibleChildren->isNotEmpty()) {
            $accessibleStudentIds = $accessibleChildren->pluck('id')->all();

            $academicYears = AcademicYear::whereHas('terms.marks', function ($query) use ($accessibleStudentIds) {
                $query->whereIn('student_id', $accessibleStudentIds);
            })
                ->with(['terms' => function ($termQuery) use ($accessibleStudentIds) {
                    $termQuery->whereHas('marks', function ($marksQuery) use ($accessibleStudentIds) {
                        $marksQuery->whereIn('student_id', $accessibleStudentIds);
                    })->orderBy('start_date');
                }])
                ->orderByDesc('year_name')
                ->get();

            $academicYearIds = $academicYears->pluck('id')->all();
            $termIds = $academicYears->flatMap(fn($year) => $year->terms->pluck('id'))->unique()->values()->all();

            $markCounts = Mark::query()
                ->whereIn('student_id', $accessibleStudentIds)
                ->whereIn('academic_year_id', $academicYearIds)
                ->whereIn('term_id', $termIds)
                ->selectRaw('student_id, academic_year_id, term_id, COUNT(*) as marks_count')
                ->groupBy('student_id', 'academic_year_id', 'term_id')
                ->get();

            $marksMatrix = $markCounts->mapWithKeys(function ($row) {
                $key = $row->student_id . '_' . $row->academic_year_id . '_' . $row->term_id;
                return [$key => (int) $row->marks_count];
            });
        }

        return view('parent.marks.index', [
            'children' => $children,
            'accessibleChildren' => $accessibleChildren,
            'blockedChildren' => $blockedChildren,
            'academicYears' => $academicYears,
            'marksMatrix' => $marksMatrix,
        ]);
    }

    public function show(Student $student, $academicYearId, $termId)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'parent') {
            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized access',
            ]);
        }

        $children = $user->parent->students ?? collect();
        $child = $children->firstWhere('id', $student->id);

        if (!$child) {
            return redirect()->route('parent.children.marks.index')
                ->withErrors([
                    'error' => 'Child not found or not linked to your account.',
                ]);
        }

        if ((bool) $child->fees_blocked) {
            abort(403, 'Results are currently unavailable for this student. Please contact the accounts office.');
        }

        $academicYear = AcademicYear::findOrFail($academicYearId);
        $term = Term::findOrFail($termId);

        $studentSubjects = StudentSubject::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->with('subject')
            ->get();

        $marks = Mark::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->with(['subject', 'teacher.user'])
            ->get()
            ->keyBy('subject_id');

        $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
        $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);

        $averages = [
            'midterm' => $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null,
            'endterm' => $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null,
        ];

        return view('parent.marks.show', [
            'child' => $child,
            'academicYear' => $academicYear,
            'term' => $term,
            'studentSubjects' => $studentSubjects,
            'marks' => $marks,
            'averages' => $averages,
        ]);
    }
}
