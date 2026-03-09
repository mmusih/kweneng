<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();

        $activeTerm = null;
        $schoolAverage = null;
        $totalMarks = 0;
        $classPerformance = collect();
        $subjectPerformance = collect();
        $topStudents = collect();
        $atRiskStudents = collect();
        $marksCompletion = collect();
        $teacherCompletion = collect();

        if ($activeAcademicYear) {
            $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Term::STATUS_ACTIVE)
                ->first();

            $marksQuery = Mark::query()
                ->where('marks.academic_year_id', $activeAcademicYear->id);

            if ($activeTerm) {
                $marksQuery->where('marks.term_id', $activeTerm->id);
            }

            $marks = $marksQuery->get();

            $totalMarks = $marks->count();

            $schoolAverage = $marks->map(function ($mark) {
                return $mark->average;
            })->filter(function ($value) {
                return $value !== null;
            })->avg();

            $classPerformance = ClassModel::query()
                ->where('classes.academic_year_id', $activeAcademicYear->id)
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $activeTerm) {
                    $join->on('classes.id', '=', 'marks.class_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($activeTerm) {
                        $join->where('marks.term_id', '=', $activeTerm->id);
                    }
                })
                ->select(
                    'classes.id',
                    'classes.name',
                    DB::raw('AVG((COALESCE(marks.midterm_score, 0) + COALESCE(marks.endterm_score, 0)) / 
                        (CASE 
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN 2
                            WHEN marks.midterm_score IS NOT NULL OR marks.endterm_score IS NOT NULL THEN 1
                            ELSE NULL
                        END)) as average_score'),
                    DB::raw('COUNT(marks.id) as marks_count')
                )
                ->groupBy('classes.id', 'classes.name')
                ->orderByDesc('average_score')
                ->get();

            $subjectPerformance = Subject::query()
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $activeTerm) {
                    $join->on('subjects.id', '=', 'marks.subject_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($activeTerm) {
                        $join->where('marks.term_id', '=', $activeTerm->id);
                    }
                })
                ->select(
                    'subjects.id',
                    'subjects.name',
                    'subjects.code',
                    DB::raw('AVG((COALESCE(marks.midterm_score, 0) + COALESCE(marks.endterm_score, 0)) / 
                        (CASE 
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN 2
                            WHEN marks.midterm_score IS NOT NULL OR marks.endterm_score IS NOT NULL THEN 1
                            ELSE NULL
                        END)) as average_score'),
                    DB::raw('COUNT(marks.id) as marks_count')
                )
                ->groupBy('subjects.id', 'subjects.name', 'subjects.code')
                ->orderByDesc('average_score')
                ->get();

            $studentAverages = Student::query()
                ->join('users', 'students.user_id', '=', 'users.id')
                ->join('marks', 'students.id', '=', 'marks.student_id')
                ->where('marks.academic_year_id', $activeAcademicYear->id)
                ->when($activeTerm, function ($query) use ($activeTerm) {
                    $query->where('marks.term_id', $activeTerm->id);
                })
                ->select(
                    'students.id',
                    'students.admission_no',
                    'users.name as student_name',
                    DB::raw('AVG((COALESCE(marks.midterm_score, 0) + COALESCE(marks.endterm_score, 0)) / 
                        (CASE 
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN 2
                            WHEN marks.midterm_score IS NOT NULL OR marks.endterm_score IS NOT NULL THEN 1
                            ELSE NULL
                        END)) as average_score')
                )
                ->groupBy('students.id', 'students.admission_no', 'users.name')
                ->get();

            $topStudents = $studentAverages
                ->sortByDesc('average_score')
                ->take(5)
                ->values();

            $atRiskStudents = $studentAverages
                ->filter(function ($student) {
                    return $student->average_score !== null && $student->average_score < 40;
                })
                ->sortBy('average_score')
                ->take(5)
                ->values();

            $marksCompletion = ClassModel::query()
                ->where('classes.academic_year_id', $activeAcademicYear->id)
                ->withCount('students')
                ->with(['classSubjects' => function ($query) use ($activeAcademicYear) {
                    $query->where('academic_year_id', $activeAcademicYear->id);
                }])
                ->get()
                ->map(function ($class) use ($activeAcademicYear, $activeTerm) {
                    $studentCount = $class->students_count;
                    $subjectCount = $class->classSubjects->count();

                    $expectedMarks = $studentCount * $subjectCount;

                    $actualMarks = Mark::query()
                        ->where('class_id', $class->id)
                        ->where('academic_year_id', $activeAcademicYear->id)
                        ->when($activeTerm, function ($query) use ($activeTerm) {
                            $query->where('term_id', $activeTerm->id);
                        })
                        ->count();

                    $completion = $expectedMarks > 0
                        ? round(($actualMarks / $expectedMarks) * 100, 1)
                        : 0;

                    return [
                        'class_name' => $class->name,
                        'students' => $studentCount,
                        'subjects' => $subjectCount,
                        'expected_marks' => $expectedMarks,
                        'actual_marks' => $actualMarks,
                        'completion' => $completion,
                    ];
                })
                ->sortBy('completion')
                ->values();

            $teacherCompletion = Teacher::query()
                ->with([
                    'user',
                    'teacherSubjects' => function ($query) use ($activeAcademicYear) {
                        $query->where('academic_year_id', $activeAcademicYear->id)
                            ->with(['class', 'subject']);
                    },
                ])
                ->get()
                ->map(function ($teacher) use ($activeAcademicYear, $activeTerm) {
                    $assignments = $teacher->teacherSubjects;

                    $assignedClassNames = $assignments
                        ->pluck('class.name')
                        ->filter()
                        ->unique()
                        ->values();

                    $assignedClassIds = $assignments
                        ->pluck('class_id')
                        ->filter()
                        ->unique()
                        ->values();

                    $expectedMarks = $assignments->sum(function ($assignment) {
                        return Student::where('current_class_id', $assignment->class_id)->count();
                    });

                    $teacherMarksQuery = Mark::query()
                        ->where('teacher_id', $teacher->id)
                        ->where('academic_year_id', $activeAcademicYear->id);

                    if ($activeTerm) {
                        $teacherMarksQuery->where('term_id', $activeTerm->id);
                    }

                    $actualMarks = $teacherMarksQuery->count();

                    $enteredClassIds = $teacherMarksQuery
                        ->clone()
                        ->distinct()
                        ->pluck('class_id')
                        ->filter()
                        ->unique()
                        ->values();

                    $enteredClassNames = ClassModel::whereIn('id', $enteredClassIds)
                        ->pluck('name')
                        ->unique()
                        ->values();

                    $notEnteredClassNames = $assignedClassNames->reject(function ($className) use ($enteredClassNames) {
                        return $enteredClassNames->contains($className);
                    })->values();

                    $completion = $expectedMarks > 0
                        ? round(($actualMarks / $expectedMarks) * 100, 1)
                        : 0;

                    return [
                        'teacher_name' => $teacher->user->name ?? 'Unknown Teacher',
                        'role' => $teacher->user && $teacher->user->role === 'headmaster' ? 'Headmaster' : 'Teacher',
                        'assigned_classes' => $assignedClassNames,
                        'classes_entered' => $enteredClassNames,
                        'classes_not_entered' => $notEnteredClassNames,
                        'assigned_subjects_count' => $assignments->count(),
                        'expected_marks' => $expectedMarks,
                        'actual_marks' => $actualMarks,
                        'completion' => $completion,
                    ];
                })
                ->sortBy('completion')
                ->values();
        }

        return view('headmaster.dashboard', compact(
            'activeAcademicYear',
            'activeTerm',
            'schoolAverage',
            'totalMarks',
            'classPerformance',
            'subjectPerformance',
            'topStudents',
            'atRiskStudents',
            'marksCompletion',
            'teacherCompletion'
        ));
    }
}