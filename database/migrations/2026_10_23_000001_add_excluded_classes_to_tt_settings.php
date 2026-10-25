<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_settings', function (Blueprint $table) {
            $table->json('excluded_class_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tt_settings', fn (Blueprint $table) => $table->dropColumn('excluded_class_ids'));
    }
};
