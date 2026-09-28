<?php

namespace Tests\Feature\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\TeacherSubject;
use App\Models\Tt\Card;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\User;
use App\Services\Timetable\Conflict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

/**
 * The grid endpoints, and the page that draws them.
 *
 * The centre of gravity is the last group of tests: the client is told which slots are
 * legal so it can shade them, but that answer is a snapshot. A second admin filling the
 * slot, a stale tab, or a crafted request all arrive here as "a move the client let
 * through" — and every one of them has to be refused by the server on its own authority.
 */
class GridControllerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSchool();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    // -----------------------------------------------------------------
    // The payload
    // -----------------------------------------------------------------

    public function test_room_menu_changes_and_clears_only_the_selected_double(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true, rooms: ['Lab 1']);
        $card = $this->placeCard($lesson, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $other = $this->placeCard($lesson, day: 2, period: 1, room: $this->rooms['Lab 1']);
        foreach ([$this->rooms['Room 2']->id, null] as $roomId) {
            $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.card-room'), [
                'setting_id' => $this->setting->id, 'card_id' => $card->id, 'room_id' => $roomId,
            ])->assertOk();
            $this->assertSame([$roomId, $roomId], Card::where('tt_lesson_id', $lesson->id)->where('days', '100000')->pluck('tt_room_id')->all());
            $this->assertSame($this->rooms['Lab 1']->id, $other->fresh()->tt_room_id);
            $this->assertSame([$this->rooms['Lab 1']->id], $lesson->rooms()->pluck('tt_rooms.id')->all());
        }
    }

    public function test_room_menu_refuses_occupied_rooms_and_locked_cards(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);
        $card = $this->placeCard($lesson, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $other = $this->lesson('Chemistry', 'Form 5B', 'CHE A', 'N Chisenga');
        $this->placeCard($other, day: 1, period: 2, room: $this->rooms['Room 2']);
        $payload = ['setting_id' => $this->setting->id, 'card_id' => $card->id, 'room_id' => $this->rooms['Room 2']->id];
        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.card-room'), $payload)->assertStatus(422);
        $this->assertSame($this->rooms['Lab 1']->id, $card->fresh()->tt_room_id);
        $card->update(['locked' => true]);
        $payload['room_id'] = null;
        $this->postJson(route('admin.timetable.grid.card-room'), $payload)->assertStatus(422);
        $this->assertSame($this->rooms['Lab 1']->id, $card->fresh()->tt_room_id);
    }

    public function test_the_grid_payload_carries_the_day_the_classes_and_the_tray(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 3);
        $this->placeCard($bio, day: 2, period: 3);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]));

        $response->assertOk()
            ->assertJsonPath('setting.id', $this->setting->id)
            ->assertJsonPath('setting.cycle_length', 6)
            ->assertJsonCount(6, 'days')
            ->assertJsonCount(8, 'periods')
            ->assertJsonPath('breaks.0.after_period', 4)
            ->assertJsonPath('cards.0.day', 2)
            ->assertJsonPath('cards.0.period', 3)
            ->assertJsonPath('cards.0.span', 1)
            ->assertJsonPath('cards.0.lesson_id', $bio->id);

        // Three periods a week, one placed, so two are still owed.
        $this->assertSame(2, $response->json('tray.0.unplaced'));
        $this->assertSame(['Form 5A', 'Form 5B'], collect($response->json('classes'))->pluck('name')->all());
    }

    public function test_a_double_is_one_card_spanning_two_periods(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);
        $this->placeCard($bio, day: 1, period: 2);

        $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.span', 2)
            ->assertJsonPath('cards.0.period', 2);
    }

    public function test_parallel_option_groups_stay_in_one_class_row(): void
    {
        // Even four parallel groups in one period must not grow the class into four rows.
        foreach ([['Biology', 'BIO A'], ['Chemistry', 'CHE A'], ['Setswana', 'SET 1'], ['Physics', 'PHY A']] as $i => [$subject, $group]) {
            $lesson = $this->lesson($subject, 'Form 5A', $group, ['K Simukonda', 'N Chisenga', 'Zoë Kgosi', 'M Tau'][$i]);
            $this->placeCard($lesson, day: 1, period: 1);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]));

        $response->assertOk()->assertJsonCount(4, 'cards');

        $form5a = collect($response->json('classes'))->firstWhere('name', 'Form 5A');
        $this->assertSame(1, $form5a['lanes'], 'A class always has exactly one row.');

        $lanes = collect($response->json('cards'))->pluck('lane')->sort()->values()->all();
        $this->assertSame([0, 0, 0, 0], $lanes, 'Every lesson card stays on the class row.');
    }

    public function test_option_cards_in_different_periods_share_the_same_class_row(): void
    {
        foreach ([['Biology', 'BIO A'], ['Chemistry', 'CHE A'], ['Setswana', 'SET 1']] as $i => [$subject, $group]) {
            $lesson = $this->lesson(
                $subject,
                'Form 5A',
                $group,
                ['K Simukonda', 'N Chisenga', 'Zoë Kgosi'][$i],
            );
            $this->placeCard($lesson, day: 1, period: 1 + ($i * 2));
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk();

        $form5a = collect($response->json('classes'))->firstWhere('name', 'Form 5A');
        $lanes = collect($response->json('cards'))->pluck('lane')->sort()->values()->all();

        $this->assertSame([0, 0, 0], $lanes);
        $this->assertSame(1, $form5a['lanes']);
    }

    public function test_the_editor_page_renders_the_grid_and_its_alpine_component(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 1, period: 1);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertSee('timetableGrid(', escape: false)
            ->assertSee('timetableDayStructure(', escape: false)
            ->assertSee('onTrayDrop()', escape: false)
            ->assertSee('Repeated lesson cards are stacked; drag the top card to the grid, or a placed card back here.', escape: false)
            ->assertSee('Math.max(48, Math.min(64, pixels))', escape: false)
            ->assertSee('Math.max(2, stack.count)', escape: false)
            ->assertSee("{ 'z-20': dragging }", escape: false)
            ->assertSee('fixed inset-x-3 bottom-3', escape: false)
            ->assertSee('Collapse unplaced lesson tray')
            ->assertSee('Show lessons for class')
            ->assertSee('Whole school')
            ->assertSee('item.class_ids.includes(classId)', escape: false)
            ->assertSee('Classes &amp; divisions', escape: false)
            ->assertSee('Create lesson cards')
            ->assertSee("editor.mode === 'edit' && closeEditor()", escape: false)
            ->assertSee('x-show="editor.mode === \'edit\'"', escape: false)
            ->assertSee('Creation is deliberately persistent', escape: false)
            ->assertSee('data-timetable-grid class="w-full overflow-visible', escape: false)
            ->assertSee('aria-label="Timetable days and periods"', escape: false)
            ->assertSee('x-bind:style="headingStyle"', escape: false)
            ->assertSee('const teacherPalette = [', escape: false)
            ->assertSee('teacherPalette[(teacherId * 7) % teacherPalette.length]', escape: false)
            ->assertSee('data-card-stack', escape: false)
            ->assertSee('No room — leave this class without a room')
            ->assertSee('tt-division-field', escape: false)
            ->assertSee('border-r-2 border-slate-500', escape: false)
            ->assertSee('border-b-2 border-slate-500', escape: false)
            ->assertSee('tt-day-divider', escape: false)
            ->assertSee('tt-midday-divider', escape: false)
            ->assertSee('tt-class-row', escape: false)
            ->assertSee('Attending classes and groups')
            ->assertSee('Change class / split…')
            ->assertDontSee('Import aSc timetable')
            ->assertSee('Form 5A')
            ->assertSee('Biology');

        $html = $response->getContent();

        // The timetable itself fits the viewport; the assignment dialog may scroll its table on mobile.
        $gridHtml = explode('<template x-if="preparationPayload">', $html)[0];
        $this->assertStringNotContainsString('overflow-x-auto', $gridHtml);
        $response->assertSee('Lessons from teacher assignments')->assertSee('Save &amp; timetable', escape: false);
        $this->assertStringNotContainsString('zoomSteps', $html);
        $this->assertLessThan(
            strpos($html, 'Unplaced lessons'),
            strpos($html, 'aria-label="Timetable days"') ?: strpos($html, 'Class'),
            'The class grid should be rendered before the unplaced lesson tray.',
        );

        foreach (['grid.candidates', 'grid.move', 'grid.unplace', 'grid.lock', 'day-structure'] as $name) {
            $response->assertSee(route('admin.timetable.'.$name, absolute: false));
        }

        $response->assertSee(route('admin.timetable.settings.publish', $this->setting, absolute: false));
    }

    public function test_the_grid_exposes_right_click_lesson_editor_options(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: ['Lab 1']);
        $this->placeCard($bio, day: 1, period: 2, room: $this->rooms['Lab 1']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertJsonPath('cards.0.subject_id', $bio->subject_id)
            ->assertJsonPath('cards.0.teacher_ids.0', $this->teachers['K Simukonda']->id)
            ->assertJsonPath('cards.0.room_ids.0', $this->rooms['Lab 1']->id)
            ->assertJsonFragment(['name' => 'N Chisenga'])
            ->assertJsonFragment(['name' => 'Room 2']);

        $this->assertNotEmpty($response->json('editor.subjects'));
    }

    public function test_card_colours_follow_the_teacher_and_missing_resources_are_exposed(): void
    {
        DB::table('tt_teacher_meta')->insert([
            ['teacher_id' => $this->teachers['K Simukonda']->id, 'colour' => '#112233'],
            ['teacher_id' => $this->teachers['N Chisenga']->id, 'colour' => '#AABBCC'],
        ]);
        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $chemistry = $this->lesson('Chemistry', 'Form 5A', 'CHE A', 'K Simukonda');
        $physics = $this->lesson('Physics', 'Form 5B', 'PHY A', 'N Chisenga');

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk();
        $tray = collect($response->json('tray'))->keyBy('lesson_id');

        $this->assertSame('#112233', $tray[$biology->id]['colour']);
        $this->assertSame('#112233', $tray[$chemistry->id]['colour']);
        $this->assertSame('#AABBCC', $tray[$physics->id]['colour']);
        $this->assertSame('teacher-'.$this->teachers['K Simukonda']->id, $tray[$biology->id]['colour_key']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertSee('missingRoom(card)', false)
            ->assertSee('missingTeacher(card)', false)
            ->assertSee('No room assigned')
            ->assertSee('No teacher assigned');
    }

    public function test_grid_reuses_existing_class_subject_and_teacher_assignments(): void
    {
        ClassSubject::create([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'academic_year_id' => $this->year->id,
        ]);
        TeacherSubject::create([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk();

        $form5a = collect($response->json('classes'))->firstWhere('name', 'Form 5A');
        $this->assertContains($this->subjects['Biology']->id, $form5a['subject_ids']);
        $this->assertContains([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'is_primary' => true,
            'student_count' => 0,
        ], $response->json('editor.teacher_assignments'));
    }

    public function test_grid_exposes_saved_lesson_details_as_create_form_presets(): void
    {
        $biology = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            double: true,
            periodsPerWeek: 4,
            rooms: ['Lab 1'],
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk();

        $preset = collect($response->json('editor.lesson_presets'))
            ->firstWhere('lesson_id', $biology->id);

        $this->assertSame($biology->subject_id, $preset['subject_id']);
        $this->assertSame([$this->classes['Form 5A']->id], $preset['class_ids']);
        $this->assertSame([$this->teachers['K Simukonda']->id], $preset['teacher_ids']);
        $this->assertSame([$this->rooms['Lab 1']->id], $preset['room_ids']);
        $this->assertSame(4, $preset['periods_per_week']);
        $this->assertSame(2, $preset['periods_per_card']);
    }

    public function test_an_admin_can_create_lesson_cards_from_existing_school_assignments(): void
    {
        ClassSubject::create([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'academic_year_id' => $this->year->id,
        ]);
        TeacherSubject::create([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.store'), [
            'setting_id' => $this->setting->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [$this->rooms['Lab 1']->id],
            'periods_per_week' => 3,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => null],
            ],
        ])->assertOk()
            ->assertJsonPath('message', 'Lesson cards added to the tray.');

        $lesson = Lesson::query()->latest('id')->firstOrFail();
        $this->assertSame($this->subjects['Biology']->id, $lesson->subject_id);
        $this->assertSame([$this->classes['Form 5A']->id], $lesson->classes()->pluck('classes.id')->all());
        $this->assertSame([$this->teachers['K Simukonda']->id], $lesson->teachers()->pluck('teachers.id')->all());
        $this->assertSame([$this->rooms['Lab 1']->id], $lesson->rooms()->pluck('tt_rooms.id')->all());
        $this->assertSame(3, collect($response->json('grid.tray'))->firstWhere('lesson_id', $lesson->id)['unplaced']);
    }

    public function test_three_double_period_cards_create_three_placeable_cards(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.store'), [
            'setting_id' => $this->setting->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'periods_per_week' => 1,
            'periods_per_card' => 2,
            'cards_per_cycle' => 3,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => null],
            ],
        ])->assertOk();

        $lesson = Lesson::query()->latest('id')->firstOrFail();
        $tray = collect($response->json('grid.tray'))->firstWhere('lesson_id', $lesson->id);

        $this->assertSame(3, $lesson->cardsRequired());
        $this->assertSame(3, $tray['unplaced']);
        $this->assertEquals(6.0, (float) $lesson->periods_per_week);
    }

    public function test_new_lesson_cards_respect_the_classes_existing_subject_setup(): void
    {
        ClassSubject::create([
            'class_id' => $this->classes['Form 5A']->id,
            'subject_id' => $this->subjects['Biology']->id,
            'academic_year_id' => $this->year->id,
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.store'), [
            'setting_id' => $this->setting->id,
            'subject_id' => $this->subjects['Chemistry']->id,
            'teacher_ids' => [],
            'room_ids' => [],
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => null],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('subject_id');
    }

    public function test_an_admin_can_define_a_class_division_and_its_groups(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('admin.timetable.grid.divisions.store'),
            [
                'setting_id' => $this->setting->id,
                'class_ids' => [$this->classes['Form 5A']->id],
                'name' => 'Languages',
                'groups' => ['French', 'Setswana'],
            ],
        )->assertOk()
            ->assertJsonPath('message', 'Division added to Form 5A.');

        $division = Division::query()
            ->where('class_id', $this->classes['Form 5A']->id)
            ->where('name', 'Languages')
            ->firstOrFail();

        $this->assertSame(['French', 'Setswana'], $division->groups()->orderBy('id')->pluck('name')->all());
        $form5a = collect($response->json('grid.classes'))->firstWhere('name', 'Form 5A');
        $this->assertContains('Languages', collect($form5a['divisions'])->pluck('name')->all());
    }

    public function test_one_division_can_be_shared_by_classes_and_supply_matching_joint_lesson_groups(): void
    {
        $classIds = [$this->classes['Form 5A']->id, $this->classes['Form 5B']->id];

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.timetable.grid.divisions.store'),
            [
                'setting_id' => $this->setting->id,
                'class_ids' => $classIds,
                'name' => 'Languages',
                'groups' => ['French', 'Setswana'],
            ],
        )->assertOk()
            ->assertJsonPath('message', 'Division added to Form 5A, Form 5B.');

        $divisions = Division::query()
            ->with('groups')
            ->whereIn('class_id', $classIds)
            ->where('name', 'Languages')
            ->orderBy('class_id')
            ->get();

        $this->assertCount(2, $divisions);
        $this->assertNotNull($divisions->first()->shared_key);
        $this->assertCount(1, $divisions->pluck('shared_key')->unique());

        $frenchGroups = $divisions->map(fn (Division $division) => $division->groups->firstWhere('name', 'French'));
        $this->assertNotNull($frenchGroups->first()->shared_key);
        $this->assertCount(1, $frenchGroups->pluck('shared_key')->unique());

        foreach (['Form 5A', 'Form 5B'] as $className) {
            $klass = collect($response->json('grid.classes'))->firstWhere('name', $className);
            $languages = collect($klass['divisions'])->firstWhere('name', 'Languages');
            $this->assertEqualsCanonicalizing($classIds, $languages['class_ids']);
        }

        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $lesson->id,
            'subject_id' => $lesson->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => $frenchGroups->map(fn (Group $group) => [
                'class_id' => $group->class_id,
                'group_id' => $group->id,
            ])->values()->all(),
        ])->assertOk();

        $this->assertEqualsCanonicalizing($classIds, $lesson->fresh()->classes()->pluck('classes.id')->all());
        $this->assertEqualsCanonicalizing($frenchGroups->pluck('id')->all(), $lesson->groups()->pluck('tt_groups.id')->all());
    }

    public function test_editing_a_shared_division_syncs_groups_and_can_remove_an_unused_class(): void
    {
        $classIds = [$this->classes['Form 5A']->id, $this->classes['Form 5B']->id];
        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.divisions.store'), [
            'setting_id' => $this->setting->id,
            'class_ids' => $classIds,
            'name' => 'Languages',
            'groups' => ['French', 'Setswana'],
        ])->assertOk();

        $source = Division::query()
            ->with('groups')
            ->where('class_id', $this->classes['Form 5A']->id)
            ->where('name', 'Languages')
            ->firstOrFail();

        $this->actingAs($this->admin)->putJson(route('admin.timetable.grid.divisions.update', $source), [
            'setting_id' => $this->setting->id,
            'class_ids' => [$this->classes['Form 5A']->id],
            'name' => 'Language choices',
            'groups' => $source->groups->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name === 'French' ? 'French advanced' : $group->name,
            ])->all(),
        ])->assertOk()
            ->assertJsonPath('message', 'Shared division updated.');

        $this->assertDatabaseMissing('tt_divisions', [
            'class_id' => $this->classes['Form 5B']->id,
            'shared_key' => $source->shared_key,
        ]);
        $this->assertDatabaseHas('tt_divisions', [
            'class_id' => $this->classes['Form 5A']->id,
            'name' => 'Language choices',
        ]);
        $this->assertDatabaseHas('tt_groups', [
            'tt_division_id' => $source->id,
            'name' => 'French advanced',
        ]);
    }

    public function test_a_used_split_group_cannot_be_removed_from_its_division(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $division = $this->options['Form 5A']->fresh('groups');
        $used = $lesson->groups()->firstOrFail();
        $spare = Group::create([
            'class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $division->id,
            'name' => 'CHE A',
            'entire_class' => false,
        ]);

        $this->actingAs($this->admin)->putJson(
            route('admin.timetable.grid.divisions.update', $division),
            [
                'setting_id' => $this->setting->id,
                'class_ids' => [$this->classes['Form 5A']->id],
                'name' => 'Options',
                'groups' => [
                    ['id' => $spare->id, 'name' => $spare->name],
                    ['id' => null, 'name' => 'SET A'],
                ],
            ],
        )->assertUnprocessable()
            ->assertJsonValidationErrors('groups');

        $this->assertDatabaseHas('tt_groups', ['id' => $used->id]);
        $this->assertSame('Option block', $division->fresh()->name, 'The transaction must roll back the division rename too.');
    }

    public function test_an_admin_can_assign_a_split_group_and_joint_class_to_a_lesson(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $form5aGroup = $lesson->groups()->firstOrFail();
        $form5bGroup = Group::create([
            'class_id' => $this->classes['Form 5B']->id,
            'tt_division_id' => $this->options['Form 5B']->id,
            'name' => 'BIO B',
            'entire_class' => false,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $lesson->id,
            'subject_id' => $lesson->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $form5aGroup->id],
                ['class_id' => $this->classes['Form 5B']->id, 'group_id' => $form5bGroup->id],
            ],
        ])->assertOk();

        $this->assertSame(
            [$this->classes['Form 5A']->id, $this->classes['Form 5B']->id],
            $lesson->fresh()->classes()->orderBy('classes.id')->pluck('classes.id')->all(),
        );
        $this->assertSame(
            [$form5aGroup->id, $form5bGroup->id],
            $lesson->fresh()->groups()->orderBy('tt_groups.id')->pluck('tt_groups.id')->all(),
        );
        $this->assertSame('Form 5A, Form 5B', collect($response->json('grid.tray'))->firstWhere('lesson_id', $lesson->id)['class_names']);
    }

    public function test_one_lesson_can_include_multiple_groups_from_the_same_class_division(): void
    {
        $firstGroup = Group::create([
            'class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->options['Form 5A']->id,
            'name' => 'Geography 1',
            'entire_class' => false,
        ]);
        $secondGroup = Group::create([
            'class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->options['Form 5A']->id,
            'name' => 'Geography 2',
            'entire_class' => false,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.store'), [
            'setting_id' => $this->setting->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'periods_per_week' => 2,
            'periods_per_card' => 2,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $firstGroup->id],
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $secondGroup->id],
            ],
        ])->assertOk();

        $lesson = Lesson::query()->latest('id')->firstOrFail();

        $this->assertSame([$this->classes['Form 5A']->id], $lesson->classes()->pluck('classes.id')->all());
        $this->assertEqualsCanonicalizing(
            [$firstGroup->id, $secondGroup->id],
            $lesson->groups()->pluck('tt_groups.id')->all(),
        );

        $preset = collect($response->json('grid.editor.lesson_presets'))->firstWhere('lesson_id', $lesson->id);
        $this->assertCount(2, $preset['groups']);
    }

    public function test_two_groups_of_the_same_subject_can_run_in_parallel_for_one_class(): void
    {
        $first = $this->lesson('Biology', 'Form 5A', 'BIO 1', 'K Simukonda', double: true);
        $second = $this->lesson('Biology', 'Form 5A', 'BIO 2', 'N Chisenga', double: true);
        $this->placeCard($first, day: 1, period: 1);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $second->id,
            'day' => 1,
            'period' => 1,
        ])->assertOk();

        $parallel = collect($response->json('grid.cards'))
            ->filter(fn (array $card) => $card['day'] === 1 && $card['period'] === 1);

        $this->assertCount(2, $parallel);
        $this->assertSame([$this->subjects['Biology']->id], $parallel->pluck('subject_id')->unique()->values()->all());
        $this->assertSame([2], $parallel->pluck('span')->unique()->values()->all());
    }

    public function test_same_class_groups_from_different_divisions_cannot_be_combined(): void
    {
        $optionGroup = Group::create([
            'class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->options['Form 5A']->id,
            'name' => 'Option group',
            'entire_class' => false,
        ]);
        $scienceGroup = Group::create([
            'class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->science->id,
            'name' => 'Science group',
            'entire_class' => false,
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.store'), [
            'setting_id' => $this->setting->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_ids' => [],
            'room_ids' => [],
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $optionGroup->id],
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $scienceGroup->id],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('attendance');
    }

    public function test_a_lesson_cannot_use_a_group_from_another_selected_class(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $wrongGroup = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga')->groups()->firstOrFail();

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $lesson->id,
            'subject_id' => $lesson->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => $wrongGroup->id],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('attendance.0.group_id');
    }

    public function test_changing_a_placed_split_lesson_to_the_entire_class_rolls_back_on_a_clash(): void
    {
        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $chemistry = $this->lesson('Chemistry', 'Form 5A', 'CHE A', 'N Chisenga');
        $originalGroupId = $biology->groups()->firstOrFail()->id;
        $this->placeCard($biology, day: 1, period: 1);
        $this->placeCard($chemistry, day: 1, period: 1);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $biology->id,
            'subject_id' => $biology->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
            'attendance' => [
                ['class_id' => $this->classes['Form 5A']->id, 'group_id' => null],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('lesson');

        $this->assertSame([$originalGroupId], $biology->fresh()->groups()->pluck('tt_groups.id')->all());
    }

    public function test_division_management_is_closed_to_non_admins(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)->postJson(route('admin.timetable.grid.divisions.store'), [
            'setting_id' => $this->setting->id,
            'class_ids' => [$this->classes['Form 5A']->id],
            'name' => 'Languages',
            'groups' => ['French', 'Setswana'],
        ])->assertForbidden();
    }

    public function test_an_admin_can_add_a_room_from_separate_fields_and_assign_class_baserooms(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: []);
        $existingCard = $this->placeCard($bio, day: 1, period: 2);
        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.rooms.store'), [
            'setting_id' => $this->setting->id,
            'name' => 'Art Room',
            'short_name' => 'ART',
            'capacity' => 28,
        ])->assertOk()->assertJsonFragment(['name' => 'Art Room', 'capacity' => 28]);

        $artRoomId = collect($response->json('grid.editor.rooms'))->firstWhere('name', 'Art Room')['id'];

        $this->actingAs($this->admin)->putJson(route('admin.timetable.grid.base-rooms.update'), [
            'setting_id' => $this->setting->id,
            'base_rooms' => [
                $this->classes['Form 5A']->id => $artRoomId,
                $this->classes['Form 5B']->id => $this->rooms['Room 2']->id,
            ],
        ])->assertOk()
            ->assertJsonPath('grid.classes.0.base_room_id', $artRoomId)
            ->assertJsonPath('grid.classes.1.base_room_id', $this->rooms['Room 2']->id);

        $this->assertDatabaseHas('tt_class_base_rooms', [
            'tt_setting_id' => $this->setting->id,
            'class_id' => $this->classes['Form 5A']->id,
            'tt_room_id' => $artRoomId,
        ]);
        $this->assertSame($artRoomId, (int) $existingCard->fresh()->tt_room_id);
    }

    public function test_a_single_class_lesson_uses_its_baseroom_when_no_special_room_is_set(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: []);
        DB::table('tt_class_base_rooms')->insert([
            'tt_setting_id' => $this->setting->id,
            'class_id' => $this->classes['Form 5A']->id,
            'tt_room_id' => $this->rooms['Lab 1']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'day' => 2,
            'period' => 3,
        ])->assertOk()->assertJsonPath('grid.cards.0.room', 'Lab 1');

        $this->assertDatabaseHas('tt_cards', [
            'tt_lesson_id' => $bio->id,
            'tt_room_id' => $this->rooms['Lab 1']->id,
        ]);
    }

    public function test_editing_a_baseroom_lesson_keeps_the_implicit_room(): void
    {
        DB::table('tt_class_base_rooms')->insert([
            'tt_setting_id' => $this->setting->id,
            'class_id' => $this->classes['Form 5A']->id,
            'tt_room_id' => $this->rooms['Lab 1']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: []);

        $placed = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'day' => 2,
            'period' => 3,
        ])->assertOk();

        $cardId = $placed->json('placement.card_ids.0');

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'card_id' => $cardId,
            'subject_id' => $bio->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
        ])->assertOk()->assertJsonPath('grid.cards.0.room', 'Lab 1');
    }

    public function test_the_canonical_timetable_page_is_the_only_editor(): void
    {
        $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->actingAs($this->admin)
            ->get(route('admin.timetable.index', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertSee('timetableGrid(', escape: false)
            ->assertSee('Unplaced lessons')
            ->assertDontSee('Legacy timetable');

        $this->actingAs($this->admin)
            ->get('/admin/timetable/legacy')
            ->assertRedirect('/admin/timetable');
    }

    public function test_the_grid_guides_an_admin_to_create_an_academic_year_first(): void
    {
        Setting::query()->delete();
        AcademicYear::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.timetable.index'))
            ->assertRedirect(route('admin.academic-years.index'))
            ->assertSessionHas('error', 'Create an academic year before building a timetable.');
    }

    public function test_the_page_is_closed_to_other_roles(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)
            ->get(route('admin.timetable.grid'))
            ->assertForbidden();
    }

    public function test_the_page_is_closed_to_guests(): void
    {
        $this->get(route('admin.timetable.grid'))->assertRedirect('/login');
    }

    // -----------------------------------------------------------------
    // Candidates — what paints the grid on pick-up
    // -----------------------------------------------------------------

    public function test_candidates_marks_the_occupied_slot_illegal_and_names_the_clash(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 1, period: 1);

        // Same teacher, different class — so period 1 of day 1 is taken for them.
        $chem = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'K Simukonda');

        $response = $this->actingAs($this->admin)->getJson(route('admin.timetable.grid.candidates', [
            'setting_id' => $this->setting->id,
            'lesson_id' => $chem->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('lesson_id', $chem->id)
            ->assertJsonPath('span', 1)
            ->assertJsonCount(48, 'slots');

        $slots = collect($response->json('slots'));

        $taken = $slots->first(fn (array $slot) => $slot['day'] === 1 && $slot['period'] === 1);
        $this->assertFalse($taken['ok']);
        $this->assertSame(Conflict::TEACHER, $taken['conflicts'][0]['kind']);
        $this->assertStringContainsString('K Simukonda', $taken['conflicts'][0]['message']);

        $free = $slots->first(fn (array $slot) => $slot['day'] === 1 && $slot['period'] === 2);
        $this->assertTrue($free['ok']);

        // Every other slot of the cycle is free — only the one collision was raised.
        $this->assertSame(1, $slots->where('ok', false)->count());
    }

    public function test_candidates_leaves_parallel_groups_of_one_division_legal(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 3, period: 5);

        // A different group of the same division: disjoint students by construction.
        $set = $this->lesson('Setswana', 'Form 5A', 'SET 1', 'N Chisenga');

        $response = $this->actingAs($this->admin)->getJson(route('admin.timetable.grid.candidates', [
            'setting_id' => $this->setting->id,
            'lesson_id' => $set->id,
        ]));

        $slot = collect($response->assertOk()->json('slots'))
            ->first(fn (array $slot) => $slot['day'] === 3 && $slot['period'] === 5);

        $this->assertTrue($slot['ok'], 'Two option groups of one division may share a period.');
    }

    public function test_candidates_refuses_the_slots_where_a_double_would_straddle_a_break(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);

        $response = $this->actingAs($this->admin)->getJson(route('admin.timetable.grid.candidates', [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
        ]));

        $slots = collect($response->assertOk()->json('slots'));
        $this->assertSame(2, $response->json('span'));

        // The break sits after period 4, so a double starting there spans it.
        $straddling = $slots->first(fn (array $slot) => $slot['day'] === 1 && $slot['period'] === 4);
        $this->assertFalse($straddling['ok']);
        $this->assertSame(Conflict::STRUCTURE, $straddling['conflicts'][0]['kind']);

        // And one starting on the last period has nowhere to put its second half.
        $offTheEnd = $slots->first(fn (array $slot) => $slot['day'] === 1 && $slot['period'] === 8);
        $this->assertFalse($offTheEnd['ok']);

        $this->assertTrue($slots->first(fn (array $s) => $s['day'] === 1 && $s['period'] === 3)['ok']);
    }

    public function test_a_card_being_moved_does_not_clash_with_itself(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 2, period: 6);

        $response = $this->actingAs($this->admin)->getJson(route('admin.timetable.grid.candidates', [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
        ]));

        $slot = collect($response->assertOk()->json('slots'))
            ->first(fn (array $slot) => $slot['day'] === 2 && $slot['period'] === 6);

        $this->assertTrue($slot['ok'], 'A card must not be blocked by the slot it already sits in.');
        $this->assertSame([$card->id], $response->json('card_ids'));
    }

    // -----------------------------------------------------------------
    // Moving
    // -----------------------------------------------------------------

    public function test_placing_a_tray_card_writes_it_and_returns_the_refreshed_grid(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 2);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'day' => 4,
            'period' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('placement.day', 4)
            ->assertJsonPath('placement.period', 2)
            ->assertJsonPath('grid.cards.0.day', 4)
            ->assertJsonPath('grid.tray.0.unplaced', 1);

        $this->assertDatabaseHas('tt_cards', [
            'tt_lesson_id' => $bio->id,
            'period_number' => 2,
            'days' => '000100',
        ]);
    }

    public function test_moving_a_double_carries_both_rows(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);
        $card = $this->placeCard($bio, day: 1, period: 1);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'day' => 5,
            'period' => 6,
        ])->assertOk()->assertJsonPath('placement.span', 2);

        $this->assertSame(
            [6, 7],
            Card::where('tt_lesson_id', $bio->id)->orderBy('period_number')->pluck('period_number')
                ->map(fn ($n) => (int) $n)->all(),
        );
        $this->assertSame(2, Card::where('tt_lesson_id', $bio->id)->count());
    }

    public function test_a_move_the_client_let_through_is_still_refused(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 1, period: 1);

        // The client never asked for candidates; it just posted. Same teacher, same slot.
        $chem = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'K Simukonda');

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $chem->id,
            'day' => 1,
            'period' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('conflicts.0.kind', Conflict::TEACHER);

        $this->assertDatabaseCount('tt_cards', 1);
    }

    public function test_a_move_onto_a_slot_taken_since_the_grid_loaded_is_refused(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 1);

        // The whole class is busy at day 2 period 4 — the second admin got there first.
        $maths = $this->lesson('Mathematics', 'Form 5A', 'Form 5A', 'M Tau', entireClass: true);
        $this->placeCard($maths, day: 2, period: 4);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'day' => 2,
            'period' => 4,
        ])
            ->assertStatus(422)
            ->assertJsonPath('conflicts.0.kind', Conflict::STUDENTS);

        // Refused means unmoved, not half-moved.
        $this->assertSame(1, (int) $card->fresh()->period_number);
    }

    public function test_a_move_naming_neither_a_card_nor_a_lesson_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'day' => 1,
            'period' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('lesson_id');
    }

    public function test_a_move_outside_the_selected_cycle_is_a_validation_error(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'day' => 7,
            'period' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('day');

        $this->assertDatabaseCount('tt_cards', 0);
    }

    public function test_grid_writes_cannot_cross_timetable_revisions(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 1);
        $other = Setting::factory()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'Another revision',
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $other->id,
            'lesson_id' => $bio->id,
            'day' => 2,
            'period' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors('lesson_id');

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.unplace'), [
            'setting_id' => $other->id,
            'card_id' => $card->id,
        ])->assertStatus(422)->assertJsonValidationErrors('card_id');

        $this->assertDatabaseHas('tt_cards', ['id' => $card->id, 'period_number' => 1]);
    }

    public function test_a_move_cannot_assign_a_room_from_another_timetable(): void
    {
        $bio = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            rooms: ['Lab 1'],
        );
        $other = Setting::factory()->create(['academic_year_id' => $this->year->id]);
        $foreignRoom = Room::factory()->for($other, 'setting')->create();

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'room_id' => $foreignRoom->id,
            'day' => 1,
            'period' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('room_id');

        $this->assertDatabaseCount('tt_cards', 0);
    }

    // -----------------------------------------------------------------
    // Unplacing and locking
    // -----------------------------------------------------------------

    public function test_an_admin_can_change_a_cards_teacher_room_and_duration(): void
    {
        $bio = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            periodsPerWeek: 2,
            rooms: ['Lab 1', 'Room 2'],
        );
        $card = $this->placeCard($bio, day: 1, period: 2, room: $this->rooms['Lab 1']);

        $response = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
            'card_id' => $card->id,
            'subject_id' => $bio->subject_id,
            'teacher_ids' => [$this->teachers['N Chisenga']->id],
            'room_ids' => [$this->rooms['Lab 1']->id, $this->rooms['Room 2']->id],
            'placement_room_id' => $this->rooms['Room 2']->id,
            'periods_per_week' => 2,
            'periods_per_card' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('grid.cards.0.span', 2)
            ->assertJsonPath('grid.cards.0.room', 'Room 2')
            ->assertJsonPath('grid.cards.0.teachers.0', 'N Chisenga');

        $this->assertSame([2, 3], Card::query()->where('tt_lesson_id', $bio->id)
            ->orderBy('period_number')->pluck('period_number')->map(fn ($period) => (int) $period)->all());
        $this->assertSame(
            [$this->teachers['N Chisenga']->id],
            $bio->fresh()->teachers()->pluck('teachers.id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function test_cards_from_the_same_lesson_can_use_different_rooms(): void
    {
        $bio = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            periodsPerWeek: 3,
            rooms: ['Lab 1', 'Room 2'],
        );
        $this->placeCard($bio, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $this->placeCard($bio, day: 2, period: 1, room: $this->rooms['Room 2']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Lab 1', 'Room 2'],
            collect($response->json('cards'))->pluck('room')->all(),
        );
        $this->assertSame(1, $response->json('tray.0.unplaced'));
    }

    public function test_a_card_can_be_made_roomless_but_cannot_take_an_occupied_shared_room(): void
    {
        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', rooms: ['Lab 1']);
        $chemistry = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga', rooms: ['Lab 1', 'Room 2']);
        $this->placeCard($biology, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $chemistryCard = $this->placeCard($chemistry, day: 1, period: 1, room: $this->rooms['Room 2']);

        $payload = [
            'setting_id' => $this->setting->id,
            'lesson_id' => $chemistry->id,
            'card_id' => $chemistryCard->id,
            'subject_id' => $chemistry->subject_id,
            'teacher_ids' => [$this->teachers['N Chisenga']->id],
            'room_ids' => [$this->rooms['Lab 1']->id, $this->rooms['Room 2']->id],
            'placement_room_id' => $this->rooms['Lab 1']->id,
            'placement_room_mode' => 'room',
            'periods_per_week' => 1,
            'periods_per_card' => 1,
        ];

        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.grid.lesson.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lesson');

        $this->assertDatabaseHas('tt_cards', [
            'id' => $chemistryCard->id,
            'tt_room_id' => $this->rooms['Room 2']->id,
        ]);

        $payload['placement_room_id'] = null;
        $payload['placement_room_mode'] = 'none';

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.grid.lesson.update'), $payload)
            ->assertOk();

        $this->assertNull(collect($response->json('grid.cards'))->firstWhere('lesson_id', $chemistry->id)['room_id']);
        $this->assertDatabaseHas('tt_cards', [
            'tt_lesson_id' => $chemistry->id,
            'tt_room_id' => null,
        ]);
    }

    public function test_a_teacher_change_that_would_clash_is_refused_and_rolled_back(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 1, period: 1);
        $chem = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga');
        $chemCard = $this->placeCard($chem, day: 1, period: 1);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.update'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $chem->id,
            'card_id' => $chemCard->id,
            'subject_id' => $chem->subject_id,
            'teacher_ids' => [$this->teachers['K Simukonda']->id],
            'room_ids' => [],
            'placement_room_id' => null,
            'periods_per_week' => 1,
            'periods_per_card' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('lesson');

        $this->assertSame(
            [$this->teachers['N Chisenga']->id],
            $chem->fresh()->teachers()->pluck('teachers.id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertDatabaseHas('tt_cards', ['id' => $chemCard->id, 'period_number' => 1]);
    }

    public function test_an_admin_can_duplicate_and_delete_a_lesson_from_the_grid(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 2);
        $card = $this->placeCard($bio, day: 1, period: 1);

        $duplicate = $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lesson.duplicate'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
        ])->assertOk();

        $this->assertDatabaseCount('tt_lessons', 2);
        $this->assertSame(3, collect($duplicate->json('grid.tray'))->sum('unplaced'));

        $this->actingAs($this->admin)->deleteJson(route('admin.timetable.grid.lesson.destroy'), [
            'setting_id' => $this->setting->id,
            'lesson_id' => $bio->id,
        ])->assertOk();

        $this->assertDatabaseMissing('tt_lessons', ['id' => $bio->id]);
        $this->assertDatabaseMissing('tt_cards', ['id' => $card->id]);
    }

    public function test_unplacing_a_double_returns_both_rows_to_the_tray(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true, periodsPerWeek: 2);
        $card = $this->placeCard($bio, day: 1, period: 1);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.unplace'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
        ])
            ->assertOk()
            ->assertJsonCount(0, 'grid.cards')
            ->assertJsonPath('grid.tray.0.unplaced', 1);

        $this->assertDatabaseCount('tt_cards', 0);
    }

    public function test_a_locked_card_refuses_to_move_until_it_is_unlocked(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 1);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lock'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'locked' => true,
        ])->assertOk()->assertJsonPath('placement.locked', true);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'day' => 3,
            'period' => 3,
        ])->assertStatus(422)->assertJsonPath('conflicts.0.kind', Conflict::LOCKED);

        $this->assertSame(1, (int) $card->fresh()->period_number);

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.lock'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'locked' => false,
        ])->assertOk()->assertJsonPath('placement.locked', false);

        // A move rewrites its rows rather than updating them, so the card that lands is a
        // new row — read the result off the lesson, not off the id we started with.
        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'card_id' => $card->id,
            'day' => 3,
            'period' => 3,
        ])->assertOk()->assertJsonPath('placement.period', 3);

        $rows = Card::where('tt_lesson_id', $bio->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame(3, (int) $rows->first()->period_number);
        $this->assertFalse((bool) $rows->first()->locked, 'The move must not carry the old lock back.');
    }

    public function test_moving_a_card_that_has_since_been_deleted_asks_for_a_refresh(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 1);
        $id = $card->id;

        $card->delete();

        $this->actingAs($this->admin)->postJson(route('admin.timetable.grid.move'), [
            'setting_id' => $this->setting->id,
            'card_id' => $id,
            'day' => 2,
            'period' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors('card_id');
    }
}
