<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_awards', function (Blueprint $table) {
            $table->index('award_run_id', 'student_awards_award_run_id_index');
        });

        Schema::table('student_awards', function (Blueprint $table) {
            $table->dropUnique(['award_run_id', 'student_id']);
            $table->foreignId('subject_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('subject_name_snapshot')->nullable()->after('award_title');
            $table->unique(['award_run_id', 'student_id', 'subject_id'], 'student_awards_run_student_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_awards', function (Blueprint $table) {
            $table->dropUnique('student_awards_run_student_subject_unique');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('subject_name_snapshot');
            $table->unique(['award_run_id', 'student_id']);
        });

        Schema::table('student_awards', function (Blueprint $table) {
            $table->dropIndex('student_awards_award_run_id_index');
        });
    }
};
