<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AlumniInterestController;
use App\Http\Controllers\Profile\PasswordController;

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

// Shared authenticated password routes
Route::middleware(['auth'])->group(function () {
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
});

// Include auth and role-based routes
require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/teacher.php';

// Include student routes
if (file_exists(__DIR__ . '/student.php')) {
    require __DIR__ . '/student.php';
}

// Include parent routes
if (file_exists(__DIR__ . '/parent.php')) {
    require __DIR__ . '/parent.php';
}

// Include accounts routes
if (file_exists(__DIR__ . '/accounts.php')) {
    require __DIR__ . '/accounts.php';
}

// Include headmaster routes
if (file_exists(__DIR__ . '/headmaster.php')) {
    require __DIR__ . '/headmaster.php';
}

// Include librarian routes
if (file_exists(__DIR__ . '/librarian.php')) {
    require __DIR__ . '/librarian.php';
}
