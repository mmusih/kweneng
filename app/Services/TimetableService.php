<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\TimetableDay;
use App\Models\TimetableEntry;
use App\Models\TimetableGroup;
use App\Models\TimetablePeriod;
use App\Models\TimetableTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    public function emptySchedule(Carbon|string|null $date = null): array
    {
        return $this->emptyPayload($date);
    }

    public function activeTemplate(?int $academicYearId = null): ?TimetableTemplate
    {
        $academicYearId ??= AcademicYear::current()?->id;

        if (! $academicYearId) {
            return null;
        }

        return TimetableTemplate::with(['academicYear', 'days.periods'])
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->latest('id')
            ->first();
    }

    public function forTeacher(Teacher $teacher, Carbon|string|null $date = null): array
    {
        $template = $this->activeTemplate();

        if (! $template) {
            return $this->emptyPayload($date);
        }

        $entries = $this->entryQuery($template)
            ->where('teacher_id', $teacher->id)
            ->get();

        return $this->payload($template, $entries, $date);
    }

    public function forStudent(Student $student, Carbon|string|null $date = null): array
    {
        $template = $this->activeTemplate();

        if (! $template) {
            return $this->emptyPayload($date);
        }

        $entries = $this->entryQuery($template)
            ->where(function (Builder $query) use ($student) {
                $query
                    ->when(
                        $student->current_class_id,
                        fn (Builder $q, $classId) => $q->where('class_id', $classId),
                    )
                    ->orWhereHas(
                        'group.students',
                        fn (Builder $q) => $q->where('students.id', $student->id),
                    );
            })
            ->get();

        return $this->payload($template, $entries, $date);
    }

    public function validateEntry(array $data, ?TimetableEntry $ignore = null): void
    {
        $day = TimetableDay::with('periods')->findOrFail($data['timetable_day_id']);
        $start = $day->periods->firstWhere('id', (int) $data['start_period_id']);
        $end = $day->periods->firstWhere('id', (int) $data['end_period_id']);

        $errors = [];

        if (! $start || ! $end) {
            $errors['start_period_id'] = 'The selected periods must belong to the selected timetable day.';
        } elseif ($end->sequence < $start->sequence) {
            $errors['end_period_id'] = 'The ending period must not be before the starting period.';
        } else {
            $selectedPeriods = $day->periods
                ->whereBetween('sequence', [$start->sequence, $end->sequence]);

            if ($selectedPeriods->contains(fn (TimetablePeriod $period) => ! $period->isTeaching())) {
                $errors['end_period_id'] = 'A lesson block cannot include break, lunch, or assembly periods.';
            }
        }

        $hasClass = filled($data['class_id'] ?? null);
        $hasGroup = filled($data['timetable_group_id'] ?? null);

        if ($hasClass === $hasGroup) {
            $errors['class_id'] = 'Choose either one full class or one split group.';
        }

        $group = $hasGroup
            ? TimetableGroup::with(['classes', 'students'])->find($data['timetable_group_id'])
            : null;

        if ($group && (int) $group->subject_id !== (int) $data['subject_id']) {
            $errors['subject_id'] = 'The lesson subject must match the selected split group.';
        }

        if ($group && $group->students->isEmpty()) {
            $errors['timetable_group_id'] = 'The selected split group has no students.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $overlapping = TimetableEntry::query()
            ->with(['class.students', 'group.students', 'teacher.user', 'room', 'subject'])
            ->where('timetable_day_id', $day->id)
            ->when($ignore, fn (Builder $query) => $query->where('id', '!=', $ignore->id))
            ->whereHas('startPeriod', fn (Builder $query) => $query->where('sequence', '<=', $end->sequence))
            ->whereHas('endPeriod', fn (Builder $query) => $query->where('sequence', '>=', $start->sequence))
            ->get();

        if ($teacherConflict = $overlapping->firstWhere('teacher_id', (int) $data['teacher_id'])) {
            $errors['teacher_id'] = 'This teacher is already scheduled for '.$teacherConflict->subject?->name.' during that time.';
        }

        if (
            filled($data['timetable_room_id'] ?? null)
            && ($roomConflict = $overlapping->firstWhere('timetable_room_id', (int) $data['timetable_room_id']))
        ) {
            $errors['timetable_room_id'] = 'This room is already in use during that time.';
        }

        $studentIds = $hasClass
            ? Student::where('current_class_id', $data['class_id'])->pluck('id')
            : $group->students->pluck('id');

        foreach ($overlapping as $entry) {
            $otherStudentIds = $entry->class_id
                ? $entry->class?->students?->pluck('id') ?? collect()
                : $entry->group?->students?->pluck('id') ?? collect();

            if ($studentIds->intersect($otherStudentIds)->isNotEmpty()) {
                $errors['class_id'] = 'One or more selected students already have another lesson during that time.';
                break;
            }
        }

        $classIds = $hasClass ? collect([(int) $data['class_id']]) : $group->classes->pluck('id');
        $assignedClassIds = TeacherSubject::query()
            ->where('teacher_id', $data['teacher_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $day->template->academic_year_id)
            ->whereIn('class_id', $classIds)
            ->pluck('class_id');

        if ($classIds->diff($assignedClassIds)->isNotEmpty()) {
            $errors['teacher_id'] = 'The teacher must be assigned to this subject for every selected class.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function entryQuery(TimetableTemplate $template): Builder
    {
        return TimetableEntry::query()
            ->with([
                'day',
                'startPeriod',
                'endPeriod',
                'class',
                'group',
                'subject',
                'teacher.user',
                'room',
            ])
            ->where('timetable_template_id', $template->id);
    }

    private function payload(
        TimetableTemplate $template,
        Collection $entries,
        Carbon|string|null $date,
    ): array {
        $date = Carbon::parse($date ?? now())->startOfDay();
        $selectedDay = $template->dayForDate($date);

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'cycle_type' => $template->cycle_type,
                'cycle_length' => $template->cycle_length,
                'academic_year' => $template->academicYear?->year_name,
            ],
            'date' => $date->toDateString(),
            'selected_day_number' => $selectedDay?->day_number,
            'selected_day_name' => $selectedDay?->name,
            'days' => $template->days
                ->map(fn (TimetableDay $day) => $this->dayPayload(
                    $day,
                    $entries->where('timetable_day_id', $day->id)->values(),
                ))
                ->values(),
        ];
    }

    private function dayPayload(TimetableDay $day, Collection $entries): array
    {
        $blocks = [];
        $coveredThrough = 0;

        foreach ($day->periods as $period) {
            if ($period->sequence <= $coveredThrough) {
                continue;
            }

            if (! $period->isTeaching()) {
                $blocks[] = $this->periodBlock($period);

                continue;
            }

            $entry = $entries->firstWhere('start_period_id', $period->id);

            if (! $entry) {
                $blocks[] = [
                    ...$this->periodBlock($period),
                    'kind' => 'free',
                    'title' => 'Free period',
                ];

                continue;
            }

            $coveredThrough = (int) $entry->endPeriod->sequence;
            $blocks[] = [
                'kind' => 'lesson',
                'period_id' => $period->id,
                'period_name' => $period->name,
                'end_period_name' => $entry->endPeriod->name,
                'start_time' => Carbon::parse($period->start_time)->format('H:i'),
                'end_time' => Carbon::parse($entry->endPeriod->end_time)->format('H:i'),
                'duration_minutes' => Carbon::parse($period->start_time)
                    ->diffInMinutes(Carbon::parse($entry->endPeriod->end_time)),
                'entry_id' => $entry->id,
                'title' => $entry->title ?: $entry->subject?->name,
                'subject' => $entry->subject?->name,
                'subject_code' => $entry->subject?->code,
                'teacher' => $entry->teacher?->user?->name,
                'class' => $entry->class?->name,
                'group' => $entry->group?->name,
                'room' => $entry->room?->name,
                'notes' => $entry->notes,
            ];
        }

        return [
            'id' => $day->id,
            'day_number' => $day->day_number,
            'name' => $day->name,
            'weekday' => $day->weekday,
            'blocks' => $blocks,
        ];
    }

    private function periodBlock(TimetablePeriod $period): array
    {
        return [
            'kind' => $period->isTeaching() ? 'free' : 'event',
            'period_id' => $period->id,
            'period_name' => $period->name,
            'start_time' => Carbon::parse($period->start_time)->format('H:i'),
            'end_time' => Carbon::parse($period->end_time)->format('H:i'),
            'duration_minutes' => Carbon::parse($period->start_time)
                ->diffInMinutes(Carbon::parse($period->end_time)),
            'title' => $period->name,
            'event_type' => $period->type,
        ];
    }

    private function emptyPayload(Carbon|string|null $date): array
    {
        return [
            'template' => null,
            'date' => Carbon::parse($date ?? now())->toDateString(),
            'selected_day_number' => null,
            'selected_day_name' => null,
            'days' => [],
        ];
    }
}
