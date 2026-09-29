<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentAcademicRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ParentAcademicRecordController extends Controller
{
    public function __construct(private readonly StudentAcademicRecordService $records) {}

    public function show(Request $request, Student $student)
    {
        $this->authorise($request, $student);

        return response()->json($this->records->build($student));
    }

    public function download(Request $request, Student $student)
    {
        $this->authorise($request, $student);
        $record = $this->records->build($student);

        return Pdf::loadView('pdf.student-academic-record', compact('record'))
            ->download(str($record['student']['name'].' academic record')->slug('_').'.pdf');
    }

    private function authorise(Request $request, Student $student): void
    {
        $parent = $request->user()->parent;
        abort_unless($parent && $parent->students()->whereKey($student->id)->exists(), 403);
        abort_if($student->fees_blocked || ! $student->results_access, 403, 'Results access is restricted.');
    }
}
