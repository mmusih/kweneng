<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\HomeworkParentRead;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $parent = Auth::user()?->parent;
        abort_unless($parent, 404);

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

        $items = $records->map(function (HomeworkMark $record) use ($unreadKeys) {
            $homework = $record->homework;
            $student = $record->student;

            return [
                'record' => $record,
                'homework' => $homework,
                'student' => $student,
                'student_id' => $student?->id,
                'student_name' => $student?->user?->name ?? 'Unknown Student',
                'class_name' => $student?->currentClass?->name ?? $homework?->class?->name ?? 'N/A',
                'subject_name' => $homework?->subject?->name ?? 'Unassigned Subject',
                'teacher_name' => $homework?->teacher?->user?->name ?? 'Teacher',
                'was_unread' => $unreadKeys->contains($record->student_id . ':' . $record->homework_id),
            ];
        });

        $groupedHomework = $items
            ->groupBy('student_id')
            ->map(function ($studentItems) {
                return [
                    'student' => $studentItems->first()['student'],
                    'subjects' => $studentItems->groupBy('subject_name'),
                ];
            });

        return view('parent.homework.index', [
            'children' => $children,
            'groupedHomework' => $groupedHomework,
            'unreadCountBeforeOpen' => $unreadKeys->count(),
            'totalHomeworkCount' => $records->count(),
        ]);
    }

    public function downloadAttachment(Request $request, Homework $homework)
    {
        $parent = Auth::user()?->parent;
        abort_unless($parent, 404);
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
}
