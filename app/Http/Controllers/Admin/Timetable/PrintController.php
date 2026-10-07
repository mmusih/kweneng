<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\Tt\Setting;
use App\Services\Timetable\PrintScheduleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function __invoke(Setting $setting, string $type, string $id, PrintScheduleService $schedules, Request $request): Response
    {
        abort_unless(in_array($type, ['class', 'teacher', 'room'], true), 404);
        $filters = $request->validate(['exclude_forms' => ['sometimes', 'array'], 'exclude_forms.*' => ['integer', 'between:1,12']]);
        $excludedForms = array_map('intval', $filters['exclude_forms'] ?? []);
        if (in_array($id, ['all', 'summary'], true)) {
            $pages = $schedules->bundle($setting, $type, $excludedForms);
            return Pdf::loadView($id === 'summary' ? 'pdf.timetable-summary' : 'pdf.timetable', [
                'pages' => $pages, 'setting' => $setting, 'type' => $type, 'excludedForms' => $excludedForms,
                'teacherLegend' => $schedules->entities($setting, 'teacher'),
                'roomLegend' => $schedules->entities($setting, 'room'),
            ])->setPaper($id === 'summary' ? 'a3' : 'a4', 'landscape')->stream($type.'_'.$id.'_timetables.pdf');
        }
        abort_unless(ctype_digit($id), 404);
        $data = $schedules->build($setting, $type, (int) $id, $excludedForms);
        $filename = str($data['entityName'].' timetable')->slug('_').'.pdf';

        return Pdf::loadView('pdf.timetable', ['pages' => [$data]])
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }
}
