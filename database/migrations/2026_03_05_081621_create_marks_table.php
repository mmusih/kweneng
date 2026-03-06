<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            
            $table->decimal('midterm_score', 5, 2)->nullable();
            $table->decimal('endterm_score', 5, 2)->nullable();
            
            $table->string('grade')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['student_id', 'subject_id', 'academic_year_id', 'term_id']);
            
            // Indexes
            $table->index(['class_id', 'term_id']);
            $table->index(['subject_id', 'term_id']);
            $table->index(['teacher_id', 'term_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('marks');
    }
};
