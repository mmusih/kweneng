<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\Tt\Setting;
use App\Services\Timetable\PrintScheduleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    public function __invoke(Setting $setting, string $type, int $id, PrintScheduleService $schedules): Response
    {
        abort_unless(in_array($type, ['class', 'teacher', 'room'], true), 404);
        $data = $schedules->build($setting, $type, $id);
        $filename = str($data['entityName'].' timetable')->slug('_').'.pdf';

        return Pdf::loadView('pdf.timetable', $data)
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }
}
