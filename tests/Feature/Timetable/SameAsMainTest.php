<?php

namespace Tests\Feature\Timetable;

use App\Models\AcademicYear;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Setting;
use App\Models\User;
use Database\Factories\Tt\PeriodFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class SameAsMainTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private Setting $afternoon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->setting->update(['schedule_type' => Setting::TYPE_DAY]);
        $this->afternoon = Setting::factory()->create([
            'academic_year_id' => $this->year->id,
            'schedule_type' => Setting::TYPE_AFTERNOON,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    public function test_copy_reuses_rooms_maps_baserooms_and_shares_splits_without_duplicates(): void
    {
        $this->rooms['Lab 1']->update(['short_name' => 'LAB', 'capacity' => 30]);
        $existing = $this->afternoon->rooms()->create(['name' => 'Lab 1', 'capacity' => 10]);
        $extra = $this->afternoon->rooms()->create(['name' => 'Study room']);
        $classId = $this->classes['Form 5A']->id;
        DB::table('tt_class_base_rooms')->insert([
            ['tt_setting_id' => $this->setting->id, 'class_id' => $classId, 'tt_room_id' => $this->rooms['Lab 1']->id],
            ['tt_setting_id' => $this->afternoon->id, 'class_id' => $classId, 'tt_room_id' => $extra->id],
        ]);
        $group = $this->science->groups()->create([
            'class_id' => $classId, 'name' => 'Science A', 'entire_class' => false,
        ]);
        $divisionCount = Division::count();
        $groupCount = Group::count();

        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson(route('admin.timetable.grid.same-as-main'), [
                'setting_id' => $this->afternoon->id,
                'source_setting_id' => $this->setting->id,
            ])->assertOk()->assertJsonPath('grid.classes.0.base_room_id', $existing->id);

            $science = collect($response->json('grid.classes.0.divisions'))->firstWhere('id', $this->science->id);
            $this->assertSame($group->id, $science['groups'][0]['id']);
            $this->assertSame(3, $this->afternoon->rooms()->count());
        }

        $this->assertSame(30, (int) $existing->fresh()->capacity);
        $this->assertSame('LAB', $existing->fresh()->short_name);
        $this->assertSame($divisionCount, Division::count());
        $this->assertSame($groupCount, Group::count());
        $this->assertDatabaseHas('tt_class_base_rooms', [
            'tt_setting_id' => $this->setting->id, 'class_id' => $classId, 'tt_room_id' => $this->rooms['Lab 1']->id,
        ]);
        $this->assertSame(2, $this->setting->rooms()->count());
    }

    public function test_copy_rejects_wrong_schedule_types_and_academic_years(): void
    {
        $otherYear = AcademicYear::create(['year_name' => '2027', 'active' => false]);
        $foreign = Setting::factory()->create(['academic_year_id' => $otherYear->id, 'schedule_type' => Setting::TYPE_DAY]);

        foreach ([
            [$this->afternoon->id, $foreign->id],
            [$this->afternoon->id, $this->afternoon->id],
            [$this->setting->id, $this->setting->id],
        ] as [$target, $source]) {
            $this->postJson(route('admin.timetable.grid.same-as-main'), [
                'setting_id' => $target, 'source_setting_id' => $source,
            ])->assertUnprocessable()->assertJsonValidationErrors('source_setting_id');
        }
        $this->assertSame(0, $this->afternoon->rooms()->count());
    }

    public function test_copy_rolls_back_rooms_and_baserooms_if_existing_cards_would_clash(): void
    {
        // Seed two simultaneous afternoon lessons, one explicitly occupying the room
        // that the imported baseroom would assign to the other lesson.
        $main = $this->setting;
        $this->setting = $this->afternoon;
        PeriodFactory::layDay($this->afternoon);
        $room = $this->afternoon->rooms()->create(['name' => 'Lab 1', 'capacity' => 10]);
        $this->rooms->put('Lab 1', $room);
        $first = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($first, day: 1, period: 1);
        $second = $this->lesson('Chemistry', 'Form 5B', 'CHE B', 'N Chisenga', rooms: ['Lab 1']);
        $this->placeCard($second, day: 1, period: 1, room: $room);
        DB::table('tt_class_base_rooms')->insert([
            'tt_setting_id' => $main->id,
            'class_id' => $this->classes['Form 5A']->id,
            'tt_room_id' => $main->rooms()->where('name', 'Lab 1')->firstOrFail()->id,
        ]);

        $this->postJson(route('admin.timetable.grid.same-as-main'), [
            'setting_id' => $this->afternoon->id, 'source_setting_id' => $main->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('base_rooms');

        $this->assertSame(1, $this->afternoon->rooms()->count());
        $this->assertSame(10, (int) $room->fresh()->capacity);
        $this->assertNull($card->fresh()->tt_room_id);
        $this->assertDatabaseMissing('tt_class_base_rooms', ['tt_setting_id' => $this->afternoon->id]);
    }

    public function test_afternoon_editor_offers_same_as_main(): void
    {
        $this->get(route('admin.timetable.index', ['setting' => $this->afternoon->id]))
            ->assertOk()->assertSee('Same as main')->assertSee('Class splits are already the same as main');
        $this->get(route('admin.timetable.index', ['setting' => $this->setting->id]))
            ->assertOk()->assertDontSee('Reuse main timetable setup');
    }

    public function test_only_admins_can_copy_setup(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'teacher', 'status' => 'active']))
            ->postJson(route('admin.timetable.grid.same-as-main'), [
                'setting_id' => $this->afternoon->id, 'source_setting_id' => $this->setting->id,
            ])->assertForbidden();
    }
}
