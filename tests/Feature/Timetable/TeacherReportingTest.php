<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Setting;
use App\Models\User;
use App\Services\Timetable\TeacherLoadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class TeacherReportingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->setting->update(['is_published' => true, 'is_active' => true]);
    }

    public function test_shared_singles_and_doubles_count_occupied_periods_once(): void
    {
        $joint = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', double: true);
        $joint->classes()->attach($this->classes['Form 5B']->id);
        $this->placeCard($joint, day: 1, period: 1);

        // Some imports represent the same shared option separately for each class.
        $peer = $this->lesson('Biology', 'Form 5B', 'BIO B', 'K Simukonda', double: true);
        $joint->update(['split_key' => 'shared-biology']);
        $peer->update(['split_key' => 'shared-biology']);
        $this->placeCard($peer, day: 1, period: 1);
        $single = $this->lesson('Biology', 'Form 5A', 'BIO C', 'K Simukonda');
        $single->classes()->attach($this->classes['Form 5B']->id);
        $single->teachers()->attach($this->teachers['N Chisenga']->id);
        $this->placeCard($single, day: 2, period: 3);
        $this->lesson('Physics', 'Form 5A', 'PHY', 'K Simukonda'); // Unplaced: excluded.

        $summary = app(TeacherLoadService::class)->summary($this->year->id);
        $teacher = collect($summary['teachers'])->firstWhere('teacher_id', $this->teachers['K Simukonda']->id);
        $this->assertSame(3, $teacher['grand_scheduled']);
        $this->assertSame([1 => 2, 2 => 1, 3 => 0, 4 => 0, 5 => 0, 6 => 0], $teacher['schedules'][0]['days']);
        $biology = collect($teacher['schedules'][0]['subjects'])->firstWhere('subject', 'Biology');
        $this->assertSame(3, $biology['scheduled_periods']);
        $this->assertSame(['Form 5A', 'Form 5B'], $biology['classes']);
        $coTeacher = collect($summary['teachers'])->firstWhere('teacher_id', $this->teachers['N Chisenga']->id);
        $this->assertSame(1, $coTeacher['grand_scheduled']);
    }

    public function test_multiday_masks_duplicates_and_afternoon_loads_are_counted_correctly(): void
    {
        $lesson = $this->lesson('Mathematics', 'Form 5A', 'Math', 'K Simukonda');
        foreach (range(1, 2) as $duplicate) {
            Card::factory()->create(['tt_lesson_id' => $lesson->id, 'period_number' => 4, 'days' => '101000']);
        }
        $afternoon = Setting::factory()->create(['academic_year_id' => $this->year->id, 'schedule_type' => 'afternoon', 'is_active' => true, 'is_published' => true]);
        $study = $this->lesson('Physics', 'Form 5A', 'Study', 'K Simukonda');
        $study->update(['tt_setting_id' => $afternoon->id]);
        $this->placeCard($study, day: 1, period: 4);
        $teacher = collect(app(TeacherLoadService::class)->summary($this->year->id)['teachers'])->firstWhere('teacher_id', $this->teachers['K Simukonda']->id);
        $this->assertSame(3, $teacher['grand_scheduled']);
        $this->assertSame(2, $teacher['schedules'][0]['scheduled_total']);
        $this->assertSame(1, $teacher['schedules'][1]['scheduled_total']);
    }

    public function test_published_report_ignores_newer_drafts_and_supports_teacher_filter(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda');
        $this->placeCard($lesson, day: 1, period: 1);
        $draft = Setting::factory()->create(['academic_year_id' => $this->year->id, 'is_published' => false, 'is_active' => false, 'name' => 'New draft']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.timetable.teacher-loads'))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['schedules'][0]['id'] === $this->setting->id);
        $this->get(route('admin.timetable.teacher-loads', ['source' => 'working']))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['schedules'][0]['id'] === $draft->id);
        $this->get(route('admin.timetable.teaching-summary', ['teacher_id' => $this->teachers['K Simukonda']->id]))
            ->assertOk()->assertSee('BIO')->assertViewHas('summary', fn ($summary) => count($summary['teachers']) === 1);
    }

    public function test_admin_and_headmaster_can_open_and_export_both_reports(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', double: true);
        $lesson->classes()->attach($this->classes['Form 5B']->id);
        $this->placeCard($lesson, day: 1, period: 1);
        foreach (['admin', 'headmaster'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
            foreach (['teacher-loads', 'teaching-summary'] as $report) {
                $this->get(route($role.'.timetable.'.$report))->assertOk()->assertSee('K Simukonda')
                    ->assertSee(route($role.'.timetable.teacher-loads'), false)->assertSee(route($role.'.timetable.teaching-summary'), false);
                $response = $this->get(route($role.'.timetable.'.$report.'.download'))->assertOk()->assertHeader('content-type', 'application/pdf');
                $this->assertStringStartsWith('%PDF-', $response->getContent());
            }
            $csv = $this->get(route($role.'.timetable.teaching-summary.csv'))->assertOk()->streamedContent();
            $this->assertStringContainsString('Form 5A; Form 5B', $csv);
            $this->assertStringContainsString('K Simukonda', $csv);
        }
    }

    public function test_other_roles_cannot_access_or_export_reports(): void
    {
        $this->actingAs($this->teachers['K Simukonda']->user);
        foreach (['admin', 'headmaster'] as $role) {
            foreach (['teacher-loads', 'teacher-loads.download', 'teaching-summary', 'teaching-summary.download', 'teaching-summary.csv'] as $report) {
                $this->get(route($role.'.timetable.'.$report))->assertForbidden();
            }
        }
    }

    public function test_missing_timetable_has_a_clear_empty_state(): void
    {
        $this->setting->update(['is_published' => false]);
        $this->actingAs(User::factory()->create(['role' => 'headmaster', 'status' => 'active']))
            ->get(route('headmaster.timetable.teaching-summary'))->assertOk()
            ->assertSee('No active published timetable exists')->assertSee('No scheduled teaching periods match');
    }
    public function test_excluding_a_form_recalculates_totals_and_keeps_shared_lessons(): void
    {
        $four = \App\Models\ClassModel::create(['name' => 'Form 4A', 'level' => 4, 'academic_year_id' => $this->year->id]);
        $shared = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', double: true);
        $shared->classes()->attach($four->id);
        $this->placeCard($shared, day: 1, period: 1);
        $fives = $this->lesson('Physics', 'Form 5B', 'PHY', 'K Simukonda');
        $this->placeCard($fives, day: 2, period: 3);
        $summary = app(TeacherLoadService::class)->summary($this->year->id, true, [5]);
        $teacher = collect($summary['teachers'])->firstWhere('teacher_id', $this->teachers['K Simukonda']->id);
        $this->assertSame(2, $teacher['grand_scheduled']);
        $this->assertSame(['Form 4A'], $teacher['schedules'][0]['subjects'][0]['classes']);
        $this->assertSame([], $teacher['schedules'][0]['subjects'][0]['groups']);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $filters = ['exclude_forms' => [5]];
        $this->get(route('admin.timetable.teaching-summary', $filters))->assertOk()->assertSee('Form 4A')->assertDontSee('Form 5A')->assertDontSee('Physics');
        $csv = $this->get(route('admin.timetable.teaching-summary.csv', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('Form 4A', $csv);
        $this->assertStringNotContainsString('Form 5', $csv);
        $allExcluded = app(TeacherLoadService::class)->summary($this->year->id, true, [4, 5]);
        $this->assertSame(0, collect($allExcluded['teachers'])->sum('grand_scheduled'));
    }

    public function test_reports_return_to_timetable_and_are_absent_from_global_navigation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $this->get(route('admin.teachers.index'))->assertOk()
            ->assertDontSee(route('admin.timetable.teacher-loads'), false)
            ->assertDontSee(route('admin.timetable.teaching-summary'), false);
        $this->get(route('admin.timetable.teacher-loads', ['setting' => $this->setting->id]))->assertOk()
            ->assertSee(route('admin.timetable.index', ['setting' => $this->setting->id]), false)
            ->assertDontSee('window.history.back()', false);
        $this->actingAs(User::factory()->create(['role' => 'headmaster', 'status' => 'active']));
        $this->get(route('headmaster.timetable.index'))->assertOk()->assertSee(route('headmaster.timetable.teacher-loads'), false);
        $this->get(route('headmaster.timetable.teaching-summary'))->assertOk()->assertSee(route('headmaster.timetable.index'), false);
    }

}
