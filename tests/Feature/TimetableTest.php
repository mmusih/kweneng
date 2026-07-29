<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\TimetableDay;
use App\Models\TimetableEntry;
use App\Models\TimetableGroup;
use App\Models\TimetablePeriod;
use App\Models\TimetableRoom;
use App\Models\TimetableTemplate;
use App\Models\User;
use App\Services\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_five_day_weekly_timetable_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $year = $this->academicYear();

        $response = $this->actingAs($admin)->post(route('admin.timetable.templates.store'), [
            'academic_year_id' => $year->id,
            'name' => 'Main timetable',
            'cycle_type' => 'weekly',
            'cycle_length' => 5,
        ]);

        $template = TimetableTemplate::firstOrFail();

        $response->assertRedirect(route('admin.timetable.index', ['template_id' => $template->id]));
        $this->assertSame(5, $template->days()->count());
        $this->assertSame(
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            $template->days()->pluck('name')->all(),
        );

        $this->actingAs($admin)
            ->get(route('admin.timetable.index', ['template_id' => $template->id]))
            ->assertOk()
            ->assertSee('School timetable');
    }

    public function test_clash_detection_rejects_an_overlapping_teacher_lesson(): void
    {
        $fixture = $this->fixture();
        $service = app(TimetableService::class);

        TimetableEntry::create([
            'timetable_template_id' => $fixture['template']->id,
            'timetable_day_id' => $fixture['day']->id,
            'start_period_id' => $fixture['period']->id,
            'end_period_id' => $fixture['period']->id,
            'class_id' => $fixture['class']->id,
            'subject_id' => $fixture['subject']->id,
            'teacher_id' => $fixture['teacher']->id,
            'timetable_room_id' => $fixture['room']->id,
        ]);

        try {
            $service->validateEntry([
                'timetable_day_id' => $fixture['day']->id,
                'start_period_id' => $fixture['period']->id,
                'end_period_id' => $fixture['period']->id,
                'class_id' => $fixture['class']->id,
                'timetable_group_id' => null,
                'subject_id' => $fixture['subject']->id,
                'teacher_id' => $fixture['teacher']->id,
                'timetable_room_id' => null,
            ]);
            $this->fail('Expected a teacher clash validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('teacher_id', $exception->errors());
        }
    }

    public function test_split_groups_create_individual_student_timetables(): void
    {
        $fixture = $this->fixture();
        $secondStudent = $this->student('student.two@example.test', 'Student Two', $fixture['class']);
        $group = TimetableGroup::create([
            'academic_year_id' => $fixture['year']->id,
            'subject_id' => $fixture['subject']->id,
            'name' => 'Computer Studies Option',
        ]);
        $group->classes()->attach($fixture['class']);
        $group->students()->attach($fixture['student']);

        TimetableEntry::create([
            'timetable_template_id' => $fixture['template']->id,
            'timetable_day_id' => $fixture['day']->id,
            'start_period_id' => $fixture['period']->id,
            'end_period_id' => $fixture['period']->id,
            'timetable_group_id' => $group->id,
            'subject_id' => $fixture['subject']->id,
            'teacher_id' => $fixture['teacher']->id,
            'title' => 'Option lesson',
        ]);

        $service = app(TimetableService::class);
        $selected = $service->forStudent($fixture['student'], '2026-07-27');
        $notSelected = $service->forStudent($secondStudent, '2026-07-27');

        $this->assertSame('Option lesson', $selected['days']->first()['blocks'][0]['title']);
        $this->assertSame('Free period', $notSelected['days']->first()['blocks'][0]['title']);
    }

    public function test_teacher_student_and_linked_parent_can_view_the_published_timetable(): void
    {
        $fixture = $this->fixture();
        $parentUser = User::factory()->create([
            'name' => 'Parent User',
            'email' => 'parent@example.test',
            'role' => 'parent',
            'status' => 'active',
        ]);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($fixture['student'], ['relationship' => 'parent']);

        TimetableEntry::create([
            'timetable_template_id' => $fixture['template']->id,
            'timetable_day_id' => $fixture['day']->id,
            'start_period_id' => $fixture['period']->id,
            'end_period_id' => $fixture['period']->id,
            'class_id' => $fixture['class']->id,
            'subject_id' => $fixture['subject']->id,
            'teacher_id' => $fixture['teacher']->id,
            'title' => 'Mathematics lesson',
        ]);

        $this->actingAs($fixture['teacher']->user)
            ->get(route('teacher.timetable'))
            ->assertOk()
            ->assertSee('Mathematics lesson');

        $this->actingAs($fixture['student']->user)
            ->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('Mathematics lesson');

        $this->actingAs($fixture['student']->user, 'sanctum')
            ->getJson('/api/student/timetable')
            ->assertOk()
            ->assertJsonPath('days.0.blocks.0.title', 'Mathematics lesson');

        $this->actingAs($parentUser)
            ->get(route('parent.timetable', ['student_id' => $fixture['student']->id]))
            ->assertOk()
            ->assertSee('Mathematics lesson');

        $this->actingAs($parentUser, 'sanctum')
            ->getJson('/api/parent/timetable?student_id='.$fixture['student']->id)
            ->assertOk()
            ->assertJsonPath('days.0.blocks.0.title', 'Mathematics lesson');
    }

    private function academicYear(): AcademicYear
    {
        return AcademicYear::create([
            'year_name' => '2026',
            'active' => true,
            'status' => AcademicYear::STATUS_OPEN,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(): array
    {
        $year = $this->academicYear();
        $class = ClassModel::create([
            'name' => 'Form 1A',
            'level' => 1,
            'academic_year_id' => $year->id,
        ]);
        $subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_active' => true,
        ]);
        $teacherUser = User::factory()->create([
            'name' => 'Teacher One',
            'email' => 'teacher@example.test',
            'role' => 'teacher',
            'status' => 'active',
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);
        TeacherSubject::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);
        $student = $this->student('student.one@example.test', 'Student One', $class);
        $template = TimetableTemplate::create([
            'academic_year_id' => $year->id,
            'name' => 'Main timetable',
            'cycle_type' => TimetableTemplate::CYCLE_WEEKLY,
            'cycle_length' => 5,
            'is_active' => true,
            'is_published' => true,
        ]);
        $day = TimetableDay::create([
            'timetable_template_id' => $template->id,
            'day_number' => 1,
            'name' => 'Monday',
            'weekday' => 1,
        ]);
        $period = TimetablePeriod::create([
            'timetable_day_id' => $day->id,
            'sequence' => 1,
            'name' => 'Period 1',
            'start_time' => '08:00',
            'end_time' => '08:40',
            'type' => 'lesson',
        ]);
        $room = TimetableRoom::create(['name' => 'Room 1']);

        return compact('year', 'class', 'subject', 'teacher', 'student', 'template', 'day', 'period', 'room');
    }

    private function student(string $email, string $name, ClassModel $class): Student
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'student',
            'status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'admission_no' => 'ADM-'.strtoupper(substr(md5($email), 0, 8)),
            'gender' => 'female',
            'date_of_birth' => '2012-01-01',
            'current_class_id' => $class->id,
        ]);
    }
}
