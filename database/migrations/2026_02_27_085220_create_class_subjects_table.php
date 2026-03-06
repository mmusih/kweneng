<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->decimal('max_marks', 8, 2)->default(100.00);
            $table->integer('passing_marks')->default(40);
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->unique(['class_id', 'subject_id', 'academic_year_id']);
            $table->index(['academic_year_id', 'class_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_subjects');
    }
};
