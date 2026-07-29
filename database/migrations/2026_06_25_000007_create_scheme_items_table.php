<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->foreignId('syllabus_topic_id')->nullable()->constrained('syllabus_topics')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('estimated_periods')->default(1);
            $table->unsignedSmallInteger('planned_order')->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'moved', 'skipped', 'needs_reteaching'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('teacher_comment')->nullable();
            $table->text('hod_comment')->nullable();
            $table->timestamps();

            $table->index(['scheme_id', 'term_id', 'week_number', 'planned_order'], 'scheme_plan_index');
            $table->index(['scheme_id', 'status']);
            $table->index(['completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_items');
    }
};
