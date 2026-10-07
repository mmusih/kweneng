<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_assignment_links_are_visible_and_their_pages_open(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));

        foreach (['admin.dashboard', 'admin.subjects.index', 'admin.classes.index', 'admin.teachers.index'] as $page) {
            $this->get(route($page))->assertOk()
                ->assertSee(route('admin.subjects.manage-classes'), false)
                ->assertSee(route('admin.subjects.manage-teachers'), false);
        }

        $this->get(route('admin.subjects.manage-classes'))->assertOk()->assertSee('Assign Subjects to Classes');
        $this->get(route('admin.subjects.manage-teachers'))->assertOk()->assertSee('Teaching Assignments');
        \App\Models\AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        $this->get(route('admin.timetable.index'))->assertOk()->assertDontSee('Legacy timetable');
    }

    public function test_registered_controller_routes_have_callable_actions(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }
            [$controller, $method] = explode('@', $action);
            $this->assertTrue(method_exists($controller, $method), $route->uri().' points to '.$action);
        }
    }

    public function test_named_links_in_views_resolve(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            preg_match_all('/\broute\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);
            foreach ($matches[1] as $name) {
                // The unused Laravel welcome screen guards optional registration.
                if ($name === 'register' && str_contains($source, "Route::has('register')")) {
                    continue;
                }
                $this->assertTrue(Route::has($name), $file->getPathname().' links to '.$name);
            }
        }
    }
}
