<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_absence_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('absence_date');
            $table->date('expected_return_date')->nullable();
            $table->string('reason', 80);
            $table->text('note')->nullable();
            $table->string('status', 30)->default('pending'); // pending, seen, resolved
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('seen_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'created_at']);
            $table->index(['student_id', 'absence_date']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_absence_notices');
    }
};
