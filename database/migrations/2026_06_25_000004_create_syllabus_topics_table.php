<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_id')->constrained('syllabuses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('estimated_periods')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['syllabus_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_topics');
    }
};
