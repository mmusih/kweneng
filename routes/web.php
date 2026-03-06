<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AlumniInterestController;
use App\Models\Student;

// Existing routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// New marketing pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/academics', [PageController::class, 'academics'])->name('academics');
Route::get('/admissions', [PageController::class, 'admissions'])->name('admissions');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact-submit', [PageController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/alumni', [PageController::class, 'alumni'])->name('alumni');
Route::get('/student-life', [PageController::class, 'studentLife'])->name('student-life');
Route::get('/news-events', [PageController::class, 'newsEvents'])->name('news-events');
Route::get('/facilities', [PageController::class, 'facilities'])->name('facilities');
Route::get('/parent-resources', [PageController::class, 'parentResources'])->name('parent-resources');
Route::get('/term-dates', [PageController::class, 'termDates'])->name('term-dates');
Route::get('/policies', [PageController::class, 'policies'])->name('policies');
Route::get('/success-stories', [PageController::class, 'successStories'])->name('success-stories');

// Alumni interest registration
Route::post('/alumni/register-interest', [AlumniInterestController::class, 'store'])->name('alumni.register-interest');

// Include auth and role-based routes
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';

// Include student routes with file existence check
if (file_exists(__DIR__.'/student.php')) {
    require __DIR__.'/student.php';
}

// Include parent routes with file existence check
if (file_exists(__DIR__.'/parent.php')) {
    require __DIR__.'/parent.php';
}

// Include accounts routes with file existence check
if (file_exists(__DIR__.'/accounts.php')) {
    require __DIR__.'/accounts.php';
}

// TEMPORARY DEBUG ROUTE
Route::get('/debug-enrollment/{classId}/{academicYearId}', function ($classId, $academicYearId) {
    try {
        $rawHistories = DB::table('student_class_history')
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        $students = Student::whereHas('classHistory', function ($query) use ($classId, $academicYearId) {
            $query->where('class_id', $classId)
                  ->where('academic_year_id', $academicYearId)
                  ->where('status', 'active')
                  ->whereNull('exited_at');
        })->with('user')->get();

        return response()->json([
            'raw_histories_count' => $rawHistories->count(),
            'raw_histories' => $rawHistories,
            'students_count' => $students->count(),
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name ?? 'Unknown',
                    'admission_no' => $student->admission_no,
                ];
            }),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});