<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('division_tag');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'division_tag'], 'tt_divisions_class_tag_unique');
        });

        Schema::create('tt_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('tt_division_id')->nullable()->constrained('tt_divisions')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('entire_class')->default(false);
            $table->string('asc_id', 32)->nullable()->index();
            $table->string('partner_id', 32)->nullable();
            $table->timestamps();

            $table->index(['class_id', 'tt_division_id'], 'tt_groups_class_division_index');
        });

        Schema::create('tt_group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_group_id')->constrained('tt_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tt_group_id', 'student_id'], 'tt_group_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_group_student');
        Schema::dropIfExists('tt_groups');
        Schema::dropIfExists('tt_divisions');
    }
};
