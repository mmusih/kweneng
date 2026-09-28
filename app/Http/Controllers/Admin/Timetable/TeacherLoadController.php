<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\Timetable\TeacherLoadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TeacherLoadController extends Controller
{
    public function __construct(private readonly TeacherLoadService $loads) {}

    public function index(Request $request)
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;

        return view('admin.timetable.teacher-loads', [
            'summary' => $this->loads->summary($yearId, false),
            'academicYears' => AcademicYear::query()->orderByDesc('id')->get(),
            'selectedYearId' => $yearId,
        ]);
    }

    public function download(Request $request)
    {
        $yearId = $request->integer('academic_year_id') ?: AcademicYear::current()?->id;
        $summary = $this->loads->summary($yearId, false);

        return Pdf::loadView('pdf.teacher-loads', compact('summary'))
            ->setPaper('a4', 'landscape')
            ->download('teacher_load_summary.pdf');
    }
}
