<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Term;
use App\Services\ExamSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamSummaryController extends Controller
{
    public function __construct(
        protected ExamSummaryService $examSummaryService
    ) {}

    public function index(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $classes = collect();
        $terms = collect();
        $summary = null;

        $selectedAcademicYearId = $request->input('academic_year_id', $activeAcademicYear?->id);
        $selectedClassId = $request->input('class_id');
        $selectedTermId = $request->input('term_id');
        $selectedExamType = $request->input('exam_type', ExamSummaryService::EXAM_MIDTERM);

        if ($selectedAcademicYearId) {
            $request->validate([
                'academic_year_id' => ['nullable', 'exists:academic_years,id'],
                'class_id' => ['nullable', 'exists:classes,id'],
                'term_id' => ['nullable', 'exists:terms,id'],
                'exam_type' => ['nullable', Rule::in(ExamSummaryService::examTypes())],
            ]);

            $classes = ClassModel::where('academic_year_id', $selectedAcademicYearId)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            $terms = Term::where('academic_year_id', $selectedAcademicYearId)
                ->orderBy('start_date')
                ->get();
        }

        if ($selectedAcademicYearId && $selectedClassId && $selectedTermId && $selectedExamType) {
            $summary = $this->examSummaryService->generate(
                (int) $selectedClassId,
                (int) $selectedAcademicYearId,
                (int) $selectedTermId,
                $selectedExamType
            );
        }

        return view('headmaster.exam-summaries.index', compact(
            'activeAcademicYear',
            'classes',
            'terms',
            'summary',
            'selectedAcademicYearId',
            'selectedClassId',
            'selectedTermId',
            'selectedExamType'
        ));
    }

    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'exam_type' => ['required', Rule::in(ExamSummaryService::examTypes())],
        ]);

        $summary = $this->examSummaryService->generate(
            (int) $validated['class_id'],
            (int) $validated['academic_year_id'],
            (int) $validated['term_id'],
            $validated['exam_type']
        );

        $term = Term::findOrFail($validated['term_id']);
        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

        $pdf = Pdf::loadView('pdf.exam-summary', [
            'summary' => $summary,
            'term' => $term,
            'academicYear' => $academicYear,
            'schoolName' => 'Kweneng International Secondary School',
            'logoPath' => public_path('images/logo.png'),
        ])->setPaper('a4', 'landscape');

        $filename = str_replace(' ', '_', $summary['class']->name)
            . '_' . $validated['exam_type']
            . '_summary_sheet.pdf';

        return $pdf->download($filename);
    }
}
