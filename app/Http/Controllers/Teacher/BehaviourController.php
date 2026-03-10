<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BehaviourRecord;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BehaviourController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        $teacher = $user->teacher;

        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $activeTerm = null;
        $classes = collect();
        $students = collect();
        $records = collect();

        $selectedClassId = $request->input('class_id');
        $selectedStudentId = $request->input('student_id');
        $selectedDate = $request->input('record_date', now()->toDateString());

        if ($activeAcademicYear) {
            $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Term::STATUS_ACTIVE)
                ->first();

            $classes = ClassModel::with('classTeacher.user')
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('class_teacher_id', $teacher->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            if ($selectedClassId) {
                $class = ClassModel::where('id', $selectedClassId)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->where('class_teacher_id', $teacher->id)
                    ->firstOrFail();

                $students = Student::with('user')
                    ->where('current_class_id', $class->id)
                    ->orderBy('admission_no')
                    ->get();

                $records = BehaviourRecord::with('student.user')
                    ->where('class_id', $class->id)
                    ->when($selectedStudentId, function ($query) use ($selectedStudentId) {
                        $query->where('student_id', $selectedStudentId);
                    })
                    ->orderByDesc('record_date')
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get();
            }
        }

        return view('teacher.behaviour.index', compact(
            'activeAcademicYear',
            'activeTerm',
            'classes',
            'students',
            'records',
            'selectedClassId',
            'selectedStudentId',
            'selectedDate'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        $teacher = $user->teacher;

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'student_id' => ['required', 'exists:students,id'],
            'record_date' => ['required', 'date'],
            'category' => ['required', Rule::in(BehaviourRecord::categories())],
            'severity' => ['required', Rule::in(BehaviourRecord::severities())],
            'incident' => ['required', 'string', 'max:2000'],
            'action_taken' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeAcademicYear = AcademicYear::where('active', true)->firstOrFail();

        $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
            ->where('status', Term::STATUS_ACTIVE)
            ->first();

        if (! $activeTerm) {
            return back()->withErrors([
                'record_date' => 'No active term found. Behaviour cannot be recorded without an active term.',
            ])->withInput();
        }

        $class = ClassModel::where('id', $validated['class_id'])
            ->where('academic_year_id', $activeAcademicYear->id)
            ->where('class_teacher_id', $teacher->id)
            ->first();

        if (! $class) {
            abort(403, 'You are not the class teacher for this class.');
        }

        $student = Student::where('id', $validated['student_id'])
            ->where('current_class_id', $class->id)
            ->first();

        if (! $student) {
            return back()->withErrors([
                'student_id' => 'Selected student does not belong to this class.',
            ])->withInput();
        }

        BehaviourRecord::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'academic_year_id' => $activeAcademicYear->id,
            'term_id' => $activeTerm->id,
            'record_date' => $validated['record_date'],
            'category' => $validated['category'],
            'severity' => $validated['severity'],
            'incident' => $validated['incident'],
            'action_taken' => $validated['action_taken'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()->route('teacher.behaviour.index', [
            'class_id' => $class->id,
            'student_id' => $student->id,
            'record_date' => $validated['record_date'],
        ])->with('success', 'Behaviour record saved successfully.');
    }
}
