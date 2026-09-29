<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentAcademicRecordService;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentAcademicRecordController extends Controller
{
    public function __construct(private readonly StudentAcademicRecordService $records) {}

    public function show(Student $student)
    {
        return view('students.academic-record', [
            'record' => $this->records->build($student),
            'downloadRoute' => route('admin.students.academic-record.download', $student),
            'backRoute' => route('admin.students.show', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))),
        ]);
    }

    public function download(Student $student)
    {
        $record = $this->records->build($student);

        return Pdf::loadView('pdf.student-academic-record', compact('record'))
            ->download(str($record['student']['name'].' academic record')->slug('_').'.pdf');
    }
}
