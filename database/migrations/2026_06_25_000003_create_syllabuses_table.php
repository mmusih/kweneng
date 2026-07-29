<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('title');
            $table->enum('source', ['manual', 'text', 'pdf', 'docx'])->default('manual');
            $table->string('file_path')->nullable();
            $table->longText('raw_text')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'subject_id', 'class_id']);
            $table->index(['created_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabuses');
    }
};
