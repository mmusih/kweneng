<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use App\Models\Tt\TermsDef;
use App\Services\Timetable\Conflict;
use App\Services\Timetable\ConflictChecker;
use Database\Factories\Tt\PeriodFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

/**
 * The rules that decide whether a card may land on a slot.
 *
 * The case this class exists for is the third block below: four option groups of one
 * division running in the same period must NOT clash. The legacy timetable could not
 * express that, and it is most of this school's senior timetable — if this test ever goes
 * green by accident (say, because the checker stopped finding cards at all), the two
 * "must clash" tests either side of it are what catch it.
 */
class ConflictCheckerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private ConflictChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = new ConflictChecker;
        $this->seedSchool();
    }

    // -----------------------------------------------------------------
    // Students
    // -----------------------------------------------------------------

    /**
     * The whole point of a division. BIO A, CHE A and the two Setswana sets are the same
     * students split four ways, so they are *meant* to occupy one period — and the
     * checker must see that as legal rather than as three collisions.
     */
    public function test_parallel_groups_of_one_division_do_not_clash(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $che = $this->lesson('Chemistry', 'Form 5A', 'CHE A', 'N Chisenga');
        $set1 = $this->lesson('Setswana', 'Form 5A', 'SET 1', 'Zoë Kgosi');
        $set2 = $this->lesson('Setswana', 'Form 5A', 'SET 2', 'M Tau');

        $this->placeCard($bio, day: 1, period: 1);
        $this->placeCard($che, day: 1, period: 1);
        $this->placeCard($set1, day: 1, period: 1);

        $this->assertSame([], $this->checker->check($set2, dayNumber: 1, startPeriod: 1));
    }

    public function test_two_lessons_sharing_a_group_clash(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $extra = $this->lesson('Physics', 'Form 5A', 'BIO A', 'M Tau');

        $this->placeCard($bio, day: 1, period: 1);

        $conflicts = $this->checker->check($extra, dayNumber: 1, startPeriod: 1);

        $this->assertSame([Conflict::STUDENTS], $this->kinds($conflicts));
        $this->assertStringContainsString('Form 5A', $conflicts[0]->message);
    }

    /**
     * Different divisions are not disjoint: a student can take a science option and a
     * language option, so the two blocks cannot share a period.
     */
    public function test_groups_in_different_divisions_clash(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $phy = $this->lesson('Physics', 'Form 5A', 'PHY', 'M Tau', division: $this->science);

        $this->placeCard($bio, day: 1, period: 1);

        $this->assertSame([Conflict::STUDENTS], $this->kinds(
            $this->checker->check($phy, dayNumber: 1, startPeriod: 1),
        ));
    }

    public function test_a_whole_class_lesson_clashes_with_any_group_of_that_class(): void
    {
        $maths = $this->lesson('Mathematics', 'Form 5A', 'Entire class', 'M Tau', entireClass: true);
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->placeCard($maths, day: 2, period: 3);

        $this->assertSame([Conflict::STUDENTS], $this->kinds(
            $this->checker->check($bio, dayNumber: 2, startPeriod: 3),
        ));
    }

    public function test_lessons_in_different_classes_do_not_clash_over_students(): void
    {
        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $b = $this->lesson('Biology', 'Form 5B', 'BIO B', 'N Chisenga');

        $this->placeCard($a, day: 1, period: 1);

        $this->assertSame([], $this->checker->check($b, dayNumber: 1, startPeriod: 1));
    }

    // -----------------------------------------------------------------
    // Teachers and rooms
    // -----------------------------------------------------------------

    public function test_a_teacher_cannot_be_in_two_classes_at_once(): void
    {
        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $b = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'K Simukonda');

        $this->placeCard($a, day: 1, period: 1);

        $conflicts = $this->checker->check($b, dayNumber: 1, startPeriod: 1);

        $this->assertSame([Conflict::TEACHER], $this->kinds($conflicts));
        $this->assertStringContainsString('K Simukonda', $conflicts[0]->message);
        $this->assertStringContainsString('Biology', $conflicts[0]->message);
    }

    public function test_a_room_cannot_hold_two_lessons(): void
    {
        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $b = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga');

        $this->placeCard($a, day: 1, period: 1, room: $this->rooms['Lab 1']);

        $conflicts = $this->checker->check(
            $b, dayNumber: 1, startPeriod: 1, roomId: $this->rooms['Lab 1']->id,
        );

        $this->assertSame([Conflict::ROOM], $this->kinds($conflicts));
        $this->assertStringContainsString('Lab 1', $conflicts[0]->message);
    }

    public function test_a_free_room_in_the_same_period_is_fine(): void
    {
        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $b = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga');

        $this->placeCard($a, day: 1, period: 1, room: $this->rooms['Lab 1']);

        $this->assertSame([], $this->checker->check(
            $b, dayNumber: 1, startPeriod: 1, roomId: $this->rooms['Room 2']->id,
        ));
    }

    // -----------------------------------------------------------------
    // Masks
    // -----------------------------------------------------------------

    public function test_the_same_period_on_a_different_day_never_clashes(): void
    {
        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $b = $this->lesson('Chemistry', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->placeCard($a, day: 1, period: 1, room: $this->rooms['Lab 1']);

        // Same teacher, same room, same group — only the day differs, and that is enough.
        $this->assertSame([], $this->checker->check(
            $b, dayNumber: 4, startPeriod: 1, roomId: $this->rooms['Lab 1']->id,
        ));
    }

    public function test_lessons_in_different_terms_do_not_clash(): void
    {
        $termOne = TermsDef::create([
            'tt_setting_id' => $this->setting->id, 'name' => 'Term 1', 'terms' => '10',
        ]);
        $termTwo = TermsDef::create([
            'tt_setting_id' => $this->setting->id, 'name' => 'Term 2', 'terms' => '01',
        ]);

        $a = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $a->update(['tt_termsdef_id' => $termOne->id]);

        $b = $this->lesson('Chemistry', 'Form 5A', 'BIO A', 'K Simukonda');
        $b->update(['tt_termsdef_id' => $termTwo->id]);

        $this->placeCard($a, day: 1, period: 1, terms: '10');

        $this->assertSame([], $this->checker->check($b->fresh(), dayNumber: 1, startPeriod: 1));
    }

    // -----------------------------------------------------------------
    // Structure
    // -----------------------------------------------------------------

    public function test_a_double_cannot_start_in_the_last_period_of_the_day(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $conflicts = $this->checker->check($bio, dayNumber: 1, startPeriod: 8);

        $this->assertSame([Conflict::STRUCTURE], $this->kinds($conflicts));
        $this->assertStringContainsString('past the end of the day', $conflicts[0]->message);
    }

    public function test_a_double_cannot_run_across_a_break(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        // Break sits after period 4, so 4-5 straddles it while 5-6 does not.
        $conflicts = $this->checker->check($bio, dayNumber: 1, startPeriod: 4);

        $this->assertSame([Conflict::STRUCTURE], $this->kinds($conflicts));
        $this->assertStringContainsString('across a break', $conflicts[0]->message);

        $this->assertSame([], $this->checker->check($bio, dayNumber: 1, startPeriod: 5));
    }

    public function test_a_double_collides_on_either_of_its_two_periods(): void
    {
        $maths = $this->lesson('Mathematics', 'Form 5A', 'Entire class', 'M Tau', entireClass: true);
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        // The single sits on period 2; the double covering 1-2 has to notice it.
        $this->placeCard($maths, day: 1, period: 2);

        $this->assertSame([Conflict::STUDENTS], $this->kinds(
            $this->checker->check($bio, dayNumber: 1, startPeriod: 1),
        ));
    }

    // -----------------------------------------------------------------
    // Moving
    // -----------------------------------------------------------------

    /**
     * A card being dragged must not collide with the copy of itself it is leaving behind,
     * or nothing could ever be nudged one period sideways.
     */
    public function test_a_card_being_moved_does_not_clash_with_itself(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $card = $this->placeCard($bio, day: 1, period: 1);

        $this->assertNotSame([], $this->checker->check($bio, dayNumber: 1, startPeriod: 1));
        $this->assertSame([], $this->checker->check(
            $bio, dayNumber: 1, startPeriod: 1, ignoreCardIds: [$card->id],
        ));
    }

    public function test_a_lesson_in_another_setting_is_invisible(): void
    {
        $other = Setting::factory()->draft()->create();
        PeriodFactory::layDay($other);

        $mine = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $theirs = Lesson::factory()->for($other, 'setting')->single()->create([
            'subject_id' => $this->subjects['Biology']->id,
        ]);
        $theirs->classes()->attach($this->classes['Form 5A']->id);
        $theirs->teachers()->attach($this->teachers['K Simukonda']->id);

        $this->placeCard($theirs, day: 1, period: 1);

        $this->assertSame([], $this->checker->check($mine, dayNumber: 1, startPeriod: 1));
    }

    /**
     * @param  list<Conflict>  $conflicts
     * @return list<string>
     */
    private function kinds(array $conflicts): array
    {
        return array_values(array_unique(array_map(fn (Conflict $c) => $c->kind, $conflicts)));
    }
}
