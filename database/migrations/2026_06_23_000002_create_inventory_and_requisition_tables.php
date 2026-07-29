<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('item_type')->default('equipment'); // equipment, stationery, supply, furniture, ict, other
            $table->string('category')->nullable();
            $table->string('asset_tag')->nullable()->unique();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->string('unit')->default('item');
            $table->unsignedInteger('quantity_on_hand')->default(1);
            $table->unsignedInteger('minimum_quantity')->default(0);
            $table->string('condition_status')->default('serviceable'); // serviceable, damaged, needs_repair, broken, lost, retired
            $table->string('procurement_status')->default('none'); // none, needs_buying, requested, ordered, received
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'condition_status']);
            $table->index(['procurement_status']);
            $table->index(['category']);
        });

        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('submitted'); // submitted, acknowledged, approved, rejected, ordered, fulfilled, cancelled
            $table->date('needed_by')->nullable();
            $table->text('reason')->nullable();
            $table->text('inventory_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index(['requested_by']);
            $table->index(['academic_year_id', 'term_id']);
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('category')->nullable();
            $table->string('unit')->default('item');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('estimated_unit_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
        Schema::dropIfExists('inventory_items');
    }
};
