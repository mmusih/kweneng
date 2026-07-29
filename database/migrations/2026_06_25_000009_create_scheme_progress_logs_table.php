<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->foreignId('scheme_item_id')->nullable()->constrained('scheme_items')->cascadeOnDelete();
            $table->foreignId('scheme_item_subtopic_id')->nullable()->constrained('scheme_item_subtopics')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['scheme_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_progress_logs');
    }
};
