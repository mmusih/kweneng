<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subjects', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('teachers')
                ->cascadeOnDelete();
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->index('student_id', 'student_subjects_student_id_index');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropUnique('student_subjects_student_id_subject_id_academic_year_id_unique');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'subject_id', 'teacher_id', 'academic_year_id'],
                'student_subjects_student_subject_teacher_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropUnique('student_subjects_student_subject_teacher_year_unique');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropIndex('student_subjects_student_id_index');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'subject_id', 'academic_year_id'],
                'student_subjects_student_id_subject_id_academic_year_id_unique'
            );
        });
    }
};
