<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_lessons', function (Blueprint $table) {
            $table->unsignedSmallInteger('cards_per_cycle')->nullable()->after('periods_per_card');
        });

        DB::table('tt_lessons')
            ->select(['id', 'periods_per_week', 'periods_per_card'])
            ->orderBy('id')
            ->chunkById(200, function ($lessons) {
                foreach ($lessons as $lesson) {
                    DB::table('tt_lessons')->where('id', $lesson->id)->update([
                        'cards_per_cycle' => (int) ceil(
                            (float) $lesson->periods_per_week / max(1, (int) $lesson->periods_per_card)
                        ),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tt_lessons', function (Blueprint $table) {
            $table->dropColumn('cards_per_cycle');
        });
    }
};
