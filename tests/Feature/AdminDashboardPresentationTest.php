<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_keeps_core_links_and_accessible_navigation_visible(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee(route('admin.timetable.index', absolute: false))
            ->assertSee(route('admin.schemes.index', absolute: false))
            ->assertSee(route('admin.parents.index', absolute: false))
            ->assertSee(route('admin.librarians.index', absolute: false))
            ->assertSee(route('admin.accounts-officers.index', absolute: false))
            ->assertSee(route('admin.student-subjects.index', absolute: false))
            ->assertSee(route('admin.absence-notices.index', absolute: false))
            ->assertSee(route('admin.activity-logs.index', absolute: false))
            ->assertSee(route('admin.events.create', absolute: false))
            ->assertSee('role="tablist"', escape: false)
            ->assertSee('aria-controls="mobile-menu"', escape: false)
            ->assertSee('data-theme-toggle', escape: false)
            ->assertSee('adminDashboard(', escape: false);
    }

    public function test_admin_dashboard_keeps_every_legacy_destination(): void
    {
        $source = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

        $legacyRouteNames = [
            'admin.absence-notices.index',
            'admin.academic-years.index',
            'admin.accounts-officers.index',
            'admin.activity-logs.index',
            'admin.alumni.interests',
            'admin.alumni.process-interest',
            'admin.announcements.index',
            'admin.classes.create',
            'admin.classes.edit',
            'admin.classes.index',
            'admin.documents.index',
            'admin.events.calendar',
            'admin.events.create',
            'admin.events.index',
            'admin.exam-summaries.index',
            'admin.librarians.create',
            'admin.librarians.index',
            'admin.marks.index',
            'admin.messages.index',
            'admin.parents.index',
            'admin.promotions.index',
            'admin.reports.index',
            'admin.student-subjects.index',
            'admin.students.create',
            'admin.students.index',
            'admin.subjects.index',
            'admin.subjects.manage-classes',
            'admin.subjects.manage-teachers',
            'admin.teachers.index',
            'admin.terms.index',
            'admin.users.index',
            'inventory.dashboard',
            'inventory.requisitions.index',
            'office.dashboard',
            'register-officer.dashboard',
        ];

        foreach ($legacyRouteNames as $routeName) {
            $this->assertMatchesRegularExpression(
                "/route\\(\\s*['\"]".preg_quote($routeName, '/')."['\"]/",
                $source,
                "The admin dashboard no longer links to the legacy destination [{$routeName}].",
            );
        }
    }
}
