<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('admission_no')->unique();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth');
            $table->foreignId('current_class_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->string('photo')->nullable();
            $table->boolean('results_access')->default(true);
            $table->boolean('fees_blocked')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('admission_no');
            $table->index('current_class_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};
