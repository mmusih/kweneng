<?php

namespace Tests\Feature\Timetable;

use App\Services\Timetable\ClassCapacity;
use App\Services\Timetable\LessonEditorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class ClassCapacityTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    public function test_parallel_options_share_capacity_and_extra_cards_can_be_kept_in_the_tray(): void
    {
        $this->seedSchool();
        $whole = $this->lesson('Mathematics', 'Form 5A', 'All', 'K Simukonda', entireClass: true, periodsPerWeek: 40);
        $this->lesson('Biology', 'Form 5A', 'Bio', 'N Chisenga', periodsPerWeek: 8);
        $this->lesson('Chemistry', 'Form 5A', 'Chem', 'Zoë Kgosi', periodsPerWeek: 8);
        $this->lesson('Physics', 'Form 5A', 'Physics', 'M Tau', periodsPerWeek: 6);
        $capacity = app(ClassCapacity::class);
        $this->assertSame(48, $capacity->limit($this->setting));
        $this->assertEquals(48, $capacity->usage($this->setting)[$this->classes['Form 5A']->id]);
        $before = $this->setting->lessons()->count();
        app(LessonEditorService::class)->create($this->setting, [
            'subject_id' => $whole->subject_id, 'teacher_ids' => [], 'room_ids' => [],
            'class_ids' => [$this->classes['Form 5A']->id], 'group_ids' => [],
            'cards_per_cycle' => 12, 'periods_per_card' => 1,
        ]);
        $this->assertSame($before + 1, $this->setting->lessons()->count());
        $this->assertEquals(60, $capacity->usage($this->setting)[$this->classes['Form 5A']->id]);
        $this->assertDatabaseCount('tt_cards', 0);

    }

    public function test_five_parallel_doubles_use_two_of_the_48_grid_periods(): void
    {
        $this->seedSchool();
        $this->lesson('Mathematics', 'Form 5A', 'All', 'K Simukonda', entireClass: true, periodsPerWeek: 46);
        foreach (['Biology', 'Chemistry', 'Setswana', 'Physics', 'Mathematics'] as $subject) {
            $this->lesson($subject, 'Form 5A', $subject.' option', 'N Chisenga', double: true, periodsPerWeek: 2);
        }
        $capacity = app(ClassCapacity::class);
        $this->assertEquals(48, $capacity->usage($this->setting)[$this->classes['Form 5A']->id]);
    }

    public function test_joint_double_cards_charge_both_classes_and_use_the_current_day_structure(): void
    {
        $this->seedSchool();
        $lesson = app(LessonEditorService::class)->create($this->setting, [
            'subject_id' => $this->subjects['Mathematics']->id, 'teacher_ids' => [], 'room_ids' => [],
            'class_ids' => $this->classes->pluck('id')->all(), 'group_ids' => [],
            'cards_per_cycle' => 24, 'periods_per_card' => 2,
        ]);
        $capacity = app(ClassCapacity::class);
        $this->assertEquals([48, 48], array_values($capacity->usage($this->setting)));
        $this->setting->update(['cycle_length' => 5]);
        $this->assertSame(40, $capacity->limit($this->setting));
    }
}
