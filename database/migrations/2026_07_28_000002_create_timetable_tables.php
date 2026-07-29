<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->nullable()->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('timetable_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->enum('cycle_type', ['weekly', 'rotating'])->default('weekly');
            $table->unsignedTinyInteger('cycle_length')->default(5);
            $table->date('cycle_start_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['academic_year_id', 'is_active', 'is_published'], 'timetable_template_active_index');
        });

        Schema::create('timetable_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_template_id')->constrained('timetable_templates')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('name');
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->timestamps();

            $table->unique(['timetable_template_id', 'day_number'], 'timetable_day_number_unique');
        });

        Schema::create('timetable_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_day_id')->constrained('timetable_days')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['lesson', 'break', 'lunch', 'assembly', 'other'])->default('lesson');
            $table->timestamps();

            $table->unique(['timetable_day_id', 'sequence'], 'timetable_period_sequence_unique');
        });

        Schema::create('timetable_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'name'], 'timetable_group_year_name_unique');
        });

        Schema::create('timetable_group_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_group_id')->constrained('timetable_groups')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['timetable_group_id', 'class_id'], 'timetable_group_class_unique');
        });

        Schema::create('timetable_group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_group_id')->constrained('timetable_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['timetable_group_id', 'student_id'], 'timetable_group_student_unique');
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_template_id')->constrained('timetable_templates')->cascadeOnDelete();
            $table->foreignId('timetable_day_id')->constrained('timetable_days')->cascadeOnDelete();
            $table->foreignId('start_period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $table->foreignId('end_period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignId('timetable_group_id')->nullable()->constrained('timetable_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('timetable_room_id')->nullable()->constrained('timetable_rooms')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['timetable_day_id', 'teacher_id'], 'timetable_entry_teacher_day_index');
            $table->index(['timetable_day_id', 'class_id'], 'timetable_entry_class_day_index');
            $table->index(['timetable_day_id', 'timetable_room_id'], 'timetable_entry_room_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_group_student');
        Schema::dropIfExists('timetable_group_class');
        Schema::dropIfExists('timetable_groups');
        Schema::dropIfExists('timetable_periods');
        Schema::dropIfExists('timetable_days');
        Schema::dropIfExists('timetable_templates');
        Schema::dropIfExists('timetable_rooms');
    }
};
