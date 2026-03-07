<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\StudentClassHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Safely get the authenticated user with student relationship
        $user = Auth::user();
        
        // Validate user and role
        if (!$user || $user->role !== 'student') {
            return redirect()->route('login')->withErrors(['error' => 'Unauthorized access']);
        }
        
        // Initialize variables
        $student = null;
        $currentAcademicYear = null;
        $currentTerm = null;
        
        // Use caching for better performance
        $cacheKey = 'student_dashboard_' . $user->id . '_' . now()->format('Y-m-d');
        
        $dashboardData = Cache::remember($cacheKey, 300, function() use ($user) {
            $data = [
                'student' => null,
                'current_academic_year' => null,
                'current_term' => null
            ];
            
            // Check if user has student relationship
            if ($user->student) {
                $data['student'] = $user->student;
                
                // Load necessary relationships
                $data['student']->load(['currentClass.academicYear']);
                
                // Get current academic year (prefer 'open' status, fallback to 'active')
                $data['current_academic_year'] = AcademicYear::where('status', 'open')
                    ->orWhere('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Get current term for the academic year
                if ($data['current_academic_year']) {
                    $data['current_term'] = Term::where('academic_year_id', $data['current_academic_year']->id)
                        ->where('status', 'active')
                        ->orderBy('start_date', 'asc')
                        ->first();
                }
            }
            
            return $data;
        });
        
        $student = $dashboardData['student'];
        $currentAcademicYear = $dashboardData['current_academic_year'];
        $currentTerm = $dashboardData['current_term'];

        return view('student.dashboard', compact('student', 'currentAcademicYear', 'currentTerm'));
    }
}
