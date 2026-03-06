<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_class_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            // Ensure only one current class per student per academic year
            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_class_history');
    }
};
