<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the grid draws, in one payload.
 *
 * Every class has exactly one visual row. Parallel option lessons can share a period,
 * but the browser fits those cards inside that row instead of increasing its height.
 */
class GridPayload
{
    public function __construct(private readonly CardPlacementService $placement = new CardPlacementService) {}

    public function build(Setting $setting): array
    {
        $lessons = $this->lessons($setting);
        $classes = $this->classesOf($setting);
        $schoolAssignments = $this->schoolAssignments($setting, $classes);
        $divisionClassIds = $classes
            ->flatMap(fn (ClassModel $class) => $class->timetableDivisions)
            ->groupBy(fn ($division) => $division->shared_key ?: 'division-'.$division->id)
            ->map(fn (Collection $divisions) => $divisions
                ->pluck('class_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all());
        $colours = $this->teacherColours();
        $shortNames = $this->subjectShortNames();
        $baseRooms = DB::table('tt_class_base_rooms')
            ->where('tt_setting_id', $setting->id)
            ->pluck('tt_room_id', 'class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $cards = $this->cards($lessons, $colours, $shortNames);

        return [
            'setting' => [
                'id' => (int) $setting->id,
                'name' => $setting->name,
                'term_label' => $setting->term_label,
                'cycle_length' => (int) $setting->cycle_length,
                'schedule_type' => $setting->schedule_type,
                'schedule_label' => $setting->typeLabel(),
                'cycle_anchor_date' => $setting->cycle_anchor_date?->toDateString(),
                'cycle_anchor_day' => (int) $setting->cycle_anchor_day,
                'is_published' => (bool) $setting->is_published,
            ],
            'days' => $this->days($setting),
            'periods' => $this->periods($setting),
            'breaks' => $this->breaks($setting),
            'classes' => $classes->map(fn (ClassModel $class) => [
                'id' => (int) $class->id,
                'name' => $class->name,
                'lanes' => 1,
                'base_room_id' => $baseRooms[$class->id] ?? null,
                'subject_ids' => $schoolAssignments['subjects_by_class'][$class->id] ?? [],
                'divisions' => $class->timetableDivisions
                    ->sortBy('division_tag')
                    ->map(function ($division) use ($divisionClassIds) {
                        $sharedKey = $division->shared_key ?: 'division-'.$division->id;

                        return [
                            'id' => (int) $division->id,
                            'name' => $division->name ?: 'Division '.$division->division_tag,
                            'tag' => (int) $division->division_tag,
                            'shared_key' => $sharedKey,
                            'class_ids' => $divisionClassIds->get($sharedKey, [(int) $division->class_id]),
                            'groups' => $division->groups->sortBy('id')->map(fn (Group $group) => [
                                'id' => (int) $group->id,
                                'name' => $group->name,
                                'shared_key' => $group->shared_key ?: 'group-'.$group->id,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
            ])->values()->all(),
            'cards' => $cards->map(fn (array $card) => $card + ['lane' => 0])
                ->values()->all(),
            'tray' => $this->tray($lessons, $colours, $shortNames),
            'editor' => $this->editorOptions($setting, $baseRooms, $lessons) + [
                'teacher_assignments' => $schoolAssignments['teacher_assignments'],
            ],
        ];
    }

    /**
     * @return Collection<int, Lesson>
     */
    private function lessons(Setting $setting): Collection
    {
        return $setting->lessons()
            ->with([
                'subject',
                'teachers.user',
                'classes',
                'groups.division',
                'rooms',
                'cards.room',
                'weeksDef',
                'termsDef',
                'setting',
            ])
            ->get();
    }

    /** @return Collection<int, ClassModel> */
    private function classesOf(Setting $setting): Collection
    {
        // The timetable is built for the school's classes, not merely for the subset
        // that already has an imported lesson. A newly-created class must immediately
        // have a row where work can later be placed.
        return ClassModel::query()
            ->with(['timetableDivisions.groups'])
            ->where('academic_year_id', $setting->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Reuse the school's existing curriculum and enrolment records when a timetable
     * lesson is created. The three tables overlap intentionally: older schools may have
     * one of them populated more completely than the others.
     *
     * @param  Collection<int, ClassModel>  $classes
     * @return array{subjects_by_class: array<int, list<int>>, teacher_assignments: list<array<string, mixed>>}
     */
    private function schoolAssignments(Setting $setting, Collection $classes): array
    {
        $classIds = $classes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $yearId = (int) $setting->academic_year_id;
        $subjectsByClass = collect($classIds)->mapWithKeys(fn (int $id) => [$id => collect()]);

        $classSubjects = DB::table('class_subjects')
            ->where('academic_year_id', $yearId)
            ->whereIn('class_id', $classIds)
            ->get(['class_id', 'subject_id']);
        $teacherSubjects = DB::table('teacher_subjects')
            ->where('academic_year_id', $yearId)
            ->whereIn('class_id', $classIds)
            ->get(['class_id', 'subject_id', 'teacher_id', 'is_primary']);
        $studentSubjects = DB::table('student_subjects')
            ->where('academic_year_id', $yearId)
            ->whereIn('class_id', $classIds)
            ->selectRaw('class_id, subject_id, teacher_id, COUNT(DISTINCT student_id) as student_count')
            ->groupBy('class_id', 'subject_id', 'teacher_id')
            ->get();
        $activeTeacherIds = Teacher::query()
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        foreach ($classSubjects->concat($teacherSubjects)->concat($studentSubjects) as $assignment) {
            $subjectsByClass->get((int) $assignment->class_id)?->push((int) $assignment->subject_id);
        }

        $studentCounts = $studentSubjects->keyBy(fn ($row) => implode(':', [
            (int) $row->class_id,
            (int) $row->subject_id,
            (int) $row->teacher_id,
        ]));

        $teacherAssignments = $teacherSubjects
            ->map(fn ($row) => [
                'class_id' => (int) $row->class_id,
                'subject_id' => (int) $row->subject_id,
                'teacher_id' => (int) $row->teacher_id,
                'is_primary' => (bool) $row->is_primary,
                'student_count' => (int) ($studentCounts->get(implode(':', [
                    (int) $row->class_id,
                    (int) $row->subject_id,
                    (int) $row->teacher_id,
                ]))?->student_count ?? 0),
            ])
            ->concat($studentSubjects->map(fn ($row) => [
                'class_id' => (int) $row->class_id,
                'subject_id' => (int) $row->subject_id,
                'teacher_id' => (int) $row->teacher_id,
                'is_primary' => false,
                'student_count' => (int) $row->student_count,
            ]))
            ->unique(fn (array $row) => implode(':', [$row['class_id'], $row['subject_id'], $row['teacher_id']]))
            ->filter(fn (array $row) => $activeTeacherIds->contains($row['teacher_id']))
            ->values()
            ->all();

        return [
            'subjects_by_class' => $subjectsByClass
                ->map(fn (Collection $ids) => $ids->unique()->sort()->values()->all())
                ->all(),
            'teacher_assignments' => $teacherAssignments,
        ];
    }

    /**
     * One entry per card on the grid — a double is one entry spanning two periods.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  array<int, string>  $colours keyed by teacher id
     * @param  array<int, string>  $shortNames
     * @return Collection<int, array<string, mixed>>
     */
    private function cards(Collection $lessons, array $colours, array $shortNames): Collection
    {
        $cards = collect();

        foreach ($lessons as $lesson) {
            foreach ($this->placement->unitsFor($lesson) as $unit) {
                $cards->push($this->describe($lesson, $colours, $shortNames) + [
                    'key' => implode('-', $unit->cardIds()),
                    'card_ids' => $unit->cardIds(),
                    'day' => $unit->dayNumber(),
                    'period' => $unit->startPeriod(),
                    'span' => $unit->span(),
                    'room' => $unit->first()->room?->name,
                    'room_id' => $unit->roomId(),
                    'locked' => $unit->isLocked(),
                ]);
            }
        }

        return $cards;
    }

    /**
     * Lessons still owing cards, with how many.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  array<int, string>  $colours keyed by teacher id
     * @param  array<int, string>  $shortNames
     * @return list<array<string, mixed>>
     */
    private function tray(Collection $lessons, array $colours, array $shortNames): array
    {
        // Prepared lessons store individual occurrences so that one double can be joint
        // or split independently. Present interchangeable occurrences as counted stacks;
        // keep the first unplaced lesson as the concrete occurrence dragged from a stack.
        $signatures = $lessons->mapWithKeys(fn (Lesson $lesson) => [
            $lesson->id => $this->traySignature($lesson),
        ]);
        $splitSignatures = $lessons->filter(fn (Lesson $lesson) => $lesson->split_key !== null)
            ->groupBy('split_key')
            ->map(fn (Collection $members) => $members
                ->map(fn (Lesson $lesson) => $signatures[$lesson->id])->sort()->values()->all());

        return $lessons
            ->map(fn (Lesson $lesson) => $this->describe($lesson, $colours, $shortNames) + [
                'unplaced' => $this->placement->unplacedCount($lesson),
                'room' => $lesson->rooms->first()?->name,
                'room_id' => $lesson->rooms->first()?->id,
                'stack_key' => $lesson->preparation_key === null
                    ? 'lesson-'.$lesson->id
                    : 'prepared-'.hash('sha256', json_encode([
                        $signatures[$lesson->id],
                        $lesson->split_key ? $splitSignatures[$lesson->split_key] : null,
                    ], JSON_THROW_ON_ERROR)),
            ])
            ->filter(fn (array $entry) => $entry['unplaced'] > 0)
            ->groupBy('stack_key')
            ->map(function (Collection $entries): array {
                $stack = $entries->first();
                $stack['unplaced'] = (int) $entries->sum('unplaced');

                return $stack;
            })
            ->sortBy([['class_names', 'asc'], ['subject', 'asc']])
            ->values()
            ->all();
    }

    private function traySignature(Lesson $lesson): string
    {
        return json_encode([
            'subject' => (int) $lesson->subject_id,
            'teachers' => $lesson->teachers->pluck('id')->sort()->values()->all(),
            'classes' => $lesson->classes->pluck('id')->sort()->values()->all(),
            'groups' => $lesson->groups->pluck('id')->sort()->values()->all(),
            // Preserve room preference order as well as room availability.
            'rooms' => $lesson->rooms->pluck('id')->values()->all(),
            'span' => (int) $lesson->periods_per_card,
            'days' => $lesson->tt_daysdef_id,
            'weeks' => $lesson->tt_weeksdef_id,
            'terms' => $lesson->tt_termsdef_id,
            'capacity' => $lesson->capacity,
            'seminar' => $lesson->seminar_group,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * The face of a card: what the admin reads before deciding where it goes.
     *
     * @param  array<int, string>  $colours keyed by teacher id
     * @param  array<int, string>  $shortNames
     * @return array<string, mixed>
     */
    private function describe(Lesson $lesson, array $colours, array $shortNames): array
    {
        $subjectId = (int) $lesson->subject_id;
        $teacherId = $lesson->teachers->pluck('id')->map(fn ($id) => (int) $id)->sort()->first();

        return [
            'lesson_id' => (int) $lesson->id,
            'preparation_key' => $lesson->preparation_key,
            'split_key' => $lesson->split_key,
            'subject_id' => $subjectId,
            'subject' => $lesson->subject?->name ?? 'Subject',
            'subject_short' => $shortNames[$subjectId]
                ?? $lesson->subject?->code
                ?? mb_strtoupper(mb_substr((string) $lesson->subject?->name, 0, 3)),
            'colour' => $teacherId ? ($colours[$teacherId] ?? null) : null,
            'colour_key' => $teacherId ? 'teacher-'.$teacherId : 'teacher-unassigned',
            'teachers' => $lesson->teachers->map(fn ($t) => $t->user?->name)->filter()->values()->all(),
            'teacher_ids' => $lesson->teachers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'room_ids' => $lesson->rooms->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'class_ids' => $lesson->classes->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'class_names' => $lesson->classes->pluck('name')->implode(', '),
            'groups' => $lesson->groups->map(fn (Group $group) => [
                'id' => (int) $group->id,
                'class_id' => (int) $group->class_id,
                'name' => $group->name,
                'entire_class' => (bool) $group->entire_class,
                'division_id' => $group->tt_division_id === null ? null : (int) $group->tt_division_id,
                'shared_key' => $group->shared_key ?: 'group-'.$group->id,
            ])->values()->all(),
            'span' => max(1, (int) $lesson->periods_per_card),
            'periods_per_week' => (float) $lesson->periods_per_week,
            'cards_per_cycle' => $lesson->cardsRequired(),
        ];
    }

    /**
     * @param  array<int, int>  $baseRooms
     * @param  Collection<int, Lesson>  $lessons
     * @return array<string, mixed>
     */
    private function editorOptions(Setting $setting, array $baseRooms, Collection $lessons): array
    {
        return [
            'subjects' => Subject::query()
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Subject $subject) => [
                    'id' => (int) $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                ])->values()->all(),
            'teachers' => Teacher::query()
                ->with('user:id,name,status')
                ->get()
                ->filter(fn (Teacher $teacher) => $teacher->user !== null && $teacher->user->status === 'active')
                ->sortBy(fn (Teacher $teacher) => $teacher->user->name)
                ->map(fn (Teacher $teacher) => [
                    'id' => (int) $teacher->id,
                    'name' => $teacher->user->name,
                ])->values()->all(),
            'rooms' => $setting->rooms()
                ->orderBy('name')
                ->get(['id', 'name', 'short_name', 'capacity'])
                ->map(fn ($room) => [
                    'id' => (int) $room->id,
                    'name' => $room->name,
                    'short_name' => $room->short_name,
                    'capacity' => $room->capacity === null ? null : (int) $room->capacity,
                ])->values()->all(),
            'base_rooms' => $baseRooms,
            // Creating another set of cards for a subject should start from the
            // lesson details already saved in this timetable. This is deliberately
            // separate from cards/tray: a fully placed lesson and a wholly unplaced
            // lesson provide the same reusable editor preset.
            'lesson_presets' => $lessons->map(fn (Lesson $lesson) => [
                'lesson_id' => (int) $lesson->id,
                'subject_id' => (int) $lesson->subject_id,
                'teacher_ids' => $lesson->teachers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'room_ids' => $lesson->rooms->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'class_ids' => $lesson->classes->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                'groups' => $lesson->groups->map(fn (Group $group) => [
                    'id' => (int) $group->id,
                    'class_id' => (int) $group->class_id,
                    'entire_class' => (bool) $group->entire_class,
                ])->values()->all(),
                'periods_per_week' => (float) $lesson->periods_per_week,
                'periods_per_card' => max(1, (int) $lesson->periods_per_card),
                'cards_per_cycle' => $lesson->cardsRequired(),
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array{number: int, label: string}>
     */
    private function days(Setting $setting): array
    {
        $cycle = max(1, (int) $setting->cycle_length);

        // A 5-day cycle is a normal week and reads better named; anything else is a
        // rotating cycle, where "Day 3" is the only honest label.
        $names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return collect(range(1, $cycle))->map(fn (int $number) => [
            'number' => $number,
            'label' => $cycle === 5 ? $names[$number - 1] : 'Day '.$number,
            'short' => $cycle === 5 ? substr($names[$number - 1], 0, 3) : 'D'.$number,
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function periods(Setting $setting): array
    {
        return $setting->periods()->get()->map(fn ($period) => [
            'number' => (int) $period->period_number,
            'name' => $period->name,
            'short' => $period->short_name ?: (string) $period->period_number,
            'start' => substr((string) $period->start_time, 0, 5),
            'end' => substr((string) $period->end_time, 0, 5),
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function breaks(Setting $setting): array
    {
        return $setting->breaks()->get()->map(fn ($break) => [
            'after_period' => (int) $break->after_period,
            'name' => $break->name,
            'short' => $break->short_name ?: 'BK',
            'start' => substr((string) $break->start_time, 0, 5),
            'end' => substr((string) $break->end_time, 0, 5),
        ])->filter(fn (array $break) => $break['after_period'] > 0)->values()->all();
    }

    /**
     * A teacher keeps one colour across every subject and class they teach.
     *
     * @return array<int, string>
     */
    private function teacherColours(): array
    {
        return DB::table('tt_teacher_meta')
            ->whereNotNull('colour')
            ->pluck('colour', 'teacher_id')
            ->map(fn ($colour) => (string) $colour)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function subjectShortNames(): array
    {
        return DB::table('tt_subject_meta')
            ->whereNotNull('short_name')
            ->pluck('short_name', 'subject_id')
            ->map(fn ($name) => (string) $name)
            ->all();
    }
}
