<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\SubjectOptionPlan;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectOptionPlanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'must_change_password' => false]);
    }

    private function input(): array
    {
        $setting = Setting::factory()->create();
        $class = ClassModel::create(['name' => 'Selected class', 'level' => 10, 'academic_year_id' => $setting->academic_year_id]);
        $room = Room::factory()->create(['tt_setting_id' => $setting->id, 'capacity' => 30]);
        $teacher = Teacher::create(['user_id' => $this->user('teacher')->id]);
        $subjects = [];
        foreach (['Biology', 'Chemistry', 'English'] as $index => $name) {
            $subject = Subject::create(['name' => $name, 'code' => 'PLAN'.$index, 'is_active' => true, 'is_core' => $index === 2]);
            TeacherSubject::create(['subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'class_id' => $class->id, 'academic_year_id' => $setting->academic_year_id]);
            $subjects[] = ['subject_id' => $subject->id, 'use' => $index === 2 ? 'core' : 'option', 'groups' => 1, 'demand' => 20, 'room_ids' => [$room->id]];
        }

        return ['name' => 'IGCSE plan', 'tt_setting_id' => $setting->id, 'class_ids' => [$class->id], 'block_count' => 2, 'subjects' => $subjects];
    }

    public function test_admin_selects_classes_generates_edits_and_accepts_without_changing_timetable(): void
    {
        $input = $this->input();
        $this->actingAs($this->user('admin'));
        $this->get(route('admin.subject-option-plans.index'))->assertOk()->assertSee('Selected class');
        $this->post(route('admin.subject-option-plans.generate'), $input)->assertSessionHasNoErrors()->assertRedirect();
        $plan = SubjectOptionPlan::firstOrFail();
        $this->assertCount(2, $plan->arrangement);
        $this->assertCount(1, $plan->configuration['compulsory_ids']);
        $this->assertSame(0, $plan->assessment['hard_conflicts']);
        $this->get(route('admin.subject-option-plans.show', $plan))->assertOk()->assertSee('Accept arrangement');
        $this->get(route('admin.subject-option-plans.index', ['from' => $plan->id]))->assertOk()
            ->assertViewHas('defaults', fn ($defaults) => $defaults['class_ids'] === $input['class_ids']);
        $this->put(route('admin.subject-option-plans.update', $plan), ['arrangement' => $plan->arrangement, 'action' => 'accept', 'acknowledge' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('accepted', $plan->fresh()->status);
        $this->assertDatabaseCount('tt_cards', 0);
        $this->assertDatabaseCount('student_subjects', 0);
        $this->put(route('admin.subject-option-plans.update', $plan), ['arrangement' => $plan->arrangement, 'action' => 'save'])->assertSessionHasNoErrors();
        $this->assertSame('draft', $plan->fresh()->status);
    }

    public function test_acceptance_rechecks_resources_and_rejects_missing_or_forged_groups(): void
    {
        $input = $this->input();
        $this->actingAs($this->user('admin'))->post(route('admin.subject-option-plans.generate'), $input)->assertSessionHasNoErrors();
        $plan = SubjectOptionPlan::firstOrFail();
        Room::query()->update(['capacity' => 1]);
        $this->put(route('admin.subject-option-plans.update', $plan), ['arrangement' => $plan->arrangement, 'action' => 'accept', 'acknowledge' => 1])->assertSessionHasErrors('arrangement');
        $this->assertSame('draft', $plan->fresh()->status);
        $this->put(route('admin.subject-option-plans.update', $plan), ['arrangement' => ['fake' => 0], 'action' => 'save'])->assertSessionHasErrors('arrangement');
    }

    public function test_only_active_admins_can_access_planner_and_cross_year_resources_are_rejected(): void
    {
        $input = $this->input();
        foreach (['hr', 'accounts_officer', 'teacher', 'headmaster'] as $role) {
            $this->actingAs($this->user($role))->get(route('admin.subject-option-plans.index'))->assertForbidden();
            $this->post(route('admin.subject-option-plans.generate'), $input)->assertForbidden();
        }
        $this->actingAs($this->user('admin'));
        $input['subjects'][0]['room_ids'] = [Room::factory()->create(['tt_setting_id' => Setting::factory()->create()->id])->id];
        $this->post(route('admin.subject-option-plans.generate'), $input)->assertSessionHasErrors('subjects');
        $this->assertDatabaseCount('subject_option_plans', 0);
    }

    public function test_hr_role_login_and_admin_account_creation_and_navigation(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'HR Officer', 'email' => 'hr@example.test', 'role' => 'hr', 'status' => 'active', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasNoErrors();
        $hr = User::where('email', 'hr@example.test')->firstOrFail();
        $this->assertTrue($hr->canManageHr());
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('href="'.route('hr.dashboard').'"', false)->assertDontSee('href="'.route('finance.dashboard').'"', false)->assertSee(route('admin.subject-option-plans.index'));
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $hr->email, 'password' => 'password123'])->assertRedirect(route('hr.dashboard', false));
        $this->get(route('hr.dashboard'))->assertOk()->assertSee('HR Dashboard');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->put(route('password.update'), ['current_password' => 'password123', 'password' => 'replacement123', 'password_confirmation' => 'replacement123'])->assertRedirect(route('hr.dashboard', false));
        $hr->update(['status' => 'inactive']);
        $this->actingAs($hr)->get(route('hr.dashboard'))->assertForbidden();
    }
}
