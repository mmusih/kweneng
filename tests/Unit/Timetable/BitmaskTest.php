<?php

namespace Tests\Unit\Timetable;

use App\Support\Timetable\Bitmask;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Bitmask is pure value-object logic with no framework or database dependency, so this
 * extends PHPUnit's TestCase directly rather than Tests\TestCase — there is nothing to
 * boot and no connection to guard.
 *
 * The behaviour under test is the one the rest of the timetable leans on: a mask is
 * always exactly as wide as the cycle it describes, and that width comes from the
 * owning setting (6 at this school), never from a hardcoded 5 or 7.
 */
class BitmaskTest extends TestCase
{
    public function test_a_mask_reports_its_set_positions_one_based(): void
    {
        $mask = new Bitmask('010000', 6);

        $this->assertSame([2], $mask->positions());
        $this->assertSame('010000', $mask->toAscString());
        $this->assertSame('010000', (string) $mask);
        $this->assertSame(6, $mask->width());
        $this->assertSame(1, $mask->count());
        $this->assertFalse($mask->isEmpty());
    }

    public function test_has_is_one_based_and_out_of_range_positions_are_simply_unset(): void
    {
        $mask = new Bitmask('101000', 6);

        $this->assertTrue($mask->has(1));
        $this->assertFalse($mask->has(2));
        $this->assertTrue($mask->has(3));

        // Out of range is a question with a sensible answer ("no"), not an error:
        // callers loop over a cycle that may be wider than the mask they were handed.
        $this->assertFalse($mask->has(0));
        $this->assertFalse($mask->has(7));
        $this->assertFalse($mask->has(-1));
    }

    /**
     * aSc masks arrive padded, spaced or short. Normalising rather than rejecting is
     * what keeps the XML round-trip lossless.
     */
    public function test_a_short_mask_is_padded_and_a_long_one_is_truncated_to_the_width(): void
    {
        $this->assertSame('110000', (new Bitmask('11', 6))->toAscString());
        $this->assertSame('111111', (new Bitmask('11111111', 6))->toAscString());
        $this->assertSame('000000', (new Bitmask('', 6))->toAscString());

        // Separators and stray characters are stripped before padding, so a spaced
        // mask means the same thing as its compact form.
        $this->assertSame('101000', (new Bitmask('1 0 1', 6))->toAscString());
        $this->assertSame('100000', (new Bitmask('1x', 6))->toAscString());
    }

    public function test_a_width_below_one_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Bitmask('1', 0);
    }

    public function test_from_day_number_sets_exactly_one_day_of_the_cycle(): void
    {
        $this->assertSame('100000', Bitmask::fromDayNumber(1, 6)->toAscString());
        $this->assertSame('010000', Bitmask::fromDayNumber(2, 6)->toAscString());
        $this->assertSame('000001', Bitmask::fromDayNumber(6, 6)->toAscString());

        $this->assertSame([4], Bitmask::fromDayNumber(4, 6)->positions());
    }

    /**
     * Day 6 is valid in this school's 6-day rotation and invalid in a 5-day week. The
     * width decides, which is the whole reason width is a required argument.
     */
    public function test_a_day_outside_the_cycle_is_rejected(): void
    {
        $this->assertSame('000001', Bitmask::fromDayNumber(6, 6)->toAscString());

        $this->expectException(InvalidArgumentException::class);

        Bitmask::fromDayNumber(6, 5);
    }

    public function test_from_day_number_rejects_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bitmask::fromDayNumber(0, 6);
    }

    public function test_from_positions_sets_every_listed_position(): void
    {
        $mask = Bitmask::fromPositions([1, 3, 6], 6);

        $this->assertSame('101001', $mask->toAscString());
        $this->assertSame([1, 3, 6], $mask->positions());
        $this->assertSame(3, $mask->count());

        // Repeats are idempotent, and an empty list is a legitimate empty mask.
        $this->assertSame('100000', Bitmask::fromPositions([1, 1], 6)->toAscString());
        $this->assertSame('000000', Bitmask::fromPositions([], 6)->toAscString());
    }

    public function test_from_positions_rejects_a_position_outside_the_width(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bitmask::fromPositions([1, 7], 6);
    }

    public function test_all_and_none_span_the_full_width(): void
    {
        $all = Bitmask::all(6);
        $none = Bitmask::none(6);

        $this->assertSame('111111', $all->toAscString());
        $this->assertSame([1, 2, 3, 4, 5, 6], $all->positions());
        $this->assertSame(6, $all->count());
        $this->assertFalse($all->isEmpty());

        $this->assertSame('000000', $none->toAscString());
        $this->assertSame([], $none->positions());
        $this->assertSame(0, $none->count());
        $this->assertTrue($none->isEmpty());
    }

    /**
     * intersects() is the clash primitive: two cards on the same period collide only if
     * their day masks overlap. Getting this wrong either lets a teacher be in two rooms
     * at once or refuses valid placements.
     */
    public function test_intersects_is_true_only_where_both_masks_are_set(): void
    {
        $monday = new Bitmask('100000', 6);
        $tuesday = new Bitmask('010000', 6);
        $monAndTue = new Bitmask('110000', 6);

        $this->assertFalse($monday->intersects($tuesday));
        $this->assertTrue($monday->intersects($monAndTue));
        $this->assertTrue($monAndTue->intersects($tuesday));
        $this->assertTrue($monday->intersects($monday));

        // Symmetric, as any overlap test must be.
        $this->assertSame($monday->intersects($monAndTue), $monAndTue->intersects($monday));
    }

    public function test_an_empty_mask_intersects_nothing(): void
    {
        $none = Bitmask::none(6);

        $this->assertFalse($none->intersects(Bitmask::all(6)));
        $this->assertFalse(Bitmask::all(6)->intersects($none));
        $this->assertFalse($none->intersects($none));
    }

    /**
     * A card's 6-wide mask gets compared against a narrower daysdef. Comparison runs to
     * the shorter of the two; the wider mask's extra positions have no counterpart to
     * collide with.
     */
    public function test_masks_of_different_widths_compare_over_the_shared_prefix(): void
    {
        $sixWide = new Bitmask('000011', 6);
        $fourWide = new Bitmask('0000', 4);

        $this->assertFalse($sixWide->intersects($fourWide));
        $this->assertFalse($fourWide->intersects($sixWide));

        $overlapping = new Bitmask('0010', 4);

        $this->assertTrue((new Bitmask('001000', 6))->intersects($overlapping));
        $this->assertTrue($overlapping->intersects(new Bitmask('001000', 6)));
    }

    public function test_the_six_single_day_masks_of_the_cycle_are_mutually_exclusive(): void
    {
        $days = array_map(fn (int $n) => Bitmask::fromDayNumber($n, 6), range(1, 6));

        $this->assertSame(
            ['100000', '010000', '001000', '000100', '000010', '000001'],
            array_map(fn (Bitmask $mask) => $mask->toAscString(), $days),
        );

        foreach ($days as $i => $a) {
            foreach ($days as $j => $b) {
                $this->assertSame(
                    $i === $j,
                    $a->intersects($b),
                    "Day ".($i + 1)." and day ".($j + 1)." disagree on intersection.",
                );
            }
        }
    }
}
