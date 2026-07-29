<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('previewed'); // previewed, imported, cancelled
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->unsignedInteger('ambiguous_rows')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_import_batches');
    }
};
