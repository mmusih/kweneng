<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Timetable\TeacherLoadService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly TeacherLoadService $loads) {}

    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        return view('teacher.dashboard', [
            'teacherLoad' => $teacher ? $this->loads->forTeacher($teacher) : null,
        ]);
    }
}
