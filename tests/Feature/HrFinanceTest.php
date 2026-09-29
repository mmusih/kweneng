<?php

namespace Tests\Feature;

use App\Models\ParentModel;
use App\Models\PaymentCategory;
use App\Models\SchoolPayment;
use App\Models\StaffDocument;
use App\Models\StaffDocumentType;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentFinanceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'must_change_password' => false]);
    }

    private function student(): Student
    {
        return Student::create(['user_id' => $this->user('student')->id, 'admission_no' => 'T-'.Str::random(8), 'gender' => 'male', 'date_of_birth' => '2010-01-01']);
    }

    private function profile(): StaffProfile
    {
        return StaffProfile::create(['name' => 'Teacher Test', 'employee_number' => 'EMP-'.Str::random(8), 'position' => 'Teacher', 'citizenship' => 'Zimbabwean', 'is_citizen' => false, 'is_teacher' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['submission_key' => (string) Str::uuid(), 'payer_name' => 'Test Payer', 'paid_on' => today()->format('Y-m-d'), 'method' => 'cash', 'amount' => '25.50', 'items' => [['payment_category_id' => PaymentCategory::where('kind', 'income')->first()->id, 'description' => 'Uniform', 'amount' => '25.50']]], $overrides);
    }

    private function payment(array $overrides = []): SchoolPayment
    {
        $this->post(route('finance.payments.store'), $this->payload($overrides))->assertSessionHasNoErrors()->assertRedirect();

        return SchoolPayment::latest('id')->firstOrFail();
    }

    public function test_hr_access_requires_an_explicit_grant_without_changing_teacher_role(): void
    {
        $teacher = $this->user('teacher');
        $this->actingAs($teacher)->get('/hr/staff')->assertForbidden();
        $teacher->forceFill(['hr_access' => true])->save();
        $this->get('/hr/staff')->assertOk();
        $this->assertSame('teacher', $teacher->fresh()->role);
        $this->get('/finance/payments')->assertForbidden();
    }

    public function test_staff_creation_builds_citizenship_and_teaching_checklist(): void
    {
        $this->actingAs($this->user())->post('/hr/staff', ['name' => 'New Teacher', 'employee_number' => 'KWE-001', 'position' => 'Teacher', 'citizenship' => 'Zimbabwean', 'is_citizen' => 0, 'is_teacher' => 1, 'status' => 'active'])->assertSessionHasNoErrors();
        $staff = StaffProfile::firstOrFail();
        $names = $staff->requirements->map(fn ($r) => $r->type->name);
        $this->assertContains('Permission to teach', $names);
        $this->assertContains('Work permit', $names);
        $this->assertNotContains('Omang', $names);
        $this->get(route('hr.staff.show', $staff))->assertOk()->assertSee('Upload document');
    }

    public function test_private_upload_verification_and_replacement_keep_expired_status_until_verified(): void
    {
        Storage::fake('local');
        $admin = $this->user();
        $this->actingAs($admin);
        $staff = $this->profile();
        $requirement = $staff->requirements()->create(['staff_document_type_id' => StaffDocumentType::where('name', 'Work permit')->first()->id]);
        $this->post(route('hr.documents.store', $requirement), ['document' => UploadedFile::fake()->create('permit.pdf', 20, 'application/pdf'), 'does_not_expire' => 0, 'expires_on' => today()->subDay()->format('Y-m-d')])->assertSessionHasNoErrors();
        $document = StaffDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame('awaiting_verification', $requirement->fresh()->status());
        $this->post(route('hr.documents.verify', $document))->assertRedirect();
        $this->assertSame('expired', $requirement->fresh()->status());
        $this->post(route('hr.documents.store', $requirement), ['document' => UploadedFile::fake()->create('renewed.pdf', 20, 'application/pdf'), 'does_not_expire' => 0, 'expires_on' => today()->addYear()->format('Y-m-d')])->assertSessionHasNoErrors();
        $requirement->update(['renewal_status' => 'submitted']);
        $this->assertSame('expired', $requirement->fresh()->status());
        $this->assertDatabaseCount('staff_documents', 2);
        $this->actingAs($this->user('accounts_officer'))->get(route('hr.documents.download', $document))->assertForbidden();
        $this->actingAs($admin)->get(route('hr.documents.download', $document))->assertOk();
        $this->post(route('hr.documents.verify', StaffDocument::latest('id')->first()))->assertRedirect();
        $this->assertSame('valid', $requirement->fresh()->status());
    }

    public function test_documents_require_expiry_or_explicit_non_expiring_choice(): void
    {
        $this->actingAs($this->user());
        $requirement = $this->profile()->requirements()->create(['staff_document_type_id' => StaffDocumentType::first()->id]);
        $this->post(route('hr.documents.store', $requirement), ['document' => UploadedFile::fake()->create('permit.pdf', 20, 'application/pdf'), 'does_not_expire' => 0])->assertSessionHasErrors('expires_on');
    }

    public function test_work_permit_reminder_starts_before_six_month_renewal_deadline_and_digest_is_deduplicated(): void
    {
        Mail::fake();
        Cache::flush();
        $admin = $this->user();
        $requirement = $this->profile()->requirements()->create(['staff_document_type_id' => StaffDocumentType::where('name', 'Work permit')->first()->id]);
        $requirement->documents()->create(['path' => 'private.pdf', 'original_name' => 'permit.pdf', 'expires_on' => today()->addDays(200), 'uploaded_by' => $admin->id, 'verified_by' => $admin->id, 'verified_at' => now()]);
        $this->assertSame('expiring_soon', $requirement->fresh()->status());
        // Raw messages are checked using the array transport, not sent externally.
        Mail::swap(app('mail.manager'));
        config(['mail.default' => 'array']);
        $this->artisan('hr:document-reminders')->assertSuccessful();
        $this->artisan('hr:document-reminders')->assertSuccessful();
        $this->assertTrue(Cache::has('hr-reminder:'.today()->format('Y-m-d').':'.$admin->id));
    }

    public function test_payment_totals_are_exact_and_duplicate_submission_does_not_issue_two_receipts(): void
    {
        $this->actingAs($this->user('accounts_officer'));
        $bad = $this->payload(['amount' => '25.51']);
        $this->post('/finance/payments', $bad)->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('school_payments', 0);
        $data = $this->payload(['confirm_now' => 1]);
        $this->post('/finance/payments', $data)->assertSessionHasNoErrors();
        $this->post('/finance/payments', $data)->assertRedirect();
        $this->assertDatabaseCount('school_payments', 1);
        $payment = SchoolPayment::first();
        $this->assertSame(2550, $payment->amount_minor);
        $this->assertNotNull($payment->receipt_number);
        $this->post(route('finance.payments.confirm', $payment))->assertRedirect();
        $this->assertSame($payment->receipt_number, $payment->fresh()->receipt_number);
    }

    public function test_pending_payments_have_no_receipt_and_confirmed_pdf_can_be_downloaded(): void
    {
        $this->actingAs($this->user());
        $payment = $this->payment();
        $this->get(route('finance.payments.download', $payment))->assertNotFound();
        $this->post(route('finance.payments.confirm', $payment))->assertRedirect();
        $response = $this->get(route('finance.payments.download', $payment))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        foreach (['finance.payments.index', 'finance.payments.create', 'finance.accounts.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('finance.payments.show', $payment))->assertOk();
    }

    public function test_parent_receipts_are_explicitly_scoped_on_web_and_api(): void
    {
        $parent = $this->user('parent');
        $other = $this->user('parent');
        $this->actingAs($this->user());
        $payment = $this->payment(['parent_user_id' => $parent->id, 'confirm_now' => 1]);
        $this->actingAs($parent)->get('/parent/receipts')->assertOk()->assertSee($payment->receipt_number);
        $this->get(route('parent.receipts.download', $payment))->assertOk();
        Sanctum::actingAs($other);
        $this->getJson('/api/parent/receipts')->assertOk()->assertJsonCount(0, 'receipts.data');
        $this->getJson('/api/parent/receipts/'.$payment->id.'/download')->assertNotFound();
        Sanctum::actingAs($parent);
        $this->getJson('/api/parent/receipts')->assertJsonPath('receipts.data.0.receipt_number', $payment->receipt_number);
    }

    public function test_cross_family_receipt_cannot_be_assigned_to_a_parent(): void
    {
        $parent = $this->user('parent');
        ParentModel::create(['user_id' => $parent->id]);
        $student = $this->student();
        $this->actingAs($this->user());
        $data = $this->payload(['parent_user_id' => $parent->id]);
        $data['items'][0]['student_id'] = $student->id;
        $this->post('/finance/payments', $data)->assertSessionHasErrors('parent_user_id');
        $this->assertDatabaseCount('school_payments', 0);
    }

    public function test_fee_allocation_changes_ledger_only_when_confirmed_and_reversal_restores_balance(): void
    {
        $admin = $this->user();
        $student = $this->student();
        $this->actingAs($admin);
        $account = StudentFinanceAccount::create(['student_id' => $student->id, 'opened_on' => today(), 'opening_balance_minor' => 100000, 'created_by' => $admin->id]);
        $payment = $this->payment(['amount' => '350.00', 'items' => [
            ['payment_category_id' => PaymentCategory::where('kind', 'fees')->first()->id, 'student_id' => $student->id, 'description' => 'Tuition', 'amount' => '300.00'],
            ['payment_category_id' => PaymentCategory::where('kind', 'income')->first()->id, 'student_id' => $student->id, 'description' => 'Uniform', 'amount' => '50.00'],
        ]]);
        $this->assertSame(100000, $account->balanceMinor());
        $this->post(route('finance.payments.confirm', $payment))->assertSessionHasNoErrors();
        $this->assertSame(70000, $account->balanceMinor());
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.payments.reverse', $payment), ['reason' => 'Entered in error'])->assertForbidden();
        $this->actingAs($admin)->post(route('finance.payments.reverse', $payment), ['reason' => 'Entered in error'])->assertRedirect();
        $this->assertSame(100000, $account->balanceMinor());
        $this->post(route('finance.payments.confirm', $payment))->assertSessionHasErrors('payment');
        $this->get(route('finance.accounts.show', $account))->assertOk();
    }

    public function test_tuition_requires_a_ledger_and_cannot_predate_cutover(): void
    {
        $admin = $this->user();
        $student = $this->student();
        $this->actingAs($admin);
        $data = $this->payload();
        $data['items'][0]['payment_category_id'] = PaymentCategory::where('kind', 'fees')->first()->id;
        $data['items'][0]['student_id'] = $student->id;
        $this->post('/finance/payments', $data)->assertSessionHasErrors('items');
        StudentFinanceAccount::create(['student_id' => $student->id, 'opened_on' => today(), 'opening_balance_minor' => 0, 'created_by' => $admin->id]);
        $data['paid_on'] = today()->subDay()->format('Y-m-d');
        $this->post('/finance/payments', $data)->assertSessionHasErrors('items');
    }

    public function test_email_failure_keeps_confirmed_receipt_and_can_be_retried(): void
    {
        $this->actingAs($this->user());
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $payment = $this->payment(['payer_email' => 'payer@example.test', 'confirm_now' => 1]);
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('failed', $payment->email_status);
        Mail::shouldReceive('raw')->once()->andReturnNull();
        $number = $payment->receipt_number;
        $this->post(route('finance.payments.email', $payment))->assertRedirect();
        $this->assertSame('sent', $payment->fresh()->email_status);
        $this->assertSame($number, $payment->fresh()->receipt_number);
    }

    public function test_charge_posting_is_idempotent_and_credits_require_admin(): void
    {
        $admin = $this->user();
        $student = $this->student();
        $this->actingAs($admin);
        $this->post('/finance/accounts', ['student_id' => $student->id, 'opened_on' => today()->format('Y-m-d'), 'opening_balance' => '0.00'])->assertSessionHasNoErrors();
        $account = StudentFinanceAccount::firstOrFail();
        $entry = ['submission_key' => (string) Str::uuid(), 'charged_on' => today()->format('Y-m-d'), 'description' => 'Term tuition', 'amount' => '1500.00'];
        $this->post(route('finance.accounts.charge', $account), $entry)->assertSessionHasNoErrors();
        $this->post(route('finance.accounts.charge', $account), $entry)->assertSessionHasNoErrors();
        $this->assertSame(150000, $account->balanceMinor());
        $entry['submission_key'] = (string) Str::uuid();
        $entry['amount'] = '-100.00';
        $this->actingAs($this->user('accounts_officer'))->post(route('finance.accounts.charge', $account), $entry)->assertForbidden();
    }

    public function test_ledger_is_used_by_parent_api_even_when_imported_balances_exist(): void
    {
        $student = $this->student();
        $parent = $this->user('parent');
        $profile = ParentModel::create(['user_id' => $parent->id]);
        $profile->students()->attach($student->id);
        $admin = $this->user();
        $year = \App\Models\AcademicYear::create(['year_name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'open']);
        $term = \App\Models\Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-01-01', 'end_date' => '2026-04-30', 'status' => 'active']);
        $import = \App\Models\StudentFeeBalance::create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'term_id' => $term->id, 'closing_balance' => '9999.00', 'updated_by' => $admin->id]);
        StudentFinanceAccount::create(['student_id' => $student->id, 'opened_on' => today(), 'opening_balance_minor' => 120050, 'created_by' => $admin->id]);
        Sanctum::actingAs($parent);
        $this->getJson('/api/parent/fees')->assertOk()->assertJsonPath('children.0.closing_balance', 1200.5)->assertJsonPath('children.0.balance_source', 'ledger');
        $import->update(['closing_balance' => '7000.00']);
        $this->getJson('/api/parent/fees')->assertJsonPath('children.0.closing_balance', 1200.5);
        $this->getJson('/api/parent/dashboard')->assertOk()->assertJsonPath('children.0.fees.closing_balance', 1200.5);
    }

    public function test_removed_family_link_revokes_receipt_access(): void
    {
        $parent = $this->user('parent');
        $profile = ParentModel::create(['user_id' => $parent->id]);
        $student = $this->student();
        $profile->students()->attach($student->id);
        $this->actingAs($this->user());
        $data = $this->payload(['parent_user_id' => $parent->id, 'confirm_now' => 1]);
        $data['items'][0]['student_id'] = $student->id;
        $this->post('/finance/payments', $data)->assertSessionHasNoErrors();
        $payment = SchoolPayment::firstOrFail();
        $this->actingAs($parent)->get(route('parent.receipts.download', $payment))->assertOk();
        $profile->students()->detach($student->id);
        $this->get(route('parent.receipts.download', $payment))->assertNotFound();
    }

    public function test_inactive_accounts_cannot_access_new_modules(): void
    {
        $admin = $this->user();
        $admin->update(['status' => 'inactive']);
        $this->actingAs($admin)->get('/hr/staff')->assertForbidden();
        $this->get('/finance/payments')->assertForbidden();
    }

    public function test_delegated_hr_cannot_grant_hr_access_and_document_settings_render(): void
    {
        $hr = $this->user('teacher');
        $hr->forceFill(['hr_access' => true])->save();
        $other = $this->user('teacher');
        $this->actingAs($hr)->post('/hr/staff', ['user_id' => $other->id, 'name' => $other->name, 'employee_number' => 'DELEGATED', 'position' => 'Teacher', 'citizenship' => 'Botswana', 'is_citizen' => 1, 'is_teacher' => 1, 'status' => 'active', 'hr_access' => 1])->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->hr_access);
        $this->get('/hr/document-types')->assertOk();
        $this->get('/hr/staff/create')->assertOk();
        $this->get(route('hr.staff.edit', StaffProfile::first()))->assertOk();
    }

    public function test_document_types_can_be_added_and_reminder_lead_times_changed(): void
    {
        $staff = $this->profile();
        $this->actingAs($this->user());
        $this->post('/hr/document-types', ['name' => 'School induction', 'applies_to' => 'all', 'reminder_days' => 30])->assertSessionHasNoErrors();
        $type = StaffDocumentType::where('name', 'School induction')->firstOrFail();
        $this->assertDatabaseHas('staff_requirements', ['staff_profile_id' => $staff->id, 'staff_document_type_id' => $type->id]);
        $this->put(route('hr.types.update', $type), ['reminder_days' => 45, 'guidance' => 'School policy'])->assertSessionHasNoErrors();
        $this->assertSame(45, $type->fresh()->reminder_days);
    }
}
