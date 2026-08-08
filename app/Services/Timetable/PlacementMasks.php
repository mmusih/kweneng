<?php

namespace App\Services\Timetable;

use App\Models\Tt\Lesson;
use App\Support\Timetable\Bitmask;

/**
 * The three masks a card carries: which days, weeks and terms it runs on.
 *
 * A placement fixes the day — you drop a card on Day 3, not on "any day" — so the days
 * mask always has exactly one bit set. Weeks and terms come from the lesson's defs,
 * because a lesson that only runs in Term 1 keeps that restriction wherever it lands.
 *
 * Two placements can only collide if all three masks intersect. A card on Day 1 and a
 * card on Day 2 never fight over a room, however identical everything else is.
 */
final class PlacementMasks
{
    public function __construct(
        public readonly Bitmask $days,
        public readonly Bitmask $weeks,
        public readonly Bitmask $terms,
    ) {}

    public static function forLesson(Lesson $lesson, int $dayNumber): self
    {
        $width = (int) ($lesson->setting?->cycle_length ?? 6);

        return new self(
            Bitmask::fromDayNumber($dayNumber, max(1, $width)),
            self::firstMask($lesson->weeksDef?->weeks),
            self::firstMask($lesson->termsDef?->terms),
        );
    }

    /**
     * Rebuild the masks of a card already in the database.
     */
    public static function fromStrings(string $days, string $weeks, string $terms, int $cycleLength): self
    {
        return new self(
            new Bitmask($days, max(1, $cycleLength)),
            new Bitmask($weeks, max(1, strlen($weeks))),
            new Bitmask($terms, max(1, strlen($terms))),
        );
    }

    public function intersects(self $other): bool
    {
        return $this->days->intersects($other->days)
            && $this->weeks->intersects($other->weeks)
            && $this->terms->intersects($other->terms);
    }

    /**
     * A def may offer alternatives ("100000,010000,…"); the first is the one a new card
     * takes. Absent a def at all, the lesson runs every week and every term, which is
     * the single-bit "1" this school's file uses throughout.
     */
    private static function firstMask(?string $raw): Bitmask
    {
        $first = trim(explode(',', (string) $raw)[0] ?? '');

        if ($first === '') {
            return new Bitmask('1', 1);
        }

        return new Bitmask($first, strlen($first));
    }
}
