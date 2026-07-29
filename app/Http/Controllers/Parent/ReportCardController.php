<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ReportCardController as AdminReportCardController;
use App\Models\Student;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportCardController extends Controller
{
    public function __construct(
        protected AdminReportCardController $adminReportCardController
    ) {}

    /**
     * Download the PDF report card for a child belonging to the authenticated parent.
     *
     * Route: GET /parent/children/{student}/report-card/{termId}/download
     * Name: parent.children.report-card.download
     */
    public function download(Student $student, int $termId)
    {
        $user = Auth::user();

        // Confirm the student belongs to this parent
        $children = $user->parent?->students ?? collect();
        $child = $children->firstWhere('id', $student->id);

        if (! $child) {
            abort(403, 'This student is not linked to your account.');
        }

        if ((bool) $child->fees_blocked) {
            abort(403, 'Report card is currently unavailable for this student due to an outstanding balance. Please contact the accounts office.');
        }

        // Confirm the term exists
        $term = Term::findOrFail($termId);

        // Reuse the exact same data-building logic as the headmaster's report card
        $data = $this->adminReportCardController->buildReportDataPublic($student, $termId);

        $pdf = Pdf::loadView('pdf.report-card', array_merge($data, [
            'schoolName' => 'Kweneng International Secondary School',
            'logoPath'   => public_path('images/logo.png'),
        ]))->setPaper('a4', 'landscape');

        $filename = str_replace(' ', '_', $student->user->name)
            . '_'
            . str_replace(' ', '_', $term->name)
            . '_report_card.pdf';

        return $pdf->download($filename);
    }
}
