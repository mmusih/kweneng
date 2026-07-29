<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Homework;
use App\Models\ParentAbsenceNotice;
use App\Models\TeacherSubject;
use App\Models\Term;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        abort_unless($teacher, 404, 'Teacher profile not found.');

        $academicYear = AcademicYear::where('active', true)->first();
        $term = $academicYear
            ? Term::where('academic_year_id', $academicYear->id)->where('status', Term::STATUS_ACTIVE)->first()
            : null;

        $assignments = $academicYear
            ? TeacherSubject::with(['class:id,name,level,academic_year_id', 'subject:id,name,code'])
                ->where('teacher_id', $teacher->id)
                ->where('academic_year_id', $academicYear->id)
                ->orderBy('class_id')
                ->orderBy('subject_id')
                ->get()
            : collect();

        $classTeacherClasses = $academicYear
            ? ClassModel::query()
                ->where('academic_year_id', $academicYear->id)
                ->where('class_teacher_id', $teacher->id)
                ->withCount('students')
                ->orderBy('level')
                ->orderBy('name')
                ->get()
            : collect();

        $classTeacherClassIds = $classTeacherClasses->pluck('id');

        return response()->json([
            'teacher' => [
                'id' => $teacher->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'academic_year' => $academicYear ? [
                'id' => $academicYear->id,
                'name' => $academicYear->year_name,
                'status' => $academicYear->status,
            ] : null,
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'start_date' => $term->start_date?->toDateString(),
                'end_date' => $term->end_date?->toDateString(),
                'locked' => $term->isLocked(),
                'midterm_locked' => $term->isMidtermLocked(),
                'endterm_locked' => $term->isEndtermLocked(),
            ] : null,
            'class_teacher_classes' => $classTeacherClasses->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'level' => $class->level,
                'student_count' => $class->students_count,
            ])->values(),
            'teaching_assignments' => $assignments->map(fn (TeacherSubject $assignment) => [
                'id' => $assignment->id,
                'class' => [
                    'id' => $assignment->class->id,
                    'name' => $assignment->class->name,
                    'level' => $assignment->class->level,
                ],
                'subject' => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                    'code' => $assignment->subject->code,
                ],
                'is_primary' => $assignment->is_primary,
            ])->values(),
            'counts' => [
                'homeworks' => Homework::where('teacher_id', $teacher->id)->count(),
                'class_teacher_classes' => $classTeacherClasses->count(),
                'teaching_assignments' => $assignments->count(),
                'pending_absence_notices' => ParentAbsenceNotice::query()
                    ->where('status', 'pending')
                    ->whereHas('student', fn ($query) => $query->whereIn('current_class_id', $classTeacherClassIds))
                    ->count(),
            ],
        ]);
    }
}
