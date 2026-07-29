<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_subtopics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_topic_id')->constrained('syllabus_topics')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['syllabus_topic_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_subtopics');
    }
};
