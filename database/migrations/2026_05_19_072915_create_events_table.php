<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->enum('type', ['holiday', 'exam', 'meeting', 'activity', 'ceremony', 'other'])->default('other');
            $table->enum('visibility', ['all', 'parents', 'teachers', 'students', 'specific_class'])->default('all');

            // Targeting options
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();

            // Author
            $table->unsignedBigInteger('created_by');
            $table->string('created_by_role');

            // Recurrence (optional)
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly

            $table->boolean('is_all_day')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};
