<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\TimetableDay;
use App\Models\TimetablePeriod;
use App\Models\TimetableRoom;
use App\Models\TimetableTemplate;
use App\Models\User;
use App\Services\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_bookmarks_redirect_to_the_current_timetable_and_old_writes_are_removed(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get('/admin/timetable/legacy')
            ->assertRedirect('/admin/timetable');
        $this->post('/admin/timetable/templates', [])->assertNotFound();
        $this->post('/admin/timetable/templates/1/publish', [])->assertNotFound();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.timetable.legacy'));
    }

    public function test_published_legacy_schedules_are_not_served_to_teachers_or_students(): void
    {
        $fixture = $this->fixture();
        $service = app(TimetableService::class);
        foreach ([false, true] as $includeSchedules) {
            $teacher = $service->forTeacher($fixture['teacher'], '2026-07-06', $includeSchedules);
            $student = $service->forStudent($fixture['student'], '2026-07-06', $includeSchedules);
            $this->assertNull($teacher['template']);
            $this->assertNull($student['template']);
            $this->assertCount(0, $teacher['days']);
            $this->assertCount(0, $student['days']);
        }
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
