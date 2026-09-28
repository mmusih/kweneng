<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_settings', function (Blueprint $table) {
            $table->unsignedInteger('preparation_version')->default(0);
        });
        Schema::table('tt_lessons', function (Blueprint $table) {
            $table->uuid('preparation_key')->nullable();
            $table->json('assignment_sources')->nullable();
            $table->uuid('split_key')->nullable()->index();
            $table->unique(['tt_setting_id', 'preparation_key']);
        });
    }

    public function down(): void
    {
        Schema::table('tt_lessons', function (Blueprint $table) {
            $table->dropUnique(['tt_setting_id', 'preparation_key']);
            $table->dropIndex(['split_key']);
            $table->dropColumn(['preparation_key', 'assignment_sources', 'split_key']);
        });
        Schema::table('tt_settings', fn (Blueprint $table) => $table->dropColumn('preparation_version'));
    }
};
