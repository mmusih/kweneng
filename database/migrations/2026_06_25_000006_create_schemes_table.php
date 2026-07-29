<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_subject_id')->constrained('teacher_subjects')->cascadeOnDelete();
            $table->foreignId('syllabus_id')->nullable()->constrained('syllabuses')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('status', ['draft', 'submitted', 'changes_requested', 'approved', 'active', 'archived'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['teacher_subject_id', 'academic_year_id'], 'scheme_assignment_year_unique');
            $table->index(['academic_year_id', 'status']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes');
    }
};
