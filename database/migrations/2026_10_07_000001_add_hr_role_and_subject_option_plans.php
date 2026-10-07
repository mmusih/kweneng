<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLES = ['admin', 'teacher', 'headmaster', 'student', 'parent', 'accounts_officer', 'librarian', 'office', 'register_officer', 'inventory'];

    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', [...self::ROLES, 'hr'])->change());
        Schema::create('subject_option_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('academic_year_id')->constrained();
            $table->foreignId('tt_setting_id')->constrained('tt_settings');
            $table->json('configuration');
            $table->json('arrangement');
            $table->json('assessment');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Preserve HR accounts and their access when reverting the role extension.
        DB::table('users')->where('role', 'hr')->update(['role' => 'office', 'hr_access' => true]);
        Schema::dropIfExists('subject_option_plans');
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', self::ROLES)->change());
    }
};
