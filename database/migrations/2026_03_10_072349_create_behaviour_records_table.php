<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behaviour_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->string('category');
            $table->string('severity')->default('minor');
            $table->text('incident');
            $table->text('action_taken')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'record_date']);
            $table->index(['student_id', 'record_date']);
            $table->index(['teacher_id', 'record_date']);
            $table->index(['academic_year_id', 'term_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE behaviour_records
                ADD CONSTRAINT behaviour_records_severity_check
                CHECK (severity IN ('minor', 'moderate', 'major'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('behaviour_records');
    }
};
