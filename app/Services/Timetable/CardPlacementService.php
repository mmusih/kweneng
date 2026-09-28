<?php

namespace App\Services\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Support\Timetable\Bitmask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Putting cards on the grid, taking them off, and moving them.
 *
 * The awkward part is the double. A `periods_per_card = 2` lesson occupies two `tt_cards`
 * rows on consecutive periods, and nothing in the schema says those two rows belong
 * together — aSc's format does not record it either. They are recovered structurally:
 * group a lesson's rows by identical masks and room, split into contiguous runs of
 * periods, then chunk each run by `periods_per_card`. That is unambiguous because
 * `periods_per_card` is fixed for the whole lesson, and it costs no migration.
 *
 * Every write goes through ConflictChecker first, so a refusal is enforced here rather
 * than trusted from the browser.
 */
class CardPlacementService
{
    /** One prepared occurrence per lesson makes a split an atomic set of placements. */
    public function linkedLessons(Lesson $lesson): Collection
    {
        return $lesson->split_key
            ? Lesson::where('tt_setting_id', $lesson->tt_setting_id)->where('split_key', $lesson->split_key)->orderBy('id')->get()
            : collect([$lesson]);
    }

    public function place(Lesson $lesson, int $dayNumber, int $startPeriod, ?int $roomId = null): Placement
    {
        return DB::transaction(function () use ($lesson, $dayNumber, $startPeriod, $roomId) {
            \App\Models\Tt\Setting::whereKey($lesson->tt_setting_id)->lockForUpdate()->firstOrFail();
            $result = null;
            foreach ($this->linkedLessons($lesson) as $member) {
                $placed = $this->placeOne($member, $dayNumber, $startPeriod, $member->id === $lesson->id ? $roomId : null);
                if ($member->id === $lesson->id) {
                    $result = $placed;
                }
            }

            return $result;
        });
    }

    public function move(Placement $placement, int $dayNumber, int $startPeriod, ?int $roomId = null): Placement
    {
        if (! $placement->lesson->split_key) {
            return $this->moveOne($placement, $dayNumber, $startPeriod, $roomId);
        }

        return DB::transaction(function () use ($placement, $dayNumber, $startPeriod, $roomId) {
            \App\Models\Tt\Setting::whereKey($placement->lesson->tt_setting_id)->lockForUpdate()->firstOrFail();
            $units = $this->linkedUnits($placement);
            // Remove every old member before checking the new positions; rollback restores all on failure.
            foreach ($units as $unit) {
                $this->unplaceOne($unit);
            }
            $result = null;
            foreach ($units as $unit) {
                $selected = $unit->lesson->id === $placement->lesson->id;
                $newRoom = $selected ? ($roomId ?? $unit->roomId()) : $unit->roomId();
                $this->refuseIfConflicting($unit->lesson, $dayNumber, $startPeriod, $newRoom, []);
                $placed = $this->write($unit->lesson, $dayNumber, $startPeriod, $newRoom, [
                    'weeks' => (string) $unit->first()->weeks, 'terms' => (string) $unit->first()->terms,
                ]);
                if ($selected) {
                    $result = $placed;
                }
            }

            return $result;
        });
    }

    public function unplace(Placement $placement): void
    {
        DB::transaction(function () use ($placement) {
            \App\Models\Tt\Setting::whereKey($placement->lesson->tt_setting_id)->lockForUpdate()->firstOrFail();
            foreach ($this->linkedUnits($placement) as $unit) {
                $this->unplaceOne($unit);
            }
        });
    }

    public function lock(Placement $placement, bool $locked = true): Placement
    {
        return DB::transaction(function () use ($placement, $locked) {
            foreach ($this->linkedUnits($placement) as $unit) {
                $this->lockOne($unit, $locked);
            }

            return $this->reload($placement);
        });
    }

    public function linkedUnits(Placement $placement): array
    {
        return $placement->lesson->split_key
            ? $this->linkedLessons($placement->lesson)->flatMap(fn ($lesson) => $this->unitsFor($lesson))->all()
            : [$placement];
    }

