<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_term_summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();

            $table->string('attendance')->nullable();
            $table->string('punctuality')->nullable();
            $table->string('behaviour')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'term_id'], 'student_term_summary_unique');
            $table->index(['class_id', 'academic_year_id', 'term_id'], 'term_summary_class_year_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_term_summaries');
    }
};
