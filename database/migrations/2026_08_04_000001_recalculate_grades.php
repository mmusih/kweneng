<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marks')) {
            DB::table('marks')
                ->select(['id', 'midterm_score', 'endterm_score'])
                ->orderBy('id')
                ->chunkById(500, function ($marks): void {
                    foreach ($marks as $mark) {
                        $midterm = $mark->midterm_score !== null ? (float) $mark->midterm_score : null;
                        $endterm = $mark->endterm_score !== null ? (float) $mark->endterm_score : null;
                        $score = match (true) {
                            $midterm !== null && $endterm !== null => ($midterm + $endterm) / 2,
                            $midterm !== null => $midterm,
                            default => $endterm,
                        };

                        DB::table('marks')
                            ->where('id', $mark->id)
                            ->update(['grade' => $this->gradeFor($score)]);
                    }
                });
        }

        if (Schema::hasTable('homework_marks')) {
            DB::table('homework_marks')
                ->select(['id', 'percentage'])
                ->orderBy('id')
                ->chunkById(500, function ($marks): void {
                    foreach ($marks as $mark) {
                        $score = $mark->percentage !== null ? (float) $mark->percentage : null;

                        DB::table('homework_marks')
                            ->where('id', $mark->id)
                            ->update(['grade' => $this->gradeFor($score)]);
                    }
                });
        }
    }

    public function down(): void
    {
        // The previous grades were inconsistent and cannot be restored safely.
    }

    private function gradeFor(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }
        if ($score > 89) {
            return 'A*';
        }
        if ($score > 79) {
            return 'A';
        }
        if ($score > 69) {
            return 'B';
        }
        if ($score > 59) {
            return 'C';
        }
        if ($score > 49) {
            return 'D';
        }
        if ($score > 39) {
            return 'E';
        }
        if ($score > 34) {
            return 'F';
        }

        return 'G';
    }
};
