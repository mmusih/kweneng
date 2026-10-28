<?php

namespace Tests\Feature;

use App\Models\{ParentModel, Student, User};
use App\Services\{TimetableService, StudentTimetablePdfService, BehaviourSummaryService};
use App\Models\Tt\{Setting, Period};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class ParentTimetableDownloadsTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    private Student $student;
    private User $parentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->setting->update(['is_active' => true, 'is_published' => true,
            'cycle_anchor_date' => '2026-08-03', 'cycle_anchor_day' => 1]);
        $this->student = Student::create(['user_id' => User::factory()->create(['name' => 'Timetable Learner', 'role' => 'student'])->id,
            'admission_no' => 'TT-PDF', 'gender' => 'female', 'date_of_birth' => '2010-01-01',
            'current_class_id' => $this->classes['Form 5A']->id]);
        $this->parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $this->parentUser->id]);
        $parent->students()->attach($this->student, ['relationship' => 'parent']);
        $lesson = $this->lesson('Biology', 'Form 5A', 'Entire class', 'K Simukonda', entireClass: true, periodsPerWeek: 1);
        $this->placeCard($lesson, day: 1, period: 1);
        $study = Setting::factory()->published()->create(['academic_year_id' => $this->year->id,
            'schedule_type' => Setting::TYPE_AFTERNOON, 'cycle_length' => 5]);
        Period::create(['tt_setting_id' => $study->id, 'period_number' => 1, 'name' => 'Study',
            'start_time' => '14:00:00', 'end_time' => '15:00:00']);
    }

    public function test_current_activity_uses_today_and_exclusive_end_times_across_both_schedules(): void
    {
        $this->actingAs($this->parentUser);
        $this->travelTo(\Carbon\Carbon::parse('2026-08-03 07:30:00', config('app.timezone')));
        $url = route('parent.timetable.current', ['student_id' => $this->student->id]);
        $this->getJson($url)->assertOk()->assertJsonPath('blocks.0.title', 'Biology');
        $this->get(route('parent.dashboard'))->assertOk()->assertSee('Scheduled now')->assertSee('Biology')->assertDontSee('Club: Not yet assigned');
        $this->travelTo(now()->setTime(8, 10));
        $this->assertStringNotContainsString('Biology', app(TimetableService::class)->currentActivity($this->student)['description']);
        $this->travelTo(now()->setTime(14, 15));
        $this->assertCount(1, app(TimetableService::class)->currentActivity($this->student)['blocks']);
        $this->travelTo(now()->setDate(2026, 8, 8));
        $this->assertCount(0, app(TimetableService::class)->currentActivity($this->student)['blocks']);
        $this->getJson(route('parent.timetable.current', ['student_id' => 999999]))->assertForbidden();
    }

    public function test_both_downloads_are_authorized_single_page_landscape_pdfs(): void
    {
        $this->actingAs($this->parentUser);
        $payload = app(TimetableService::class)->forStudent($this->student, '2026-08-03', true);
        foreach ($payload['schedules'] as $schedule) {
            $type = $schedule['template']['schedule_type'];
            $this->assertCount($type === 'day' ? 6 : 5, $schedule['days']);
            $pdf = app(StudentTimetablePdfService::class)->render($this->student, $schedule);
            $canvas = $pdf->getDomPDF()->getCanvas();
            $this->assertSame(1, $canvas->get_page_count());
            $this->assertGreaterThan($canvas->get_height(), $canvas->get_width());
            if (getenv('TIMETABLE_PDF_QA')) {
                @mkdir(base_path('tmp/pdfs'), 0777, true);
                file_put_contents(base_path('tmp/pdfs/parent-'.$type.'.pdf'), $pdf->output());
            }
            $this->get(route('parent.timetable.download', ['student_id' => $this->student->id, 'schedule_type' => $type]))
                ->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        $this->get(route('parent.timetable.download', ['student_id' => 999999, 'schedule_type' => 'day']))->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($this->parentUser);
        $this->getJson('/api/parent/timetable/download?student_id='.$this->student->id.'&schedule_type=day')->assertOk();
        $this->getJson('/api/parent/timetable/download?student_id=999999&schedule_type=day')->assertForbidden();
    }

    public function test_dense_six_day_schedule_stays_on_one_landscape_page(): void
    {
        $schedule = app(TimetableService::class)->forStudent($this->student, '2026-08-03', true)['schedules']->first();
        $schedule['days'] = collect($schedule['days'])->map(function ($day) {
            $day['blocks'] = collect($day['blocks'])->map(fn ($block) => array_replace($block, [
                'kind' => 'lesson', 'title' => 'English First Language',
                'teacher' => 'Ms Long Teacher Name', 'room' => 'Science Laboratory 1', 'group' => 'Form 5A English',
            ]))->all();
            return $day;
        });
        $pdf = app(StudentTimetablePdfService::class)->render($this->student, $schedule);
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        if (getenv('TIMETABLE_PDF_QA')) file_put_contents(base_path('tmp/pdfs/parent-dense.pdf'), $pdf->output());
    }

    public function test_behaviour_is_based_on_records_rather_than_an_assumed_good_rating(): void
    {
        $service = app(BehaviourSummaryService::class);
        $this->assertSame('No incidents recorded', $service->label(collect()));
        $this->assertSame('2 minor, 1 major incident(s)', $service->label(collect([
            ['severity' => 'minor'], ['severity' => 'minor'], ['severity' => 'major'],
        ])));
    }
}
