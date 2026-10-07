<?php

namespace Tests\Feature;

use App\Models\{Attendance, Mark, ParentModel, Student, StudyEnrolment, StudyRetentionSetting, StudyRule, Term, User};
use App\Services\StudyRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class ParentSchoolAttendanceTest extends TestCase
{
    use RefreshDatabase, SeedsTimetableSchool;

    private Student $student;
    private Term $term;
    private Term $source;
    private User $parentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-06 10:00:00', 'Africa/Gaborone'));
        $this->term = Term::create(['academic_year_id' => $this->year->id, 'name' => 'Term 3',
            'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'status' => Term::STATUS_ACTIVE]);
        $this->source = Term::create(['academic_year_id' => $this->year->id, 'name' => 'Term 2',
            'start_date' => '2026-05-01', 'end_date' => '2026-08-01', 'status' => Term::STATUS_FINALIZED]);
        $this->student = Student::create(['user_id' => User::factory()->create(['name' => 'School Learner', 'role' => 'student'])->id,
            'admission_no' => 'ATT-001', 'gender' => 'female', 'date_of_birth' => '2010-01-01',
            'current_class_id' => $this->classes['Form 5A']->id]);
        $this->parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $this->parentUser->id]);
        $parent->students()->attach($this->student->id, ['relationship' => 'parent']);
        Sanctum::actingAs($this->parentUser);
    }

    private function mark(string $status, string $date = '2026-10-06'): Attendance
    {
        return Attendance::whereDate('attendance_date', $date)->updateOrCreate(['student_id' => $this->student->id], [
            'attendance_date' => $date,
            'class_id' => $this->classes['Form 5A']->id, 'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id, 'term_id' => $this->term->id, 'status' => $status,
        ]);
    }

    private function configureStudy(): Mark
    {
        StudyRule::create(['academic_year_id' => $this->year->id, 'term_id' => $this->term->id,
            'scope_type' => 'form', 'scope_value' => '5', 'overall_enabled' => true, 'overall_threshold' => 60,
            'subject_enabled' => true, 'subject_threshold' => 60]);
        StudyRetentionSetting::create(['term_id' => $this->term->id, 'source_term_id' => $this->source->id, 'assessment' => 'midterm']);
        return Mark::create(['student_id' => $this->student->id, 'subject_id' => $this->subjects['Biology']->id,
            'class_id' => $this->classes['Form 5A']->id, 'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id, 'term_id' => $this->source->id, 'midterm_score' => 45, 'endterm_score' => 75]);
    }

    public function test_today_status_uses_only_todays_register_and_linked_children(): void
    {
        $this->mark('present', '2026-10-05');
        $this->getJson('/api/parent/attendance/today')->assertOk()->assertJsonCount(1, 'children')
            ->assertJsonPath('children.0.status', 'unmarked')->assertJsonPath('children.0.in_school', false);
        $this->mark('present');
        $this->getJson('/api/parent/attendance/today')->assertOk()
            ->assertJsonPath('children.0.label', 'In school: 07:30–13:10')->assertJsonPath('children.0.in_school', true);
        $this->mark('absent');
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.label', 'Absent')->assertJsonPath('children.0.in_school', false);
        $this->mark('excused');
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.label', 'Absent (excused)');
        $this->mark('late');
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.in_school', true);
        $other = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        ParentModel::create(['user_id' => $other->id]);
        Sanctum::actingAs($other);
        $this->getJson('/api/parent/attendance/today?student_id='.$this->student->id)->assertOk()->assertJsonCount(0, 'children');
    }

    public function test_selected_assessment_and_voluntary_study_control_departure(): void
    {
        $mark = $this->configureStudy();
        $this->mark('present');
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.label', 'In school: 07:30–15:15');
        StudyRetentionSetting::where('term_id', $this->term->id)->update(['assessment' => 'endterm']);
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.school_end', '13:10');
        StudyEnrolment::create(['term_id' => $this->term->id, 'student_id' => $this->student->id]);
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.school_end', '15:15')->assertJsonPath('children.0.voluntary_study', true);
        $mark->delete();
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.study', true);
        $this->travelTo(now()->setTime(15, 15));
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.in_school', false)->assertJsonPath('children.0.label', 'Present today: 07:30–15:15');
        $this->travelTo(now()->addDay()->setTime(10, 0));
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.status', 'unmarked');
    }

    public function test_missing_assessment_is_not_zero_and_voluntary_enrolment_expires_with_term(): void
    {
        $mark = $this->configureStudy();
        $mark->update(['midterm_score' => null]);
        $this->assertCount(0, app(StudyRetentionService::class)->report($this->term));
        StudyEnrolment::create(['term_id' => $this->source->id, 'student_id' => $this->student->id]);
        $this->assertCount(0, app(StudyRetentionService::class)->report($this->term));
        $mark->update(['midterm_score' => 0]);
        $this->assertCount(1, app(StudyRetentionService::class)->report($this->term));
    }

    public function test_teacher_register_save_is_visible_to_parent(): void
    {
        $teacher = $this->teachers['K Simukonda'];
        $this->classes['Form 5A']->update(['class_teacher_id' => $teacher->id]);
        Sanctum::actingAs($teacher->user);
        $this->postJson('/api/teacher/attendance/register', ['class_id' => $this->classes['Form 5A']->id, 'date' => '2026-10-06',
            'students' => [['student_id' => $this->student->id, 'status' => 'present']]])->assertOk();
        $this->postJson('/api/teacher/attendance/register', ['class_id' => $this->classes['Form 5A']->id, 'date' => '2026-10-06',
            'students' => [['student_id' => $this->student->id, 'status' => 'present']]])->assertOk();
        $this->assertDatabaseCount('attendances', 1);
        Sanctum::actingAs($this->parentUser);
        $this->getJson('/api/parent/attendance/today')->assertJsonPath('children.0.label', 'In school: 07:30–13:10');
    }

    public function test_admin_can_select_results_and_manage_volunteers(): void
    {
        $this->configureStudy();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);
        $this->post(route('admin.study-retention.selection'), ['term_id' => $this->term->id, 'source_term_id' => $this->source->id, 'assessment' => 'endterm'])->assertSessionHasNoErrors();
        $this->post(route('admin.study-retention.enrol'), ['term_id' => $this->term->id, 'student_id' => $this->student->id])->assertSessionHasNoErrors();
        $this->get(route('admin.study-retention.index', ['term_id' => $this->term->id]))->assertOk()->assertSee('Voluntary study')->assertSee('School Learner');
        $this->get(route('admin.study-retention.print', ['term_id' => $this->term->id]))->assertOk()->assertSee('End of term')->assertSee('Voluntary study.');
        $this->delete(route('admin.study-retention.unenrol', StudyEnrolment::first()))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('study_enrolments', 0);
        $this->post(route('admin.study-retention.selection'), ['term_id' => $this->term->id, 'source_term_id' => $this->source->id, 'assessment' => 'combined'])->assertSessionHasErrors('assessment');
        $this->post(route('admin.study-retention.selection'), ['term_id' => $this->source->id, 'source_term_id' => $this->term->id, 'assessment' => 'midterm'])->assertSessionHasErrors('source_term_id');
    }
}
