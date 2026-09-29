<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Timetable\TeacherLoadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LoadController extends Controller
{
    public function __construct(private readonly TeacherLoadService $loads) {}

    public function download(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404);
        $load = $this->loads->forTeacher($teacher);

        return Pdf::loadView('pdf.teacher-load', compact('load'))
            ->download(str($load['teacher_name'].' teaching load')->slug('_').'.pdf');
    }
}
