<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Services\ExamSummaryService;
use App\Services\MarksService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ParentReportCardController extends Controller
{
    public function __construct(
        protected ExamSummaryService $examSummaryService,
        protected MarksService $marksService
    ) {}

    public function download(Request $request, int $studentId, int $termId)
    {
        $parent = $request->user()->parent;

        abort_if(! $parent, 403, 'Parent profile not found.');

        $student = $parent->students()
            ->with('user')
            ->where('students.id', $studentId)
            ->first();

        abort_if(! $student, 403, 'You do not have access to this student report card.');

        abort_if(
            (bool) $student->fees_blocked,
            403,
            'Report card is currently unavailable due to an outstanding balance.'
        );

        $term = Term::findOrFail($termId);

        if (! in_array($term->status, ['locked', 'finalized'], true)) {
            return response()->json([
                'message' => 'Report card is not available until the term is locked or finalized.',
            ], 403);
        }

        $adminController = new ReportCardController(
            $this->examSummaryService,
            $this->marksService
        );

        $data = $adminController->buildReportDataPublic($student, $termId);

        $pdf = Pdf::loadView('pdf.report-card', array_merge($data, [
            'schoolName' => 'Kweneng International Secondary School',
            'logoPath'   => public_path('images/logo.png'),
        ]))->setPaper('a4', 'landscape');

        $studentName = str_replace(' ', '_', $student->user->name ?? 'student');
        $termName = str_replace(' ', '_', $term->name);
        $filename = "{$studentName}_{$termName}_report_card.pdf";

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
