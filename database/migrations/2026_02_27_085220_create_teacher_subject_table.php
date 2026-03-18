<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Primary teacher for this subject
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(
                ['teacher_id', 'subject_id', 'class_id', 'academic_year_id'],
                'teacher_subject_unique'
            );
            $table->index(['academic_year_id', 'class_id', 'subject_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
