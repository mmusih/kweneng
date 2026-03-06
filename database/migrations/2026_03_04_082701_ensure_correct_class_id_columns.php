<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Ensure class_id columns exist in both tables
        Schema::table('class_subject', function (Blueprint $table) {
            if (!Schema::hasColumn('class_subjects', 'class_id')) {
                $table->unsignedBigInteger('class_id')->after('id');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            }
        });
        
        Schema::table('teacher_subject', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_subjects', 'class_id')) {
                $table->unsignedBigInteger('class_id')->after('id');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        // In production environments, we typically don't roll back schema corrections
        // Column additions for fixing schema issues are usually permanent
    }
};