    /** Check the entire split using an in-memory pool, including its proposed members. */
    public function splitVerdict(Collection $members, Collection $pool, Lesson $selected, int $day, int $period, ?int $roomId, bool $moving): array
    {
        $ids = $members->pluck('id')->all();
        $occupants = $pool->reject(fn ($card) => in_array($card->tt_lesson_id, $ids))->values();
        foreach ($members as $member) {
            $member->loadMissing(['setting', 'weeksDef', 'termsDef', 'teachers.user', 'classes', 'groups', 'rooms', 'subject']);
            $old = $pool->firstWhere('tt_lesson_id', $member->id);
            if ($pool->contains(fn ($card) => $card->tt_lesson_id === $member->id && $card->locked)) {
                return ['ok' => false, 'room_id' => null, 'conflicts' => [(new Conflict(Conflict::LOCKED, 'A card in this split is locked. Unlock the split before moving it.'))->toArray()]];
            }
            $rooms = $moving ? [$member->id === $selected->id ? ($roomId ?? $old?->tt_room_id) : $old?->tt_room_id]
                : ($member->id === $selected->id && $roomId !== null ? [$roomId] : ($member->rooms->pluck('id')->all() ?: [$this->baseRooms->forLesson($member)]));
            $checker = $this->checker->withPool($occupants);
            $first = [];
            $chosen = null;
            foreach ($rooms as $candidate) {
                $conflicts = $checker->check($member, $day, $period, $candidate);
                if ($conflicts === []) {
                    $chosen = $candidate;
                    $first = [];
                    break;
                }
                $first = $first ?: $conflicts;
            }
            if ($first !== []) {
                return ['ok' => false, 'room_id' => null, 'conflicts' => array_map(fn ($c) => $c->toArray(), $first)];
            }
            $masks = PlacementMasks::forLesson($member, $day);
            foreach (range($period, $period + $member->periods_per_card - 1) as $number) {
                $card = new Card(['tt_lesson_id' => $member->id, 'period_number' => $number, 'days' => (string) $masks->days,
                    'weeks' => (string) $masks->weeks, 'terms' => (string) $masks->terms, 'tt_room_id' => $chosen]);
                $card->setRelation('lesson', $member);
                $card->setRelation('room', $chosen ? \App\Models\Tt\Room::find($chosen) : null);
                $occupants->push($card);
            }
        }

        return ['ok' => true, 'room_id' => $roomId, 'conflicts' => []];
    }

    public function __construct(
        private readonly ConflictChecker $checker = new ConflictChecker,
        private readonly BaseRoomResolver $baseRooms = new BaseRoomResolver,
    ) {}

    /**
     * Drop a new card from the tray onto the grid.
     *
     * @throws PlacementRefused
     */
    private function placeOne(Lesson $lesson, int $dayNumber, int $startPeriod, ?int $roomId = null): Placement
    {
        $lesson->loadMissing(['setting', 'weeksDef', 'termsDef', 'rooms', 'cards']);

        if ($this->unplacedCount($lesson) < 1) {
            throw new PlacementRefused([new Conflict(
                Conflict::STRUCTURE,
                'Every card of this lesson is already on the grid.',
                null,
                $startPeriod,
            )]);
        }

        $roomId = $this->pickRoom($lesson, $dayNumber, $startPeriod, $roomId, []);

        $this->refuseIfConflicting($lesson, $dayNumber, $startPeriod, $roomId, []);

        $masks = PlacementMasks::forLesson($lesson, $dayNumber);

        return $this->write($lesson, $dayNumber, $startPeriod, $roomId, [
            'weeks' => (string) $masks->weeks,
            'terms' => (string) $masks->terms,
        ]);
    }

