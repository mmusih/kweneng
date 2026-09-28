<?php

namespace Tests\Feature\Timetable;

use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\User;
use App\Services\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class PublishedGridScheduleTest extends TestCase
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

    public function test_a_complete_grid_revision_can_be_published(): void
    {
        $biology = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            periodsPerWeek: 1,
        );
        $this->placeCard($biology, day: 1, period: 1);

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.settings.publish', $this->setting))
            ->assertRedirect(route('admin.timetable.index', ['setting' => $this->setting->id]));

        $this->assertTrue($this->setting->fresh()->is_active);
        $this->assertTrue($this->setting->fresh()->is_published);
    }

    public function test_publishing_is_refused_while_lessons_are_still_in_the_tray(): void
    {
        $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            periodsPerWeek: 2,
        );

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.settings.publish', $this->setting))
            ->assertSessionHasErrors('publish');

        $this->assertFalse($this->setting->fresh()->is_published);
    }

    public function test_teacher_schedule_comes_from_published_grid_cards_and_keeps_the_payload_contract(): void
    {
        $biology = $this->lesson(
            'Biology',
            'Form 5A',
            'BIO A',
            'K Simukonda',
            double: true,
            periodsPerWeek: 2,
            rooms: ['Lab 1'],
        );
        $this->placeCard($biology, day: 2, period: 2, room: $this->rooms['Lab 1']);
        $this->setting->update(['is_active' => true, 'is_published' => true]);

        $schedule = app(TimetableService::class)->forTeacher($this->teachers['K Simukonda'], '2026-08-04');

        $this->assertSame(
            ['template', 'date', 'selected_day_number', 'selected_day_name', 'days'],
            array_keys($schedule),
        );
        $this->assertCount(6, $schedule['days']);

        $day = $schedule['days']->firstWhere('day_number', 2);
        $lesson = collect($day['blocks'])->firstWhere('kind', 'lesson');

        $this->assertSame('Biology', $lesson['subject']);
        $this->assertSame('Period 2', $lesson['period_name']);
        $this->assertSame('Period 3', $lesson['end_period_name']);
        $this->assertSame('Lab 1', $lesson['room']);
        $this->assertSame(80, $lesson['duration_minutes']);
        $this->assertContains('event', collect($day['blocks'])->pluck('kind'));
    }

    public function test_students_in_parallel_groups_see_only_their_own_subject(): void
    {
        $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'admission_no' => 'GRID-001',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
            'current_class_id' => $this->classes['Form 5A']->id,
        ]);

        $biology = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda', periodsPerWeek: 1);
        $chemistry = $this->lesson('Chemistry', 'Form 5A', 'CHE A', 'N Chisenga', periodsPerWeek: 1);

        StudentSubject::create([
            'student_id' => $student->id,
            'subject_id' => $this->subjects['Biology']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'class_id' => $this->classes['Form 5A']->id,
            'academic_year_id' => $this->year->id,
            'is_elective' => false,
        ]);

        $this->placeCard($biology, day: 1, period: 1);
        $this->placeCard($chemistry, day: 1, period: 1);
        $this->setting->update(['is_active' => true, 'is_published' => true]);

        $schedule = app(TimetableService::class)->forStudent($student);
        $subjects = collect($schedule['days']->first()['blocks'])
            ->where('kind', 'lesson')
            ->pluck('subject')
            ->all();

        $this->assertSame(['Biology'], $subjects);
    }

    public function test_empty_payload_serializes_days_as_an_empty_array(): void
    {
        $this->setting->delete();

        $payload = app(TimetableService::class)->emptySchedule('2026-08-04');

        $this->assertSame('[]', json_encode($payload['days']));
    }
}
