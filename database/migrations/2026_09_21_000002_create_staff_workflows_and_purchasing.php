<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The earlier operational-role migration updated MySQL only. Keep SQLite
        // installations consistent so inventory users can receive purchase orders.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $t) {
                $t->enum('role', ['admin', 'teacher', 'headmaster', 'student', 'parent', 'accounts_officer', 'librarian', 'office', 'register_officer', 'inventory'])->change();
            });
        }
        Schema::create('staff_leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('uses_allowance')->default(true);
            $t->json('working_days');
            $t->boolean('exclude_holidays')->default(true);
            $t->boolean('requires_document')->default(false);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('staff_leave_holidays', function (Blueprint $t) {
            $t->id();
            $t->date('date')->unique();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('staff_leave_allowances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $t->foreignId('staff_leave_type_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('year');
            $t->unsignedInteger('half_days');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['staff_profile_id', 'staff_leave_type_id', 'year'], 'leave_allowance_unique');
        });
        Schema::create('staff_leave_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('submission_key')->unique();
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $t->foreignId('staff_leave_type_id')->constrained()->restrictOnDelete();
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('portion')->default('full');
            $t->unsignedInteger('half_days');
            $t->boolean('uses_allowance');
            $t->text('reason');
            $t->string('document_path')->nullable();
            $t->string('document_name')->nullable();
            $t->string('status')->default('submitted');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('review_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['staff_profile_id', 'starts_on', 'ends_on'], 'leave_period_index');
        });
        Schema::create('staff_expense_claims', function (Blueprint $t) {
            $t->id();
            $t->uuid('submission_key')->unique();
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->string('description');
            $t->text('reason');
            $t->string('category');
            $t->date('spent_on');
            $t->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('amount_minor');
            $t->string('document_path');
            $t->string('document_name');
            $t->string('status')->default('submitted');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('paid_on')->nullable();
            $t->string('payment_method')->nullable();
            $t->string('payment_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('school_suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('school_purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('submission_key')->unique();
            $t->foreignId('requisition_id')->constrained()->restrictOnDelete();
            $t->foreignId('school_supplier_id')->constrained()->restrictOnDelete();
            $t->string('supplier_name');
            $t->string('supplier_email')->nullable();
            $t->text('supplier_address')->nullable();
            $t->string('title');
            $t->string('department')->nullable();
            $t->date('needed_on')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('total_minor');
            $t->unsignedBigInteger('additional_cost_minor')->default(0);
            $t->string('additional_cost_description')->nullable();
            $t->string('status')->default('submitted');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->foreignId('received_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('received_on')->nullable();
            $t->text('delivery_note')->nullable();
            $t->string('invoice_reference')->nullable();
            $t->string('invoice_path')->nullable();
            $t->string('invoice_name')->nullable();
            $t->timestamps();
            $t->unique(['school_supplier_id', 'invoice_reference'], 'supplier_invoice_unique');
        });
        Schema::create('school_purchase_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_purchase_order_id')->constrained()->restrictOnDelete();
            $t->foreignId('requisition_item_id')->constrained()->restrictOnDelete();
            $t->string('description');
            $t->string('unit');
            $t->unsignedBigInteger('quantity_hundredths');
            $t->unsignedBigInteger('unit_price_minor');
            $t->unsignedBigInteger('total_minor');
            $t->timestamps();
        });
        Schema::create('school_purchase_payments', function (Blueprint $t) {
            $t->id();
            $t->uuid('submission_key')->unique();
            $t->foreignId('school_purchase_order_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('amount_minor');
            $t->date('paid_on');
            $t->string('method');
            $t->string('reference');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_purchase_order_id', 'reference'], 'purchase_payment_reference_unique');
        });
        foreach (['Annual leave' => true, 'Sick leave' => true, 'Study leave' => true, 'Unpaid leave' => false, 'Other leave' => false] as $name => $allowance) {
            DB::table('staff_leave_types')->insert(['name' => $name, 'uses_allowance' => $allowance, 'working_days' => json_encode([1, 2, 3, 4, 5]), 'exclude_holidays' => true, 'requires_document' => false, 'active' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['school_purchase_payments', 'school_purchase_lines', 'school_purchase_orders', 'school_suppliers', 'staff_expense_claims', 'staff_leave_requests', 'staff_leave_allowances', 'staff_leave_holidays', 'staff_leave_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
