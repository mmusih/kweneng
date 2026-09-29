<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarksMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_selects_term_orders_attention_and_links_student_profiles(): void
    {
        [$headmaster, $year, $term, $class, $subject, $teachers, $students] = $this->monitoringScenario();
        $otherTerm = Term::create(['academic_year_id' => $year->id, 'name' => 'Earlier term', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'finalized']);
        foreach ([40, 0, null] as $index => $score) {
            Mark::create(['student_id' => $students[$index]->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'teacher_id' => $teachers[0]->id, 'academic_year_id' => $year->id, 'term_id' => $otherTerm->id, 'midterm_score' => $score]);
        }
        $this->actingAs($headmaster)->get(route('headmaster.dashboard', ['term_id' => $otherTerm->id]))
            ->assertOk()->assertViewHas('currentTerm', fn ($value) => $value->id === $otherTerm->id)
            ->assertViewHas('dashboard', fn ($value) => $value['atRiskStudentsCount'] === 2 && $value['recentAtRiskStudents']->first()->id === $students[1]->id)
            ->assertViewHas('classStats', fn ($rows) => $rows->firstWhere('id', $class->id)['assessed'] === 2)
            ->assertSee('Student profiles');
        $this->get(route('headmaster.students.show', ['student' => $students[1], 'term_id' => $otherTerm->id]))
            ->assertOk()->assertSee('0.0%')->assertSee('Needs academic attention');
        $this->get(route('headmaster.students.index', ['search' => $students[1]->admission_no]))->assertOk();
        $this->get(route('headmaster.dashboard', ['term_id' => 999999]))->assertSessionHasErrors('term_id');
        $this->actingAs($students[0]->user)->get(route('headmaster.students.show', $students[1]))->assertForbidden();
    }

    public function test_monitor_counts_only_active_learners_assigned_to_each_teacher(): void
    {
        [$headmaster, $year, $term, $class, $subject, $teachers, $students] = $this->monitoringScenario();

        foreach ($students as $index => $student) {
            Mark::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'teacher_id' => $index < 2 ? $teachers[0]->id : $teachers[1]->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'endterm_score' => 70 + $index,
            ]);
        }

        $this->actingAs($headmaster)
            ->get(route('headmaster.marks.index', [
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'assessment' => 'endterm',
            ]))
            ->assertOk()
            ->assertViewHas('summary', function (array $summary) {
                return $summary['expected'] === 3
                    && $summary['completed'] === 3
                    && $summary['missing'] === 0
                    && $summary['progress'] === 100
                    && $summary['complete_teachers'] === 2;
            })
            ->assertSee('All assigned learners have marks entered.')
            ->assertSee('Teachers needing attention appear first.');
    }

    public function test_headmaster_dashboard_uses_real_learner_subject_assignments_for_completion(): void
    {
        [$headmaster, $year, $term, $class, $subject, $teachers, $students] = $this->monitoringScenario();

        // This class subject has no assigned learners. The old calculation
        // multiplied every learner by every class subject and incorrectly
        // treated it as missing marks.
        $unassignedElective = Subject::create([
            'name' => 'Optional Art',
            'code' => 'ART-OPT',
            'is_core' => false,
            'is_active' => true,
        ]);
        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $unassignedElective->id,
            'academic_year_id' => $year->id,
            'max_marks' => 100,
            'passing_marks' => 40,
        ]);

        foreach ($students as $index => $student) {
            Mark::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'teacher_id' => $index < 2 ? $teachers[0]->id : $teachers[1]->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'endterm_score' => 65 + $index,
            ]);
        }

        $this->actingAs($headmaster)
            ->get(route('headmaster.dashboard'))
            ->assertOk()
            ->assertViewHas('dashboard', function (array $dashboard) {
                return $dashboard['averageMarksCompletion'] === 100
                    && $dashboard['marksExpected'] === 3
                    && $dashboard['marksEntered'] === 3
                    && $dashboard['marksMissing'] === 0;
            })
            ->assertSee('3 of 3 assigned learner marks entered')
            ->assertSee('What needs your attention');
    }

    public function test_admin_manage_marks_shows_teacher_progress_without_listing_student_marks(): void
    {
        [$headmaster, $year, $term, $class, $subject, $teachers, $students] = $this->monitoringScenario();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        foreach ($students as $index => $student) {
            Mark::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'teacher_id' => $index < 2 ? $teachers[0]->id : $teachers[1]->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'midterm_score' => 73 + $index,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.marks.index', [
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'assessment' => 'midterm',
            ]))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['progress'] === 100
                && $summary['complete_teachers'] === 2)
            ->assertSee('Teacher progress')
            ->assertSee('Teacher One')
            ->assertSee('Edit marks')
            ->assertDontSee('Learner One')
            ->assertDontSee('Midterm Score')
            ->assertDontSee('Grade</th>', false);
    }

    public function test_admin_marks_defaults_to_active_term_but_allows_another_term(): void
    {
        [, $year, $activeTerm] = $this->monitoringScenario();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $otherTerm = Term::create([
            'academic_year_id' => $year->id, 'name' => 'Term 3',
            'start_date' => '2026-09-01', 'end_date' => '2026-11-30', 'status' => 'finalized',
        ]);

        $this->actingAs($admin)->get(route('admin.marks.index'))
            ->assertOk()
            ->assertViewHas('selectedTermId', $activeTerm->id);

        $this->get(route('admin.marks.index', [
            'academic_year_id' => $year->id, 'term_id' => $otherTerm->id,
        ]))->assertOk()->assertViewHas('selectedTermId', $otherTerm->id);
    }

    public function test_admin_can_enter_marks_for_any_teachers_assignment(): void
    {
        [, $year, $term, $class, $subject, $teachers, $students] = $this->monitoringScenario();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $context = [
            'academic_year_id' => $year->id, 'term_id' => $term->id,
            'class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teachers[0]->id,
        ];

        $this->actingAs($admin)->get(route('admin.marks.group.edit', $context))
            ->assertOk()->assertSee('Learner One')->assertSee('Learner Two')->assertDontSee('Learner Three');

        $this->put(route('admin.marks.group.update'), $context + [
            'marks' => [
                $students[0]->id => ['midterm' => 72, 'endterm' => 81, 'remarks' => 'Improving'],
                $students[1]->id => ['midterm' => 65, 'endterm' => 74, 'remarks' => 'Good effort'],
            ],
        ])->assertRedirect(route('admin.marks.group.edit', $context))->assertSessionHas('success');

        $this->assertDatabaseHas('marks', [
            'student_id' => $students[0]->id, 'teacher_id' => $teachers[0]->id,
            'term_id' => $term->id, 'midterm_score' => 72, 'endterm_score' => 81,
        ]);
    }

    private function monitoringScenario(): array
    {
        $headmaster = User::factory()->create(['role' => 'headmaster', 'status' => 'active']);
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-31',
            'status' => 'active',
        ]);
        $class = ClassModel::create(['name' => 'Form 2A', 'level' => 2, 'academic_year_id' => $year->id]);
        $subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH-2',
            'is_core' => true,
            'is_active' => true,
        ]);
        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'max_marks' => 100,
            'passing_marks' => 40,
        ]);

        $teachers = collect(['Teacher One', 'Teacher Two'])->map(function (string $name) use ($year, $class, $subject) {
            $user = User::factory()->create(['name' => $name, 'role' => 'teacher', 'status' => 'active']);
            $teacher = Teacher::create(['user_id' => $user->id]);
            TeacherSubject::create([
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'academic_year_id' => $year->id,
            ]);

            return $teacher;
        })->values();

        $students = collect(['Learner One', 'Learner Two', 'Learner Three'])->map(function (string $name, int $index) use ($year, $class, $subject, $teachers) {
            $user = User::factory()->create(['name' => $name, 'role' => 'student', 'status' => 'active']);
            $student = Student::create([
                'user_id' => $user->id,
                'admission_no' => 'ADM-'.($index + 1),
                'gender' => $index % 2 === 0 ? 'female' : 'male',
                'date_of_birth' => '2012-01-01',
                'current_class_id' => $class->id,
            ]);
            StudentClassHistory::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_current' => true,
            ]);
            StudentSubject::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'teacher_id' => $index < 2 ? $teachers[0]->id : $teachers[1]->id,
                'class_id' => $class->id,
                'academic_year_id' => $year->id,
            ]);

            return $student;
        })->values();

        // A former learner remains in student_subjects but is no longer on the
        // active class register, so their missing mark must not reduce progress.
        $formerUser = User::factory()->create(['name' => 'Former Learner', 'role' => 'student', 'status' => 'inactive']);
        $formerStudent = Student::create([
            'user_id' => $formerUser->id,
            'admission_no' => 'ADM-FORMER',
            'gender' => 'female',
            'date_of_birth' => '2012-01-01',
            'current_class_id' => null,
        ]);
        StudentClassHistory::create([
            'student_id' => $formerStudent->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_current' => false,
        ])->forceFill(['status' => 'transferred', 'exited_at' => now()])->save();
        StudentSubject::create([
            'student_id' => $formerStudent->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teachers[0]->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);

        return [$headmaster, $year, $term, $class, $subject, $teachers, $students];
    }
}
