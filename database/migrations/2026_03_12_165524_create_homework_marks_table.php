<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homeworks')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            $table->decimal('marks_obtained', 8, 2)->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->string('grade')->nullable();
            $table->string('remarks')->nullable();

            $table->timestamps();

            $table->unique(['homework_id', 'student_id'], 'homework_student_unique');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_marks');
    }
};
