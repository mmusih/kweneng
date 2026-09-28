<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_settings', function (Blueprint $table) {
            $table->string('schedule_type', 20)->default('day')->after('revision');
            $table->date('cycle_anchor_date')->nullable()->after('cycle_length');
            $table->unsignedTinyInteger('cycle_anchor_day')->default(1)->after('cycle_anchor_date');
            $table->index(
                ['academic_year_id', 'schedule_type', 'is_active', 'is_published'],
                'tt_settings_schedule_publication_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tt_settings', function (Blueprint $table) {
            $table->dropIndex('tt_settings_schedule_publication_index');
            $table->dropColumn(['schedule_type', 'cycle_anchor_date', 'cycle_anchor_day']);
        });
    }
};
