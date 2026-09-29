<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentAcademicRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AcademicRecordController extends Controller
{
    public function __construct(private readonly StudentAcademicRecordService $records) {}

    public function show(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 404);

        return view('students.academic-record', [
            'record' => $this->records->build($student),
            'downloadRoute' => route('student.academic-record.download'),
            'backRoute' => route('student.dashboard'),
        ]);
    }

    public function download(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 404);
        $record = $this->records->build($student);

        return Pdf::loadView('pdf.student-academic-record', compact('record'))
            ->download(str($record['student']['name'].' academic record')->slug('_').'.pdf');
    }
}
