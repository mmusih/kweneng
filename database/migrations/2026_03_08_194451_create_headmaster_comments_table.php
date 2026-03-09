<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headmaster_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('headmaster_id')->constrained('teachers')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();

            $table->unique(['student_id', 'term_id'], 'student_term_unique_headmaster_comment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headmaster_comments');
    }
};