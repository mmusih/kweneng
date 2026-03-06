<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\AccountsOfficer;
use App\Models\ClassModel;
use App\Models\Alumni; // Add this import

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalStudents' => Student::count(),
            'totalTeachers' => Teacher::count(),
            'totalParents' => ParentModel::count(),
            'totalAccountsOfficers' => AccountsOfficer::count(),
            'totalClasses' => ClassModel::count(),
            'totalAlumni' => Alumni::count(), // Add this line
        ];

        $classes = ClassModel::all();

        return view('admin.dashboard', compact('stats', 'classes'));
    }
}
