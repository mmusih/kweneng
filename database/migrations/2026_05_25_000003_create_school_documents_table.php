<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['timetable', 'prospectus', 'booklist', 'uniform'])->index();
            $table->string('file_path');       // stored path under storage/app/public/school-documents/
            $table->string('original_filename'); // shown to parents
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('uploaded_by'); // user_id of admin
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_documents');
    }
};
