<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentAcademicRecordService
{
    /** @return array<string, mixed> */
    public function build(Student $student): array
    {
        $student->loadMissing(['user', 'currentClass', 'classHistory.class', 'classHistory.academicYear']);
        $marks = Mark::query()
            ->where('student_id', $student->id)
            ->with(['academicYear:id,year_name', 'term:id,name,academic_year_id,start_date', 'subject:id,name,code', 'class:id,name'])
            ->get()
            ->sortBy(fn (Mark $mark) => implode('|', [
                $mark->academicYear?->year_name ?? '',
                str_pad((string) ($mark->term?->start_date?->timestamp ?? 0), 12, '0', STR_PAD_LEFT),
                $mark->subject?->name ?? '',
            ]));

        $years = $marks->groupBy('academic_year_id')->map(function (Collection $yearMarks) use ($student) {
            $first = $yearMarks->first();
            $yearId = (int) $first->academic_year_id;
            $history = $student->classHistory->firstWhere('academic_year_id', $yearId);

            return [
                'id' => $yearId,
                'name' => $first->academicYear?->year_name ?? 'Academic year',
                'class' => $history?->class?->name ?? $yearMarks->pluck('class.name')->filter()->first(),
                'status' => $history?->status,
                'terms' => $yearMarks->groupBy('term_id')->map(function (Collection $termMarks) {
                    $first = $termMarks->first();
                    $averages = $termMarks->map->average->filter(fn ($score) => $score !== null);

                    return [
                        'id' => (int) $first->term_id,
                        'name' => $first->term?->name ?? 'Term',
                        'average' => $averages->isEmpty() ? null : round((float) $averages->avg(), 1),
                        'subjects' => $termMarks->map(fn (Mark $mark) => [
                            'subject' => $mark->subject?->name ?? 'Unknown subject',
                            'code' => $mark->subject?->code,
                            'midterm_score' => $mark->midterm_score === null ? null : (float) $mark->midterm_score,
                            'endterm_score' => $mark->endterm_score === null ? null : (float) $mark->endterm_score,
                            'average' => $mark->average === null ? null : round((float) $mark->average, 1),
                            'grade' => $mark->grade,
                            'remarks' => $mark->remarks,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ];
        })->sortByDesc('name')->values();

        return [
            'student' => [
                'id' => (int) $student->id,
                'name' => $student->user?->name ?? 'Student',
                'admission_no' => $student->admission_no,
                'current_class' => $student->currentClass?->name,
            ],
            'years' => $years->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
