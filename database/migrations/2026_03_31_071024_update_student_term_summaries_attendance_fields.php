<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_term_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('student_term_summaries', 'attendance')) {
                $table->dropColumn('attendance');
            }

            if (!Schema::hasColumn('student_term_summaries', 'attendance_total_days')) {
                $table->unsignedInteger('attendance_total_days')->nullable()->after('term_id');
            }

            if (!Schema::hasColumn('student_term_summaries', 'attendance_days_present')) {
                $table->unsignedInteger('attendance_days_present')->nullable()->after('attendance_total_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_term_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('student_term_summaries', 'attendance_total_days')) {
                $table->dropColumn('attendance_total_days');
            }

            if (Schema::hasColumn('student_term_summaries', 'attendance_days_present')) {
                $table->dropColumn('attendance_days_present');
            }

            if (!Schema::hasColumn('student_term_summaries', 'attendance')) {
                $table->string('attendance')->nullable()->after('term_id');
            }
        });
    }
};
