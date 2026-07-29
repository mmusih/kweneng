<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentFeeBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsOfficerResultsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_officer_dashboard_exposes_bulk_controls_summaries_and_reports(): void
    {
        $officer = $this->accountsOfficer();

        $this->actingAs($officer)
            ->get(route('accounts-officer.dashboard'))
            ->assertOk()
            ->assertSee('Block above amount')
            ->assertSee('Block all')
            ->assertSee('Unblock all')
            ->assertSee(route('accounts-officer.exam-summaries.index', absolute: false))
            ->assertSee(route('accounts-officer.reports.index', absolute: false));

        $this->actingAs($officer)
            ->get(route('accounts-officer.exam-summaries.index'))
            ->assertOk()
            ->assertSee('Exam Summary Sheets')
            ->assertSee('Back to Dashboard')
            ->assertSee(route('accounts-officer.dashboard', absolute: false));

        $this->actingAs($officer)
            ->get(route('accounts-officer.reports.index'))
            ->assertOk()
            ->assertSee('Report Cards')
            ->assertSee('Back to Dashboard')
            ->assertSee(route('accounts-officer.dashboard', absolute: false));
    }

    public function test_accounts_officer_student_and_fee_pages_link_back_to_dashboard(): void
    {
        $officer = $this->accountsOfficer();

        foreach ([
            'accounts-officer.students.index',
            'accounts-officer.fees.index',
            'accounts-officer.fees.import',
        ] as $routeName) {
            $this->actingAs($officer)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('Back to Dashboard')
                ->assertSee(route('accounts-officer.dashboard', absolute: false));
        }
    }

    public function test_every_dedicated_accounts_officer_subpage_uses_dashboard_navigation(): void
    {
        $subpages = [
            'accounts-officer/students/index.blade.php',
            'accounts-officer/fees/index.blade.php',
            'accounts-officer/fees/import.blade.php',
            'accounts-officer/fees/preview.blade.php',
            'accounts-officer/fees/show.blade.php',
        ];

        foreach ($subpages as $subpage) {
            $source = file_get_contents(resource_path("views/{$subpage}"));

            $this->assertStringContainsString(
                '<x-accounts-officer-dashboard-link',
                $source,
                "The accounts-officer page [{$subpage}] is missing backward navigation.",
            );
        }
    }

    public function test_threshold_blocking_uses_only_each_students_latest_balance(): void
    {
        $officer = $this->accountsOfficer();
        $aboveThreshold = $this->student('ADM-THRESHOLD-HIGH');
        $equalToThreshold = $this->student('ADM-THRESHOLD-EQUAL');
        $paidDown = $this->student('ADM-THRESHOLD-PAID');
        $withoutBalance = $this->student('ADM-THRESHOLD-NONE');

        $this->balance($aboveThreshold, 1500, $officer);
        $this->balance($equalToThreshold, 1000, $officer);
        $this->balance($paidDown, 2500, $officer);
        $this->balance($paidDown, 200, $officer);

        $this->actingAs($officer)
            ->post(route('accounts-officer.students.bulk-fees-block'), [
                'action' => 'block_above_threshold',
                'threshold' => 1000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($aboveThreshold->fresh()->fees_blocked);
        $this->assertFalse($equalToThreshold->fresh()->fees_blocked);
        $this->assertFalse($paidDown->fresh()->fees_blocked);
        $this->assertFalse($withoutBalance->fresh()->fees_blocked);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $officer->id,
            'action' => 'results.bulk-access-updated',
        ]);
    }

    public function test_accounts_officer_can_block_and_unblock_all_students_in_a_selected_scope(): void
    {
        $officer = $this->accountsOfficer();
        $selectedClass = ClassModel::create(['name' => 'Form 4A', 'level' => 4]);
        $otherClass = ClassModel::create(['name' => 'Form 4B', 'level' => 4]);
        $selectedStudent = $this->student('ADM-SCOPE-A', $selectedClass);
        $otherStudent = $this->student('ADM-SCOPE-B', $otherClass);

        $this->actingAs($officer)
            ->post(route('accounts-officer.students.bulk-fees-block'), [
                'action' => 'block_all',
                'class_id' => $selectedClass->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($selectedStudent->fresh()->fees_blocked);
        $this->assertFalse($otherStudent->fresh()->fees_blocked);

        $this->actingAs($officer)
            ->post(route('accounts-officer.students.bulk-fees-block'), [
                'action' => 'unblock_all',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($selectedStudent->fresh()->fees_blocked);
        $this->assertFalse($otherStudent->fresh()->fees_blocked);
    }

    public function test_non_accounts_staff_cannot_use_accounts_results_tools(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->post(route('accounts-officer.students.bulk-fees-block'), [
                'action' => 'block_all',
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('accounts-officer.reports.index'))
            ->assertForbidden();
    }

    private function accountsOfficer(): User
    {
        return User::factory()->create([
            'role' => 'accounts_officer',
            'status' => 'active',
        ]);
    }

    private function student(string $admissionNo, ?ClassModel $class = null): Student
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'admission_no' => $admissionNo,
            'gender' => 'female',
            'date_of_birth' => '2012-01-01',
            'current_class_id' => $class?->id,
        ]);
    }

    private function balance(Student $student, float $amount, User $officer): StudentFeeBalance
    {
        return StudentFeeBalance::create([
            'student_id' => $student->id,
            'closing_balance' => $amount,
            'updated_by' => $officer->id,
        ]);
    }
}
