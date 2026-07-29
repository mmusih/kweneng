<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_cycle_anchors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_template_id')
                ->constrained('timetable_templates')
                ->cascadeOnDelete();
            $table->date('anchor_date');
            $table->unsignedTinyInteger('day_number');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(
                ['timetable_template_id', 'anchor_date'],
                'timetable_cycle_anchor_date_unique',
            );
        });

        $now = now();
        $anchors = DB::table('timetable_templates')
            ->where('cycle_type', 'rotating')
            ->whereNotNull('cycle_start_date')
            ->get(['id', 'cycle_start_date'])
            ->map(fn ($template) => [
                'timetable_template_id' => $template->id,
                'anchor_date' => $template->cycle_start_date,
                'day_number' => 1,
                'note' => 'Initial cycle date',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($anchors !== []) {
            DB::table('timetable_cycle_anchors')->insert($anchors);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_cycle_anchors');
    }
};
