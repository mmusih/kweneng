<?php

namespace App\Services\Timetable;

/**
 * One reason a placement is illegal.
 *
 * Carries the offending card so the grid can point at it, and a message written for the
 * person holding the card — "K Simukonda already teaches Biology to Form 5A then", not
 * "teacher conflict". A refusal the admin cannot act on is barely better than a silent one.
 */
final class Conflict
{
    public const TEACHER = 'teacher';

    public const ROOM = 'room';

    public const STUDENTS = 'students';

    public const LOCKED = 'locked';

    public const STRUCTURE = 'structure';

    public function __construct(
        public readonly string $kind,
        public readonly string $message,
        public readonly ?int $cardId = null,
        public readonly ?int $periodNumber = null,
    ) {}

    /**
     * @return array{kind: string, message: string, card_id: int|null, period: int|null}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'message' => $this->message,
            'card_id' => $this->cardId,
            'period' => $this->periodNumber,
        ];
    }
}
