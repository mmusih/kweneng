<?php

namespace App\Services\Timetable;

use RuntimeException;

/**
 * A day structure that would have deleted scheduled cards.
 *
 * Shrinking the day from 8 periods to 6 is a normal thing to want, but it silently takes
 * every card sitting on periods 7 and 8 with it. This says which periods are occupied so
 * the admin can clear them deliberately rather than discover the loss later.
 */
final class DayStructureRefused extends RuntimeException
{
    /**
     * @param  array<int, int>  $occupiedPeriods  period number => cards on it
     * @param  array<int, int>  $occupiedDays  day number => cards on it
     */
    public function __construct(
        public readonly array $occupiedPeriods = [],
        public readonly array $occupiedDays = [],
    ) {
        parent::__construct($this->describe());
    }

    private function describe(): string
    {
        $parts = [];

        if ($this->occupiedPeriods !== []) {
            $parts[] = 'period '.$this->list($this->occupiedPeriods);
        }

        if ($this->occupiedDays !== []) {
            $parts[] = 'day '.$this->list($this->occupiedDays);
        }

        if ($parts === []) {
            return 'That day structure was refused.';
        }

        return 'Move or remove the cards on '.implode(' and ', $parts)
            .' before shrinking the timetable.';
    }

    /**
     * @param  array<int, int>  $occupied
     */
    private function list(array $occupied): string
    {
        $labels = [];

        foreach ($occupied as $number => $cards) {
            $labels[] = $number.' ('.$cards.' '.($cards === 1 ? 'card' : 'cards').')';
        }

        return implode(', ', $labels);
    }
}
