<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Services\MarksMonitoringService;
use Illuminate\Http\Request;

class MarksMonitorController extends Controller
{
    public function __construct(private readonly MarksMonitoringService $marksMonitoring) {}

    public function index(Request $request)
    {
        $currentAcademicYear = AcademicYear::current();
        $academicYearId = (int) ($request->input('academic_year_id') ?: $currentAcademicYear?->id);
        $terms = $academicYearId
            ? Term::where('academic_year_id', $academicYearId)->orderBy('start_date')->get()
            : collect();
        $requestedTermId = (int) $request->input('term_id');
        $termId = $terms->contains('id', $requestedTermId)
            ? $requestedTermId
            : ($academicYearId
                ? (Term::current($academicYearId)?->id ?? $terms->sortByDesc('start_date')->first()?->id)
                : null);
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');
        $teacherId = $request->input('teacher_id');
        $search = trim((string) $request->input('search', ''));
        $requestedAssessment = $request->input('assessment');
        $assessment = in_array($requestedAssessment, ['midterm', 'endterm'], true)
            ? $requestedAssessment
            : ($academicYearId && $termId
                ? $this->marksMonitoring->defaultAssessment($academicYearId, $termId)
                : 'midterm');

        $academicYears = AcademicYear::orderByDesc('id')->get();
        $classes = ClassModel::query()
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->orderBy('level')
            ->orderBy('name')
            ->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')
            ->whereHas('teacherSubjects', fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user?->name)
            ->values();

        $monitor = $academicYearId && $termId
            ? $this->marksMonitoring->build($academicYearId, $termId, $assessment, [
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'search' => $search,
            ])
            : ['teachersData' => [], 'summary' => $this->emptySummary()];
        $teachersData = $monitor['teachersData'];
        $summary = $monitor['summary'];

        return view('headmaster.marks.index', compact(
            'teachersData',
            'summary',
            'academicYears',
            'classes',
            'subjects',
            'teachers',
            'terms',
            'assessment',
            'academicYearId',
            'termId',
            'classId',
            'subjectId',
            'teacherId',
            'search'
        ));
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'assessment' => ['required', 'in:midterm,endterm'],
        ]);
        $classId = $validated['class_id'];
        $subjectId = $validated['subject_id'];
        $teacherId = $validated['teacher_id'];
        $academicYearId = $validated['academic_year_id'];
        $termId = $validated['term_id'];
        $assessment = $validated['assessment'];

        $studentSubjects = StudentSubject::with([
            'student.user',
            'class',
            'subject',
            'teacher.user',
            'academicYear',
        ])
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('student.classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
            ->get();

        $marks = Mark::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('student_id');

        $detailRows = $studentSubjects->map(function ($studentSubject) use ($marks, $assessment) {
            $mark = $marks->get($studentSubject->student_id);

            $value = $assessment === 'midterm'
                ? $mark?->midterm_score
                : $mark?->endterm_score;

            return [
                'student_name' => $studentSubject->student?->user?->name ?? 'N/A',
                'admission_no' => $studentSubject->student?->admission_no ?? 'N/A',
                'value' => $value,
                'missing' => $value === null,
            ];
        })->sortByDesc('missing')->values();

        $meta = [
            'class' => optional($studentSubjects->first()?->class)->name ?? 'N/A',
            'subject' => optional($studentSubjects->first()?->subject)->name ?? 'N/A',
            'teacher' => Teacher::with('user')->find($teacherId)?->user?->name ?? 'N/A',
            'assessment' => ucfirst($assessment),
        ];
        $backUrl = route('headmaster.marks.index', [
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'assessment' => $assessment,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
        ]);

        return view('headmaster.marks.show', compact('detailRows', 'meta', 'backUrl'));
    }

    private function emptySummary(): array
    {
        return [
            'teachers' => 0,
            'complete_teachers' => 0,
            'incomplete_teachers' => 0,
            'assignments' => 0,
            'complete_assignments' => 0,
            'expected' => 0,
            'completed' => 0,
            'missing' => 0,
            'progress' => null,
            'critical_teachers' => 0,
        ];
    }
}
