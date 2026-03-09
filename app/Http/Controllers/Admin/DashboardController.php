<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AccountsOfficer;
use App\Models\Alumni;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalStudents' => Student::count(),
            'totalAcademicStaff' => User::whereIn('role', ['teacher', 'headmaster'])->count(),
            'totalTeachers' => User::where('role', 'teacher')->count(),
            'totalHeadmasters' => User::where('role', 'headmaster')->count(),
            'totalParents' => ParentModel::count(),
            'totalAccountsOfficers' => AccountsOfficer::count(),
            'totalLibrarians' => User::where('role', 'librarian')->count(),
            'totalClasses' => ClassModel::count(),
            'totalAlumni' => Alumni::count(),
        ];

        $classes = ClassModel::with('students')->get();

        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $currentTerm = null;

        $schoolOverview = [
            'schoolAverage' => null,
            'totalMarks' => 0,
            'bestClass' => null,
            'weakestClass' => null,
            'topSubject' => null,
            'weakestSubject' => null,
            'atRiskStudentsCount' => 0,
            'averageMarksCompletion' => null,
        ];

        if ($activeAcademicYear) {
            $currentTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Term::STATUS_ACTIVE)
                ->first();

            $marksQuery = Mark::query()
                ->where('academic_year_id', $activeAcademicYear->id);

            if ($currentTerm) {
                $marksQuery->where('term_id', $currentTerm->id);
            }

            $marks = $marksQuery->get();

            $schoolOverview['totalMarks'] = $marks->count();

            $schoolOverview['schoolAverage'] = $marks
                ->map(fn ($mark) => $mark->average)
                ->filter(fn ($value) => $value !== null)
                ->avg();

            $classPerformance = ClassModel::query()
                ->where('classes.academic_year_id', $activeAcademicYear->id)
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $currentTerm) {
                    $join->on('classes.id', '=', 'marks.class_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($currentTerm) {
                        $join->where('marks.term_id', '=', $currentTerm->id);
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
                        END)) as average_score')
                )
                ->groupBy('classes.id', 'classes.name')
                ->orderByDesc('average_score')
                ->get()
                ->filter(fn ($row) => $row->average_score !== null)
                ->values();

            $schoolOverview['bestClass'] = $classPerformance->first();
            $schoolOverview['weakestClass'] = $classPerformance->last();

            $subjectPerformance = Subject::query()
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $currentTerm) {
                    $join->on('subjects.id', '=', 'marks.subject_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($currentTerm) {
                        $join->where('marks.term_id', '=', $currentTerm->id);
                    }
                })
                ->select(
                    'subjects.id',
                    'subjects.name',
                    DB::raw('AVG((COALESCE(marks.midterm_score, 0) + COALESCE(marks.endterm_score, 0)) /
                        (CASE
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN 2
                            WHEN marks.midterm_score IS NOT NULL OR marks.endterm_score IS NOT NULL THEN 1
                            ELSE NULL
                        END)) as average_score')
                )
                ->groupBy('subjects.id', 'subjects.name')
                ->orderByDesc('average_score')
                ->get()
                ->filter(fn ($row) => $row->average_score !== null)
                ->values();

            $schoolOverview['topSubject'] = $subjectPerformance->first();
            $schoolOverview['weakestSubject'] = $subjectPerformance->last();

            $studentAverages = Student::query()
                ->join('marks', 'students.id', '=', 'marks.student_id')
                ->where('marks.academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, function ($query) use ($currentTerm) {
                    $query->where('marks.term_id', $currentTerm->id);
                })
                ->select(
                    'students.id',
                    DB::raw('AVG((COALESCE(marks.midterm_score, 0) + COALESCE(marks.endterm_score, 0)) /
                        (CASE
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN 2
                            WHEN marks.midterm_score IS NOT NULL OR marks.endterm_score IS NOT NULL THEN 1
                            ELSE NULL
                        END)) as average_score')
                )
                ->groupBy('students.id')
                ->get();

            $schoolOverview['atRiskStudentsCount'] = $studentAverages
                ->filter(fn ($student) => $student->average_score !== null && $student->average_score < 40)
                ->count();

            $completionRows = ClassModel::query()
                ->where('classes.academic_year_id', $activeAcademicYear->id)
                ->withCount('students')
                ->with(['classSubjects' => function ($query) use ($activeAcademicYear) {
                    $query->where('academic_year_id', $activeAcademicYear->id);
                }])
                ->get()
                ->map(function ($class) use ($activeAcademicYear, $currentTerm) {
                    $studentCount = $class->students_count;
                    $subjectCount = $class->classSubjects->count();
                    $expectedMarks = $studentCount * $subjectCount;

                    $actualMarks = Mark::query()
                        ->where('class_id', $class->id)
                        ->where('academic_year_id', $activeAcademicYear->id)
                        ->when($currentTerm, function ($query) use ($currentTerm) {
                            $query->where('term_id', $currentTerm->id);
                        })
                        ->count();

                    return $expectedMarks > 0
                        ? round(($actualMarks / $expectedMarks) * 100, 1)
                        : null;
                })
                ->filter(fn ($value) => $value !== null)
                ->values();

            $schoolOverview['averageMarksCompletion'] = $completionRows->count()
                ? $completionRows->avg()
                : null;
        }

        return view('admin.dashboard', compact(
            'stats',
            'classes',
            'activeAcademicYear',
            'currentTerm',
            'schoolOverview'
        ));
    }
}