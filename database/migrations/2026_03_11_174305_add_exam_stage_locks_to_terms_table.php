<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->boolean('midterm_locked')->default(false)->after('locked');
            $table->boolean('endterm_locked')->default(false)->after('midterm_locked');
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['midterm_locked', 'endterm_locked']);
        });
    }
};
