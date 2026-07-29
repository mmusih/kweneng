<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_import_batch_id')->constrained('fee_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('excel_row_number')->nullable();
            $table->string('form')->nullable();
            $table->string('surname')->nullable();
            $table->string('student_names')->nullable();
            $table->decimal('opening_balance', 12, 2)->nullable();
            $table->decimal('payment', 12, 2)->nullable();
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->foreignId('matched_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('match_status')->default('unmatched'); // matched, unmatched, ambiguous, ignored
            $table->text('match_notes')->nullable();
            $table->json('possible_student_ids')->nullable();
            $table->timestamps();

            $table->index(['fee_import_batch_id', 'match_status']);
            $table->index('matched_student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_import_rows');
    }
};
