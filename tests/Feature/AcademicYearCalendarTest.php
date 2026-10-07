<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    public function test_admin_can_create_and_update_a_single_calendar_year(): void
    {
        $this->post(route('admin.academic-years.store'), ['year_name' => '2026', 'active' => true])
            ->assertRedirect(route('admin.academic-years.index'))
            ->assertSessionHasNoErrors();

        $year = AcademicYear::where('year_name', '2026')->firstOrFail();
        $this->put(route('admin.academic-years.update', $year), ['year_name' => '2027', 'active' => true])
            ->assertRedirect(route('admin.academic-years.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('2027', $year->fresh()->year_name);
    }

    public function test_year_ranges_and_invalid_labels_are_rejected_on_create_and_update(): void
    {
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);

        foreach (['2026/2027', '2026-2027', 'School 2026', '26', '20260'] as $name) {
            $this->post(route('admin.academic-years.store'), ['year_name' => $name, 'active' => true])
                ->assertSessionHasErrors('year_name');
            $this->put(route('admin.academic-years.update', $year), ['year_name' => $name, 'active' => true])
                ->assertSessionHasErrors('year_name');
        }

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertSame('2026', $year->fresh()->year_name);
        $this->assertTrue($year->fresh()->active);
    }

    public function test_duplicate_calendar_years_are_rejected_but_unchanged_year_can_be_saved(): void
    {
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        AcademicYear::create(['year_name' => '2027', 'active' => false, 'status' => 'closed']);

        $this->post(route('admin.academic-years.store'), ['year_name' => '2026'])
            ->assertSessionHasErrors('year_name');
        $this->put(route('admin.academic-years.update', $year), ['year_name' => '2027'])
            ->assertSessionHasErrors('year_name');
        $this->put(route('admin.academic-years.update', $year), ['year_name' => '2026', 'active' => true])
            ->assertSessionHasNoErrors();
    }
}
