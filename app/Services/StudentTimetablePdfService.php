<?php

namespace App\Services;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentTimetablePdfService
{
    public function render(Student $student, array $schedule)
    {
        // Render all cycle days together, reducing spacing only when necessary.
        foreach ([9, 8, 7, 6] as $fontSize) {
            $pdf = Pdf::loadView('pdf.student-timetable', compact('student', 'schedule', 'fontSize'))->setPaper('a4', 'landscape');
            $pdf->render();
            if ($pdf->getDomPDF()->getCanvas()->get_page_count() === 1) return $pdf;
        }
        // Exceptionally dense schedules retain every entry on a larger landscape sheet.
        $pdf = Pdf::loadView('pdf.student-timetable', compact('student', 'schedule', 'fontSize'))->setPaper('a3', 'landscape');
        $pdf->render();
        abort_unless($pdf->getDomPDF()->getCanvas()->get_page_count() === 1, 422, 'This timetable is too large for a single sheet. Please contact the school.');
        return $pdf;
    }

    public function download(Student $student, string $type)
    {
        $payload = app(TimetableService::class)->forStudent($student, null, true);
        $schedule = collect($payload['schedules'] ?? [])->first(fn ($item) => $item['template']['schedule_type'] === $type);
        abort_unless($schedule, 404, 'This timetable has not been published.');
        return $this->render($student, $schedule)->download('student-'.$student->id.'-'.$type.'-timetable.pdf');
    }
}