    /**
     * Move a card already on the grid. A double moves as one block or not at all.
     *
     * @throws PlacementRefused
     */
    public function changeRoom(Placement $placement, ?int $roomId): Placement
    {
        return DB::transaction(function () use ($placement, $roomId) {
            if ($placement->isLocked()) {
                throw new PlacementRefused([new Conflict(Conflict::LOCKED, 'Unlock this card before changing its room.')]);
            }

            // A room-only change preserves the actual placement's masks and periods.
            // Imported cards can differ from their lesson's default recurrence.
            if ($roomId !== null) {
                $width = (int) $placement->lesson->setting->cycle_length;
                $first = $placement->first();
                $masks = PlacementMasks::fromStrings($first->days, $first->weeks, $first->terms, $width);
                $occupants = Card::where('tt_room_id', $roomId)
                    ->whereHas('lesson', fn ($query) => $query->where('tt_setting_id', $placement->lesson->tt_setting_id))
                    ->whereIn('period_number', $placement->periods())
                    ->whereNotIn('id', $placement->cardIds())->get();
                foreach ($occupants as $card) {
                    if ($masks->intersects(PlacementMasks::fromStrings($card->days, $card->weeks, $card->terms, $width))) {
                        throw new PlacementRefused([new Conflict(Conflict::ROOM,
                            'That room is already occupied in period '.$card->period_number.'.', $card->id, $card->period_number)]);
                    }
                }
            }

            Card::whereIn('id', $placement->cardIds())->update(['tt_room_id' => $roomId]);

            return $this->reload($placement);
        });
    }

    private function moveOne(Placement $placement, int $dayNumber, int $startPeriod, ?int $roomId = null): Placement
    {
        if ($placement->isLocked()) {
            throw new PlacementRefused([new Conflict(
                Conflict::LOCKED,
                'That card is locked. Unlock it before moving it.',
                $placement->first()->id,
                $placement->startPeriod(),
            )]);
        }

        $lesson = $placement->lesson->loadMissing(['setting', 'weeksDef', 'termsDef', 'rooms']);
        $ignore = $placement->cardIds();

        // The card must not collide with the copy of itself it is leaving behind, or
        // nothing could ever be nudged one period sideways.
        // Moving an existing card preserves its room, including an explicit "no room".
        // Automatic room selection is only for a new card coming out of the tray.
        $roomId ??= $placement->roomId();

        $this->refuseIfConflicting($lesson, $dayNumber, $startPeriod, $roomId, $ignore);

        // Weeks and terms come off the existing rows, not off the lesson: an imported card
        // may run in a narrower window than its lesson allows, and a move must not widen it.
        $keep = [
            'weeks' => (string) $placement->first()->weeks,
            'terms' => (string) $placement->first()->terms,
        ];

        return DB::transaction(function () use ($placement, $lesson, $dayNumber, $startPeriod, $roomId, $keep) {
            $this->delete($placement);

            return $this->write($lesson, $dayNumber, $startPeriod, $roomId, $keep);
        });
    }

    /**
     * Return a card to the tray. Both rows of a double go.
     */
    private function unplaceOne(Placement $placement): void
    {
        if ($placement->isLocked()) {
            throw new PlacementRefused([new Conflict(
                Conflict::LOCKED,
                'That card is locked. Unlock it before removing it.',
                $placement->first()->id,
                $placement->startPeriod(),
            )]);
        }

        DB::transaction(fn () => $this->delete($placement));
    }

    private function lockOne(Placement $placement, bool $locked = true): Placement
    {
        DB::transaction(function () use ($placement, $locked) {
            Card::whereIn('id', $placement->cardIds())->update(['locked' => $locked]);
        });

        return $this->reload($placement);
    }

    /**
     * Every card of this lesson currently on the grid.
     *
     * @return list<Placement>
     */
    public function unitsFor(Lesson $lesson): array
    {
        return $this->unitsFromRows($lesson, $lesson->cards()->orderBy('period_number')->get());
    }

    /**
     * The card a single row belongs to — the row itself, or the double it is half of.
     */
    public function unitContaining(Card $card): ?Placement
    {
        $lesson = $card->lesson;

        if ($lesson === null) {
            return null;
        }

        foreach ($this->unitsFor($lesson) as $unit) {
            if (in_array((int) $card->id, $unit->cardIds(), true)) {
                return $unit;
            }
        }

        return null;
    }

    /**
     * How many cards of this lesson are still in the tray.
     *
     * Derived, never stored: a `tt_cards` row *is* a placement, so an unplaced card is
     * precisely one the lesson owes and has not got.
     */
    public function unplacedCount(Lesson $lesson): int
    {
        return max(0, $lesson->cardsRequired() - count($this->unitsFor($lesson)));
    }

