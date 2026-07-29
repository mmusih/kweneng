<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->enum('role_in_department', ['teacher', 'hod', 'assistant_hod', 'observer'])->default('teacher');
            $table->timestamps();

            $table->unique(['department_id', 'user_id', 'academic_year_id', 'role_in_department'], 'dept_user_year_role_unique');
            $table->index(['user_id', 'role_in_department']);
            $table->index(['department_id', 'academic_year_id', 'role_in_department'], 'dept_year_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
