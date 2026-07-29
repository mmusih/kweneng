<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->decimal('closing_balance', 12, 2)->default(0);
            $table->string('source_file_name')->nullable();
            $table->foreignId('fee_import_batch_id')->nullable()->constrained('fee_import_batches')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'term_id'], 'student_fee_balances_period_unique');
            $table->index(['academic_year_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_balances');
    }
};
