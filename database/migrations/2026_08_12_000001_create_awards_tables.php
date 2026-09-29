<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['academic', 'custom'])->default('custom');
            $table->string('badge_icon')->default('trophy');
            $table->string('badge_color', 20)->default('#D4AF37');
            $table->text('default_citation')->nullable();
            $table->boolean('headmaster_only')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('award_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('scope_type', ['school', 'all_levels', 'level', 'class', 'selected'])->default('school');
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->unsignedInteger('level')->nullable();
            $table->unsignedInteger('positions')->nullable();
            $table->decimal('cutoff_percentage', 5, 2)->nullable();
            $table->string('calculation_mode')->nullable();
            $table->string('missing_marks_policy')->default('exclude');
            $table->string('tie_policy')->default('include_all');
            $table->json('calculation_config')->nullable();
            $table->json('generation_summary')->nullable();
            $table->date('award_date');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('parent_visible')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'academic_year_id']);
        });

        Schema::create('student_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('award_title');
            $table->text('citation')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->decimal('main_score', 6, 2)->nullable();
            $table->json('tie_breaker_scores')->nullable();
            $table->string('class_name_snapshot')->nullable();
            $table->unsignedInteger('level_snapshot')->nullable();
            $table->string('admission_no_snapshot')->nullable();
            $table->string('student_name_snapshot');
            $table->string('selection_source')->default('generated');
            $table->text('override_reason')->nullable();
            $table->string('certificate_reference')->unique();
            $table->timestamps();
            $table->unique(['award_run_id', 'student_id']);
            $table->index(['student_id', 'created_at']);
        });

        $now = now();
        DB::table('award_categories')->insert([
            ['name' => 'Academic Excellence', 'type' => 'academic', 'badge_icon' => 'trophy', 'badge_color' => '#D4AF37', 'default_citation' => 'Awarded for outstanding academic excellence.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => "The Headmaster's Prize", 'type' => 'custom', 'badge_icon' => 'crest', 'badge_color' => '#7C3AED', 'default_citation' => 'Presented in recognition of exceptional achievement and exemplary contribution to the school.', 'headmaster_only' => true, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Most Improved Student', 'type' => 'custom', 'badge_icon' => 'trending-up', 'badge_color' => '#0F766E', 'default_citation' => 'Awarded for exceptional improvement and commitment.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Leadership Award', 'type' => 'custom', 'badge_icon' => 'star', 'badge_color' => '#1D4ED8', 'default_citation' => 'Awarded for exemplary leadership.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Best in Discipline', 'type' => 'custom', 'badge_icon' => 'shield', 'badge_color' => '#B45309', 'default_citation' => 'Awarded for exemplary conduct and discipline.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Best in Sports', 'type' => 'custom', 'badge_icon' => 'medal', 'badge_color' => '#DC2626', 'default_citation' => 'Awarded for outstanding sporting achievement.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Perfect Attendance', 'type' => 'custom', 'badge_icon' => 'calendar-check', 'badge_color' => '#15803D', 'default_citation' => 'Awarded for perfect attendance.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Outstanding Service', 'type' => 'custom', 'badge_icon' => 'heart', 'badge_color' => '#BE185D', 'default_citation' => 'Awarded for outstanding service to the school community.', 'headmaster_only' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('student_awards');
        Schema::dropIfExists('award_runs');
        Schema::dropIfExists('award_categories');
    }
};
