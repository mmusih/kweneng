<?php

namespace App\Services\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Lesson;

/**
 * One card as the admin sees it: a lesson sitting on a day at a period.
 *
 * A double is one card on the grid but two `tt_cards` rows, so "a card" and "a row" are
 * not the same thing anywhere in this feature. This object is the card; `$cards` are its
 * rows. Nothing in the schema ties the rows of a double together — see
 * CardPlacementService::unitsFor() for how they are recovered.
 */
final class Placement
{
    /**
     * @param  list<Card>  $cards  its rows, ascending by period
     */
    public function __construct(
        public readonly Lesson $lesson,
        public readonly array $cards,
    ) {}

    public function first(): Card
    {
        return $this->cards[0];
    }

    public function startPeriod(): int
    {
        return (int) $this->first()->period_number;
    }

    public function span(): int
    {
        return count($this->cards);
    }

    /**
     * A placement sits on exactly one day, so the mask has one bit — but read it off the
     * row rather than assuming, because an imported card may carry several.
     */
    public function dayNumber(): int
    {
        return $this->first()->daysMask()->positions()[0] ?? 1;
    }

    public function roomId(): ?int
    {
        $id = $this->first()->tt_room_id;

        return $id === null ? null : (int) $id;
    }

    public function isLocked(): bool
    {
        foreach ($this->cards as $card) {
            if ($card->locked) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    public function cardIds(): array
    {
        return array_map(fn (Card $card) => (int) $card->id, $this->cards);
    }

    /**
     * @return list<int>
     */
    public function periods(): array
    {
        return array_map(fn (Card $card) => (int) $card->period_number, $this->cards);
    }

    public function toArray(): array
    {
        return [
            'card_ids' => $this->cardIds(),
            'lesson_id' => (int) $this->lesson->id,
            'day' => $this->dayNumber(),
            'period' => $this->startPeriod(),
            'span' => $this->span(),
            'room_id' => $this->roomId(),
            'locked' => $this->isLocked(),
        ];
    }
}
