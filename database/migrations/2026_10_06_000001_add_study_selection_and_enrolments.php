<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_retention_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('source_term_id')->constrained('terms')->restrictOnDelete();
            $table->string('assessment', 10)->default('endterm');
            $table->timestamps();
        });
        Schema::create('study_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['term_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_enrolments');
        Schema::dropIfExists('study_retention_settings');
    }
};
