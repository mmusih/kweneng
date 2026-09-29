<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBackNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_screen_gets_shared_backward_navigation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-back-navigation', escape: false)
            ->assertSee('Go back to the previous page')
            ->assertSee('window.history.back()', escape: false)
            ->assertSee('href="'.route('admin.dashboard').'"', escape: false);
    }

    public function test_dashboard_itself_does_not_show_redundant_back_navigation(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-back-navigation', escape: false);
    }

    public function test_headmaster_using_teacher_tools_returns_to_headmaster_dashboard(): void
    {
        $headmaster = User::factory()->create([
            'role' => 'headmaster',
            'status' => 'active',
        ]);
        Teacher::create(['user_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('data-back-navigation', escape: false)
            ->assertSee('window.history.back()', escape: false)
            ->assertSee('href="'.route('headmaster.dashboard').'"', escape: false)
            ->assertDontSee('href="'.route('teacher.dashboard').'"', escape: false);
    }
}
