<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\User;
use App\Services\Timetable\CardPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class CardAttendanceTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    private function change(Lesson $lesson, ?int $cardId, int $groupId)
    {
        return $this->postJson(route('admin.timetable.grid.card-attendance'), [
            'setting_id' => $this->setting->id, 'lesson_id' => $lesson->id, 'card_id' => $cardId,
            'version' => $this->setting->fresh()->preparation_version,
            'attendance' => [['class_id' => $this->classes['Form 5A']->id, 'group_id' => $groupId]],
        ]);
    }

    private function group(): Group
    {
        return Group::create(['class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->science->id, 'name' => 'Filler group', 'entire_class' => false]);
    }

    public function test_one_placed_double_changes_attendance_without_changing_its_siblings(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'All', 'K Simukonda', entireClass: true, double: true, periodsPerWeek: 6);
        $placement = app(CardPlacementService::class);
        $first = $placement->place($lesson, 1, 1);
        $second = $placement->place($lesson, 2, 1);
        $group = $this->group();
        $this->change($lesson, $first->cardIds()[0], $group->id)->assertOk();
        $newLesson = Card::find($first->cardIds()[0])->lesson;
        $this->assertNotEquals($lesson->id, $newLesson->id);
        $this->assertSame([$group->id], $newLesson->groups->modelKeys());
        $this->assertSame(2, Card::whereIn('id', $first->cardIds())->where('tt_lesson_id', $newLesson->id)->count());
        $this->assertSame($lesson->id, Card::find($second->cardIds()[0])->tt_lesson_id);
        $this->assertTrue($lesson->fresh()->groups->first()->entire_class);
        $this->assertSame(2, $lesson->fresh()->cardsRequired());
    }

    public function test_a_tray_stack_separates_just_one_occurrence(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'All', 'K Simukonda', entireClass: true, periodsPerWeek: 3);
        $this->change($lesson, null, $this->group()->id)->assertOk();
        $this->assertSame(2, $lesson->fresh()->cardsRequired());
        $this->assertSame(3, (int) Lesson::sum('cards_per_cycle'));
        $this->assertDatabaseCount('tt_cards', 0);
    }

    public function test_new_student_clash_rolls_back_the_card_and_count_changes(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 3);
        $other = $this->lesson('Chemistry', 'Form 5A', 'CHE', 'N Chisenga');
        $placement = app(CardPlacementService::class);
        $unit = $placement->place($lesson, 1, 1);
        $placement->place($other, 1, 1);
        $this->change($lesson, $unit->cardIds()[0], $other->groups()->first()->id)->assertUnprocessable();
        $this->assertSame($lesson->id, Card::find($unit->cardIds()[0])->tt_lesson_id);
        $this->assertSame(3, $lesson->fresh()->cardsRequired());
        $this->assertDatabaseCount('tt_lessons', 2);
    }
}
