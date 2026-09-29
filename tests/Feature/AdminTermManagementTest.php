<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTermManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_term_management_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.terms.index'))
            ->assertOk()
            ->assertSee('Manage Terms');

        $this->actingAs($admin)
            ->get(route('admin.terms.create'))
            ->assertOk()
            ->assertSee('Create Term');
    }

    public function test_admin_can_create_activate_finalize_and_lock_a_term(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $academicYear = AcademicYear::create([
            'year_name' => '2026',
            'active' => true,
            'status' => AcademicYear::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.terms.store'), [
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 1',
                'start_date' => '2026-01-12',
                'end_date' => '2026-04-10',
                'status' => Term::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.terms.index'))
            ->assertSessionHas('success');

        $term = Term::where('name', 'Term 1')->firstOrFail();
        $this->assertSame(Term::STATUS_ACTIVE, $term->status);

        $this->post(route('admin.terms.finalize', $term))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(Term::STATUS_FINALIZED, $term->fresh()->status);

        $this->post(route('admin.terms.activate', $term))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(Term::STATUS_ACTIVE, $term->fresh()->status);

        $this->post(route('admin.terms.lock', $term))
            ->assertRedirect()
            ->assertSessionHas('success');
        $term->refresh();
        $this->assertSame(Term::STATUS_LOCKED, $term->status);
        $this->assertTrue($term->locked);

        $this->post(route('admin.terms.unlock', $term))
            ->assertRedirect()
            ->assertSessionHas('success');
        $term->refresh();
        $this->assertSame(Term::STATUS_FINALIZED, $term->status);
        $this->assertFalse($term->locked);
    }
}
