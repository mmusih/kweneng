<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\Room;
use App\Models\User;
use App\Services\Timetable\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_report_identifies_unplaced_work_teacher_overload_and_room_failures(): void
    {
        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 3, rooms: ['Lab 1']);
        $chemistry = $this->lesson('Chemistry', 'Form 5A', 'CHE A', 'N Chisenga', periodsPerWeek: 1, rooms: ['Lab 1']);
        $maths = $this->lesson('Mathematics', 'Form 5B', 'MAT A', 'K Simukonda', periodsPerWeek: 1);

        $this->placeCard($biology, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $this->placeCard($chemistry, day: 1, period: 1, room: $this->rooms['Lab 1']);
        $this->placeCard($maths, day: 1, period: 2);

        DB::table('tt_teacher_meta')->insert([
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'max_lessons_per_day' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(VerificationService::class)->verify($this->setting);

        $this->assertFalse($report['clean']);
        $this->assertSame('Biology', $report['unplaced'][0]['subject']);
        $this->assertSame(2, $report['unplaced'][0]['periods']);
        $this->assertNotEmpty(collect($report['conflicts'])->where('kind', 'room'));
        $this->assertSame(2, collect($report['workloads'])->firstWhere('teacher', 'K Simukonda')['placed_periods']);
        $this->assertNotEmpty(collect($report['workloads'])->firstWhere('teacher', 'K Simukonda')['overloaded_days']);
        $this->assertArrayHasKey(100, $report['violations_by_weight']);
    }

    public function test_capacity_failure_blocks_publishing_and_is_visible_on_verification_page(): void
    {
        $smallRoom = Room::create(['tt_setting_id' => $this->setting->id, 'name' => 'Small room', 'capacity' => 10]);
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 1);
        $lesson->update(['capacity' => 25]);
        $this->placeCard($lesson, day: 1, period: 1, room: $smallRoom);

        $this->actingAs($this->admin)
            ->get(route('admin.timetable.settings.verification', $this->setting))
            ->assertOk()
            ->assertSee('Small room holds 10, but Biology needs 25 places.')
            ->assertSee('Publishing is blocked.');

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.settings.publish', $this->setting))
            ->assertSessionHasErrors('publish');

        $this->assertFalse($this->setting->fresh()->is_published);
    }

    public function test_teacher_workload_does_not_combine_mutually_exclusive_terms(): void
    {
        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 1);
        $maths = $this->lesson('Mathematics', 'Form 5B', 'MAT A', 'K Simukonda', periodsPerWeek: 1);
        $this->placeCard($biology, day: 1, period: 1, terms: '10');
        $this->placeCard($maths, day: 1, period: 2, terms: '01');
        DB::table('tt_teacher_meta')->insert([
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'max_lessons_per_day' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workload = collect(app(VerificationService::class)->verify($this->setting)['workloads'])
            ->firstWhere('teacher', 'K Simukonda');

        $this->assertSame([], $workload['overloaded_days']);
    }

    public function test_class_teacher_and_room_print_views_render_for_a_seeded_timetable(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 1, rooms: ['Lab 1']);
        $this->placeCard($lesson, day: 2, period: 3, room: $this->rooms['Lab 1']);

        foreach ([
            ['class', $this->classes['Form 5A']->id],
            ['teacher', $this->teachers['K Simukonda']->id],
            ['room', $this->rooms['Lab 1']->id],
        ] as [$type, $id]) {
            $this->actingAs($this->admin)
                ->get(route('admin.timetable.settings.print', [$this->setting, $type, $id]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }
}
