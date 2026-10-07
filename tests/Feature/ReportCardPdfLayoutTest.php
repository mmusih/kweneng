<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\MarksService;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class ReportCardPdfLayoutTest extends TestCase
{
    public function test_twelve_subject_report_card_fits_on_one_page(): void
    {
        $pdf = Pdf::loadView('pdf.report-card', $this->reportData())
            ->setPaper('a4', 'landscape');

        $pdf->render();

        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_bulk_report_cards_use_exactly_one_page_per_student(): void
    {
        $report = $this->reportData();
        $pdf = Pdf::loadView('pdf.report-cards-bulk', [
            'reports' => [$report, $report],
            'schoolName' => $report['schoolName'],
            'logoPath' => $report['logoPath'],
        ])->setPaper('a4', 'landscape');

        $pdf->render();

        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    private function reportData(): array
    {
        $student = new Student;
        $student->setRelation('user', new User(['name' => 'Aobakwe Nick Motsiane']));
        $student->setRelation('currentClass', new ClassModel(['name' => 'Form 5A', 'level' => 12]));

        $term = new Term([
            'name' => 'Term 1',
            'report_title' => 'End of Term Report',
            'report_footer_note' => 'School reopens for Term 2 on the published date.',
            'report_office_note' => 'The school office is open during normal working hours.',
            'report_extra_note' => 'Holiday classes will follow the communicated timetable.',
        ]);

        $academicYear = new AcademicYear(['year_name' => '2026']);
        $longComment = 'The learner has shown clear improvement since midterm. '
            .'This progress is encouraging; continued effort is needed.';
        $subjects = collect(range(1, 12))->map(fn (int $index) => [
            'subject_name' => match ($index) {
                1 => 'English First Language',
                2 => 'Core Mathematics',
                default => 'Subject '.$index,
            },
            'subject_code' => match ($index) {
                1 => 'EFL',
                2 => 'MaC',
                default => 'S'.$index,
            },
            'midterm_score' => 72,
            'midterm_grade' => 'B',
            'endterm_score' => 81,
            'endterm_grade' => 'A',
            'teacher_comment' => $longComment,
            'teacher_name' => 'Teacher With A Long Name',
        ]);
        $formFivePoints = (new MarksService)->calculateFormFiveReportPoints(
            $student->currentClass,
            $subjects
        );

        return [
            'student' => $student,
            'term' => $term,
            'academicYear' => $academicYear,
            'subjects' => $subjects,
            'endtermAverage' => 81,
            'endtermRanking' => ['position' => 1, 'class_size' => 40],
            'classTeacherName' => 'Class Teacher With A Long Name',
            'attendanceSummary' => ['label' => '57/57', 'rate' => 100],
            'punctualitySummary' => ['label' => 'Good'],
            'behaviourSummary' => ['label' => 'Good'],
            'headmasterComment' => null,
            'formFivePoints' => $formFivePoints,
            'schoolName' => 'Kweneng International Secondary School',
            'logoPath' => public_path('images/logo.png'),
        ];
    }
}
