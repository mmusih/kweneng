<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/terms', function (Request $request) {
        $academicYearId = $request->query('academic_year_id');
        if ($academicYearId) {
            return \App\Models\Term::where('academic_year_id', $academicYearId)->get();
        }
        return \App\Models\Term::all();
    });
});
