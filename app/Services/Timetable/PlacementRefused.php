<?php

namespace App\Services\Timetable;

use RuntimeException;

/**
 * A drop the server would not accept, carrying the reasons so the grid can name them.
 *
 * The client shades clashing slots red before the drag ends, but it is working from a
 * snapshot; a second admin may have filled the slot in between. This is what makes the
 * refusal authoritative rather than advisory.
 */
final class PlacementRefused extends RuntimeException
{
    /**
     * @param  list<Conflict>  $conflicts
     */
    public function __construct(public readonly array $conflicts)
    {
        parent::__construct(
            $conflicts === []
                ? 'That placement was refused.'
                : implode(' ', array_map(fn (Conflict $c) => $c->message, $conflicts)),
        );
    }

    /**
     * @return list<array{kind: string, message: string, card_id: int|null, period: int|null}>
     */
    public function toArray(): array
    {
        return array_map(fn (Conflict $c) => $c->toArray(), $this->conflicts);
    }
}
