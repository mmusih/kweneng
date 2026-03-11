<?php

namespace App\Http\Controllers\AccountsOfficer;

use App\Http\Controllers\Controller;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalStudents' => Student::count(),
            'blockedStudents' => Student::where('fees_blocked', true)->count(),
            'unblockedStudents' => Student::where(function ($query) {
                $query->where('fees_blocked', false)
                    ->orWhereNull('fees_blocked');
            })->count(),
        ];

        return view('accounts-officer.dashboard', compact('stats'));
    }
}
