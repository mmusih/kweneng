<?php

namespace Tests\Feature\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Tt\Setting;
use App\Models\User;
use App\Services\Timetable\CardPlacementService;
use App\Services\Timetable\CardsScheduleService;
use App\Services\Timetable\ConflictChecker;
use App\Services\Timetable\GridPayload;
use App\Services\Timetable\PrintScheduleService;
use App\Services\Timetable\TeacherLoadService;
use App\Services\Timetable\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class ClassLifecycleTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->setting->update(['is_active' => true, 'is_published' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    private function participation(array $excluded, ?int $version = null)
    {
        return $this->post(route('admin.timetable.classes.update', $this->setting), [
            'excluded_class_ids' => $excluded,
            'version' => $version ?? $this->setting->fresh()->preparation_version,
        ]);
    }

    private function student(): Student
    {
        return Student::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'admission_no' => 'LIFECYCLE-001', 'gender' => 'female', 'date_of_birth' => '2010-01-01',
            'current_class_id' => $this->classes['Form 5A']->id,
        ]);
    }

    public function test_removal_updates_grid_public_schedule_loads_prints_and_verification_and_restores_cards(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 2);
        $card = $this->placeCard($lesson, 1, 1);
        $student = $this->student();
        $this->get(route('admin.timetable.classes', $this->setting))->assertOk()->assertSee('Temporarily remove classes');
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $setting = $this->setting->fresh();
        $grid = app(GridPayload::class)->build($setting);
        $this->assertCount(1, $grid['classes']);
        $this->assertEmpty($grid['cards']);
        $this->assertEmpty($grid['tray']);
        $this->assertSame(0, app(TeacherLoadService::class)->forTeacher($this->teachers['K Simukonda'])['grand_scheduled']);
        $schedule = app(CardsScheduleService::class)->forTeacher($this->teachers['K Simukonda']);
        $this->assertEmpty(collect($schedule['days'])->flatMap(fn ($day) => $day['blocks'])->where('kind', 'lesson')->all());
        $schedule = app(CardsScheduleService::class)->forStudent($student);
        $this->assertEmpty(collect($schedule['days'])->flatMap(fn ($day) => $day['blocks'])->where('kind', 'lesson')->all());
        $print = app(PrintScheduleService::class)->build($setting, 'teacher', $this->teachers['K Simukonda']->id);
        $this->assertEmpty($print['cells']);
        $this->assertCount(1, app(PrintScheduleService::class)->entities($setting, 'class'));
        $this->assertTrue(app(VerificationService::class)->verify($setting)['clean']);
        $this->assertDatabaseHas('tt_cards', ['id' => $card->id, 'tt_lesson_id' => $lesson->id]);
        $this->participation([])->assertSessionHasNoErrors();
        $this->assertCount(1, app(GridPayload::class)->build($this->setting->fresh())['cards']);
        $this->assertSame(1, app(TeacherLoadService::class)->forTeacher($this->teachers['K Simukonda'])['grand_scheduled']);
        $this->assertSame($card->id, $lesson->cards()->sole()->id);
    }

    public function test_shared_lesson_remains_for_other_class_without_detaching_saved_participants(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 1);
        $lesson->classes()->attach($this->classes['Form 5B']->id);
        $this->placeCard($lesson, 1, 1);
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $grid = app(GridPayload::class)->build($this->setting->fresh());
        $this->assertCount(1, $grid['cards']);
        $this->assertSame([$this->classes['Form 5B']->id], $grid['cards'][0]['class_ids']);
        $this->assertSame(1, app(TeacherLoadService::class)->forTeacher($this->teachers['K Simukonda'])['grand_scheduled']);
        $this->assertCount(2, $lesson->fresh()->classes);
        $this->participation([$this->classes['Form 5A']->id, $this->classes['Form 5B']->id])->assertSessionHasNoErrors();
        $this->assertEmpty(app(GridPayload::class)->build($this->setting->fresh())['cards']);
    }

    public function test_freed_period_can_be_reused_but_clashing_restoration_rolls_back(): void
    {
        $old = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 1);
        $other = $this->lesson('Physics', 'Form 5B', 'PHY', 'K Simukonda', periodsPerWeek: 1);
        $this->placeCard($old, 1, 1, $this->rooms['Lab 1']);
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $this->assertTrue((new ConflictChecker)->allows($other->fresh(), 1, 1, $this->rooms['Lab 1']->id));
        $unit = app(CardPlacementService::class)->place($other, 1, 1, $this->rooms['Lab 1']->id);
        $this->participation([])->assertSessionHasErrors('classes');
        $this->assertSame([$this->classes['Form 5A']->id], $this->setting->fresh()->excluded_class_ids);
        app(CardPlacementService::class)->unplace($unit);
        $this->participation([])->assertSessionHasNoErrors();
        $this->assertSame([], $this->setting->fresh()->excluded_class_ids);
    }

    public function test_participation_rejects_foreign_classes_and_stale_forms(): void
    {
        $year = AcademicYear::create(['year_name' => '2027', 'status' => 'open']);
        $foreign = ClassModel::create(['name' => 'Form 2B', 'level' => 2, 'academic_year_id' => $year->id]);
        $this->participation([$foreign->id])->assertSessionHasErrors('classes');
        $this->participation([$this->classes['Form 5A']->id], 0)->assertSessionHasNoErrors();
        $this->participation([], 0)->assertSessionHasErrors('classes');
        $this->assertSame([$this->classes['Form 5A']->id], $this->setting->fresh()->excluded_class_ids);
    }

    private function rolloverData(): array
    {
        $year = AcademicYear::create(['year_name' => '2027', 'status' => 'open']);
        $target = ClassModel::create(['name' => 'Form 2B', 'level' => 2, 'academic_year_id' => $year->id]);

        return ['target_year_id' => $year->id, 'name' => '2027 timetable', 'cycle_anchor_date' => '2027-01-11',
            'cycle_anchor_day' => 1, 'mapping' => [$this->classes['Form 5A']->id => $target->id, $this->classes['Form 5B']->id => 0]];
    }

    public function test_rollover_copies_custom_mapping_rooms_groups_and_disabled_lessons_without_promoting_students(): void
    {
        $this->classes['Form 5A']->update(['name' => 'Form 1A', 'level' => 1]);
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 2, double: true, rooms: ['Lab 1']);
        $lesson->update(['preparation_key' => (string) \Illuminate\Support\Str::uuid(), 'assignment_sources' => [['key' => 'old-class']], 'split_key' => (string) \Illuminate\Support\Str::uuid()]);
        $this->placeCard($lesson, 1, 1, $this->rooms['Lab 1']);
        $leaving = $this->lesson('Physics', 'Form 5B', 'PHY', 'M Tau', periodsPerWeek: 1);
        $this->placeCard($leaving, 2, 1);
        $student = $this->student();
        $lesson->groups->first()->students()->attach($student->id);
        DB::table('tt_class_base_rooms')->insert(['tt_setting_id' => $this->setting->id, 'class_id' => $this->classes['Form 5A']->id, 'tt_room_id' => $this->rooms['Lab 1']->id]);
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $data = $this->rolloverData();
        $this->get(route('admin.timetable.classes', [$this->setting, 'target_year_id' => $data['target_year_id']]))->assertOk()->assertSee('Form 2B');
        $this->post(route('admin.timetable.rollover', $this->setting), $data)->assertSessionHasNoErrors();
        $copy = Setting::latest('id')->first();
        $this->assertNotSame($this->setting->id, $copy->id);
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_published);
        $this->assertSame([], $copy->excluded_class_ids);
        $this->assertSame($data['target_year_id'], $copy->academic_year_id);
        $this->assertSame('2027-01-11', $copy->cycle_anchor_date->toDateString());
        $newLesson = $copy->lessons()->sole();
        $this->assertSame('Form 2B', $newLesson->classes->sole()->name);
        $this->assertNull($newLesson->preparation_key);
        $this->assertNull($newLesson->assignment_sources);
        $this->assertNotSame($lesson->split_key, $newLesson->split_key);
        $this->assertSame($this->teachers['K Simukonda']->id, $newLesson->teachers->sole()->id);
        $this->assertCount(2, $newLesson->cards);
        $this->assertSame($copy->id, $newLesson->cards->first()->room->tt_setting_id);
        $this->assertSame($newLesson->classes->sole()->id, $newLesson->groups->sole()->division->class_id);
        $this->assertSame(0, $newLesson->groups->sole()->students()->count());
        $this->assertSame($this->classes['Form 5A']->id, $student->fresh()->current_class_id);
        $this->assertSame(2, $this->setting->lessons()->count());
        $this->assertCount(1, app(GridPayload::class)->build($copy)['cards']);
        $this->assertSame($copy->rooms()->where('name', 'Lab 1')->sole()->id, DB::table('tt_class_base_rooms')->where('tt_setting_id', $copy->id)->value('tt_room_id'));
    }

    public function test_rollover_rejects_missing_duplicate_wrong_year_and_same_year_mappings_atomically(): void
    {
        $data = $this->rolloverData();
        $invalid = $data;
        unset($invalid['mapping'][$this->classes['Form 5B']->id]);
        $this->post(route('admin.timetable.rollover', $this->setting), $invalid)->assertSessionHasErrors('mapping');
        $invalid = $data;
        $invalid['mapping'][$this->classes['Form 5B']->id] = $invalid['mapping'][$this->classes['Form 5A']->id];
        $this->post(route('admin.timetable.rollover', $this->setting), $invalid)->assertSessionHasErrors('mapping');
        $invalid = $data;
        $invalid['mapping'][$this->classes['Form 5A']->id] = $this->classes['Form 5B']->id;
        $this->post(route('admin.timetable.rollover', $this->setting), $invalid)->assertSessionHasErrors('mapping');
        $invalid = $data;
        $invalid['target_year_id'] = $this->year->id;
        $this->post(route('admin.timetable.rollover', $this->setting), $invalid)->assertSessionHasErrors('mapping');
        $this->assertSame(1, Setting::count());
    }

    public function test_teacher_cannot_change_participation_or_copy_a_timetable(): void
    {
        $this->actingAs($this->teachers['K Simukonda']->user);
        $this->get(route('admin.timetable.classes', $this->setting))->assertForbidden();
        $this->participation([])->assertForbidden();
        $this->post(route('admin.timetable.rollover', $this->setting), $this->rolloverData())->assertForbidden();
    }

    public function test_removal_is_per_schedule_and_split_moves_leave_removed_cards_saved(): void
    {
        $first = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 1);
        $second = $this->lesson('Physics', 'Form 5B', 'PHY', 'M Tau', periodsPerWeek: 1);
        $first->update(['split_key' => 'split']);
        $second->update(['split_key' => 'split']);
        $firstCard = $this->placeCard($first, 1, 1);
        $secondCard = $this->placeCard($second, 1, 1);
        $afternoon = Setting::factory()->create(['academic_year_id' => $this->year->id, 'schedule_type' => 'afternoon', 'is_active' => true, 'is_published' => true]);
        $study = $this->lesson('Biology', 'Form 5A', 'Study', 'K Simukonda', periodsPerWeek: 1);
        $study->update(['tt_setting_id' => $afternoon->id]);
        $this->placeCard($study, 1, 1);
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $this->assertSame(1, app(TeacherLoadService::class)->forTeacher($this->teachers['K Simukonda'])['grand_scheduled']);
        $placement = app(CardPlacementService::class);
        $placement->move($placement->unitContaining($secondCard->fresh()), 2, 2);
        $this->assertSame('100000', $firstCard->fresh()->days);
        $this->assertSame(1, $firstCard->fresh()->period_number);
        $this->assertSame([], $placement->linkedLessons($first->fresh())->where('id', $first->id)->all());
    }

    public function test_shared_lesson_details_cannot_silently_drop_removed_class_and_stale_unplace_is_rejected(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', periodsPerWeek: 1);
        $card = $this->placeCard($lesson, 1, 1);
        $this->participation([$this->classes['Form 5A']->id])->assertSessionHasNoErrors();
        $this->postJson(route('admin.timetable.grid.unplace'), ['setting_id' => $this->setting->id, 'card_id' => $card->id])->assertUnprocessable();
        $this->assertDatabaseHas('tt_cards', ['id' => $card->id]);
        $lesson->classes()->attach($this->classes['Form 5B']->id);
        $this->deleteJson(route('admin.timetable.grid.lesson.destroy'), ['setting_id' => $this->setting->id, 'lesson_id' => $lesson->id])->assertUnprocessable();
        $this->assertCount(2, $lesson->fresh()->classes);
    }
}
