<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentModel;
use App\Models\AcademicYear;
use App\Models\Term;

class DashboardController extends Controller
{
    public function index()
    {
        // Get the authenticated parent's record
        $parent = auth()->user()->parent;
        
        // Get children if parent exists
        $children = collect(); // Empty collection by default
        if ($parent) {
            $children = $parent->students()->with(['currentClass.academicYear'])->get();
        }
        
        // Get current academic year
        $currentAcademicYear = AcademicYear::where('active', true)->first();
        
        // Get current term (if any)
        $currentTerm = null;
        if ($currentAcademicYear) {
            $currentTerm = Term::where('academic_year_id', $currentAcademicYear->id)
                              ->where('locked', false)
                              ->orderBy('start_date', 'asc')
                              ->first();
        }

        return view('parent.dashboard', compact('parent', 'children', 'currentAcademicYear', 'currentTerm'));
    }
}
