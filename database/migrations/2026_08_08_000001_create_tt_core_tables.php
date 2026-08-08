<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('term_label')->nullable();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->unsignedTinyInteger('cycle_length')->default(6);
            $table->text('asc_options')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['academic_year_id', 'is_active', 'is_published'], 'tt_settings_active_index');
        });

        Schema::create('tt_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['tt_setting_id', 'period_number'], 'tt_periods_number_unique');
        });

        Schema::create('tt_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('after_period')->nullable();
            $table->string('days', 32)->nullable();
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tt_daysdefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->text('days');
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tt_weeksdefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->text('weeks');
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tt_termsdefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->text('terms');
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tt_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->nullable()->constrained('tt_settings')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tt_subject_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('short_name', 20)->nullable();
            $table->string('colour', 9)->nullable();
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();

            $table->unique(['subject_id'], 'tt_subject_meta_subject_unique');
        });

        Schema::create('tt_teacher_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('short_name', 20)->nullable();
            $table->string('colour', 9)->nullable();
            $table->unsignedTinyInteger('max_lessons_per_day')->nullable();
            $table->string('asc_id', 32)->nullable()->index();
            $table->timestamps();

            $table->unique(['teacher_id'], 'tt_teacher_meta_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_teacher_meta');
        Schema::dropIfExists('tt_subject_meta');
        Schema::dropIfExists('tt_rooms');
        Schema::dropIfExists('tt_termsdefs');
        Schema::dropIfExists('tt_weeksdefs');
        Schema::dropIfExists('tt_daysdefs');
        Schema::dropIfExists('tt_breaks');
        Schema::dropIfExists('tt_periods');
        Schema::dropIfExists('tt_settings');
    }
};
