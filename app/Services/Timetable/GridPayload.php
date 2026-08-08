<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the grid draws, in one payload.
 *
 * The interesting part is the sub-rows. A class row is one line tall until a division
 * splits it, and then a slot holding BIO A, CHE A, SET 1 and SET 2 has to show four cards
 * stacked — that is the layout in docs/asc.png and the reason the legacy grid could not
 * be reused.
 *
 * Lanes are assigned per division rather than per class, so a class with a 4-way option
 * block and a separate 2-way science block is four lines tall, not six: the two blocks
 * never occupy the same period (that would be a student clash), so they share the lanes.
 * Within a division the lane follows the group id, which keeps a given group on the same
 * line all week — that stability is most of what makes the grid readable.
 */
class GridPayload
{
    public function __construct(private readonly CardPlacementService $placement = new CardPlacementService) {}

    public function build(Setting $setting): array
    {
        $lessons = $this->lessons($setting);
        $classes = $this->classesOf($lessons);
        $colours = $this->subjectColours();
        $shortNames = $this->subjectShortNames();

        $cards = $this->cards($lessons, $colours, $shortNames);
        $lanes = $this->assignLanes($cards, $classes);

        return [
            'setting' => [
                'id' => (int) $setting->id,
                'name' => $setting->name,
                'term_label' => $setting->term_label,
                'cycle_length' => (int) $setting->cycle_length,
                'is_published' => (bool) $setting->is_published,
            ],
            'days' => $this->days($setting),
            'periods' => $this->periods($setting),
            'breaks' => $this->breaks($setting),
            'classes' => $classes->map(fn (ClassModel $class) => [
                'id' => (int) $class->id,
                'name' => $class->name,
                'lanes' => $lanes['height'][$class->id] ?? 1,
            ])->values()->all(),
            'cards' => $cards->map(fn (array $card) => $card + ['lane' => $lanes['lane'][$card['key']] ?? 0])
                ->values()->all(),
            'tray' => $this->tray($lessons, $colours, $shortNames),
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

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return Collection<int, ClassModel>
     */
    private function classesOf(Collection $lessons): Collection
    {
        return $lessons
            ->flatMap(fn (Lesson $lesson) => $lesson->classes)
            ->unique('id')
            ->sortBy([['level', 'asc'], ['name', 'asc']])
            ->values();
    }

    /**
     * One entry per card on the grid — a double is one entry spanning two periods.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  array<int, string>  $colours
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
     * @param  array<int, string>  $colours
     * @param  array<int, string>  $shortNames
     * @return list<array<string, mixed>>
     */
    private function tray(Collection $lessons, array $colours, array $shortNames): array
    {
        return $lessons
            ->map(fn (Lesson $lesson) => $this->describe($lesson, $colours, $shortNames) + [
                'unplaced' => $this->placement->unplacedCount($lesson),
                'room' => $lesson->rooms->first()?->name,
                'room_id' => $lesson->rooms->first()?->id,
            ])
            ->filter(fn (array $entry) => $entry['unplaced'] > 0)
            ->sortBy([['class_names', 'asc'], ['subject', 'asc']])
            ->values()
            ->all();
    }

    /**
     * The face of a card: what the admin reads before deciding where it goes.
     *
     * @param  array<int, string>  $colours
     * @param  array<int, string>  $shortNames
     * @return array<string, mixed>
     */
    private function describe(Lesson $lesson, array $colours, array $shortNames): array
    {
        $subjectId = (int) $lesson->subject_id;

        return [
            'lesson_id' => (int) $lesson->id,
            'subject' => $lesson->subject?->name ?? 'Subject',
            'subject_short' => $shortNames[$subjectId]
                ?? $lesson->subject?->code
                ?? mb_strtoupper(mb_substr((string) $lesson->subject?->name, 0, 3)),
            'colour' => $colours[$subjectId] ?? null,
            'teachers' => $lesson->teachers->map(fn ($t) => $t->user?->name)->filter()->values()->all(),
            'class_ids' => $lesson->classes->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'class_names' => $lesson->classes->pluck('name')->implode(', '),
            'groups' => $lesson->groups->map(fn (Group $group) => [
                'id' => (int) $group->id,
                'class_id' => (int) $group->class_id,
                'name' => $group->name,
                'entire_class' => (bool) $group->entire_class,
                'division_id' => $group->tt_division_id === null ? null : (int) $group->tt_division_id,
            ])->values()->all(),
            'span' => max(1, (int) $lesson->periods_per_card),
            'periods_per_week' => (float) $lesson->periods_per_week,
        ];
    }

    /**
     * Which sub-row each card sits on, and how many sub-rows each class row needs.
     *
     * @param  Collection<int, array<string, mixed>>  $cards
     * @param  Collection<int, ClassModel>  $classes
     * @return array{lane: array<string, int>, height: array<int, int>}
     */
    private function assignLanes(Collection $cards, Collection $classes): array
    {
        $lane = [];
        $height = [];

        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $mine = $cards->filter(fn (array $card) => in_array($classId, $card['class_ids'], true));

            $order = $this->laneOrder($mine, $classId);

            // Row height is the busiest slot, not the number of groups: two divisions
            // never share a period, so their lanes overlap rather than stack.
            $busiest = 1;
            $taken = [];

            foreach ($mine->sortBy(fn (array $card) => [$card['day'], $card['period']]) as $card) {
                $group = $this->groupOf($card, $classId);
                $wanted = $group === null ? 0 : ($order[$group['id']] ?? 0);

                $slots = $this->slotsOf($card);

                // Two cards wanting the same lane in one slot can only happen with data
                // the checker would have refused; bump rather than draw them on top of
                // each other, so nothing is silently invisible.
                while ($this->laneTaken($taken, $slots, $wanted)) {
                    $wanted++;
                }

                foreach ($slots as $slot) {
                    $taken[$slot][$wanted] = true;
                    $busiest = max($busiest, count($taken[$slot]));
                }

                $lane[$card['key']] = $wanted;
            }

            $height[$classId] = $busiest;
        }

        return ['lane' => $lane, 'height' => $height];
    }

    /**
     * Groups of one division, ordered by id, take lanes 0..n-1 — the same line all week.
     *
     * @param  Collection<int, array<string, mixed>>  $cards
     * @return array<int, int> group id => lane
     */
    private function laneOrder(Collection $cards, int $classId): array
    {
        $byDivision = [];

        foreach ($cards as $card) {
            $group = $this->groupOf($card, $classId);

            if ($group === null || $group['entire_class'] || $group['division_id'] === null) {
                continue;
            }

            $byDivision[$group['division_id']][$group['id']] = true;
        }

        $order = [];

        foreach ($byDivision as $groupIds) {
            $ids = array_keys($groupIds);
            sort($ids);

            foreach ($ids as $index => $groupId) {
                $order[$groupId] = $index;
            }
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>|null
     */
    private function groupOf(array $card, int $classId): ?array
    {
        foreach ($card['groups'] as $group) {
            if ((int) $group['class_id'] === $classId) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $card
     * @return list<string>
     */
    private function slotsOf(array $card): array
    {
        $slots = [];

        foreach (range(0, $card['span'] - 1) as $offset) {
            $slots[] = $card['day'].':'.($card['period'] + $offset);
        }

        return $slots;
    }

    /**
     * @param  array<string, array<int, bool>>  $taken
     * @param  list<string>  $slots
     */
    private function laneTaken(array $taken, array $slots, int $lane): bool
    {
        foreach ($slots as $slot) {
            if (isset($taken[$slot][$lane])) {
                return true;
            }
        }

        return false;
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
     * tt_subject_meta has no model — it is a lookup the importer fills, read here as one.
     *
     * @return array<int, string>
     */
    private function subjectColours(): array
    {
        return DB::table('tt_subject_meta')
            ->whereNotNull('colour')
            ->pluck('colour', 'subject_id')
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
