<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_shows_schemes_entry_point(): void
    {
        $user = $this->teacherUser();

        $this->actingAs($user)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Schemes of Work')
            ->assertSee(route('teacher.schemes.index', absolute: false));
    }

    public function test_teacher_can_open_scheme_creation_with_the_syllabus_bank(): void
    {
        $user = $this->teacherUser();

        $this->actingAs($user)
            ->get(route('teacher.schemes.create'))
            ->assertOk()
            ->assertSee('Create Scheme of Work');
    }

    public function test_hod_sees_direct_scheme_review_entry_point(): void
    {
        $user = $this->teacherUser();
        $department = Department::create([
            'name' => 'Mathematics',
            'code' => 'MATH',
        ]);

        DepartmentUser::create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'academic_year_id' => null,
            'role_in_department' => DepartmentUser::ROLE_HOD,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('HOD Scheme Review')
            ->assertSee(route('teacher.hod.schemes.dashboard', absolute: false));

        $this->actingAs($user)
            ->get(route('teacher.hod.schemes.dashboard'))
            ->assertOk();
    }

    public function test_headmaster_dashboard_shows_school_wide_scheme_oversight(): void
    {
        $headmaster = User::factory()->create([
            'role' => 'headmaster',
            'status' => 'active',
        ]);

        $this->actingAs($headmaster)
            ->get(route('headmaster.dashboard'))
            ->assertOk()
            ->assertSee('Schemes Oversight')
            ->assertSee(route('teacher.hod.schemes.dashboard', absolute: false));
    }

    public function test_admin_dashboard_keeps_scheme_oversight_visible(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.schemes.index', absolute: false));
    }

    public function test_teacher_mobile_api_exposes_schemes_only_to_teaching_staff(): void
    {
        $teacher = $this->teacherUser();

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/teacher/schemes')
            ->assertOk()
            ->assertExactJson([]);

        $parent = User::factory()->create([
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/teacher/schemes')
            ->assertForbidden();
    }

    private function teacherUser(): User
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        Teacher::create(['user_id' => $user->id]);

        return $user;
    }
}
