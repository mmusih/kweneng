<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('hr_access')->default(false));
        Schema::create('staff_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('employee_number')->unique();
            $t->string('position');
            $t->string('department')->nullable();
            $t->string('citizenship');
            $t->boolean('is_citizen');
            $t->boolean('is_teacher')->default(false);
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->date('started_on')->nullable();
            $t->date('contract_ends_on')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('staff_document_types', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('applies_to')->default('optional');
            $t->unsignedSmallInteger('reminder_days')->default(90);
            $t->text('guidance')->nullable();
            $t->timestamps();
        });
        Schema::create('staff_requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $t->foreignId('staff_document_type_id')->constrained()->restrictOnDelete();
            $t->boolean('required')->default(true);
            $t->text('exception_reason')->nullable();
            $t->string('renewal_status')->default('not_started');
            $t->date('application_date')->nullable();
            $t->string('application_reference')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['staff_profile_id', 'staff_document_type_id'], 'staff_requirement_unique');
        });
        Schema::create('staff_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_requirement_id')->constrained()->restrictOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->string('reference')->nullable();
            $t->string('issuer')->nullable();
            $t->date('issued_on')->nullable();
            $t->date('expires_on')->nullable();
            $t->boolean('does_not_expire')->default(false);
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
        Schema::create('erp_audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('kind')->default('income');
            $t->timestamps();
        });
        Schema::create('school_payments', function (Blueprint $t) {
            $t->id();
            $t->uuid('submission_key')->unique();
            $t->string('receipt_number')->nullable()->unique();
            $t->string('payer_name');
            $t->string('payer_email')->nullable();
            $t->foreignId('parent_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('paid_on');
            $t->string('method');
            $t->string('reference')->nullable();
            $t->unsignedBigInteger('amount_minor');
            $t->string('status')->default('pending');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('confirmed_at')->nullable();
            $t->text('reversal_reason')->nullable();
            $t->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reversed_at')->nullable();
            $t->string('email_status')->default('not_sent');
            $t->timestamp('emailed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('school_payment_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('student_name')->nullable();
            $t->string('category_name');
            $t->string('kind');
            $t->string('description');
            $t->unsignedBigInteger('amount_minor');
            $t->timestamps();
        });
        Schema::create('student_finance_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $t->date('opened_on');
            $t->bigInteger('opening_balance_minor');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('student_finance_charges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_finance_account_id')->constrained()->restrictOnDelete();
            $t->uuid('submission_key')->unique();
            $t->date('charged_on');
            $t->string('description');
            $t->bigInteger('amount_minor');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        foreach ([
            ['Permission to teach', 'teachers', 120], ['BOTEPCO registration / teaching licence', 'teachers', 180],
            ['Work permit', 'non_citizens', 210], ['Residence permit', 'non_citizens', 180],
            ['Passport', 'non_citizens', 180], ['Omang', 'citizens', 90],
            ['Employment contract', 'all', 90], ['Qualifications and transcripts', 'teachers', 90],
            ['BQA evaluation / verification', 'optional', 90], ['References', 'optional', 90],
        ] as [$name, $scope, $days]) {
            DB::table('staff_document_types')->insert(['name' => $name, 'applies_to' => $scope, 'reminder_days' => $days, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['Tuition' => 'fees', 'Registration' => 'income', 'Examinations' => 'income', 'Transport' => 'income', 'Meals' => 'income', 'Boarding' => 'income', 'Trips and activities' => 'income', 'Uniforms and books' => 'income', 'Donation / sponsorship' => 'income', 'Facility hire' => 'income', 'Staff repayment' => 'income', 'Refundable deposit' => 'deposit', 'Other' => 'income'] as $name => $kind) {
            DB::table('payment_categories')->insert(['name' => $name, 'kind' => $kind, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['student_finance_charges', 'student_finance_accounts', 'school_payment_items', 'school_payments', 'payment_categories', 'erp_audit_events', 'staff_documents', 'staff_requirements', 'staff_document_types', 'staff_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('hr_access'));
    }
};
