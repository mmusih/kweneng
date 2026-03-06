<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Safely get the authenticated user
        $user = Auth::user();
        
        // Initialize variables
        $student = null;
        $currentAcademicYear = null;
        $currentTerm = null;
        
        // Check if user exists and has student relationship
        if ($user && $user->student) {
            $student = $user->student;
            
            // Load current class and academic year info
            $student->load(['currentClass.academicYear']);
            
            // Get current academic year
            $currentAcademicYear = AcademicYear::where('active', true)->first();
            
            // Get current term (if any)
            if ($currentAcademicYear) {
                $currentTerm = Term::where('academic_year_id', $currentAcademicYear->id)
                                  ->where('status', 'active') // Using status instead of locked
                                  ->orderBy('start_date', 'asc')
                                  ->first();
            }
        }

        return view('student.dashboard', compact('student', 'currentAcademicYear', 'currentTerm'));
    }
}
