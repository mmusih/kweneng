<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prefect_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('duties');
            $table->date('appointed_on');
            $table->date('service_ends_on')->nullable();
            $table->enum('status', ['active', 'completed', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->string('class_name_snapshot')->nullable();
            $table->string('certificate_reference')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'title'], 'prefect_student_year_title_unique');
            $table->index(['academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefect_appointments');
    }
};
