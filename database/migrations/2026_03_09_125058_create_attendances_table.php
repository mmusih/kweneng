<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date'], 'student_attendance_date_unique');
            $table->index(['class_id', 'attendance_date']);
            $table->index(['teacher_id', 'attendance_date']);
            $table->index(['academic_year_id', 'term_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE attendances
                ADD CONSTRAINT attendances_status_check
                CHECK (status IN ('present', 'absent', 'late', 'excused'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};