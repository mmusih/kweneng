<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeYearIds = DB::table('academic_years')
            ->where('active', true)
            ->orderByDesc('id')
            ->pluck('id');

        $currentYearId = $activeYearIds->first();

        if ($currentYearId) {
            DB::table('academic_years')
                ->where('id', $currentYearId)
                ->update(['active' => true, 'status' => 'open']);

            DB::table('academic_years')
                ->where('id', '!=', $currentYearId)
                ->where('active', true)
                ->update(['active' => false, 'status' => 'closed']);
        }

        $activeTermQuery = DB::table('terms')
            ->where('status', 'active');

        $currentTerm = (clone $activeTermQuery)
            ->when($currentYearId, fn ($query) => $query->where('academic_year_id', $currentYearId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if (! $currentTerm) {
            $currentTerm = (clone $activeTermQuery)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }

        if ($currentTerm) {
            DB::table('terms')
                ->where('id', $currentTerm->id)
                ->update(['status' => 'active', 'locked' => false]);

            DB::table('terms')
                ->where('id', '!=', $currentTerm->id)
                ->where('status', 'active')
                ->update(['status' => 'finalized', 'locked' => false]);
        }
    }

    public function down(): void
    {
        // This migration only normalizes existing operational state.
    }
};
