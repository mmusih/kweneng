<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcement_activities')) {
            return;
        }

        Schema::create('announcement_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->string('actor_type')->default('admin');
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['announcement_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_activities');
    }
};