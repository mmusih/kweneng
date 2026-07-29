<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\HomeworkParentRead;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParentHomeworkController extends Controller
{
    public function index(Request $request)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $children = $parent->students()
            ->with(['user', 'currentClass'])
            ->orderBy('students.id')
            ->get();

        $studentIds = $children->pluck('id')->map(fn ($id) => (int) $id)->values();

        $records = HomeworkMark::with([
            'student.user',
            'student.currentClass',
            'homework.class',
            'homework.subject',
            'homework.teacher.user',
            'homework.term',
        ])
            ->whereIn('student_id', $studentIds)
            ->whereHas('homework')
            ->get()
            ->sortByDesc(function (HomeworkMark $record) {
                $date = optional($record->homework?->assigned_date)->format('Y-m-d') ?? '0000-00-00';
                return $date . '-' . str_pad((string) ($record->homework_id ?? 0), 12, '0', STR_PAD_LEFT);
            })
            ->values();

        $readKeys = HomeworkParentRead::where('parent_id', $parent->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(fn (HomeworkParentRead $read) => $read->student_id . ':' . $read->homework_id)
            ->flip();

        $unreadKeys = $records
            ->filter(fn (HomeworkMark $record) => ! $readKeys->has($record->student_id . ':' . $record->homework_id))
            ->map(fn (HomeworkMark $record) => $record->student_id . ':' . $record->homework_id)
            ->unique()
            ->values();

        if (! $request->boolean('preview_only')) {
            foreach ($records as $record) {
                HomeworkParentRead::firstOrCreate(
                    [
                        'parent_id' => $parent->id,
                        'student_id' => $record->student_id,
                        'homework_id' => $record->homework_id,
                    ],
                    ['read_at' => now()]
                );
            }
        }

        $items = $records->map(fn (HomeworkMark $record) => $this->formatRecord(
            $record,
            $request,
            $unreadKeys->contains($record->student_id . ':' . $record->homework_id)
        ));

        return response()->json([
            'children' => $children->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Unnamed student',
                'admission_no' => $student->admission_no,
                'identity' => [
                    'nationality' => $student->nationality,
                    'document_type' => $student->identity_document_type,
                    'document_number' => $student->identity_document_number,
                    'display' => $student->identityDisplay(),
                ],
                'profile_complete' => $student->isProfileComplete(),
                'missing_fields' => $student->profileCompletionIssues(),
                'class' => $student->currentClass->name ?? null,
            ])->values(),
            'unread_count_before_open' => $unreadKeys->count(),
            'homework' => $items,
            'threads' => $items
                ->groupBy('student_id')
                ->map(fn ($studentItems) => $studentItems->groupBy('subject_name')),
        ]);
    }

    public function downloadAttachment(Request $request, Homework $homework)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        abort_unless($this->parentCanAccessHomework($parent, $homework), 403);
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

    private function parentCanAccessHomework($parent, Homework $homework): bool
    {
        return HomeworkMark::where('homework_id', $homework->id)
            ->whereIn('student_id', $parent->students()->select('students.id'))
            ->exists();
    }

    private function formatRecord(HomeworkMark $record, Request $request, bool $isUnread): array
    {
        $homework = $record->homework;
        $student = $record->student;

        return [
            'id' => $homework->id,
            'homework_mark_id' => $record->id,
            'student_id' => $student->id,
            'student_name' => $student->user->name ?? 'Unnamed student',
            'class_name' => $student->currentClass->name ?? $homework->class->name ?? null,
            'subject_name' => $homework->subject->name ?? null,
            'teacher_name' => $homework->teacher->user->name ?? null,
            'term_name' => $homework->term->name ?? null,
            'title' => $homework->title,
            'description' => $homework->description,
            'total_marks' => $homework->hasMarksConfigured() ? (float) $homework->total_marks : null,
            'marks_obtained' => $record->marks_obtained !== null ? (float) $record->marks_obtained : null,
            'percentage' => $record->percentage !== null ? (float) $record->percentage : null,
            'grade' => $record->grade,
            'submission_status' => $record->submission_status,
            'submission_status_label' => HomeworkMark::statusLabel($record->submission_status),
            'remarks' => $record->remarks,
            'assigned_date' => optional($homework->assigned_date)->toDateString(),
            'due_date' => optional($homework->due_date)->toDateString(),
            'has_attachment' => $homework->hasAttachment(),
            'attachment_removed' => $homework->attachmentWasPurged(),
            'attachment_download_url' => $homework->hasAttachment()
                ? url('/api/parent/homework/' . $homework->id . '/attachment')
                : null,
            'is_unread' => $isUnread,
            'created_at' => optional($homework->created_at)->toIso8601String(),
        ];
    }
}