    /**
     * @param  list<int>  $ignoreCardIds
     *
     * @throws PlacementRefused
     */
    private function refuseIfConflicting(
        Lesson $lesson,
        int $dayNumber,
        int $startPeriod,
        ?int $roomId,
        array $ignoreCardIds,
    ): void {
        $conflicts = $this->checker->check($lesson, $dayNumber, $startPeriod, $roomId, $ignoreCardIds);

        if ($conflicts !== []) {
            throw new PlacementRefused($conflicts);
        }
    }

    /**
     * @param  array{weeks: string, terms: string}  $masks
     */
    private function write(Lesson $lesson, int $dayNumber, int $startPeriod, ?int $roomId, array $masks): Placement
    {
        $days = (string) Bitmask::fromDayNumber($dayNumber, max(1, (int) ($lesson->setting?->cycle_length ?? 6)));
        $span = $this->checker->span($lesson);

        $cards = DB::transaction(function () use ($lesson, $startPeriod, $span, $days, $masks, $roomId) {
            $written = [];

            foreach (range(0, $span - 1) as $offset) {
                $written[] = Card::create([
                    'tt_lesson_id' => $lesson->id,
                    'period_number' => $startPeriod + $offset,
                    'days' => $days,
                    'weeks' => $masks['weeks'],
                    'terms' => $masks['terms'],
                    'tt_room_id' => $roomId,
                    'locked' => false,
                ]);
            }

            return $written;
        });

        $lesson->unsetRelation('cards');

        return new Placement($lesson, $cards);
    }

    private function delete(Placement $placement): void
    {
        Card::whereIn('id', $placement->cardIds())->delete();

        $placement->lesson->unsetRelation('cards');
    }

    private function reload(Placement $placement): Placement
    {
        $cards = Card::whereIn('id', $placement->cardIds())->orderBy('period_number')->get()->all();

        return new Placement($placement->lesson->fresh(), $cards);
    }

    /**
     * A lesson may name several usable rooms ("any free lab"). Take the first that is
     * actually free at this slot, and if none are, take the first so the refusal names a
     * room the admin recognises rather than saying nothing about rooms at all.
     *
     * @param  list<int>  $ignoreCardIds
     */
    private function pickRoom(
        Lesson $lesson,
        int $dayNumber,
        int $startPeriod,
        ?int $roomId,
        array $ignoreCardIds,
    ): ?int {
        if ($roomId !== null) {
            return $roomId;
        }

        $candidates = $lesson->rooms->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($candidates === []) {
            return $this->baseRooms->forLesson($lesson);
        }

        foreach ($candidates as $candidate) {
            if ($this->checker->allows($lesson, $dayNumber, $startPeriod, $candidate, $ignoreCardIds)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    /**
     * Recover cards from rows. See the class docblock for why this is structural.
     *
     * @param  Collection<int, Card>  $rows
     * @return list<Placement>
     */
    private function unitsFromRows(Lesson $lesson, Collection $rows): array
    {
        $span = $this->checker->span($lesson);
        $units = [];

        $buckets = $rows->groupBy(fn (Card $card) => implode('|', [
            (string) $card->days,
            (string) $card->weeks,
            (string) $card->terms,
            (string) ($card->tt_room_id ?? ''),
        ]));

        foreach ($buckets as $bucket) {
            foreach ($this->contiguousRuns($bucket) as $run) {
                foreach (array_chunk($run, $span) as $chunk) {
                    $units[] = new Placement($lesson, $chunk);
                }
            }
        }

        usort($units, fn (Placement $a, Placement $b) => [$a->dayNumber(), $a->startPeriod()]
            <=> [$b->dayNumber(), $b->startPeriod()]);

        return $units;
    }

    /**
     * @param  Collection<int, Card>  $bucket
     * @return list<list<Card>>
     */
    private function contiguousRuns(Collection $bucket): array
    {
        $runs = [];
        $current = [];

        foreach ($bucket->sortBy('period_number')->values() as $card) {
            if ($current !== [] && (int) $card->period_number !== (int) end($current)->period_number + 1) {
                $runs[] = $current;
                $current = [];
            }

            $current[] = $card;
        }

        if ($current !== []) {
            $runs[] = $current;
        }

        return $runs;
    }
}
