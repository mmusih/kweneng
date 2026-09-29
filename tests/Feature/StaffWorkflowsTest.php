<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\SchoolPurchaseOrder;
use App\Models\SchoolSupplier;
use App\Models\StaffExpenseClaim;
use App\Models\StaffLeaveAllowance;
use App\Models\StaffLeaveHoliday;
use App\Models\StaffLeaveRequest;
use App\Models\StaffLeaveType;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 21)->setTime(10, 0));
        Storage::fake('local');
    }

    private function user(string $role = 'teacher'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'must_change_password' => false]);
    }

    private function staff(?User $user = null): StaffProfile
    {
        $user ??= $this->user();

        return StaffProfile::create(['user_id' => $user->id, 'name' => $user->name, 'employee_number' => 'EMP-'.Str::random(8), 'position' => 'Teacher', 'citizenship' => 'Botswana', 'is_citizen' => true, 'is_teacher' => true, 'status' => 'active']);
    }

    private function leaveData(array $overrides = []): array
    {
        return array_replace(['submission_key' => (string) Str::uuid(), 'staff_leave_type_id' => StaffLeaveType::where('name', 'Annual leave')->first()->id, 'starts_on' => '2026-09-21', 'ends_on' => '2026-09-25', 'portion' => 'full', 'reason' => 'Family arrangements'], $overrides);
    }

    private function leaveSetup(int $halfDays = 40): StaffProfile
    {
        $staff = $this->staff();
        $type = StaffLeaveType::where('name', 'Annual leave')->first();
        $type->update(['active' => true]);
        StaffLeaveAllowance::create(['staff_profile_id' => $staff->id, 'staff_leave_type_id' => $type->id, 'year' => 2026, 'half_days' => $halfDays]);
        $this->actingAs($staff->user);

        return $staff;
    }

    private function expenseData(array $overrides = []): array
    {
        return array_replace(['submission_key' => (string) Str::uuid(), 'description' => 'Classroom books', 'reason' => 'Approved classroom materials', 'category' => 'classroom', 'spent_on' => '2026-09-20', 'amount' => '123.45', 'document' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf')], $overrides);
    }

    private function expense(): StaffExpenseClaim
    {
        $staff = $this->staff();
        $this->actingAs($staff->user)->post('/staff/expenses', $this->expenseData())->assertSessionHasNoErrors();

        return StaffExpenseClaim::firstOrFail();
    }

    private function requisition(): Requisition
    {
        $req = Requisition::create(['requested_by' => $this->user()->id, 'title' => 'Classroom supplies', 'status' => 'approved']);
        $req->items()->create(['item_name' => 'Workbooks', 'quantity' => '2.50', 'unit' => 'packs', 'estimated_unit_cost' => '10.01']);

        return $req;
    }

    private function orderData(Requisition $req, array $overrides = []): array
    {
        $supplier = SchoolSupplier::firstOrCreate(['name' => 'Example School Supplies'], ['active' => true]);

        return array_replace(['submission_key' => (string) Str::uuid(), 'school_supplier_id' => $supplier->id, 'prices' => [$req->items->first()->id => '10.01'], 'additional_cost' => '5.00', 'additional_cost_description' => 'Delivery'], $overrides);
    }

    private function order(): SchoolPurchaseOrder
    {
        $req = $this->requisition();
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.store', $req), $this->orderData($req))->assertSessionHasNoErrors();

        return SchoolPurchaseOrder::firstOrFail();
    }

    private function approveOrder(SchoolPurchaseOrder $order): void
    {
        $this->actingAs($this->user('headmaster'))->post(route('finance.purchasing.review', $order), ['decision' => 'approved'])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function receiveOrder(SchoolPurchaseOrder $order): void
    {
        $this->actingAs($this->user('inventory'))->post(route('finance.purchasing.receive', $order), ['received_on' => '2026-09-21', 'delivery_note' => 'All packs checked', 'all_received' => 1])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function invoiceOrder(SchoolPurchaseOrder $order): void
    {
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.invoice', $order), ['invoice_reference' => 'INV-100', 'amount' => '30.03', 'document' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_seeded_leave_types_require_school_configuration(): void
    {
        $this->assertSame(0, StaffLeaveType::where('active', true)->count());
        $this->assertDatabaseCount('staff_leave_allowances', 0);
    }

    public function test_leave_counts_workdays_excludes_holidays_and_reserves_pending_balance(): void
    {
        $staff = $this->leaveSetup();
        StaffLeaveHoliday::create(['date' => '2026-09-22', 'name' => 'School closure']);
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasNoErrors()->assertRedirect();
        $leave = StaffLeaveRequest::firstOrFail();
        $this->assertSame(8, $leave->half_days);
        $allowance = StaffLeaveAllowance::first();
        $this->assertSame(8, $allowance->usedHalfDays(['submitted']));
        $this->assertSame(0, $allowance->usedHalfDays());
        $this->get('/staff/leave')->assertOk()->assertSee('My leave');
        $this->actingAs($this->user('admin'))->post(route('hr.leave.review', $leave), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame(8, $allowance->usedHalfDays());
    }

    public function test_leave_duplicate_and_overlap_are_handled_without_double_deduction(): void
    {
        $this->leaveSetup();
        $data = $this->leaveData();
        $this->post('/staff/leave', $data)->assertSessionHasNoErrors();
        $this->post('/staff/leave', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_leave_requests', 1);
        $this->post('/staff/leave', $this->leaveData(['starts_on' => '2026-09-24', 'ends_on' => '2026-09-28']))->assertSessionHasErrors('leave');
    }

    public function test_half_days_can_share_a_day_but_cannot_overlap_same_portion(): void
    {
        $this->leaveSetup();
        foreach (['am', 'pm'] as $portion) {
            $this->post('/staff/leave', $this->leaveData(['ends_on' => '2026-09-21', 'portion' => $portion]))->assertSessionHasNoErrors();
        }
        $this->assertSame(2, (int) StaffLeaveRequest::sum('half_days'));
        $this->post('/staff/leave', $this->leaveData(['ends_on' => '2026-09-21', 'portion' => 'am']))->assertSessionHasErrors('leave');
    }

    public function test_leave_rejects_cross_year_and_insufficient_allowance(): void
    {
        $this->leaveSetup(2);
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasErrors('leave');
        $this->post('/staff/leave', $this->leaveData(['starts_on' => '2026-12-31', 'ends_on' => '2027-01-02']))->assertSessionHasErrors('leave');
        $this->assertDatabaseCount('staff_leave_requests', 0);
    }

    public function test_pending_requests_reserve_allowance_against_later_nonoverlapping_requests(): void
    {
        $this->leaveSetup(10);
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasNoErrors();
        $this->post('/staff/leave', $this->leaveData(['starts_on' => '2026-09-28', 'ends_on' => '2026-09-28']))->assertSessionHasErrors('leave');
    }

    public function test_required_leave_evidence_is_private_and_hr_cannot_self_approve(): void
    {
        $staff = $this->leaveSetup();
        $staff->user->forceFill(['hr_access' => true])->save();
        StaffLeaveType::where('name', 'Annual leave')->update(['requires_document' => true]);
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasErrors('document');
        $this->post('/staff/leave', $this->leaveData(['document' => UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf')]))->assertSessionHasNoErrors();
        $leave = StaffLeaveRequest::firstOrFail();
        Storage::disk('local')->assertExists($leave->document_path);
        $this->actingAs($staff->user->fresh())->post(route('hr.leave.review', $leave), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($this->user('accounts_officer'))->get(route('staff.leave.document', $leave))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('staff.leave.document', $leave))->assertOk();
    }

    public function test_cancellation_releases_balance_and_approved_leave_requires_hr_cancellation(): void
    {
        $staff = $this->leaveSetup();
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasNoErrors();
        $leave = StaffLeaveRequest::firstOrFail();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('hr.leave.review', $leave), ['decision' => 'approved'])->assertRedirect();
        $this->actingAs($staff->user)->post(route('staff.leave.cancel', $leave), ['note' => 'Change of plans'])->assertStatus(422);
        $this->actingAs($admin)->post(route('staff.leave.cancel', $leave), ['note' => 'Authorised cancellation'])->assertRedirect();
        $this->assertSame(0, StaffLeaveAllowance::first()->usedHalfDays(['approved', 'submitted']));
    }

    public function test_leave_configuration_cannot_erase_reserved_entitlement_or_change_used_rules(): void
    {
        $staff = $this->leaveSetup();
        $this->post('/staff/leave', $this->leaveData())->assertSessionHasNoErrors();
        $type = StaffLeaveType::where('name', 'Annual leave')->first();
        $this->actingAs($this->user('admin'))->post(route('hr.leave.allowances'), ['staff_profile_id' => $staff->id, 'staff_leave_type_id' => $type->id, 'year' => 2026, 'days' => '1.0', 'notes' => 'Correction'])->assertSessionHasErrors('days');
        $this->put(route('hr.leave.types.update', $type), ['name' => $type->name, 'active' => 1, 'uses_allowance' => 0, 'requires_document' => 0, 'exclude_holidays' => 1, 'working_days' => [1, 2, 3, 4, 5]])->assertSessionHasErrors('type');
        $this->get(route('hr.leave.settings'))->assertOk();
        $this->get(route('hr.leave.index'))->assertOk();
    }

    public function test_expense_receipts_are_private_and_duplicate_submit_creates_one_claim(): void
    {
        $staff = $this->staff();
        $data = $this->expenseData();
        $this->actingAs($staff->user)->post('/staff/expenses', $data)->assertSessionHasNoErrors();
        $this->post('/staff/expenses', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_expense_claims', 1);
        $claim = StaffExpenseClaim::first();
        $this->assertSame(12345, $claim->amount_minor);
        $this->get('/staff/expenses')->assertOk();
        $this->get(route('staff.expenses.document', $claim))->assertOk();
        $this->actingAs($this->user())->get(route('staff.expenses.document', $claim))->assertForbidden();
        $this->actingAs($this->user('headmaster'))->get(route('staff.expenses.show', $claim))->assertOk();
    }

    public function test_expense_approval_and_payment_require_the_right_roles_and_order(): void
    {
        $claim = $this->expense();
        $accounts = $this->user('accounts_officer');
        $payment = ['paid_on' => '2026-09-21', 'payment_method' => 'bank_transfer', 'payment_reference' => 'EFT-100'];
        $this->actingAs($accounts)->post(route('finance.expenses.pay', $claim), $payment)->assertStatus(422);
        $this->post(route('finance.expenses.review', $claim), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($this->user('headmaster'))->post(route('finance.expenses.review', $claim), ['decision' => 'approved'])->assertRedirect();
        $this->actingAs($accounts)->post(route('finance.expenses.pay', $claim), $payment)->assertRedirect();
        $this->post(route('finance.expenses.pay', $claim), $payment)->assertRedirect();
        $this->assertSame('paid', $claim->fresh()->status);
        $this->assertSame(1, DB::table('erp_audit_events')->where('action', 'finance.expense_paid')->count());
        $this->get(route('finance.expenses.index'))->assertOk();
    }

    public function test_spending_approver_cannot_approve_their_own_expense(): void
    {
        $admin = $this->user('admin');
        $this->staff($admin);
        $this->actingAs($admin)->post('/staff/expenses', $this->expenseData())->assertSessionHasNoErrors();
        $this->post(route('finance.expenses.review', StaffExpenseClaim::first()), ['decision' => 'approved'])->assertForbidden();
    }

    public function test_purchase_snapshots_prices_and_supplier_and_repeated_submit_is_safe(): void
    {
        $req = $this->requisition();
        $data = $this->orderData($req);
        $this->actingAs($this->user('accounts_officer'));
        $this->post(route('finance.purchasing.store', $req), $data)->assertSessionHasNoErrors();
        $this->post(route('finance.purchasing.store', $req), $data)->assertSessionHasNoErrors();
        $order = SchoolPurchaseOrder::first();
        $this->assertDatabaseCount('school_purchase_orders', 1);
        $this->assertSame(3003, $order->total_minor);
        $order->supplier->update(['name' => 'Changed supplier name']);
        $this->assertSame('Example School Supplies', $order->fresh()->supplier_name);
        $this->get(route('finance.purchasing.pdf', $order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('finance.purchasing.index'))->assertOk();
        $this->get(route('finance.purchasing.show', $order))->assertOk();
        $this->get(route('finance.purchasing.suppliers'))->assertOk();
    }

    public function test_purchase_approval_updates_requisition_and_cannot_be_bypassed_in_inventory(): void
    {
        $order = $this->order();
        $this->actingAs(User::find($order->created_by))->post(route('finance.purchasing.review', $order), ['decision' => 'approved'])->assertForbidden();
        $this->approveOrder($order);
        $this->assertSame('ordered', $order->requisition->fresh()->status);
        $this->actingAs($this->user('inventory'))->patch(route('inventory.requisitions.update', $order->requisition_id), ['status' => 'fulfilled'])->assertStatus(422);
        $this->receiveOrder($order);
        $this->assertSame('fulfilled', $order->requisition->fresh()->status);
        $this->assertSame('received', $order->fresh()->status);
    }

    public function test_supplier_payments_require_matching_invoice_and_delivery_and_cannot_overpay(): void
    {
        $order = $this->order();
        $this->approveOrder($order);
        $accounts = $this->user('accounts_officer');
        $payment = ['submission_key' => (string) Str::uuid(), 'paid_on' => '2026-09-21', 'amount' => '10.00', 'method' => 'bank_transfer', 'reference' => 'BANK-1'];
        $this->actingAs($accounts)->post(route('finance.purchasing.pay', $order), $payment)->assertStatus(422);
        $this->post(route('finance.purchasing.invoice', $order), ['invoice_reference' => 'INV-100', 'amount' => '30.00', 'document' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf')])->assertSessionHasErrors('amount');
        $this->receiveOrder($order);
        $this->invoiceOrder($order);
        $this->actingAs($accounts)->post(route('finance.purchasing.pay', $order), $payment)->assertSessionHasNoErrors();
        $this->post(route('finance.purchasing.pay', $order), $payment)->assertSessionHasNoErrors();
        $this->assertSame(2003, $order->outstandingMinor());
        $payment['submission_key'] = (string) Str::uuid();
        $payment['reference'] = 'BANK-2';
        $payment['amount'] = '21.00';
        $this->post(route('finance.purchasing.pay', $order), $payment)->assertSessionHasErrors('amount');
        $payment['amount'] = '20.03';
        $this->post(route('finance.purchasing.pay', $order), $payment)->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(0, $order->outstandingMinor());
        $this->assertDatabaseCount('school_purchase_payments', 2);
        $this->get(route('finance.purchasing.invoice.download', $order))->assertOk();
    }

    public function test_parent_and_inactive_users_cannot_access_staff_or_purchasing(): void
    {
        $this->actingAs($this->user('parent'))->get('/staff/leave')->assertForbidden();
        $this->get('/staff/expenses')->assertForbidden();
        $this->get('/finance/purchasing')->assertForbidden();
        $inactive = $this->user('admin');
        $inactive->update(['status' => 'inactive']);
        $this->actingAs($inactive)->get('/finance/purchasing')->assertForbidden();
    }

    public function test_rejected_purchase_can_be_replaced_but_second_active_order_is_blocked(): void
    {
        $order = $this->order();
        $req = $order->requisition;
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.store', $req), $this->orderData($req))->assertStatus(422);
        $this->actingAs($this->user('admin'))->post(route('finance.purchasing.review', $order), ['decision' => 'rejected', 'note' => 'Use revised supplier quotation'])->assertRedirect();
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.store', $req), $this->orderData($req))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('school_purchase_orders', 2);
    }

    public function test_missing_profile_does_not_allow_staff_submissions(): void
    {
        $this->actingAs($this->user())->get('/staff/expenses')->assertOk()->assertSee('link an active staff profile');
        $this->post('/staff/expenses', $this->expenseData())->assertForbidden();
    }

    public function test_a_holiday_on_the_first_day_is_excluded(): void
    {
        $this->leaveSetup();
        StaffLeaveHoliday::create(['date' => '2026-09-21', 'name' => 'Non-working date']);
        $this->post('/staff/leave', $this->leaveData(['ends_on' => '2026-09-21']))->assertSessionHasErrors('leave');
        $this->post('/staff/leave', $this->leaveData(['ends_on' => '2026-09-22']))->assertSessionHasNoErrors();
        $this->assertSame(2, StaffLeaveRequest::first()->half_days);
    }

    public function test_staff_cannot_submit_leave_for_another_employee_or_view_their_leave(): void
    {
        $own = $this->leaveSetup();
        $other = $this->staff();
        $this->post('/staff/leave', $this->leaveData(['staff_profile_id' => $other->id]))->assertSessionHasNoErrors();
        $leave = StaffLeaveRequest::first();
        $this->assertSame($own->id, $leave->staff_profile_id);
        $this->actingAs($other->user)->get(route('staff.leave.show', $leave))->assertForbidden();
    }

    public function test_hr_can_configure_leave_types_allowances_and_nonworking_dates(): void
    {
        $staff = $this->staff();
        $this->actingAs($this->user('admin'));
        $this->post(route('hr.leave.types.store'), ['name' => 'Special study leave', 'active' => 1, 'uses_allowance' => 1, 'requires_document' => 0, 'exclude_holidays' => 1, 'working_days' => [1, 2, 3, 4, 5, 6]])->assertSessionHasNoErrors();
        $type = StaffLeaveType::where('name', 'Special study leave')->firstOrFail();
        $this->post(route('hr.leave.allowances'), ['staff_profile_id' => $staff->id, 'staff_leave_type_id' => $type->id, 'year' => 2027, 'days' => '12.5', 'notes' => 'School-approved allowance'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_leave_allowances', ['staff_profile_id' => $staff->id, 'year' => 2027, 'half_days' => 25]);
        $this->post(route('hr.leave.holidays'), ['date' => '2027-01-04', 'name' => 'Closure'])->assertSessionHasNoErrors();
        $holiday = StaffLeaveHoliday::first();
        $this->delete(route('hr.leave.holidays.remove', $holiday))->assertRedirect();
        $this->assertDatabaseCount('staff_leave_holidays', 0);
        $this->get(route('hr.leave.settings', ['year' => 2027]))->assertOk()->assertSee('12.5');
        $this->actingAs($staff->user)->get('/staff/leave?year=2027')->assertOk()->assertSee('12.5');
    }

    public function test_rejected_claim_cannot_be_paid_or_changed_into_approved(): void
    {
        $claim = $this->expense();
        $this->actingAs($this->user('headmaster'))->post(route('finance.expenses.review', $claim), ['decision' => 'rejected', 'note' => 'Supporting evidence does not match'])->assertRedirect();
        $this->post(route('finance.expenses.review', $claim), ['decision' => 'approved'])->assertStatus(422);
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.expenses.pay', $claim), ['paid_on' => '2026-09-21', 'payment_method' => 'cash', 'payment_reference' => 'VOUCHER-1'])->assertStatus(422);
    }

    public function test_order_requester_and_preparer_cannot_approve_the_purchase(): void
    {
        $requester = $this->user('headmaster');
        $preparer = $this->user('admin');
        $req = $this->requisition();
        $req->update(['requested_by' => $requester->id]);
        $this->actingAs($preparer)->post(route('finance.purchasing.store', $req), $this->orderData($req))->assertSessionHasNoErrors();
        $order = SchoolPurchaseOrder::firstOrFail();
        $this->post(route('finance.purchasing.review', $order), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($requester)->post(route('finance.purchasing.review', $order), ['decision' => 'approved'])->assertForbidden();
    }

    public function test_delivery_requires_approval_and_full_delivery_confirmation(): void
    {
        $order = $this->order();
        $this->actingAs($this->user('inventory'))->post(route('finance.purchasing.receive', $order), ['received_on' => '2026-09-21', 'delivery_note' => 'Received', 'all_received' => 1])->assertStatus(422);
        $this->approveOrder($order);
        $this->actingAs($this->user('inventory'))->post(route('finance.purchasing.receive', $order), ['received_on' => '2026-09-21', 'delivery_note' => 'Part delivery'])->assertSessionHasErrors('all_received');
        $this->assertSame('approved', $order->fresh()->status);
    }

    public function test_duplicate_supplier_invoice_numbers_and_payment_references_are_rejected(): void
    {
        $first = $this->order();
        $this->approveOrder($first);
        $this->receiveOrder($first);
        $this->invoiceOrder($first);
        $req = $this->requisition();
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.store', $req), $this->orderData($req))->assertSessionHasNoErrors();
        $second = SchoolPurchaseOrder::latest('id')->first();
        $this->approveOrder($second);
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.invoice', $second), ['invoice_reference' => 'INV-100', 'amount' => '30.03', 'document' => UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf')])->assertSessionHasErrors('invoice_reference');
        $payment = ['submission_key' => (string) Str::uuid(), 'paid_on' => '2026-09-21', 'amount' => '10.00', 'method' => 'bank_transfer', 'reference' => 'BANK-REPEAT'];
        $this->post(route('finance.purchasing.pay', $first), $payment)->assertSessionHasNoErrors();
        $payment['submission_key'] = (string) Str::uuid();
        $this->post(route('finance.purchasing.pay', $first), $payment)->assertSessionHasErrors('reference');
        $this->assertDatabaseCount('school_purchase_payments', 1);
    }

    public function test_purchase_prices_cannot_overflow_money_calculations(): void
    {
        $req = $this->requisition();
        $req->items()->first()->update(['quantity' => '999999.99']);
        $req->unsetRelation('items');
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.purchasing.store', $req), $this->orderData($req,['prices' => [$req->items->first()->id => '999999999.00']]))->assertSessionHasErrors('prices');
        $this->assertDatabaseCount('school_purchase_orders',0);
    }
}
