<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\Card;
use App\Services\Timetable\CardPlacementService;
use App\Services\Timetable\Conflict;
use App\Services\Timetable\PlacementRefused;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

/**
 * Writing cards to the grid.
 *
 * Most of these are about the double, because a double is the one thing the schema does
 * not represent directly: two rows on consecutive periods with nothing joining them. If
 * the recovery in unitsFor() is wrong, a double half-moves and leaves an orphan row
 * behind — which the grid would happily render as a lesson nobody scheduled.
 */
class CardPlacementTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private CardPlacementService $placement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placement = new CardPlacementService;
        $this->seedSchool();
    }

    // -----------------------------------------------------------------
    // Placing
    // -----------------------------------------------------------------

    public function test_placing_a_single_writes_one_row(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $placed = $this->placement->place($bio, dayNumber: 2, startPeriod: 3);

        $this->assertSame(1, $placed->span());
        $this->assertSame(2, $placed->dayNumber());
        $this->assertSame(3, $placed->startPeriod());
        $this->assertDatabaseHas('tt_cards', [
            'tt_lesson_id' => $bio->id,
            'period_number' => 3,
            'days' => '010000',
        ]);
    }

    public function test_placing_a_double_writes_two_consecutive_rows(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 2);

        $this->assertSame(2, $placed->span());
        $this->assertSame([2, 3], $placed->periods());
        $this->assertSame(2, Card::where('tt_lesson_id', $bio->id)->count());

        // Both rows carry the same masks, which is what lets unitsFor() find them again.
        $rows = Card::where('tt_lesson_id', $bio->id)->get();
        $this->assertCount(1, $rows->pluck('days')->unique());
        $this->assertCount(1, $rows->pluck('terms')->unique());
    }

    public function test_a_clashing_drop_is_refused_and_writes_nothing(): void
    {
        $maths = $this->lesson('Mathematics', 'Form 5A', 'Entire class', 'M Tau', entireClass: true);
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->placement->place($maths, dayNumber: 1, startPeriod: 1);

        try {
            $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
            $this->fail('A clashing placement should have been refused.');
        } catch (PlacementRefused $refused) {
            $this->assertSame(Conflict::STUDENTS, $refused->conflicts[0]->kind);
        }

        $this->assertSame(0, Card::where('tt_lesson_id', $bio->id)->count());
    }

    public function test_a_double_is_refused_across_a_break_and_at_the_end_of_the_day(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        // Break sits after period 4; period 8 is the last of the day.
        foreach ([4, 8] as $start) {
            try {
                $this->placement->place($bio, dayNumber: 1, startPeriod: $start);
                $this->fail("A double starting at period {$start} should have been refused.");
            } catch (PlacementRefused $refused) {
                $this->assertSame(Conflict::STRUCTURE, $refused->conflicts[0]->kind);
            }
        }

        $this->assertSame(0, Card::where('tt_lesson_id', $bio->id)->count());
    }

    /**
     * The tray is derived from what the lesson owes minus what is on the grid, so nothing
     * stops the client asking for a ninth card of an eight-card lesson except this.
     */
    public function test_a_lesson_cannot_be_placed_more_often_than_it_owes(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 2.0);

        $this->assertSame(2, $this->placement->unplacedCount($bio));

        $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $this->placement->place($bio, dayNumber: 2, startPeriod: 1);

        $this->assertSame(0, $this->placement->unplacedCount($bio->fresh()));

        $this->expectException(PlacementRefused::class);
        $this->placement->place($bio->fresh(), dayNumber: 3, startPeriod: 1);
    }

    public function test_a_double_counts_as_one_card_against_the_tray(): void
    {
        // Four periods a week in doubles is two cards, not four.
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true, periodsPerWeek: 4.0);

        $this->assertSame(2, $this->placement->unplacedCount($bio));

        $this->placement->place($bio, dayNumber: 1, startPeriod: 1);

        $this->assertSame(1, $this->placement->unplacedCount($bio->fresh()));
        $this->assertSame(2, Card::where('tt_lesson_id', $bio->id)->count());
    }

    // -----------------------------------------------------------------
    // Rooms
    // -----------------------------------------------------------------

    public function test_a_lesson_takes_the_first_of_its_rooms_that_is_free(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: ['Lab 1', 'Room 2']);
        $che = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga', rooms: ['Lab 1', 'Room 2']);

        $first = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $second = $this->placement->place($che, dayNumber: 1, startPeriod: 1);

        $this->assertSame($this->rooms['Lab 1']->id, $first->roomId());
        $this->assertSame($this->rooms['Room 2']->id, $second->roomId());
    }

    public function test_moving_a_roomless_card_does_not_silently_assign_a_room(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: ['Lab 1']);
        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        Card::query()->whereKey($placed->cardIds())->update(['tt_room_id' => null]);
        $roomless = $this->placement->unitContaining(Card::query()->findOrFail($placed->cardIds()[0]));

        $moved = $this->placement->move($roomless, dayNumber: 2, startPeriod: 2);

        $this->assertNull($moved->roomId());
        $this->assertDatabaseHas('tt_cards', [
            'tt_lesson_id' => $bio->id,
            'period_number' => 2,
            'tt_room_id' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // Moving
    // -----------------------------------------------------------------

    public function test_a_double_moves_as_one_block(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $moved = $this->placement->move($placed, dayNumber: 3, startPeriod: 6);

        $this->assertSame([6, 7], $moved->periods());
        $this->assertSame(3, $moved->dayNumber());

        // No orphan left on Day 1 — the whole point of moving as a unit.
        $this->assertSame(2, Card::where('tt_lesson_id', $bio->id)->count());
        $this->assertSame(
            ['001000'],
            Card::where('tt_lesson_id', $bio->id)->pluck('days')->unique()->values()->all(),
        );
    }

    public function test_a_card_can_be_nudged_one_period_sideways(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);

        // 1-2 → 2-3 overlaps its own old position; only ignoring itself makes this legal.
        $moved = $this->placement->move($placed, dayNumber: 1, startPeriod: 2);

        $this->assertSame([2, 3], $moved->periods());
        $this->assertSame(2, Card::where('tt_lesson_id', $bio->id)->count());
    }

    public function test_a_refused_move_leaves_the_card_where_it_was(): void
    {
        $maths = $this->lesson('Mathematics', 'Form 5A', 'Entire class', 'M Tau', entireClass: true);
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->placement->place($maths, dayNumber: 1, startPeriod: 5);
        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);

        $this->expectException(PlacementRefused::class);

        try {
            $this->placement->move($placed, dayNumber: 1, startPeriod: 5);
        } finally {
            $this->assertDatabaseHas('tt_cards', [
                'tt_lesson_id' => $bio->id,
                'period_number' => 1,
            ]);
            $this->assertSame(1, Card::where('tt_lesson_id', $bio->id)->count());
        }
    }

    public function test_a_locked_card_refuses_to_move_or_unplace(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $placed = $this->placement->lock($this->placement->place($bio, dayNumber: 1, startPeriod: 1));

        $this->assertTrue($placed->isLocked());

        foreach (['move', 'unplace'] as $action) {
            try {
                $action === 'move'
                    ? $this->placement->move($placed, dayNumber: 2, startPeriod: 1)
                    : $this->placement->unplace($placed);
                $this->fail("A locked card should refuse to {$action}.");
            } catch (PlacementRefused $refused) {
                $this->assertSame(Conflict::LOCKED, $refused->conflicts[0]->kind);
            }
        }

        $this->assertDatabaseHas('tt_cards', ['tt_lesson_id' => $bio->id, 'period_number' => 1]);
    }

    // -----------------------------------------------------------------
    // Unplacing
    // -----------------------------------------------------------------

    public function test_unplacing_a_double_removes_both_rows(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true, periodsPerWeek: 4.0);

        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);

        $this->assertSame(1, $this->placement->unplacedCount($bio->fresh()));

        $this->placement->unplace($placed);

        $this->assertSame(0, Card::where('tt_lesson_id', $bio->id)->count());
        $this->assertSame(2, $this->placement->unplacedCount($bio->fresh()));
    }

    // -----------------------------------------------------------------
    // Recovering cards from rows
    // -----------------------------------------------------------------

    /**
     * Two doubles on the same day back to back are four consecutive rows sharing every
     * mask. Splitting that run into 1-2 and 3-4 rather than one four-period card is the
     * whole of the recovery rule, and it is the case a naive "group by mask" gets wrong.
     */
    public function test_two_adjacent_doubles_are_recovered_as_two_cards(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true, periodsPerWeek: 4.0);

        $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $this->placement->place($bio, dayNumber: 1, startPeriod: 3);

        $units = $this->placement->unitsFor($bio->fresh());

        $this->assertCount(2, $units);
        $this->assertSame([1, 2], $units[0]->periods());
        $this->assertSame([3, 4], $units[1]->periods());
    }

    public function test_the_second_row_of_a_double_resolves_to_the_whole_card(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $placed = $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $second = Card::where('tt_lesson_id', $bio->id)->where('period_number', 2)->firstOrFail();

        $unit = $this->placement->unitContaining($second);

        $this->assertNotNull($unit);
        $this->assertSame($placed->cardIds(), $unit->cardIds());
    }

    public function test_cards_of_one_lesson_on_different_days_stay_separate(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 2.0);

        $this->placement->place($bio, dayNumber: 1, startPeriod: 1);
        $this->placement->place($bio, dayNumber: 2, startPeriod: 1);

        $units = $this->placement->unitsFor($bio->fresh());

        $this->assertCount(2, $units);
        $this->assertSame([1, 2], array_map(fn ($u) => $u->dayNumber(), $units));
    }
}
