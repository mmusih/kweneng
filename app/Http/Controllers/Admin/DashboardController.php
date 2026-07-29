<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AccountsOfficer;
use App\Models\Alumni;
use App\Models\AlumniInterest;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\Mark;
use App\Models\ParentAbsenceNotice;
use App\Models\ParentMessage;
use App\Models\ParentModel;
use App\Models\Requisition;
use App\Models\SchoolDocument;
use App\Models\Scheme;
use App\Models\Student;
use App\Models\StudentSubject;
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

        // ── new: messaging + documents quick stats ────────────────────────
        $unreadMessageCount = ParentMessage::where('is_read_by_admin', false)->count();
        $activeDocumentCount = SchoolDocument::where('is_active', true)->count();
        // ─────────────────────────────────────────────────────────────────

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

        $pendingInterests = AlumniInterest::where('processed', false)->count();
        $recentInterests = AlumniInterest::where('processed', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $subjectOverview = [
            'studentSubjectCount' => StudentSubject::count(),
            'totalSubjects' => Subject::count(),
            'activeSubjects' => Subject::where('is_active', true)->count(),
            'coreSubjects' => Subject::where('is_core', true)->count(),
            'classAssignments' => ClassSubject::count(),
        ];

        $communicationsOverview = [
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('start_datetime', '>=', now())->count(),
            'totalAnnouncements' => Announcement::count(),
            'recentAnnouncements' => Announcement::where('created_at', '>=', now()->subDays(7))->count(),
            'pendingAbsenceNotices' => ParentAbsenceNotice::where('status', 'pending')->count(),
            'recentAbsenceNotices' => ParentAbsenceNotice::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $inventoryOverview = [
            'attentionCount' => InventoryItem::where(function ($query) {
                $query->whereIn('condition_status', [
                    InventoryItem::CONDITION_DAMAGED,
                    InventoryItem::CONDITION_NEEDS_REPAIR,
                    InventoryItem::CONDITION_BROKEN,
                ])
                    ->orWhere('procurement_status', InventoryItem::PROCUREMENT_NEEDS_BUYING)
                    ->orWhereColumn('quantity_on_hand', '<=', 'minimum_quantity');
            })->count(),
            'newRequisitionCount' => Requisition::where('status', Requisition::STATUS_SUBMITTED)->count(),
            'openRequisitionCount' => Requisition::whereNotIn('status', [
                Requisition::STATUS_FULFILLED,
                Requisition::STATUS_CANCELLED,
                Requisition::STATUS_REJECTED,
            ])->count(),
        ];

        $departmentOverview = [
            'totalDepartments' => Department::count(),
            'hodAssignments' => DepartmentUser::where('role_in_department', DepartmentUser::ROLE_HOD)->count(),
        ];

        $schemeQuery = Scheme::query();

        if ($activeAcademicYear) {
            $schemeQuery->where('academic_year_id', $activeAcademicYear->id);
        }

        $schemes = $schemeQuery
            ->with(['items.subtopics', 'logs'])
            ->latest()
            ->limit(250)
            ->get();

        $schemeOverview = [
            'total' => $schemes->count(),
            'submitted' => $schemes->where('status', Scheme::STATUS_SUBMITTED)->count(),
            'approved' => $schemes->whereIn('status', [Scheme::STATUS_APPROVED, Scheme::STATUS_ACTIVE])->count(),
            'changesRequested' => $schemes->where('status', Scheme::STATUS_CHANGES_REQUESTED)->count(),
            'behind' => $schemes->filter(fn (Scheme $scheme) => in_array($scheme->pacingStatus(), ['behind', 'critical'], true))->count(),
            'averageCompletion' => $schemes->count()
                ? round($schemes->avg(fn (Scheme $scheme) => $scheme->completionPct()), 1)
                : 0,
        ];

        $todayRegisterMissingCount = 0;
        if ($activeAcademicYear) {
            $todayDate = now()->toDateString();
            $holidayToday = Event::attendanceHolidayForDate($todayDate, null, $activeAcademicYear->id);

            if (! $holidayToday) {
                $classTeacherClassesForRegister = ClassModel::withCount('students')
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->whereNotNull('class_teacher_id')
                    ->get();

                foreach ($classTeacherClassesForRegister as $registerClass) {
                    if ($registerClass->students_count < 1) {
                        continue;
                    }

                    $recordedCount = Attendance::where('class_id', $registerClass->id)
                        ->whereDate('attendance_date', $todayDate)
                        ->distinct('student_id')
                        ->count('student_id');

                    if ($recordedCount < $registerClass->students_count) {
                        $todayRegisterMissingCount++;
                    }
                }
            }
        }

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
                ->map(fn($mark) => $mark->average)
                ->filter(fn($value) => $value !== null)
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
                ->filter(fn($row) => $row->average_score !== null)
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
                ->filter(fn($row) => $row->average_score !== null)
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
                ->filter(fn($student) => $student->average_score !== null && $student->average_score < 40)
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
                ->filter(fn($value) => $value !== null)
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
            'schoolOverview',
            'unreadMessageCount',   // ← new
            'activeDocumentCount',  // ← new
            'communicationsOverview',
            'departmentOverview',
            'inventoryOverview',
            'pendingInterests',
            'recentInterests',
            'schemeOverview',
            'subjectOverview',
            'todayRegisterMissingCount',
        ));
    }
}
