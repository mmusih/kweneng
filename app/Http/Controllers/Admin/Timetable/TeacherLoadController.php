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

    public function timetable(Request $request)
    {
        return view('admin.timetable.report-home', $this->reportData($request));
    }

    public function index(Request $request)
    {
        return view('admin.timetable.teacher-loads', $this->reportData($request));
    }

    public function teachingSummary(Request $request)
    {
        return view('admin.timetable.teaching-summary', $this->reportData($request));
    }

    public function download(Request $request)
    {
        return Pdf::loadView('pdf.teacher-loads', $this->reportData($request))
            ->setPaper('a3', 'landscape')
            ->download('teacher_load_summary.pdf');
    }

    public function teachingSummaryDownload(Request $request)
    {
        return Pdf::loadView('pdf.teaching-summary', $this->reportData($request))
            ->setPaper('a3', 'landscape')->download('teaching_summary.pdf');
    }

    public function csv(Request $request)
    {
        $data = $this->reportData($request);

        return response()->streamDownload(function () use ($data) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Academic year', 'Source', 'Timetable', 'Teacher', 'Subject', 'Classes', 'Groups', 'Scheduled periods per cycle']);
            foreach ($data['summary']['teachers'] as $teacher) {
                foreach ($teacher['schedules'] as $schedule) {
                    foreach ($schedule['subjects'] as $subject) {
                        if (! $subject['scheduled_periods']) {
                            continue;
                        }
                        $row = [$data['summary']['academic_year'], $data['source'], $schedule['label'], $teacher['teacher_name'], $subject['subject'], implode('; ', $subject['classes']), implode('; ', $subject['groups']), $subject['scheduled_periods']];
                        // Keep user-entered names as text when opening the report in a spreadsheet.
                        fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value, $row));
                    }
                }
            }
            fclose($stream);
        }, 'teaching_summary.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportData(Request $request): array
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'source' => ['nullable', 'in:published,working'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'exclude_forms' => ['sometimes', 'array'],
            'exclude_forms.*' => ['integer', 'between:1,12'],
            'setting' => ['nullable', 'integer', 'exists:tt_settings,id'],
        ]);
        $yearId = isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : AcademicYear::current()?->id;
        $source = $filters['source'] ?? 'published';
        $excludedForms = array_values(array_unique(array_map('intval', $filters['exclude_forms'] ?? [])));
        $summary = $this->loads->summary($yearId, $source === 'published', $excludedForms);
        $teachers = collect($summary['teachers']);
        $selectedTeacherId = isset($filters['teacher_id']) ? (int) $filters['teacher_id'] : null;
        if ($selectedTeacherId) {
            $summary['teachers'] = $teachers->where('teacher_id', $selectedTeacherId)->values()->all();
        }

        return [
            'summary' => $summary,
            'excludedForms' => $excludedForms,
            'availableForms' => \App\Models\ClassModel::where('academic_year_id', $yearId)->whereNotNull('level')->distinct()->orderBy('level')->pluck('level'),
            'academicYears' => AcademicYear::query()->orderByDesc('year_name')->get(),
            'selectedYearId' => $yearId,
            'teachers' => $teachers,
            'selectedTeacherId' => $selectedTeacherId,
            'source' => $source,
            'routePrefix' => $request->user()->role === 'headmaster' ? 'headmaster' : 'admin',
            'reportFilters' => array_filter(['academic_year_id' => $yearId, 'source' => $source, 'teacher_id' => $selectedTeacherId, 'exclude_forms' => $excludedForms, 'setting' => $filters['setting'] ?? null]),
        ];
    }
}
