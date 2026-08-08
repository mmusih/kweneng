<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->decimal('periods_per_week', 4, 1)->default(0);
            $table->unsignedTinyInteger('periods_per_card')->default(1);
            $table->foreignId('tt_daysdef_id')->nullable()->constrained('tt_daysdefs')->nullOnDelete();
            $table->foreignId('tt_weeksdef_id')->nullable()->constrained('tt_weeksdefs')->nullOnDelete();
            $table->foreignId('tt_termsdef_id')->nullable()->constrained('tt_termsdefs')->nullOnDelete();
            $table->string('seminar_group', 20)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();

            $table->index(['tt_setting_id', 'subject_id'], 'tt_lessons_setting_subject_index');
        });

        Schema::create('tt_lesson_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_lesson_id')->constrained('tt_lessons')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tt_lesson_id', 'teacher_id'], 'tt_lesson_teacher_unique');
        });

        Schema::create('tt_lesson_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_lesson_id')->constrained('tt_lessons')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tt_lesson_id', 'class_id'], 'tt_lesson_class_unique');
        });

        Schema::create('tt_lesson_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_lesson_id')->constrained('tt_lessons')->cascadeOnDelete();
            $table->foreignId('tt_group_id')->constrained('tt_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tt_lesson_id', 'tt_group_id'], 'tt_lesson_group_unique');
        });

        Schema::create('tt_lesson_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_lesson_id')->constrained('tt_lessons')->cascadeOnDelete();
            $table->foreignId('tt_room_id')->constrained('tt_rooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tt_lesson_id', 'tt_room_id'], 'tt_lesson_room_unique');
        });

        Schema::create('tt_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_lesson_id')->constrained('tt_lessons')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->string('days', 32);
            $table->string('weeks', 32)->default('1');
            $table->string('terms', 32)->default('1');
            $table->foreignId('tt_room_id')->nullable()->constrained('tt_rooms')->nullOnDelete();
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->index(['tt_lesson_id'], 'tt_cards_lesson_index');
            $table->index(['period_number'], 'tt_cards_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_cards');
        Schema::dropIfExists('tt_lesson_room');
        Schema::dropIfExists('tt_lesson_group');
        Schema::dropIfExists('tt_lesson_class');
        Schema::dropIfExists('tt_lesson_teacher');
        Schema::dropIfExists('tt_lessons');
    }
};
