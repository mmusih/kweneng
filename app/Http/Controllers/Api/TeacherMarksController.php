<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\Student;
use App\Models\TeacherSubject;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\MarksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherMarksController extends Controller
{
    public function __construct(
        private readonly MarksService $marksService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function show(Request $request)
    {
        $validated = $this->validateContext($request);
        [$assignment, $term] = $this->context($request, $validated);

        $studentRows = $this->marksService->getStudentsForMarksEntry(
            $assignment->class_id,
            $assignment->subject_id,
            $assignment->academic_year_id,
            $request->user()->teacher->id
        );

        $authorizedStudentIds = collect($studentRows)
            ->pluck('student.id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $existing = Mark::query()
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('term_id', $term->id)
            ->whereIn('student_id', $authorizedStudentIds)
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'assignment' => $this->formatAssignment($assignment),
            'term' => $this->formatTerm($term),
            'students' => collect($studentRows)->map(function (array $row) use ($existing) {
                /** @var Student $student */
                $student = $row['student'];
                $mark = $existing->get($student->id);

                return [
                    'id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'name' => $student->user->name,
                    'midterm_score' => $mark?->midterm_score !== null ? (float) $mark->midterm_score : null,
                    'endterm_score' => $mark?->endterm_score !== null ? (float) $mark->endterm_score : null,
                    'grade' => $mark?->grade,
                    'remarks' => $mark?->remarks,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = array_merge($this->validateContext($request), $request->validate([
            'marks' => ['required', 'array', 'min:1'],
            'marks.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'marks.*.midterm_score' => ['nullable', 'numeric', 'between:0,100'],
            'marks.*.endterm_score' => ['nullable', 'numeric', 'between:0,100'],
            'marks.*.remarks' => ['nullable', 'string', 'max:500'],
        ]));

        [$assignment, $term] = $this->context($request, $validated);

        if (! $term->isActive() || $term->isLocked()) {
            throw ValidationException::withMessages(['marks' => 'Marks can be changed only during an active, unlocked term.']);
        }

        $eligibleIds = collect($this->marksService->getStudentsForMarksEntry(
            $assignment->class_id,
            $assignment->subject_id,
            $assignment->academic_year_id,
            $request->user()->teacher->id
        ))->pluck('student.id')->map(fn ($id) => (int) $id);

        $submittedIds = collect($validated['marks'])->pluck('student_id')->map(fn ($id) => (int) $id);

        if ($submittedIds->diff($eligibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'marks' => 'One or more learners are not assigned to this class and subject.',
            ]);
        }

        $savedCount = DB::transaction(function () use ($validated, $assignment, $term, $request) {
            $count = 0;

            foreach ($validated['marks'] as $row) {
                $existing = Mark::query()
                    ->where('student_id', $row['student_id'])
                    ->where('subject_id', $assignment->subject_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->where('term_id', $term->id)
                    ->first();

                $midterm = $term->isMidtermLocked()
                    ? ($existing?->midterm_score !== null ? (float) $existing->midterm_score : null)
                    : ($row['midterm_score'] ?? null);
                $endterm = $term->isEndtermLocked()
                    ? ($existing?->endterm_score !== null ? (float) $existing->endterm_score : null)
                    : ($row['endterm_score'] ?? null);

                $this->marksService->upsertMarks(
                    (int) $row['student_id'],
                    $assignment->subject_id,
                    $assignment->class_id,
                    $request->user()->teacher->id,
                    $assignment->academic_year_id,
                    $term->id,
                    $midterm !== null ? (float) $midterm : null,
                    $endterm !== null ? (float) $endterm : null,
                    $row['remarks'] ?? null
                );
                $count++;
            }

            return $count;
        });

        $this->activityLogService->log(
            'marks.mobile_saved',
            'Teacher saved marks from the mobile app',
            $assignment,
            [
                'class_id' => $assignment->class_id,
                'subject_id' => $assignment->subject_id,
                'term_id' => $term->id,
                'records_count' => $savedCount,
            ],
            $request
        );

        return response()->json([
            'message' => 'Marks saved successfully.',
            'records_saved' => $savedCount,
            'locks' => $this->formatTerm($term)['locks'],
        ]);
    }

    private function validateContext(Request $request): array
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
        ]);

        $activeAcademicYear = AcademicYear::current();

        if (! isset($validated['academic_year_id'])) {
            if (! $activeAcademicYear) {
                throw ValidationException::withMessages(['academic_year_id' => 'No active academic year was found.']);
            }

            $validated['academic_year_id'] = $activeAcademicYear->id;
        }

        if (! isset($validated['term_id'])) {
            $term = Term::current((int) $validated['academic_year_id']);

            if (! $term) {
                throw ValidationException::withMessages(['term_id' => 'No active term was found for the selected academic year.']);
            }

            $validated['term_id'] = $term->id;
        }

        return $validated;
    }

    private function context(Request $request, array $validated): array
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        $assignment = TeacherSubject::with(['class:id,name,level', 'subject:id,name,code', 'academicYear:id,year_name,status'])
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->firstOrFail();

        $term = Term::whereKey($validated['term_id'])
            ->where('academic_year_id', $assignment->academic_year_id)
            ->firstOrFail();

        return [$assignment, $term];
    }

    private function formatAssignment(TeacherSubject $assignment): array
    {
        return [
            'id' => $assignment->id,
            'class' => ['id' => $assignment->class->id, 'name' => $assignment->class->name, 'level' => $assignment->class->level],
            'subject' => ['id' => $assignment->subject->id, 'name' => $assignment->subject->name, 'code' => $assignment->subject->code],
            'academic_year' => ['id' => $assignment->academicYear->id, 'name' => $assignment->academicYear->year_name],
        ];
    }

    private function formatTerm(Term $term): array
    {
        return [
            'id' => $term->id,
            'name' => $term->name,
            'status' => $term->status,
            'locks' => [
                'term' => $term->isLocked(),
                'midterm' => $term->isMidtermLocked(),
                'endterm' => $term->isEndtermLocked(),
            ],
        ];
    }
}
