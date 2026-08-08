<?php

namespace App\Support\Timetable;

use InvalidArgumentException;
use Stringable;

/**
 * An aSc-style bitmask: a fixed-width string of '0' and '1', stored verbatim in the
 * database so the XML round-trip stays lossless and the column stays readable.
 *
 * Width is always explicit. This school runs a 6-day rotating cycle, so '010000' is
 * Day 2 of 6 — assuming 5 or 7 is the bug this class exists to prevent. Callers take
 * the width from the owning setting's cycle_length, never from a constant.
 *
 * Positions are 1-based, matching the day/week/term numbers users and aSc both use:
 * '010000' has positions() === [2].
 */
final class Bitmask implements Stringable
{
    private readonly string $bits;

    public function __construct(string $mask, private readonly int $width)
    {
        if ($width < 1) {
            throw new InvalidArgumentException('A bitmask needs a width of at least 1.');
        }

        // aSc masks occasionally arrive padded, spaced or short. Normalise rather than
        // reject: a mask narrower than the cycle means "no" for the trailing days.
        $bits = preg_replace('/[^01]/', '', $mask) ?? '';

        $this->bits = substr(str_pad($bits, $width, '0'), 0, $width);
    }

    /**
     * A mask with a single day set — the common case when placing one card.
     */
    public static function fromDayNumber(int $dayNumber, int $width): self
    {
        if ($dayNumber < 1 || $dayNumber > $width) {
            throw new InvalidArgumentException(
                "Day number [{$dayNumber}] is outside a {$width}-day cycle."
            );
        }

        return new self(str_repeat('0', $dayNumber - 1).'1', $width);
    }

    /**
     * @param  list<int>  $positions
     */
    public static function fromPositions(array $positions, int $width): self
    {
        $bits = str_repeat('0', $width);

        foreach ($positions as $position) {
            if ($position < 1 || $position > $width) {
                throw new InvalidArgumentException(
                    "Position [{$position}] is outside a width of {$width}."
                );
            }

            $bits[$position - 1] = '1';
        }

        return new self($bits, $width);
    }

    public static function all(int $width): self
    {
        return new self(str_repeat('1', $width), $width);
    }

    public static function none(int $width): self
    {
        return new self('', $width);
    }

    public function width(): int
    {
        return $this->width;
    }

    /**
     * The 1-based positions that are set, ascending.
     *
     * @return list<int>
     */
    public function positions(): array
    {
        $positions = [];

        for ($i = 0; $i < $this->width; $i++) {
            if ($this->bits[$i] === '1') {
                $positions[] = $i + 1;
            }
        }

        return $positions;
    }

    public function has(int $position): bool
    {
        if ($position < 1 || $position > $this->width) {
            return false;
        }

        return $this->bits[$position - 1] === '1';
    }

    /**
     * Do these two masks share any set position? This is the clash primitive: two cards
     * on the same period collide only if their day masks intersect.
     *
     * Widths may differ (a card's mask against a narrower daysdef); comparison runs to
     * the shorter of the two, where the wider mask's extra positions are unset anyway.
     */
    public function intersects(self $other): bool
    {
        $limit = min($this->width, $other->width);

        for ($i = 0; $i < $limit; $i++) {
            if ($this->bits[$i] === '1' && $other->bits[$i] === '1') {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return substr_count($this->bits, '1');
    }

    public function isEmpty(): bool
    {
        return ! str_contains($this->bits, '1');
    }

    /**
     * The string as it is stored and as aSc writes it.
     */
    public function toAscString(): string
    {
        return $this->bits;
    }

    public function __toString(): string
    {
        return $this->bits;
    }
}
