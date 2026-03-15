<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('total_marks', 8, 2);
            $table->date('assigned_date');
            $table->date('due_date')->nullable();

            $table->timestamps();

            $table->index(['class_id', 'subject_id', 'academic_year_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeworks');
    }
};
