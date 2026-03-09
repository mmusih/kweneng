<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\HeadmasterComment;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();

        $classes = collect();
        $terms = collect();
        $students = collect();
        $selectedClassId = $request->input('class_id');
        $selectedTermId = $request->input('term_id');

        if ($activeAcademicYear) {
            $classes = ClassModel::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            $terms = Term::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('start_date')
                ->get();

            if ($selectedClassId && $selectedTermId) {
                $students = Student::with([
                        'user',
                        'currentClass',
                        'marks' => function ($query) use ($activeAcademicYear, $selectedTermId) {
                            $query->with('subject')
                                ->where('academic_year_id', $activeAcademicYear->id)
                                ->where('term_id', $selectedTermId)
                                ->orderBy('subject_id');
                        },
                        'headmasterComments' => function ($query) use ($selectedTermId) {
                            $query->where('term_id', $selectedTermId);
                        },
                    ])
                    ->where('current_class_id', $selectedClassId)
                    ->orderBy('admission_no')
                    ->get()
                    ->map(function ($student) {
                        $marks = $student->marks;

                        $subjectBreakdown = $marks->map(function ($mark) {
                            $midterm = $mark->midterm_score;
                            $endterm = $mark->endterm_score;

                            $average = null;
                            if ($midterm !== null && $endterm !== null) {
                                $average = ($midterm + $endterm) / 2;
                            } elseif ($midterm !== null) {
                                $average = $midterm;
                            } elseif ($endterm !== null) {
                                $average = $endterm;
                            }

                            return [
                                'subject_name' => $mark->subject->name ?? 'Unknown Subject',
                                'midterm_score' => $midterm,
                                'endterm_score' => $endterm,
                                'average' => $average,
                                'grade' => $mark->grade,
                                'remarks' => $mark->remarks,
                            ];
                        });

                        $averages = $subjectBreakdown->pluck('average')->filter(fn ($value) => $value !== null);

                        $studentAverage = $averages->count() > 0
                            ? round($averages->avg(), 2)
                            : null;

                        $topSubjects = $subjectBreakdown
                            ->filter(fn ($row) => $row['average'] !== null)
                            ->sortByDesc('average')
                            ->take(3)
                            ->values();

                        $weakSubjects = $subjectBreakdown
                            ->filter(fn ($row) => $row['average'] !== null)
                            ->sortBy('average')
                            ->take(3)
                            ->values();

                        $student->subject_breakdown = $subjectBreakdown;
                        $student->student_average = $studentAverage;
                        $student->top_subjects = $topSubjects;
                        $student->weak_subjects = $weakSubjects;

                        return $student;
                    });
            }
        }

        return view('headmaster.comments.index', compact(
            'activeAcademicYear',
            'classes',
            'terms',
            'students',
            'selectedClassId',
            'selectedTermId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $term = Term::findOrFail($validated['term_id']);
        $user = $request->user();

        if (! $user || $user->role !== 'headmaster' || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        HeadmasterComment::updateOrCreate(
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
            ],
            [
                'academic_year_id' => $term->academic_year_id,
                'headmaster_id' => $user->teacher->id,
                'comment' => $validated['comment'],
            ]
        );

        return redirect()
            ->route('headmaster.comments.index', [
                'class_id' => $student->current_class_id,
                'term_id' => $term->id,
            ])
            ->with('success', 'Headmaster comment saved successfully.');
    }
}