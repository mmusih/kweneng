<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\ParentModel;
use App\Models\TeacherSubject;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\FirebaseNotificationService;
use App\Services\HomeworkStorageService;
use App\Services\MarksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherHomeworkController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly MarksService $marksService,
        private readonly HomeworkStorageService $homeworkStorageService
    ) {
    }

    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'include_archived' => ['nullable', 'boolean'],
        ]);

        $activeAcademicYear = AcademicYear::current();

        $homeworks = Homework::with(['class:id,name,level', 'subject:id,name,code', 'term:id,name,status,locked'])
            ->where('teacher_id', $teacher->id)
            ->when(! ($validated['include_archived'] ?? false) && $activeAcademicYear, fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id))
            ->when($validated['class_id'] ?? null, fn ($query, $classId) => $query->where('class_id', $classId))
            ->when($validated['subject_id'] ?? null, fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->latest('assigned_date')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'homeworks' => collect($homeworks->items())->map(fn (Homework $homework) => $this->formatHomework($homework))->values(),
            'pagination' => [
                'current_page' => $homeworks->currentPage(),
                'last_page' => $homeworks->lastPage(),
                'per_page' => $homeworks->perPage(),
                'total' => $homeworks->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'client_request_id' => ['required', 'uuid'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'is_graded' => ['nullable', 'boolean'],
            'total_marks' => [
                Rule::requiredIf($request->boolean('is_graded')),
                'nullable',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:15360'],
        ]);

        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        $existingHomework = Homework::with(['class:id,name,level', 'subject:id,name,code', 'term:id,name'])
            ->where('teacher_id', $teacher->id)
            ->where('client_request_id', $validated['client_request_id'])
            ->first();

        if ($existingHomework) {
            return response()->json([
                'message' => 'Homework was already sent.',
                'duplicate' => true,
                'homework' => $this->formatHomework($existingHomework),
            ]);
        }

        $activeAcademicYear = AcademicYear::current();

        if (! $activeAcademicYear) {
            throw ValidationException::withMessages(['academic_year' => 'No active academic year was found.']);
        }

        $assignment = TeacherSubject::with(['class:id,name', 'subject:id,name,code'])
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $activeAcademicYear->id)
            ->firstOrFail();

        $term = Term::current($assignment->academic_year_id);

        if (! $term || $term->isLocked()) {
            throw ValidationException::withMessages([
                'term' => 'Homework can be sent only during an active, unlocked term.',
            ]);
        }

        $file = $request->file('image');
        $disk = 'local';
        $path = $file->store("homework-images/{$assignment->academic_year_id}/{$term->id}/{$teacher->id}", $disk);

        if (! $path) {
            throw ValidationException::withMessages(['image' => 'The homework image could not be stored.']);
        }

        try {
            $homework = DB::transaction(function () use ($assignment, $teacher, $term, $validated, $disk, $path, $file) {
                $homework = Homework::create([
                    'class_id' => $assignment->class_id,
                    'subject_id' => $assignment->subject_id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'term_id' => $term->id,
                    'title' => $validated['title'] ?? ($assignment->subject->name . ' homework'),
                    'description' => $validated['description'] ?? null,
                    'is_graded' => (bool) ($validated['is_graded'] ?? false),
                    'client_request_id' => $validated['client_request_id'],
                    'total_marks' => (float) ($validated['total_marks'] ?? 0),
                    'assigned_date' => now()->toDateString(),
                    'due_date' => $validated['due_date'] ?? null,
                    'image_disk' => $disk,
                    'image_path' => $path,
                    'image_original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'image_mime_type' => $file->getMimeType(),
                    'image_size' => $file->getSize(),
                    'published_at' => now(),
                ]);

                $this->ensureHomeworkMarkRecords($homework);

                return $homework;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            $duplicate = Homework::with(['class:id,name,level', 'subject:id,name,code', 'term:id,name'])
                ->where('teacher_id', $teacher->id)
                ->where('client_request_id', $validated['client_request_id'])
                ->first();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Homework was already sent.',
                    'duplicate' => true,
                    'homework' => $this->formatHomework($duplicate),
                ]);
            }

            throw $exception;
        }

        $homework->load(['class:id,name,level', 'subject:id,name,code', 'term:id,name']);
        $parentIds = $this->recipientParentIds($homework);
        $this->sendNotifications($homework, $parentIds);

        $this->activityLogService->log(
            'homework.mobile_sent',
            'Teacher sent homework from the mobile app',
            $homework,
            [
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'recipient_parent_count' => count($parentIds),
                'has_image' => true,
            ],
            $request
        );

        return response()->json([
            'message' => 'Homework sent successfully.',
            'recipient_parent_count' => count($parentIds),
            'homework' => $this->formatHomework($homework),
        ], 201);
    }

    public function image(Request $request, Homework $homework)
    {
        abort_unless($request->user()->teacher?->id === $homework->teacher_id, 403);
        abort_unless($homework->hasAttachment(), 404, 'Homework image not found.');

        $diskName = $homework->attachmentDisk();
        $path = $homework->attachmentStoragePath();

        abort_unless($path && Storage::disk($diskName)->exists($path), 404, 'Homework image not found.');

        return Storage::disk($diskName)->response(
            $path,
            $homework->attachmentOriginalName() ?: basename($path),
            ['Cache-Control' => 'private, max-age=3600'],
            'inline'
        );
    }

    public function destroy(Request $request, Homework $homework)
    {
        abort_unless($request->user()->teacher?->id === $homework->teacher_id, 403);

        $term = Term::findOrFail($homework->term_id);

        if (! $term->isActive() || $term->isLocked()) {
            throw ValidationException::withMessages([
                'homework' => 'Only homework from the active, unlocked term can be deleted.',
            ]);
        }

        $summary = $this->homeworkStorageService->deleteHomework(
            $homework,
            $request->user()->id,
            'teacher_mobile_deleted_homework'
        );

        $this->activityLogService->log(
            'homework.mobile_deleted',
            'Teacher deleted homework from the mobile app',
            null,
            [
                'homework_id' => $homework->id,
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'attachment_removed' => (bool) $summary['path'],
                'bytes_released' => (int) $summary['bytes_released'],
            ],
            $request
        );

        return response()->json(['message' => 'Homework deleted successfully. Any uploaded image was removed from storage.']);
    }

    private function ensureHomeworkMarkRecords(Homework $homework): void
    {
        $students = $this->marksService->getStudentsForMarksEntry(
            (int) $homework->class_id,
            (int) $homework->subject_id,
            (int) $homework->academic_year_id,
            (int) $homework->teacher_id
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
                    'status_updated_by' => request()->user()?->id,
                ]
            );
        }
    }

    private function recipientParentIds(Homework $homework): array
    {
        return ParentModel::query()
            ->whereHas('students', function ($query) use ($homework) {
                $query->where('students.current_class_id', $homework->class_id)
                    ->whereHas('studentSubjects', function ($subjectQuery) use ($homework) {
                        $subjectQuery->where('subject_id', $homework->subject_id)
                            ->where('class_id', $homework->class_id)
                            ->where('academic_year_id', $homework->academic_year_id)
                            ->where('teacher_id', $homework->teacher_id);
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function sendNotifications(Homework $homework, array $parentIds): void
    {
        if (! config('services.firebase.credentials') || $parentIds === []) {
            return;
        }

        try {
            app(FirebaseNotificationService::class)->sendToManyParents(
                $parentIds,
                'New homework: ' . $homework->subject->name,
                $homework->class->name . ' — ' . $homework->title,
                ['type' => 'homework', 'homework_id' => $homework->id]
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function formatHomework(Homework $homework): array
    {
        return [
            'id' => $homework->id,
            'title' => $homework->title,
            'description' => $homework->description,
            'class' => ['id' => $homework->class->id, 'name' => $homework->class->name],
            'subject' => ['id' => $homework->subject->id, 'name' => $homework->subject->name, 'code' => $homework->subject->code],
            'term' => ['id' => $homework->term->id, 'name' => $homework->term->name],
            'is_graded' => $homework->is_graded,
            'total_marks' => $homework->is_graded ? (float) $homework->total_marks : null,
            'assigned_date' => $homework->assigned_date?->toDateString(),
            'due_date' => $homework->due_date?->toDateString(),
            'published_at' => $homework->published_at?->toIso8601String(),
            'has_image' => $homework->hasImage(),
            'image_url' => $homework->hasImage() ? route('api.teacher.homeworks.image', $homework) : null,
            'attachment_removed' => $homework->attachmentWasPurged(),
            'can_delete' => $homework->term?->isActive() && ! $homework->term?->isLocked(),
        ];
    }
}
