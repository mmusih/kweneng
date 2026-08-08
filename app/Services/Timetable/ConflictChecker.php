<?php

namespace App\Services\Timetable;

use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Card;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Period;
use Illuminate\Support\Collection;

/**
 * Whether a card may sit at a given day and period.
 *
 * Four rules. A teacher cannot be in two places, a room cannot hold two lessons, a
 * student cannot attend two lessons, and a card must fit inside the day it is dropped on.
 *
 * The student rule is the one the legacy system could not express, and it is why this
 * class exists rather than a tweak to TimetableService. Four option groups of one
 * division — BIO A, CHE A, and two Setswana sets — are *meant* to run in the same period;
 * that is the entire point of a division. The old row-per-class model read that as four
 * collisions and refused to build the timetable this school actually runs.
 *
 * Membership is not loaded to decide it. Groups of one division are disjoint by
 * construction, so the structure answers the question and 128 roster lookups per drag do
 * not have to.
 */
class ConflictChecker
{
    /**
     * Cards to check against, when a caller has already loaded them.
     *
     * @var Collection<int, Card>|null
     */
    private ?Collection $pool = null;

    /** @var array<int, list<int>> setting id => period numbers */
    private array $periodCache = [];

    /** @var array<int, list<int>> setting id => break after_period values */
    private array $breakCache = [];

    /**
     * A checker that reads one preloaded set of cards instead of querying per call.
     *
     * Painting the grid on pick-up asks about every slot — 6 days by 8 periods is 48
     * questions, and 48 near-identical queries for what is one small table. The rules are
     * unchanged; only where the occupants come from is.
     *
     * @param  Collection<int, Card>  $cards  every card of the setting, relations loaded
     */
    public function withPool(Collection $cards): self
    {
        $clone = clone $this;
        $clone->pool = $cards;

        return $clone;
    }

