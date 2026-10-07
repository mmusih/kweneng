<?php

namespace Tests\Feature;

use App\Models\PaymentCategory;
use App\Models\Requisition;
use App\Models\SchoolPayment;
use App\Models\SchoolPurchaseOrder;
use App\Models\SchoolSupplier;
use App\Models\StaffExpenseClaim;
use App\Models\StaffLeaveRequest;
use App\Models\StaffLeaveType;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentFinanceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'must_change_password' => false]);
    }

    private function staff(User $user): StaffProfile
    {
        return StaffProfile::create(['user_id' => $user->id, 'name' => $user->name, 'employee_number' => Str::random(8), 'position' => 'Teacher', 'citizenship' => 'Botswana', 'is_citizen' => true, 'is_teacher' => true, 'status' => 'active']);
    }

    private function leave(StaffProfile $staff, string $status = 'submitted'): StaffLeaveRequest
    {
        return StaffLeaveRequest::create(['submission_key' => Str::uuid(), 'staff_profile_id' => $staff->id, 'staff_leave_type_id' => StaffLeaveType::first()->id, 'submitted_by' => $staff->user_id, 'starts_on' => today(), 'ends_on' => today(), 'half_days' => 2, 'uses_allowance' => true, 'reason' => 'Family appointment', 'status' => $status]);
    }

    private function expense(StaffProfile $staff, string $status = 'submitted', string $description = 'Training materials'): StaffExpenseClaim
    {
        return StaffExpenseClaim::create(['submission_key' => Str::uuid(), 'staff_profile_id' => $staff->id, 'submitted_by' => $staff->user_id, 'description' => $description, 'reason' => 'School training', 'category' => 'training', 'spent_on' => today(), 'amount_minor' => 12500, 'document_path' => 'test.pdf', 'document_name' => 'test.pdf', 'status' => $status]);
    }

    public function test_dashboards_render_empty_states_and_keep_workspace_navigation(): void
    {
        $this->actingAs($this->user());
        $this->get(route('hr.dashboard'))->assertOk()->assertSee('HR Dashboard')->assertSee('No approved leave scheduled for today.')->assertSee(route('hr.leave.settings'));
        $this->get(route('finance.dashboard'))->assertOk()->assertSee('Finance Dashboard')->assertSee('No payments recorded yet.')->assertSee(route('finance.purchasing.index'));
        $this->get(route('staff.dashboard'))->assertOk()->assertSee('My workspace')->assertSee('Ask HR to link an active staff profile');
        $this->get(route('hr.staff.index'))->assertOk()->assertSee('Workspace sections')->assertSee(route('hr.dashboard'));
    }

    public function test_dashboard_permissions_match_existing_modules(): void
    {
        $teacher = $this->user('teacher');
        $this->actingAs($teacher)->get(route('hr.dashboard'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('staff.dashboard'))->assertOk()->assertDontSee('href="'.route('finance.dashboard').'"', false);
        $teacher->forceFill(['hr_access' => true])->save();
        $this->get(route('hr.dashboard'))->assertOk();
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->actingAs($this->user('accounts_officer'))->get(route('finance.dashboard'))->assertRedirect(route('accounts-officer.dashboard'));
        $this->get(route('accounts-officer.dashboard'))->assertOk()->assertSee('Finance work queue')->assertSee('Fees Management');
        $this->get(route('hr.dashboard'))->assertForbidden();
        $this->actingAs($this->user('headmaster'))->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('finance.expenses.index'))->assertOk();
        $this->actingAs($this->user('parent'))->get(route('staff.dashboard'))->assertForbidden();
        $inactive = $this->user();
        $inactive->update(['status' => 'inactive']);
        $this->actingAs($inactive)->get(route('hr.dashboard'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
    }

    public function test_hr_summary_counts_distinct_people_and_links_to_reviewable_requests(): void
    {
        $staff = $this->staff($this->user('teacher'));
        $pending = $this->leave($staff);
        $this->leave($staff, 'approved');
        $this->leave($staff, 'approved')->update(['portion' => 'pm', 'half_days' => 1]);
        $this->actingAs($this->user())->get(route('hr.dashboard'))->assertOk()
            ->assertViewHas('activeStaff', 1)->assertViewHas('pendingLeave', 1)->assertViewHas('awayCount', 1)
            ->assertSee(route('staff.leave.show', $pending));
        $this->get(route('staff.leave.show', $pending))->assertOk()->assertSee('HR review')->assertSee(route('hr.dashboard'));
    }

    public function test_personal_workspace_never_displays_another_employees_requests(): void
    {
        $own = $this->staff($this->user('teacher'));
        $other = $this->staff($this->user('teacher'));
        $this->leave($own);
        $this->expense($own, 'approved', 'My training claim');
        $foreign = $this->expense($other, 'submitted', 'Private other employee claim');
        $this->actingAs($own->user)->get(route('staff.dashboard'))->assertOk()->assertSee('My training claim')
            ->assertDontSee('Private other employee claim')->assertViewHas('pendingLeave', 1)->assertViewHas('pendingExpenses', 12500);
        $this->get(route('staff.expenses.show', $foreign))->assertForbidden();
    }

    public function test_finance_totals_exclude_unconfirmed_reversed_and_pre_ledger_payments(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $admin = $this->user();
        $student = Student::create(['user_id' => $this->user('student')->id, 'admission_no' => 'LEDGER-1', 'gender' => 'male', 'date_of_birth' => '2010-01-01']);
        $account = StudentFinanceAccount::create(['student_id' => $student->id, 'opened_on' => '2026-10-01', 'opening_balance_minor' => 100000, 'created_by' => $admin->id]);
        $account->charges()->create(['submission_key' => Str::uuid(), 'charged_on' => today(), 'description' => 'Term charge', 'amount_minor' => 20000, 'created_by' => $admin->id]);
        foreach ([['confirmed', '2026-10-06', 30000], ['pending', '2026-10-06', 5000], ['reversed', '2026-10-06', 8000], ['confirmed', '2026-09-30', 9000]] as [$status, $date, $amount]) {
            $payment = SchoolPayment::create(['submission_key' => Str::uuid(), 'payer_name' => 'Test payer', 'paid_on' => $date, 'method' => 'cash', 'amount_minor' => $amount, 'status' => $status, 'created_by' => $admin->id]);
            $payment->items()->create(['student_id' => $student->id, 'payment_category_id' => PaymentCategory::first()->id, 'kind' => 'fees', 'category_name' => 'School fees', 'description' => 'Fees', 'amount_minor' => $amount]);
        }
        $creditStudent = Student::create(['user_id' => $this->user('student')->id, 'admission_no' => 'CREDIT-2', 'gender' => 'male', 'date_of_birth' => '2010-01-01']);
        StudentFinanceAccount::create(['student_id' => $creditStudent->id, 'opened_on' => today(), 'opening_balance_minor' => -50000, 'created_by' => $admin->id]);
        $staff = $this->staff($this->user('teacher'));
        $this->expense($staff, 'approved');
        $this->expense($staff, 'paid')->update(['paid_on' => today()]);
        $this->actingAs($admin)->get(route('finance.dashboard'))->assertOk()
            ->assertViewHas('collected', 30000)->assertViewHas('outstandingFees', 90000)
            ->assertViewHas('approvedExpenses', 12500)->assertViewHas('outgoing', 12500)
            ->assertViewHas('pendingPayments', 1)->assertSee('P900.00');
    }

    public function test_queue_filters_work_and_personal_filters_preserve_ownership(): void
    {
        $staff = $this->staff($this->user('teacher'));
        $submitted = $this->leave($staff);
        $this->leave($staff, 'approved');
        $this->expense($staff, 'approved', 'Ready to pay');
        $this->expense($staff, 'submitted', 'Awaiting decision');
        $this->actingAs($this->user())->get(route('hr.leave.index', ['status' => 'submitted']))->assertOk()
            ->assertViewHas('requests', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $submitted->id);
        $this->get(route('finance.expenses.index', ['status' => 'approved']))->assertOk()->assertSee('Ready to pay')->assertDontSee('Awaiting decision');
        $this->get(route('finance.purchasing.index', ['status' => 'submitted']))->assertOk();
        foreach (['hr.leave.index', 'finance.expenses.index', 'finance.purchasing.index'] as $route) {
            $this->getJson(route($route, ['status' => 'invalid']))->assertUnprocessable();
        }
        $other = $this->staff($this->user('teacher'));
        $this->expense($other, 'approved', 'Another staff claim');
        $this->actingAs($staff->user)->get(route('staff.expenses.index', ['status' => 'approved']))->assertOk()->assertSee('Ready to pay')->assertDontSee('Another staff claim');
    }

    public function test_supplier_commitments_and_monthly_outgoings_account_for_partial_payments(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $admin = $this->user();
        $supplier = SchoolSupplier::create(['name' => 'School supplies', 'active' => true]);
        foreach (['approved', 'received', 'submitted', 'rejected', 'cancelled', 'paid'] as $status) {
            $requisition = Requisition::create(['requested_by' => $admin->id, 'title' => 'Supplies', 'status' => 'approved']);
            $order = SchoolPurchaseOrder::create(['submission_key' => Str::uuid(), 'requisition_id' => $requisition->id, 'school_supplier_id' => $supplier->id, 'supplier_name' => $supplier->name, 'title' => 'Supplies '.$status, 'total_minor' => 100000, 'status' => $status, 'created_by' => $admin->id]);
            if ($status === 'received') {
                foreach ([['2026-09-30', 20000], ['2026-10-06', 30000]] as [$date, $amount]) {
                    $order->payments()->create(['submission_key' => Str::uuid(), 'paid_on' => $date, 'amount_minor' => $amount, 'method' => 'cash', 'reference' => $date, 'recorded_by' => $admin->id]);
                }
            }
        }
        $this->actingAs($admin)->get(route('finance.dashboard'))->assertOk()
            ->assertViewHas('supplierOutstanding', 150000)->assertViewHas('outgoing', 30000)->assertViewHas('pendingOrders', 1);
        $this->get(route('finance.purchasing.index', ['status' => 'received']))->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->first()->status === 'received');
    }
}
