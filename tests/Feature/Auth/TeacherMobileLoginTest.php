<?php

namespace Tests\Feature\Auth;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherMobileLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_mobile_login_repairs_a_missing_teacher_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy.teacher@example.com',
            'password' => 'teacher-password',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->assertNull($user->teacher);

        $response = $this->postJson('/api/auth/teacher-login', [
            'email' => 'legacy.teacher@example.com',
            'password' => 'teacher-password',
            'device_name' => 'teacher-test-device',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonStructure(['token', 'user' => ['teacher_id']]);

        $this->assertDatabaseHas('teachers', ['user_id' => $user->id]);
    }

    public function test_headmaster_can_use_teacher_mobile_login(): void
    {
        $user = User::factory()->create([
            'email' => 'headmaster@example.com',
            'password' => 'headmaster-password',
            'role' => 'headmaster',
            'status' => 'active',
        ]);

        Teacher::create(['user_id' => $user->id]);

        $this->postJson('/api/auth/teacher-login', [
            'email' => 'headmaster@example.com',
            'password' => 'headmaster-password',
            'device_name' => 'headmaster-test-device',
        ])->assertOk()
            ->assertJsonPath('user.role', 'headmaster');
    }

    public function test_inactive_teacher_receives_a_clear_forbidden_message(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive.teacher@example.com',
            'password' => 'teacher-password',
            'role' => 'teacher',
            'status' => 'inactive',
        ]);

        $this->postJson('/api/auth/teacher-login', [
            'email' => $user->email,
            'password' => 'teacher-password',
            'device_name' => 'teacher-test-device',
        ])->assertForbidden()
            ->assertExactJson([
                'message' => 'Your account is inactive. Please contact the school.',
            ]);
    }

    public function test_non_teacher_account_receives_a_clear_forbidden_message(): void
    {
        $user = User::factory()->create([
            'email' => 'parent@example.com',
            'password' => 'parent-password',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->postJson('/api/auth/teacher-login', [
            'email' => $user->email,
            'password' => 'parent-password',
            'device_name' => 'parent-test-device',
        ])->assertForbidden()
            ->assertExactJson([
                'message' => 'This app is available only to teacher and headmaster accounts.',
            ]);
    }
}
