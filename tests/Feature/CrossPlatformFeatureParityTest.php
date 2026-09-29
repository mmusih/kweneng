<?php

namespace Tests\Feature;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossPlatformFeatureParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_view_and_download_a_linked_child_academic_record_on_the_web(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $studentUser = User::factory()->create(['name' => 'Parity Student', 'role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'admission_no' => 'PARITY-01',
            'gender' => 'female',
            'date_of_birth' => '2012-01-01',
            'results_access' => true,
            'fees_blocked' => false,
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);

        $this->actingAs($parentUser)
            ->get(route('parent.children.academic-record.show', $student))
            ->assertOk()
            ->assertSee('Academic Record')
            ->assertSee('Parity Student');

        $this->actingAs($parentUser)
            ->get(route('parent.children.academic-record.download', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_parent_cannot_open_an_unlinked_students_academic_record(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        ParentModel::create(['user_id' => $parentUser->id]);
        $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'admission_no' => 'PRIVATE-01',
            'gender' => 'male',
            'date_of_birth' => '2012-01-01',
            'results_access' => true,
            'fees_blocked' => false,
        ]);

        $this->actingAs($parentUser)
            ->get(route('parent.children.academic-record.show', $student))
            ->assertForbidden();
    }

    public function test_teacher_can_download_the_same_teaching_load_from_the_mobile_api(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        Teacher::create(['user_id' => $teacherUser->id]);

        $this->actingAs($teacherUser, 'sanctum')
            ->get('/api/teacher/teaching-load/download')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
