<?php

namespace Tests\Feature;

use App\Models\Mark;
use App\Models\Student;
use App\Models\StudyRule;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class StudyRetentionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    public function test_admin_can_configure_both_conditions_and_print_the_result(): void
    {
        $this->seedSchool();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $studentUser = User::factory()->create(['name' => 'Study Learner', 'role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'admission_no' => 'ST-001',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
            'current_class_id' => $this->classes['Form 5A']->id,
        ]);
        $term = Term::create([
            'academic_year_id' => $this->year->id,
            'name' => 'Term 2',
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-01',
            'status' => Term::STATUS_ACTIVE,
        ]);

        Mark::create([
            'student_id' => $student->id,
            'subject_id' => $this->subjects['Biology']->id,
            'class_id' => $this->classes['Form 5A']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id,
            'term_id' => $term->id,
            'midterm_score' => 45,
            'endterm_score' => 55,
        ]);
        Mark::create([
            'student_id' => $student->id,
            'subject_id' => $this->subjects['Chemistry']->id,
            'class_id' => $this->classes['Form 5A']->id,
            'teacher_id' => $this->teachers['N Chisenga']->id,
            'academic_year_id' => $this->year->id,
            'term_id' => $term->id,
            'midterm_score' => 65,
            'endterm_score' => 65,
        ]);

        $this->actingAs($admin)->post(route('admin.study-retention.store'), [
            'term_id' => $term->id,
            'scope_type' => 'form',
            'scope_value' => '5',
            'overall_enabled' => '1',
            'overall_threshold' => 60,
            'subject_enabled' => '1',
            'subject_threshold' => 60,
        ])->assertRedirect(route('admin.study-retention.index', ['term_id' => $term->id]));

        $this->assertDatabaseHas('study_rules', [
            'term_id' => $term->id,
            'scope_type' => 'form',
            'scope_value' => '5',
            'overall_enabled' => true,
            'subject_enabled' => true,
        ]);

        $this->get(route('admin.study-retention.index', ['term_id' => $term->id]))
            ->assertOk()->assertSee('Study Learner')->assertSee('Biology')->assertSee('57.5%');
        $this->get(route('admin.study-retention.print', ['term_id' => $term->id]))
            ->assertOk()->assertSee('Study Learner')->assertSee('Attend: Biology (50%)');
    }

    public function test_class_rule_overrides_a_form_rule(): void
    {
        $this->seedSchool();
        $studentUser = User::factory()->create(['name' => 'Override Learner', 'role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $studentUser->id, 'admission_no' => 'ST-002', 'gender' => 'male',
            'date_of_birth' => '2010-01-01', 'current_class_id' => $this->classes['Form 5A']->id,
        ]);
        $term = Term::create([
            'academic_year_id' => $this->year->id, 'name' => 'Term 1',
            'start_date' => '2026-01-01', 'end_date' => '2026-04-01', 'status' => Term::STATUS_FINALIZED,
        ]);

        StudyRule::create([
            'academic_year_id' => $this->year->id, 'term_id' => $term->id,
            'scope_type' => 'form', 'scope_value' => '5', 'overall_enabled' => true,
            'overall_threshold' => 60, 'subject_enabled' => false, 'subject_threshold' => 60,
        ]);
        StudyRule::create([
            'academic_year_id' => $this->year->id, 'term_id' => $term->id,
            'scope_type' => 'class', 'scope_value' => (string) $this->classes['Form 5A']->id,
            'overall_enabled' => true, 'overall_threshold' => 50,
            'subject_enabled' => false, 'subject_threshold' => 60,
        ]);

        Mark::create([
            'student_id' => $student->id, 'subject_id' => $this->subjects['Biology']->id,
            'class_id' => $this->classes['Form 5A']->id,
            'teacher_id' => $this->teachers['K Simukonda']->id,
            'academic_year_id' => $this->year->id, 'term_id' => $term->id,
            'midterm_score' => 55, 'endterm_score' => 55,
        ]);

        $this->assertCount(0, app(\App\Services\StudyRetentionService::class)->report($term));
    }
}