    /**
     * Every reason this lesson cannot start at this period on this day.
     *
     * @param  list<int>  $ignoreCardIds  cards belonging to the placement being moved
     * @return list<Conflict>
     */
    public function check(
        Lesson $lesson,
        int $dayNumber,
        int $startPeriod,
        ?int $roomId = null,
        array $ignoreCardIds = [],
    ): array {
        $lesson->loadMissing(['setting', 'weeksDef', 'termsDef', 'teachers.user', 'classes', 'groups', 'subject']);

        $span = $this->span($lesson);
        $periods = range($startPeriod, $startPeriod + $span - 1);

        if ($structural = $this->structuralConflicts($lesson, $periods, $span)) {
            return $structural;
        }

        $masks = PlacementMasks::forLesson($lesson, $dayNumber);
        $cycleLength = (int) ($lesson->setting?->cycle_length ?? 6);

        $conflicts = [];

        foreach ($this->occupants($lesson, $periods, $ignoreCardIds) as $card) {
            $other = $card->lesson;

            if ($other === null) {
                continue;
            }

            $cardMasks = PlacementMasks::fromStrings(
                (string) $card->days,
                (string) $card->weeks,
                (string) $card->terms,
                $cycleLength,
            );

            if (! $masks->intersects($cardMasks)) {
                continue;
            }

            foreach ($this->collisionsBetween($lesson, $other, $card, $roomId) as $conflict) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    public function allows(
        Lesson $lesson,
        int $dayNumber,
        int $startPeriod,
        ?int $roomId = null,
        array $ignoreCardIds = [],
    ): bool {
        return $this->check($lesson, $dayNumber, $startPeriod, $roomId, $ignoreCardIds) === [];
    }

    /**
     * How many consecutive periods one card of this lesson occupies. A double is 2.
     */
    public function span(Lesson $lesson): int
    {
        return max(1, (int) ($lesson->periods_per_card ?: 1));
    }

    /**
     * @param  list<int>  $periods
     * @return list<Conflict>
     */
    private function structuralConflicts(Lesson $lesson, array $periods, int $span): array
    {
        $settingId = (int) $lesson->tt_setting_id;

        $missing = array_diff($periods, $this->availablePeriods($settingId));

        if ($missing !== []) {
            return [new Conflict(
                Conflict::STRUCTURE,
                $span > 1
                    ? "A double period cannot start here — it would run past the end of the day."
                    : 'That period does not exist in this timetable.',
                null,
                $periods[0],
            )];
        }

        // A double either side of morning break is two separate lessons in practice; the
        // school treats break as a hard divider, so the grid must not offer it as a slot.
        if ($span > 1) {
            foreach ($this->breakAfterPeriods($settingId) as $afterPeriod) {
                if ($afterPeriod >= $periods[0] && $afterPeriod < end($periods)) {
                    return [new Conflict(
                        Conflict::STRUCTURE,
                        'A double period cannot run across a break.',
                        null,
                        $periods[0],
                    )];
                }
            }
        }

        return [];
    }

    /**
     * The day structure. Memoised only while a pool is set: a sweep is read-only and asks
     * the same question 48 times, whereas a plain checker may be called either side of a
     * period change and must not answer from a stale list.
     *
     * @return list<int>
     */
    private function availablePeriods(int $settingId): array
    {
        if ($this->pool !== null && isset($this->periodCache[$settingId])) {
            return $this->periodCache[$settingId];
        }

        $periods = Period::query()
            ->where('tt_setting_id', $settingId)
            ->pluck('period_number')
            ->map(fn ($n) => (int) $n)
            ->all();

        return $this->periodCache[$settingId] = $periods;
    }

    /**
     * @return list<int>
     */
    private function breakAfterPeriods(int $settingId): array
    {
        if ($this->pool !== null && isset($this->breakCache[$settingId])) {
            return $this->breakCache[$settingId];
        }

        $breaks = BreakPeriod::query()
            ->where('tt_setting_id', $settingId)
            ->pluck('after_period')
            ->map(fn ($n) => (int) $n)
            ->all();

        return $this->breakCache[$settingId] = $breaks;
    }

    /**
     * @param  list<int>  $periods
     * @param  list<int>  $ignoreCardIds
     * @return Collection<int, Card>
     */
    private function occupants(Lesson $lesson, array $periods, array $ignoreCardIds): Collection
    {
        if ($this->pool !== null) {
            return $this->pool->filter(
                fn (Card $card) => in_array((int) $card->period_number, $periods, true)
                    && ! in_array((int) $card->id, $ignoreCardIds, true)
                    && (int) $card->lesson?->tt_setting_id === (int) $lesson->tt_setting_id,
            )->values();
        }

        return Card::query()
            ->whereIn('period_number', $periods)
            ->whereHas('lesson', fn ($q) => $q->where('tt_setting_id', $lesson->tt_setting_id))
            ->when($ignoreCardIds !== [], fn ($q) => $q->whereNotIn('id', $ignoreCardIds))
            ->with(['lesson.teachers.user', 'lesson.classes', 'lesson.groups', 'lesson.subject', 'room'])
            ->get();
    }

    /**
     * Every card of a setting, with the relations the rules read — the argument to
     * withPool().
     *
     * @return Collection<int, Card>
     */
    public static function poolFor(int $settingId): Collection
    {
        return Card::query()
            ->whereHas('lesson', fn ($q) => $q->where('tt_setting_id', $settingId))
            ->with(['lesson.teachers.user', 'lesson.classes', 'lesson.groups', 'lesson.subject', 'room'])
            ->get();
    }

    /**
     * @return list<Conflict>
     */
    private function collisionsBetween(Lesson $lesson, Lesson $other, Card $card, ?int $roomId): array
    {
        $conflicts = [];
        $where = $other->subject?->name ?? 'another lesson';
        $classes = $other->classes->pluck('name')->implode(', ');

        $sharedTeachers = $lesson->teachers->pluck('id')->intersect($other->teachers->pluck('id'));

        if ($sharedTeachers->isNotEmpty()) {
            $name = $other->teachers->firstWhere('id', $sharedTeachers->first())?->user?->name ?? 'That teacher';

            $conflicts[] = new Conflict(
                Conflict::TEACHER,
                "{$name} already teaches {$where}".($classes !== '' ? " to {$classes}" : '').' then.',
                $card->id,
                $card->period_number,
            );
        }

        if ($roomId !== null && (int) $card->tt_room_id === $roomId) {
            $room = $card->room?->name ?? 'That room';

            $conflicts[] = new Conflict(
                Conflict::ROOM,
                "{$room} is already taken by {$where}".($classes !== '' ? " ({$classes})" : '').' then.',
                $card->id,
                $card->period_number,
            );
        }

        if ($clash = $this->studentClash($lesson, $other)) {
            $conflicts[] = new Conflict(
                Conflict::STUDENTS,
                "{$clash} already has {$where} then.",
                $card->id,
                $card->period_number,
            );
        }

        return $conflicts;
    }

    /**
     * The name of the class whose students would be double-booked, or null if none are.
     *
     * Two lessons only compete for students where they share a class. Within a shared
     * class it comes down to the groups, and the table below is the whole rule:
     *
     *   same group ................................. clash
     *   either is the entire class ................. clash
     *   different groups, same division ............ NO clash  (parallel option groups)
     *   different groups, different divisions ...... clash     (a student may take both)
     */
    private function studentClash(Lesson $lesson, Lesson $other): ?string
    {
        $shared = $lesson->classes->keyBy('id')->intersectByKeys($other->classes->keyBy('id'));

        foreach ($shared as $classId => $class) {
            $mine = $lesson->groups->where('class_id', $classId);
            $theirs = $other->groups->where('class_id', $classId);

            // No group named for this class means the lesson takes the class whole.
            if ($mine->isEmpty() || $theirs->isEmpty()) {
                return $class->name;
            }

            foreach ($mine as $a) {
                foreach ($theirs as $b) {
                    if ($this->groupsOverlap($a, $b)) {
                        return $class->name;
                    }
                }
            }
        }

        return null;
    }

    private function groupsOverlap(Group $a, Group $b): bool
    {
        if ($a->id === $b->id || $a->entire_class || $b->entire_class) {
            return true;
        }

        // Disjoint only when both sit in the same division. A group with no division is
        // an unknown quantity, so it is assumed to overlap rather than assumed safe.
        if ($a->tt_division_id !== null && $a->tt_division_id === $b->tt_division_id) {
            return false;
        }

        return true;
    }
}
