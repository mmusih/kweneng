<?php

namespace Tests\Feature\Timetable;

use App\Models\TeacherSubject;
use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class AssignmentPreparationTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    private function source(string $subject = 'Chemistry', string $class = 'Form 5A', string $teacher = 'K Simukonda'): array
    {
        $a = TeacherSubject::firstOrCreate([
            'class_id' => $this->classes[$class]->id, 'subject_id' => $this->subjects[$subject]->id,
            'teacher_id' => $this->teachers[$teacher]->id, 'academic_year_id' => $this->year->id,
        ]);

        return ['key' => implode(':', [$a->class_id, $a->subject_id, $a->teacher_id]), 'group_id' => null];
    }

    private function unit(array $source, int $span = 1, ?string $split = null): array
    {
        return ['key' => (string) Str::uuid(), 'span' => $span, 'sources' => [$source], 'split_key' => $split];
    }

    private function saveUnits(array $units, int $version = 0)
    {
        return $this->postJson(route('admin.timetable.prepare.store', $this->setting), compact('units', 'version'));
    }

    private function moveLesson(Lesson $lesson, int $day = 1, int $period = 1)
    {
        return $this->postJson(route('admin.timetable.grid.move'), ['setting_id' => $this->setting->id, 'lesson_id' => $lesson->id, 'day' => $day, 'period' => $period]);
    }

    public function test_preparation_page_renders_and_requires_admin_access(): void
    {
        $this->get(route('admin.timetable.prepare', $this->setting))->assertOk()
            ->assertSee('Prepare class lessons')->assertSee('Select joint classes')
            ->assertDontSee('Splits &amp; joint classes', false)->assertDontSee('New split from selection')
            ->assertDontSee('Drop here to choose another class');
        $this->actingAs(User::factory()->create(['role' => 'teacher', 'status' => 'active']))
            ->get(route('admin.timetable.prepare', $this->setting))->assertForbidden();
    }

    public function test_two_doubles_and_one_single_are_three_cards_using_five_periods_and_saving_again_is_idempotent(): void
    {
        $source = $this->source();
        $units = [$this->unit($source, 2), $this->unit($source, 2), $this->unit($source)];
        $response = $this->saveUnits($units)->assertOk()->assertJsonCount(3, 'units');
        $this->assertSame(5, (int) Lesson::sum('periods_per_week'));
        $ids = Lesson::pluck('id')->all();
        $this->saveUnits($response->json('units'), 1)->assertOk();
        $this->assertSame($ids, Lesson::pluck('id')->all());
        $this->moveLesson(Lesson::first())->assertOk();
        $this->assertDatabaseCount('tt_cards', 2);
        $this->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))->assertJsonCount(1, 'cards')->assertJsonCount(2, 'tray');
    }

    public function test_partial_joint_keeps_the_single_separate_and_shares_each_double(): void
    {
        $a = $this->source();
        $b = $this->source(class: 'Form 5B');
        $joint = $this->unit($a, 2);
        $joint['sources'][] = $b;
        $this->saveUnits([$joint, $this->unit($a), $this->unit($b)])->assertOk();
        $lesson = Lesson::where('preparation_key', $joint['key'])->first();
        $this->assertSame(2, $lesson->classes()->count());
        $this->assertSame(1, $lesson->teachers()->count());
        $this->moveLesson($lesson)->assertOk();
        $this->assertDatabaseCount('tt_cards', 2);
    }

    public function test_tray_stacks_matching_occurrences_and_counts_down_then_restores_returned_cards(): void
    {
        $source = $this->source();
        $units = [$this->unit($source, 2), $this->unit($source, 2), $this->unit($source)];
        $saved = $this->saveUnits($units)->assertOk();
        $url = route('admin.timetable.grid', ['setting' => $this->setting->id]);
        $tray = collect($this->getJson($url)->assertOk()->json('tray'));
        $this->assertCount(2, $tray);
        $stack = $tray->firstWhere('span', 2);
        $this->assertSame(2, $stack['unplaced']);
        $this->assertSame(1, $tray->firstWhere('span', 1)['unplaced']);
        $this->moveLesson(Lesson::findOrFail($stack['lesson_id']))->assertOk();
        $next = collect($this->getJson($url)->json('tray'))->firstWhere('span', 2);
        $this->assertSame(1, $next['unplaced']);
        $this->assertSame($stack['stack_key'], $next['stack_key']);
        $this->assertNotSame($stack['lesson_id'], $next['lesson_id']);
        $this->moveLesson(Lesson::findOrFail($next['lesson_id']), day: 2)->assertOk();
        $this->assertCount(1, $this->getJson($url)->json('tray'));
        $this->postJson(route('admin.timetable.grid.unplace'), ['setting_id' => $this->setting->id, 'card_id' => Card::first()->id])->assertOk();
        $returned = collect($this->getJson($url)->json('tray'))->firstWhere('span', 2);
        $this->assertSame(1, $returned['unplaced']);
        $this->assertSame($stack['stack_key'], $returned['stack_key']);
        $this->saveUnits($saved->json('units'), 1)->assertOk();
        $this->assertDatabaseCount('tt_lessons', 3);
        $this->assertCount(2, $this->getJson($url)->json('tray'));
    }

    public function test_repeated_split_rows_stack_but_different_room_or_partner_does_not(): void
    {
        $a = $this->source();
        $b = $this->source('Biology', teacher: 'N Chisenga');
        $c = $this->source('Physics', teacher: 'M Tau');
        $units = [];
        for ($i = 0; $i < 2; $i++) {
            $split = (string) Str::uuid();
            $units[] = $this->unit($a, 2, $split);
            $units[] = $this->unit($b, 2, $split);
        }
        $otherSplit = (string) Str::uuid();
        $units[] = $this->unit($a, 2, $otherSplit);
        $units[] = $this->unit($c, 2, $otherSplit);
        $roomUnit = $this->unit($a, 2);
        $roomUnit['room_id'] = $this->rooms['Lab 1']->id;
        $units[] = $roomUnit;
        $this->saveUnits($units)->assertOk();
        $url = route('admin.timetable.grid', ['setting' => $this->setting->id]);
        $tray = collect($this->getJson($url)->assertOk()->json('tray'));
        $this->assertCount(5, $tray);
        $this->assertCount(3, $tray->where('subject', 'Chemistry'));
        $stack = $tray->first(fn ($item) => $item['subject'] === 'Chemistry' && $item['unplaced'] === 2);
        $this->moveLesson(Lesson::findOrFail($stack['lesson_id']))->assertOk();
        $next = collect($this->getJson($url)->json('tray'));
        $this->assertSame(1, $next->firstWhere('stack_key', $stack['stack_key'])['unplaced']);
        $this->assertSame(1, $next->firstWhere('subject', 'Biology')['unplaced']);
        $this->assertDatabaseCount('tt_cards', 4);
    }

    public function test_joint_split_creates_disjoint_groups_and_moves_unplaces_and_locks_together(): void
    {
        $split = (string) Str::uuid();
        $chem = $this->unit($this->source(), 2, $split);
        $chem['sources'][] = $this->source(class: 'Form 5B');
        $bio = $this->unit($this->source('Biology', teacher: 'N Chisenga'), 2, $split);
        $bio['sources'][] = $this->source('Biology', 'Form 5B', 'N Chisenga');
        $saved = $this->saveUnits([$chem, $bio])->assertOk();
        $lessons = Lesson::orderBy('id')->get();
        $this->assertSame(2, $lessons[0]->groups()->count());
        $this->assertSame(2, $lessons[1]->groups()->count());
        $this->moveLesson($lessons[0])->assertOk();
        $this->assertDatabaseCount('tt_cards', 4);
        $this->saveUnits($saved->json('units'), 1)->assertOk();
        $first = Card::first();
        $this->postJson(route('admin.timetable.grid.move'), ['setting_id' => $this->setting->id, 'card_id' => $first->id, 'day' => 2, 'period' => 2])->assertOk();
        $this->assertSame([2, 3], Card::pluck('period_number')->unique()->sort()->values()->all());
        $this->assertSame(['010000'], Card::pluck('days')->unique()->values()->all());
        $first = Card::first();
        $this->postJson(route('admin.timetable.grid.lock'), ['setting_id' => $this->setting->id, 'card_id' => $first->id, 'locked' => true])->assertOk();
        $this->assertSame(4, Card::where('locked', true)->count());
        $this->postJson(route('admin.timetable.grid.unplace'), ['setting_id' => $this->setting->id, 'card_id' => $first->id])->assertStatus(422);
        $this->postJson(route('admin.timetable.grid.lock'), ['setting_id' => $this->setting->id, 'card_id' => $first->id, 'locked' => false])->assertOk();
        $this->postJson(route('admin.timetable.grid.unplace'), ['setting_id' => $this->setting->id, 'card_id' => $first->id])->assertOk();
        $this->assertDatabaseCount('tt_cards', 0);
    }

    public function test_clashing_split_member_refuses_the_entire_drop_and_candidates_agree(): void
    {
        $split = (string) Str::uuid();
        $a = $this->unit($this->source(), 1, $split);
        $b = $this->unit($this->source('Biology', teacher: 'N Chisenga'), 1, $split);
        $this->saveUnits([$a, $b])->assertOk();
        $blocker = $this->lesson('Physics', 'Form 5B', 'All', 'N Chisenga', entireClass: true);
        $this->placeCard($blocker, 1, 1);
        $lesson = Lesson::where('preparation_key', $a['key'])->first();
        $this->moveLesson($lesson)->assertStatus(422);
        $this->assertDatabaseCount('tt_cards', 1);
        $this->getJson(route('admin.timetable.grid.candidates', ['setting_id' => $this->setting->id, 'lesson_id' => $lesson->id]))->assertOk()->assertJsonPath('slots.0.ok', false)->assertJsonPath('slots.1.ok', true);
    }

    public function test_stale_save_cannot_overwrite_a_newer_preparation(): void
    {
        $units = [$this->unit($this->source())];
        $this->saveUnits($units)->assertOk();
        $this->saveUnits([])->assertUnprocessable();
        $this->assertDatabaseCount('tt_lessons', 1);
    }

    public function test_placed_occurrences_cannot_be_removed_or_restructured(): void
    {
        $unit = $this->unit($this->source());
        $this->saveUnits([$unit])->assertOk();
        $this->moveLesson(Lesson::first())->assertOk();
        $unit['span'] = 2;
        $this->saveUnits([$unit], 1)->assertUnprocessable();
        $this->saveUnits([], 1)->assertUnprocessable();
        $this->assertDatabaseCount('tt_cards', 1);
        $this->assertSame(1, Lesson::first()->periods_per_card);
    }

    public function test_assignment_resave_with_new_database_id_does_not_break_the_link(): void
    {
        $source = $this->source();
        $unit = $this->unit($source);
        $this->saveUnits([$unit])->assertOk();
        TeacherSubject::query()->delete();
        $this->assertSame($source, $this->source());
        $this->saveUnits([$unit], 1)->assertOk();
        $this->assertDatabaseCount('tt_lessons', 1);
    }

    public function test_prepared_required_count_can_be_reduced_and_restored_without_changing_assignments(): void
    {
        $source = $this->source();
        $units = [$this->unit($source), $this->unit($source), $this->unit($source)];
        $saved = $this->saveUnits($units)->assertOk()->json();
        $placed = Lesson::first();
        $this->moveLesson($placed)->assertOk();
        $cards = Card::get()->toArray();
        $assignments = TeacherSubject::get()->toArray();
        $spare = Lesson::whereKeyNot($placed->id)->first();
        $response = $this->putJson(route('admin.timetable.grid.required-count'), [
            'setting_id' => $this->setting->id, 'lesson_id' => $spare->id, 'required' => 1, 'expected_required' => 3, 'expected_placed' => 1, 'version' => $saved['version'],
        ])->assertOk()->assertJsonCount(0, 'grid.tray');
        $this->assertDatabaseCount('tt_lessons', 3);
        $this->assertSame($cards, Card::get()->toArray());
        $this->assertSame($assignments, TeacherSubject::get()->toArray());
        $this->saveUnits($saved['units'], $saved['version'])->assertUnprocessable();
        $fresh = $this->getJson(route('admin.timetable.prepare', $this->setting))->assertOk()->json();
        $this->assertCount(1, $fresh['units']);
        $this->putJson(route('admin.timetable.grid.required-count'), [
            'setting_id' => $this->setting->id, 'lesson_id' => $spare->id, 'required' => 3,
            'expected_required' => 1, 'expected_placed' => 1, 'version' => $fresh['version'],
        ])->assertOk()->assertJsonPath('grid.tray.0.unplaced', 2);
        $this->assertDatabaseCount('tt_lessons', 3);
        $this->assertSame($cards, Card::get()->toArray());
        $fresh = $this->getJson(route('admin.timetable.prepare', $this->setting))->assertOk()->json();
        $this->saveUnits($fresh['units'], $fresh['version'])->assertOk()->assertJsonCount(3, 'units');
    }

    public function test_manual_lesson_overlap_is_not_duplicated(): void
    {
        $source = $this->source();
        $this->lesson('Chemistry', 'Form 5A', 'All', 'K Simukonda', entireClass: true);
        $this->saveUnits([$this->unit($source)])->assertUnprocessable();
        $this->assertDatabaseCount('tt_lessons', 1);
    }

    public function test_more_occurrences_can_be_added_after_placement_without_changing_placed_cards(): void
    {
        $source = $this->source();
        $unit = $this->unit($source, 2);
        $this->saveUnits([$unit])->assertOk();
        $this->moveLesson(Lesson::first())->assertOk();
        $before = Card::orderBy('id')->get()->toArray();
        $units = [$unit, $this->unit($source), $this->unit($source, 2)];
        $this->saveUnits($units, 1)->assertOk()->assertJsonCount(3, 'units')->assertJsonCount(1, 'grid.cards');
        $this->assertSame($before, Card::orderBy('id')->get()->toArray());
        $this->saveUnits($units, 2)->assertOk();
        $this->assertDatabaseCount('tt_lessons', 3);
        $this->assertSame($before, Card::orderBy('id')->get()->toArray());
    }

    public function test_existing_individual_lessons_are_counted_and_can_be_supplemented_without_duplication(): void
    {
        $source = $this->source();
        $lesson = $this->lesson('Chemistry', 'Form 5A', 'All', 'K Simukonda', entireClass: true, periodsPerWeek: 3);
        $this->placeCard($lesson, day: 1, period: 1);
        $before = Card::first()->toArray();
        $data = $this->getJson(route('admin.timetable.prepare', $this->setting))->assertOk()->json();
        $assignment = collect($data['assignments'])->firstWhere('key', $source['key']);
        $this->assertSame(3, $assignment['manual']['singles']);
        $this->assertSame(1, $assignment['manual']['placed']);
        $body = ['version' => 0, 'units' => [$this->unit($source)], 'manual_baselines' => [$source['key'] => $assignment['manual']['signature']]];
        $this->postJson(route('admin.timetable.prepare.store', $this->setting), $body)->assertOk();
        $body['version'] = 1;
        $this->postJson(route('admin.timetable.prepare.store', $this->setting), $body)->assertOk();
        $this->assertDatabaseCount('tt_lessons', 2);
        $this->assertSame($before, Card::first()->toArray());
        $lesson->update(['cards_per_cycle' => 4]);
        $body['version'] = 2;
        $this->postJson(route('admin.timetable.prepare.store', $this->setting), $body)->assertUnprocessable();
        $this->assertDatabaseCount('tt_lessons', 2);
    }

    public function test_bad_split_pattern_same_teacher_and_forged_assignment_are_rejected_atomically(): void
    {
        $split = (string) Str::uuid();
        $a = $this->unit($this->source(), 1, $split);
        $b = $this->unit($this->source('Biology', teacher: 'N Chisenga'), 2, $split);
        $this->saveUnits([$a, $b])->assertUnprocessable();
        $b = $this->unit($this->source('Biology'), 1, $split);
        $this->saveUnits([$a, $b])->assertUnprocessable();
        $this->saveUnits([$this->unit(['key' => '999:999:999', 'group_id' => null])])->assertUnprocessable();
        $this->assertDatabaseCount('tt_lessons', 0);
        $this->assertDatabaseCount('tt_groups', 0);
    }

    public function test_filler_occurrences_keep_different_split_divisions_and_whole_class_attendance(): void
    {
        $filler = $this->source();
        $bio = $this->source('Biology', teacher: 'N Chisenga');
        $physics = $this->source('Physics', teacher: 'M Tau');
        $makeGroup = fn ($division, $name) => \App\Models\Tt\Group::create([
            'class_id' => $this->classes['Form 5A']->id, 'tt_division_id' => $division->id,
            'name' => $name, 'entire_class' => false,
        ])->id;
        $first = $makeGroup($this->options['Form 5A'], 'Filler A');
        $second = $makeGroup($this->science, 'Filler B');
        $optionsSplit = (string) Str::uuid();
        $scienceSplit = (string) Str::uuid();
        $units = [
            $this->unit(array_replace($filler, ['group_id' => $first]), 1, $optionsSplit),
            $this->unit(array_replace($bio, ['group_id' => $makeGroup($this->options['Form 5A'], 'Bio')]), 1, $optionsSplit),
            $this->unit(array_replace($filler, ['group_id' => $second]), 1, $scienceSplit),
            $this->unit(array_replace($physics, ['group_id' => $makeGroup($this->science, 'Physics')]), 1, $scienceSplit),
            $this->unit($filler),
        ];
        $response = $this->saveUnits($units)->assertOk();
        $saved = collect($response->json('units'))->keyBy('key');
        $this->assertSame($first, $saved[$units[0]['key']]['sources'][0]['group_id']);
        $this->assertSame($second, $saved[$units[2]['key']]['sources'][0]['group_id']);
        $this->assertNull($saved[$units[4]['key']]['sources'][0]['group_id']);
        $this->saveUnits($response->json('units'), 1)->assertOk();
    }

    public function test_prepared_card_attendance_updates_only_its_source_and_survives_preparation_save(): void
    {
        $source = $this->source();
        $units = [$this->unit($source), $this->unit($source)];
        $this->saveUnits($units)->assertOk();
        $lesson = Lesson::where('preparation_key', $units[0]['key'])->firstOrFail();
        $group = \App\Models\Tt\Group::create(['class_id' => $this->classes['Form 5A']->id,
            'tt_division_id' => $this->science->id, 'name' => 'Filler', 'entire_class' => false]);
        $this->postJson(route('admin.timetable.grid.card-attendance'), [
            'setting_id' => $this->setting->id, 'lesson_id' => $lesson->id, 'version' => 1,
            'attendance' => [['class_id' => $this->classes['Form 5A']->id, 'group_id' => $group->id]],
        ])->assertOk();
        $this->assertSame($group->id, $lesson->fresh()->assignment_sources[0]['group_id']);
        $this->assertNull(Lesson::where('preparation_key', $units[1]['key'])->first()->assignment_sources[0]['group_id']);
        $payload = app(\App\Services\Timetable\AssignmentPreparationService::class)->payload($this->setting->fresh());
        $this->saveUnits($payload['units']->all(), 2)->assertOk();
        $this->assertSame($group->id, $lesson->fresh()->groups->first()->id);
    }

    public function test_assignment_cards_can_exceed_grid_capacity_and_be_reduced_later(): void
    {
        $source = $this->source();
        $units = array_map(fn () => $this->unit($source, 2), range(1, 30));
        $response = $this->saveUnits($units)->assertOk()->assertJsonCount(30, 'units');
        $this->assertEquals(60, $response->json('grid.classes.0.periods_used'));
        $this->assertEquals(48, $response->json('grid.setting.capacity'));
        $this->saveUnits(array_slice($response->json('units'), 0, 24), 1)->assertOk()->assertJsonCount(24, 'units');
    }
}
