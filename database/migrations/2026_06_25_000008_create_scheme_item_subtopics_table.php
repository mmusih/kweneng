<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_item_subtopics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_item_id')->constrained('scheme_items')->cascadeOnDelete();
            $table->foreignId('syllabus_subtopic_id')->nullable()->constrained('syllabus_subtopics')->nullOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'skipped', 'needs_reteaching'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scheme_item_id', 'sort_order']);
            $table->index(['scheme_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_item_subtopics');
    }
};
