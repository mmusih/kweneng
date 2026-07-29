<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\HomeworkStorageService;
use App\Services\MarksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HomeworkController extends Controller
{
    public function __construct(
        protected MarksService $marksService,
        protected ActivityLogService $activityLogService,
        protected HomeworkStorageService $homeworkStorageService
    ) {}

    public function index()
    {
        $teacher = Auth::user()->teacher;
        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : null;
        $classes = $this->marksService->getTeacherClassesForMarks($teacher, $activeAcademicYear?->id);

        $homeworks = Homework::with(['class', 'subject', 'term'])
            ->withCount([
                'homeworkMarks as submitted_count' => fn ($query) => $query->where('submission_status', HomeworkMark::STATUS_SUBMITTED),
                'homeworkMarks as late_submission_count' => fn ($query) => $query->where('submission_status', HomeworkMark::STATUS_LATE_SUBMISSION),
                'homeworkMarks as copied_count' => fn ($query) => $query->where('submission_status', HomeworkMark::STATUS_COPIED),
                'homeworkMarks as not_submitted_count' => fn ($query) => $query->where('submission_status', HomeworkMark::STATUS_NOT_SUBMITTED),
                'homeworkMarks as marked_count' => fn ($query) => $query->whereNotNull('marks_obtained'),
            ])
            ->where('teacher_id', $teacher->id)
            ->when($activeAcademicYear, fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id))
            ->latest('assigned_date')
            ->latest('id')
            ->get();

        return view('teacher.homeworks.index', compact('classes', 'homeworks', 'activeAcademicYear', 'activeTerm'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_marks' => ['nullable', 'numeric', 'min:0.01'],
            'assigned_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:assigned_date'],
            'homework_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $teacher = Auth::user()->teacher;
        $activeAcademicYear = AcademicYear::current();

        if (! $activeAcademicYear) {
            return back()->withErrors(['homework' => 'No active academic year was found. Please ask the administrator to activate the current year.'])->withInput();
        }

        $validated['academic_year_id'] = (int) ($validated['academic_year_id'] ?? $activeAcademicYear->id);

        if ((int) $validated['academic_year_id'] !== (int) $activeAcademicYear->id) {
            return back()->withErrors(['homework' => 'Homework can only be created for the current active academic year.'])->withInput();
        }

        $term = isset($validated['term_id'])
            ? Term::whereKey($validated['term_id'])->where('academic_year_id', $activeAcademicYear->id)->firstOrFail()
            : Term::current($activeAcademicYear->id);

        if (! $term) {
            return back()->withErrors(['homework' => 'No active term was found. Please ask the administrator to activate the current term.'])->withInput();
        }

        $validated['term_id'] = (int) $term->id;

        if (! $this->marksService->validateMarksEntry(
            $teacher,
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            (int) $validated['academic_year_id'],
            (int) $validated['term_id']
        )) {
            return back()->withErrors([
                'homework' => 'You do not have permission to create homework for this class, subject, and term combination.',
            ])->withInput();
        }

        if (! $term->isActive() || $term->isLocked()) {
            return back()->withErrors([
                'homework' => 'Homework can be created only during the active, unlocked term.',
            ])->withInput();
        }

        $attachmentData = $this->storeHomeworkImage($request);
        $homework = null;

        try {
            DB::transaction(function () use ($validated, $teacher, $attachmentData, &$homework) {
                $homework = Homework::create(array_merge([
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $validated['academic_year_id'],
                    'term_id' => $validated['term_id'],
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'total_marks' => $validated['total_marks'] ?? 0,
                    'assigned_date' => $validated['assigned_date'],
                    'due_date' => $validated['due_date'] ?? null,
                ], $attachmentData));

                $this->ensureHomeworkMarkRecords($homework);
            });
        } catch (\Throwable $exception) {
            if (! empty($attachmentData['attachment_path'])) {
                Storage::disk('local')->delete($attachmentData['attachment_path']);
            }

            throw $exception;
        }

        $this->activityLogService->log(
            'homework.created',
            'Teacher created homework',
            $homework,
            [
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'title' => $homework->title,
                'total_marks' => $homework->total_marks,
                'has_attachment' => $homework->hasAttachment(),
            ],
            request()
        );

        return redirect()
            ->route('teacher.homeworks.marks', $homework)
            ->with('success', 'Homework created successfully. Student submission records were created as Submitted by default.');
    }

    public function marks(Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);

        $this->ensureHomeworkMarkRecords($homework);

        $students = $this->marksService->getStudentsForMarksEntry(
            $homework->class_id,
            $homework->subject_id,
            $homework->academic_year_id,
            $teacher->id
        );

        $existingMarks = HomeworkMark::where('homework_id', $homework->id)
            ->get()
            ->keyBy('student_id');

        $term = Term::findOrFail($homework->term_id);
        $submissionStatuses = HomeworkMark::statusLabels();

        return view('teacher.homeworks.marks', compact('homework', 'students', 'existingMarks', 'term', 'submissionStatuses'));
    }

    public function storeMarks(Request $request, Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);

        $term = Term::findOrFail($homework->term_id);

        if (! $term->isActive() || $term->isLocked()) {
            return back()->withErrors([
                'homework_marks' => 'Homework records can be changed only during the active, unlocked term.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'total_marks' => ['nullable', 'numeric', 'min:0.01'],
            'marks' => ['required', 'array'],
            'marks.*.submission_status' => ['required', Rule::in(HomeworkMark::statuses())],
            'marks.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $totalMarks = $request->filled('total_marks') ? (float) $request->input('total_marks') : null;

            if ($totalMarks === null) {
                return;
            }

            foreach ($request->input('marks', []) as $studentId => $row) {
                $value = $row['marks_obtained'] ?? null;
                if ($value !== null && $value !== '' && (float) $value > $totalMarks) {
                    $validator->errors()->add("marks.{$studentId}.marks_obtained", 'A student mark cannot be higher than the homework total marks.');
                }
            }
        });

        $validated = $validator->validate();
        $totalMarks = array_key_exists('total_marks', $validated) && $validated['total_marks'] !== null && $validated['total_marks'] !== ''
            ? (float) $validated['total_marks']
            : null;

        $this->ensureHomeworkMarkRecords($homework);

        $authorizedStudentIds = $this->marksService->getAuthorizedStudentIdsForMarks(
            $teacher,
            (int) $homework->class_id,
            (int) $homework->subject_id,
            (int) $homework->academic_year_id
        );

        $submittedStudentIds = collect(array_keys($validated['marks']))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($submittedStudentIds->diff($authorizedStudentIds)->isNotEmpty()) {
            return back()->withErrors([
                'homework_marks' => 'Some submitted learners are not assigned to you for this homework subject. Please reload the page and try again.',
            ]);
        }

        DB::transaction(function () use ($validated, $homework, $totalMarks, $authorizedStudentIds) {
            $homework->forceFill(['total_marks' => $totalMarks ?? 0])->save();

            foreach ($validated['marks'] as $studentId => $row) {
                $studentId = (int) $studentId;

                if (! $authorizedStudentIds->contains($studentId)) {
                    continue;
                }

                $existing = HomeworkMark::where('homework_id', $homework->id)
                    ->where('student_id', $studentId)
                    ->first();

                $marksObtained = array_key_exists('marks_obtained', $row) && $row['marks_obtained'] !== null && $row['marks_obtained'] !== ''
                    ? (float) $row['marks_obtained']
                    : null;

                $percentage = null;
                $grade = null;

                if ($marksObtained !== null && $totalMarks !== null && $totalMarks > 0) {
                    $percentage = round(($marksObtained / $totalMarks) * 100, 2);
                    $grade = $this->marksService->calculateGrade($percentage);
                }

                $statusChanged = ! $existing || $existing->submission_status !== $row['submission_status'];

                HomeworkMark::updateOrCreate(
                    [
                        'homework_id' => $homework->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'submission_status' => $row['submission_status'],
                        'marks_obtained' => $marksObtained,
                        'percentage' => $percentage,
                        'grade' => $grade,
                        'remarks' => $row['remarks'] ?? null,
                        'status_updated_at' => $statusChanged ? now() : $existing?->status_updated_at,
                        'status_updated_by' => $statusChanged ? Auth::id() : $existing?->status_updated_by,
                    ]
                );
            }
        });

        $this->activityLogService->log(
            'homework.records_saved',
            'Teacher saved homework submission records and optional marks',
            $homework,
            [
                'homework_id' => $homework->id,
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'records_count' => count($validated['marks']),
                'total_marks' => $totalMarks,
            ],
            request()
        );

        return redirect()
            ->route('teacher.homeworks.marks', $homework)
            ->with('success', 'Homework records saved successfully.');
    }

    public function destroy(Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);

        $term = Term::findOrFail($homework->term_id);

        if (! $term->isActive() || $term->isLocked()) {
            return back()->withErrors([
                'homework' => 'Only homework from the active, unlocked term can be deleted by the teacher.',
            ]);
        }

        $summary = $this->homeworkStorageService->deleteHomework($homework, Auth::id(), 'teacher_deleted_homework');

        $this->activityLogService->log(
            'homework.deleted',
            'Teacher deleted homework',
            null,
            [
                'homework_id' => $homework->id,
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'attachment_removed' => (bool) $summary['path'],
                'bytes_released' => (int) $summary['bytes_released'],
            ],
            request()
        );

        return redirect()
            ->route('teacher.homeworks.index')
            ->with('success', 'Homework deleted successfully. Any uploaded photo/file was removed from storage.');
    }

    public function downloadAttachment(Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);
        abort_unless($homework->hasAttachment(), 404);

        $disk = $homework->attachmentDisk();
        $path = $homework->attachmentStoragePath();

        abort_unless($path && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download(
            $path,
            $homework->attachmentOriginalName() ?: basename($path),
            ['Content-Type' => $homework->attachmentMime() ?: 'application/octet-stream']
        );
    }

    private function ensureHomeworkMarkRecords(Homework $homework): void
    {
        $students = $this->marksService->getStudentsForMarksEntry(
            $homework->class_id,
            $homework->subject_id,
            $homework->academic_year_id,
            $homework->teacher_id
        );

        foreach ($students as $studentData) {
            $student = $studentData['student'] ?? null;

            if (! $student) {
                continue;
            }

            HomeworkMark::firstOrCreate(
                [
                    'homework_id' => $homework->id,
                    'student_id' => $student->id,
                ],
                [
                    'submission_status' => HomeworkMark::STATUS_SUBMITTED,
                    'status_updated_at' => now(),
                    'status_updated_by' => Auth::id(),
                ]
            );
        }
    }

    private function storeHomeworkImage(Request $request): array
    {
        if (! $request->hasFile('homework_image')) {
            return [];
        }

        $file = $request->file('homework_image');
        $path = $file->store('homeworks/' . now()->format('Y/m'), 'local');

        return [
            'attachment_path' => $path,
            'attachment_original_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }
}
