<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 10);
            $table->string('scope_value');
            $table->boolean('overall_enabled')->default(true);
            $table->decimal('overall_threshold', 5, 2)->default(60);
            $table->boolean('subject_enabled')->default(true);
            $table->decimal('subject_threshold', 5, 2)->default(60);
            $table->timestamps();

            $table->unique(['term_id', 'scope_type', 'scope_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_rules');
    }
};
