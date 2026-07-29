<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_parent_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['parent_id', 'student_id', 'homework_id'], 'homework_parent_reads_unique');
            $table->index(['parent_id', 'read_at'], 'homework_parent_reads_parent_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_parent_reads');
    }
};
