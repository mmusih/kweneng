<?php

namespace Tests\Feature\Timetable\Concerns;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Card;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\User;
use Database\Factories\Tt\PeriodFactory;
use Illuminate\Support\Collection;

/**
 * A small school shaped like the real one: two Form 5 streams, an eight-period day with
 * break after period 4, and — the part that matters — an option block per class, so the
 * parallel-groups rule has something to be tested against.
 */
trait SeedsTimetableSchool
{
    protected Setting $setting;

    protected AcademicYear $year;

    /** @var Collection<string, ClassModel> */
    protected Collection $classes;

    /** @var Collection<string, Subject> */
    protected Collection $subjects;

    /** @var Collection<string, Teacher> */
    protected Collection $teachers;

    /** @var Collection<string, Room> */
    protected Collection $rooms;

    /** @var Collection<string, Division> */
    protected Collection $options;

    /** A second division of Form 5A, so "different divisions" has a case. */
    protected Division $science;

    protected function seedSchool(): void
    {
        $this->year = AcademicYear::create([
            'year_name' => '2026',
            'active' => true,
            'status' => AcademicYear::STATUS_OPEN,
        ]);

        $this->setting = Setting::factory()->create([
            'academic_year_id' => $this->year->id,
            'cycle_length' => 6,
        ]);

        PeriodFactory::layDay($this->setting);

        BreakPeriod::create([
            'tt_setting_id' => $this->setting->id,
            'name' => 'Breaktime',
            'short_name' => 'BK',
            'start_time' => '10:10:00',
            'end_time' => '10:30:00',
            'after_period' => 4,
        ]);

        $this->classes = collect(['Form 5A', 'Form 5B'])->mapWithKeys(
            fn (string $name) => [$name => ClassModel::create([
                'name' => $name,
                'level' => 5,
                'academic_year_id' => $this->year->id,
            ])],
        );

        $this->subjects = collect(['Biology', 'Chemistry', 'Setswana', 'Physics', 'Mathematics'])
            ->mapWithKeys(fn (string $name) => [$name => Subject::create([
                'name' => $name,
                'code' => strtoupper(substr($name, 0, 3)),
                'is_active' => true,
            ])]);

        $this->teachers = collect(['K Simukonda', 'N Chisenga', 'Zoë Kgosi', 'M Tau'])
            ->mapWithKeys(function (string $name, int $i) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => 'tt.teacher.'.$i.'@example.test',
                    'role' => 'teacher',
                    'status' => 'active',
                ]);

                return [$name => Teacher::create(['user_id' => $user->id])];
            });

        $this->rooms = collect(['Lab 1', 'Room 2'])->mapWithKeys(
            fn (string $name) => [$name => Room::create([
                'tt_setting_id' => $this->setting->id,
                'name' => $name,
            ])],
        );

        $this->options = $this->classes->map(fn (ClassModel $class) => Division::create([
            'class_id' => $class->id,
            'division_tag' => 1,
            'name' => 'Option block',
        ]));

        $this->science = Division::create([
            'class_id' => $this->classes['Form 5A']->id,
            'division_tag' => 2,
            'name' => 'Science block',
        ]);
    }

    /**
     * @param  list<string>  $rooms  names of rooms the lesson may use, in preference order
     */
    protected function lesson(
        string $subject,
        string $class,
        string $groupName,
        string $teacher,
        ?Division $division = null,
        bool $entireClass = false,
        bool $double = false,
        ?float $periodsPerWeek = null,
        array $rooms = [],
    ): Lesson {
        $classModel = $this->classes[$class];

        // Same class + same name is the same group. Minting a fresh row per call would
        // quietly turn "two lessons sharing BIO A" into two disjoint groups of one
        // division — which is legal, so the clash test would pass for the wrong reason.
        $group = Group::firstOrCreate(
            ['class_id' => $classModel->id, 'name' => $groupName],
            [
                // Default to the option block of the lesson's own class — a division always
                // belongs to one class, so borrowing another class's would be nonsense data.
                'tt_division_id' => $entireClass ? null : ($division ?? $this->options[$class])->id,
                'entire_class' => $entireClass,
            ],
        );

        $lesson = Lesson::factory()
            ->for($this->setting, 'setting')
            ->{$double ? 'double' : 'single'}()
            ->create(array_filter([
                'subject_id' => $this->subjects[$subject]->id,
                'periods_per_week' => $periodsPerWeek,
            ], fn ($value) => $value !== null));

        $lesson->classes()->attach($classModel->id);
        $lesson->groups()->attach($group->id);
        $lesson->teachers()->attach($this->teachers[$teacher]->id);

        foreach (array_values($rooms) as $order => $name) {
            $lesson->rooms()->attach($this->rooms[$name]->id, ['sort_order' => $order]);
        }

        return $lesson->fresh();
    }

    /**
     * Write cards straight to the table, bypassing CardPlacementService — so a test of
     * that service is never validating its own output.
     */
    protected function placeCard(
        Lesson $lesson,
        int $day,
        int $period,
        ?Room $room = null,
        string $terms = '1',
    ): Card {
        $days = str_repeat('0', $day - 1).'1'.str_repeat('0', 6 - $day);

        $first = null;

        foreach (range(0, max(1, (int) $lesson->periods_per_card) - 1) as $offset) {
            $card = Card::create([
                'tt_lesson_id' => $lesson->id,
                'period_number' => $period + $offset,
                'days' => $days,
                'weeks' => '1',
                'terms' => $terms,
                'tt_room_id' => $room?->id,
            ]);

            $first ??= $card;
        }

        return $first;
    }
}
