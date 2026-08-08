<?php

namespace Tests\Feature\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Card;
use App\Models\Tt\DaysDef;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Period;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\User;
use App\Support\Timetable\Bitmask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 acceptance test for the tt_* schema.
 *
 * Everything here is built with Model::create() rather than factories, on purpose:
 * this test is the proof that the migrations and models stand on their own, so it
 * must not inherit a factory's assumptions (or a factory's bugs).
 *
 * Run it on BOTH engines. sqlite is the fast inner loop; MariaDB 10.4 is the truth:
 *
 *     php artisan test tests/Feature/Timetable/TtSchemaTest.php
 *     vendor/bin/phpunit -c phpunit.mysql.xml tests/Feature/Timetable/TtSchemaTest.php
 *
 * The two engines disagree in ways that matter here — most visibly DECIMAL, which
 * MariaDB hands back as the string "6.0" and sqlite as a float. Assertions on
 * periods_per_week are therefore always numeric, never assertSame against a literal.
 */
class TtSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every table Phase 1 promised, in migration order.
     *
     * @var list<string>
     */
    private const TT_TABLES = [
        // 000001 — core
        'tt_settings',
        'tt_periods',
        'tt_breaks',
        'tt_daysdefs',
        'tt_weeksdefs',
        'tt_termsdefs',
        'tt_rooms',
        'tt_subject_meta',
        'tt_teacher_meta',
        // 000002 — divisions and groups
        'tt_divisions',
        'tt_groups',
        'tt_group_student',
        // 000003 — lessons and cards
        'tt_lessons',
        'tt_lesson_teacher',
        'tt_lesson_class',
        'tt_lesson_group',
        'tt_lesson_room',
        'tt_cards',
        // 000004 — constraints
        'tt_constraints',
        'tt_generation_runs',
    ];

    public function test_every_tt_table_exists(): void
    {
        foreach (self::TT_TABLES as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table [{$table}] to exist after the tt_* migrations ran.",
            );
        }
    }

    /**
     * The central schema fix of the whole rebuild.
     *
     * Legacy timetable_periods hangs each period off a single day, which duplicates
     * "Period 1" once per day and makes a card's days bitmask meaningless — its period
     * id already names one day. tt_periods is instead global to the setting, which is
     * what lets tt_cards.days be a real 6-wide mask. If tt_setting_id ever turns back
     * into timetable_day_id, everything downstream (doubles, cross-class options, aSc
     * round-trip) silently breaks, so it is pinned here.
     */
    public function test_tt_periods_are_global_to_the_setting_not_per_day(): void
    {
        $this->assertTrue(Schema::hasColumn('tt_periods', 'tt_setting_id'));
        $this->assertFalse(
            Schema::hasColumn('tt_periods', 'timetable_day_id'),
            'tt_periods must NOT be scoped to a day: periods are global to the setting.',
        );

        // The contrast that makes the point — the legacy table is left alone and still
        // per-day, which is exactly why tt_periods had to be a new table.
        $this->assertTrue(Schema::hasColumn('timetable_periods', 'timetable_day_id'));
    }

    public function test_a_realistic_graph_can_be_built_directly_from_the_models(): void
    {
        $g = $this->graph();

        $this->assertSame(6, (int) $g['setting']->cycle_length);
        $this->assertSame(8, $g['setting']->periods()->count());
        $this->assertSame(1, $g['setting']->breaks()->count());
        $this->assertSame(1, $g['setting']->daysdefs()->count());
        $this->assertSame(2, $g['setting']->rooms()->count());
        $this->assertCount(3, $g['subjects']);
        $this->assertCount(2, $g['teachers']);

        // periods() is ordered by period_number, so this also proves the 1..8 sequence.
        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 8],
            $g['setting']->periods()->pluck('period_number')->map(fn ($n) => (int) $n)->all(),
        );

        // The break sits between period 4 and period 5 — "Breaktime", as the school's
        // own aSc export has it.
        $this->assertSame(4, (int) $g['break']->after_period);

        // "Any day" is the six single-day masks, one per day of the cycle, stored in
        // aSc's own comma-separated form (§7) and read back through masks().
        $this->assertSame(
            ['100000', '010000', '001000', '000100', '000010', '000001'],
            array_map(
                fn (Bitmask $mask) => $mask->toAscString(),
                $g['daysdef']->fresh()->masks(),
            ),
        );
    }

    public function test_a_division_carries_a_three_wide_option_block(): void
    {
        $g = $this->graph();
        $division = $this->optionBlock($g);

        $this->assertSame(2, (int) $division->division_tag);
        $this->assertSame(3, $division->groups()->count());

        $groups = $division->groups()->orderBy('id')->get();

        $this->assertSame(
            ['Art', 'French', 'Setswana'],
            $groups->pluck('name')->sort()->values()->all(),
        );

        // Every group in an option block belongs to the class the block divides —
        // a group whose class_id drifted from its division's would let the generator
        // place a lesson for students who are not in the room.
        foreach ($groups as $group) {
            $this->assertSame((int) $g['class']->id, (int) $group->class_id);
            $this->assertSame((int) $g['class']->id, (int) $group->division->class_id);
            $this->assertFalse($group->entire_class);
        }
    }

    public function test_a_cross_class_option_lesson_round_trips_all_of_its_pivots(): void
    {
        $g = $this->graph();
        $lesson = $this->crossClassLesson($g);

        $fresh = Lesson::with(['classes', 'groups', 'teachers', 'rooms'])->findOrFail($lesson->id);

        $this->assertCount(2, $fresh->classes);
        $this->assertCount(2, $fresh->groups);
        $this->assertCount(1, $fresh->teachers);
        $this->assertCount(3, $fresh->rooms);

        // MariaDB returns DECIMAL(4,1) as the string "6.0"; sqlite returns a float.
        // Compare numerically or this passes on one engine and fails on the other.
        $this->assertEquals(6.0, (float) $fresh->periods_per_week);
        $this->assertEquals(
            6.0,
            (float) DB::table('tt_lessons')->where('id', $lesson->id)->value('periods_per_week'),
        );
        $this->assertSame(2, (int) $fresh->periods_per_card);

        // Candidate rooms are a preference list, so order is data, not incidental:
        // the resolver walks them and takes the first free one.
        $this->assertSame(
            [0, 1, 2],
            $fresh->rooms->map(fn (Room $room) => (int) $room->pivot->sort_order)->all(),
        );
        $this->assertSame(
            ['Lab 1', 'Room A', 'Room B'],
            $fresh->rooms->pluck('name')->all(),
        );
    }

    public function test_a_double_is_two_cards_on_consecutive_periods_sharing_a_days_mask(): void
    {
        $g = $this->graph();
        $lesson = $this->crossClassLesson($g);
        $cards = $this->placeAsDouble($lesson, $g['rooms'][0]);

        $fresh = $lesson->fresh(['cards']);

        $this->assertCount(2, $fresh->cards);
        $this->assertSame(
            [7, 8],
            $fresh->cards->pluck('period_number')->map(fn ($n) => (int) $n)->sort()->values()->all(),
        );
        $this->assertSame(['010000', '010000'], $fresh->cards->pluck('days')->all());
        $this->assertSame(
            [(int) $g['rooms'][0]->id, (int) $g['rooms'][0]->id],
            $fresh->cards->pluck('tt_room_id')->map(fn ($id) => (int) $id)->all(),
        );

        // Two card ROWS, but three card GROUPS still required: 6.0 periods a week at
        // 2 periods a card means three doubles, of which this is one.
        $this->assertSame(3, $fresh->cardsRequired());
        $this->assertSame(3, $cards[0]->lesson->cardsRequired());
    }

    public function test_a_card_days_mask_is_a_six_wide_bitmask_over_the_cycle(): void
    {
        $g = $this->graph();
        $lesson = $this->crossClassLesson($g);
        $cards = $this->placeAsDouble($lesson, $g['rooms'][0]);

        $mask = Card::findOrFail($cards[0]->id)->daysMask();

        $this->assertInstanceOf(Bitmask::class, $mask);

        // Width comes from the setting's cycle_length (6), never from a constant —
        // a 5-wide assumption is the bug this school's 6-day rotation would expose.
        $this->assertSame(6, $mask->width());
        $this->assertSame('010000', $mask->toAscString());
        $this->assertSame([2], $mask->positions());
    }

    public function test_deleting_a_setting_cascades_to_its_periods_lessons_and_cards(): void
    {
        $g = $this->graph();
        $lesson = $this->crossClassLesson($g);
        $this->placeAsDouble($lesson, $g['rooms'][0]);

        $this->assertSame(8, DB::table('tt_periods')->count());
        $this->assertSame(1, DB::table('tt_lessons')->count());
        $this->assertSame(2, DB::table('tt_cards')->count());

        $g['setting']->delete();

        $this->assertSame(0, DB::table('tt_settings')->count());
        $this->assertSame(0, DB::table('tt_periods')->count());
        $this->assertSame(0, DB::table('tt_breaks')->count());
        $this->assertSame(0, DB::table('tt_daysdefs')->count());
        $this->assertSame(0, DB::table('tt_rooms')->count());
        $this->assertSame(0, DB::table('tt_lessons')->count());
        $this->assertSame(0, DB::table('tt_cards')->count());
        $this->assertSame(0, DB::table('tt_lesson_class')->count());
        $this->assertSame(0, DB::table('tt_lesson_group')->count());
        $this->assertSame(0, DB::table('tt_lesson_teacher')->count());
        $this->assertSame(0, DB::table('tt_lesson_room')->count());

        // A timetable revision is disposable; the academic data underneath it is not.
        // Dropping a setting must never reach classes, subjects or teachers.
        $this->assertSame(2, DB::table('classes')->count());
        $this->assertSame(3, DB::table('subjects')->count());
        $this->assertSame(2, DB::table('teachers')->count());
        $this->assertSame(3, DB::table('tt_groups')->count());
    }

    /**
     * The migration (000002) declares tt_groups.tt_division_id as
     * ->nullable()->constrained('tt_divisions')->cascadeOnDelete(), so dropping a
     * division drops the groups inside it rather than orphaning them as loose
     * whole-class groups. That is what is asserted here — the rule as written, not a
     * guess at what it ought to be.
     */
    public function test_deleting_a_division_cascades_to_the_groups_inside_it(): void
    {
        $g = $this->graph();
        $division = $this->optionBlock($g);

        $wholeClass = Group::create([
            'class_id' => $g['class']->id,
            'tt_division_id' => null,
            'name' => 'Form 5A',
            'entire_class' => true,
        ]);

        $this->assertSame(4, DB::table('tt_groups')->count());

        $division->delete();

        $this->assertSame(0, DB::table('tt_divisions')->count());
        $this->assertSame(0, Group::whereNotNull('tt_division_id')->count());

        // The undivided whole-class group hangs off no division and must survive.
        $this->assertSame(1, DB::table('tt_groups')->count());
        $this->assertNotNull($wholeClass->fresh());
    }

    // ---------------------------------------------------------------------
    // Fixture
    // ---------------------------------------------------------------------

    /**
     * The school's real shape, minus the scale: 6-day rotating cycle, 8 periods,
     * one break before period 5.
     *
     * @return array<string, mixed>
     */
    private function graph(): array
    {
        $year = AcademicYear::create([
            'year_name' => '2026',
            'active' => true,
            'status' => AcademicYear::STATUS_OPEN,
        ]);

        $class = ClassModel::create([
            'name' => 'Form 5A',
            'level' => 5,
            'academic_year_id' => $year->id,
        ]);

        $subjects = collect([
            ['name' => 'French', 'code' => 'FRE'],
            ['name' => 'Setswana', 'code' => 'SET'],
            ['name' => 'Art', 'code' => 'ART'],
        ])->map(fn (array $attributes) => Subject::create($attributes + ['is_active' => true]))->all();

        $teachers = collect(['Kabelo Chisenga', 'Naledi Simukonda'])
            ->map(function (string $name, int $i) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => 'tt.teacher.'.$i.'@example.test',
                    'role' => 'teacher',
                    'status' => 'active',
                ]);

                return Teacher::create(['user_id' => $user->id]);
            })->all();

        $setting = Setting::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2 timetable',
            'term_label' => 'Term 2',
            'revision' => 5,
            'cycle_length' => 6,
            'is_active' => true,
            'is_published' => true,
        ]);

        // 07:30–13:10, eight periods of 40 minutes with the break folded in after 4.
        $start = strtotime('07:30');
        for ($n = 1; $n <= 8; $n++) {
            $offset = ($n - 1) * 40 * 60 + ($n > 4 ? 20 * 60 : 0);

            Period::create([
                'tt_setting_id' => $setting->id,
                'period_number' => $n,
                'name' => 'Period '.$n,
                'short_name' => (string) $n,
                'start_time' => date('H:i:s', $start + $offset),
                'end_time' => date('H:i:s', $start + $offset + 40 * 60),
            ]);
        }

        $break = BreakPeriod::create([
            'tt_setting_id' => $setting->id,
            'name' => 'Breaktime',
            'short_name' => 'Br',
            'start_time' => '10:10:00',
            'end_time' => '10:30:00',
            'after_period' => 4,
            'days' => '111111',
        ]);

        $daysdef = DaysDef::create([
            'tt_setting_id' => $setting->id,
            'name' => 'Any day',
            'short_name' => 'Any',
            'days' => implode(',', ['100000', '010000', '001000', '000100', '000010', '000001']),
            'asc_id' => 'DD-ANY',
        ]);

        $rooms = [
            Room::create([
                'tt_setting_id' => $setting->id,
                'name' => 'Room A',
                'short_name' => 'RA',
                'capacity' => 40,
            ]),
            Room::create([
                'tt_setting_id' => $setting->id,
                'name' => 'Room B',
                'short_name' => 'RB',
                'capacity' => 35,
            ]),
        ];

        return compact('year', 'class', 'subjects', 'teachers', 'setting', 'break', 'daysdef', 'rooms');
    }

    /**
     * One division, three groups — the option block shape from §5.6: a class splits
     * three ways by subject election and each branch becomes a group.
     *
     * @param  array<string, mixed>  $g
     */
    private function optionBlock(array $g): Division
    {
        $division = Division::create([
            'class_id' => $g['class']->id,
            'division_tag' => 2,
            'name' => 'Option block 2',
        ]);

        foreach ($g['subjects'] as $i => $subject) {
            Group::create([
                'class_id' => $g['class']->id,
                'tt_division_id' => $division->id,
                'name' => $subject->name,
                'entire_class' => false,
                'asc_id' => 'GRP-'.$i,
            ]);
        }

        return $division;
    }

    /**
     * One lesson serving two streams at once — the cross-class option block confirmed
     * in the school's export (one lesson over F5A+F5B). The second class and the third
     * candidate room are created here rather than in graph() because they exist only
     * to make this shape possible.
     *
     * @param  array<string, mixed>  $g
     */
    private function crossClassLesson(array $g): Lesson
    {
        $division = $this->optionBlock($g);
        $groups = $division->groups()->orderBy('id')->get();

        $partnerClass = ClassModel::create([
            'name' => 'Form 5B',
            'level' => 5,
            'academic_year_id' => $g['year']->id,
        ]);

        $lab = Room::create([
            'tt_setting_id' => $g['setting']->id,
            'name' => 'Lab 1',
            'short_name' => 'L1',
            'capacity' => 24,
        ]);

        $lesson = Lesson::create([
            'tt_setting_id' => $g['setting']->id,
            'subject_id' => $g['subjects'][0]->id,
            'periods_per_week' => 6.0,
            'periods_per_card' => 2,
            'tt_daysdef_id' => $g['daysdef']->id,
            'seminar_group' => null,
            'capacity' => null,
            'asc_id' => 'LSN-1',
        ]);

        $lesson->classes()->attach([$g['class']->id, $partnerClass->id]);
        $lesson->groups()->attach([$groups[0]->id, $groups[1]->id]);
        $lesson->teachers()->attach($g['teachers'][0]->id);
        $lesson->rooms()->attach([
            $lab->id => ['sort_order' => 0],
            $g['rooms'][0]->id => ['sort_order' => 1],
            $g['rooms'][1]->id => ['sort_order' => 2],
        ]);

        return $lesson;
    }

    /**
     * A double: two card rows on consecutive periods sharing one days mask and one
     * resolved room. 010000 is Day 2 of the 6-day cycle.
     *
     * @return list<Card>
     */
    private function placeAsDouble(Lesson $lesson, Room $room): array
    {
        return collect([7, 8])->map(fn (int $periodNumber) => Card::create([
            'tt_lesson_id' => $lesson->id,
            'period_number' => $periodNumber,
            'days' => '010000',
            'weeks' => '1',
            'terms' => '1',
            'tt_room_id' => $room->id,
            'locked' => false,
        ]))->all();
    }
}
