<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Term;
use App\Services\StudyRetentionService;
use Illuminate\Http\Request;

class ParentAttendanceController extends Controller
{
    public function today(Request $request, StudyRetentionService $study)
    {
        $parent = $request->user()->parent;
        abort_unless($parent, 404, 'Parent profile not found.');
        $now = now('Africa/Gaborone');
        $children = $parent->students()->with('currentClass')->get();
        $records = Attendance::whereIn('student_id', $children->pluck('id'))
            ->whereDate('attendance_date', $now->toDateString())->get()->keyBy('student_id');
        $terms = Term::whereIn('academic_year_id', $children->pluck('currentClass.academic_year_id')->filter())
            ->where('status', Term::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $now->toDateString())
            ->whereDate('end_date', '>=', $now->toDateString())
            ->latest('start_date')->get()->unique('academic_year_id')->keyBy('academic_year_id');
        $retained = collect();
        foreach ($terms as $term) {
            $retained = $retained->merge($study->report($term, $children->pluck('id')));
        }
        $retained = $retained->keyBy(fn ($row) => $row['student']->id);

        return response()->json([
            'checked_at' => $now->toIso8601String(),
            'children' => $children->map(function ($child) use ($records, $retained, $now) {
                $record = $records->get($child->id);
                $status = $record?->status ?? 'unmarked';
                $study = $retained->get($child->id);
                $end = $study ? '15:15' : '13:10';
                $present = in_array($status, [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE], true);
                $inSchool = $present && $now->format('H:i') >= '07:30' && $now->format('H:i') < $end;
                $label = match ($status) {
                    Attendance::STATUS_PRESENT, Attendance::STATUS_LATE => ($inSchool ? 'In school: ' : 'Present today: ').'07:30–'.$end,
                    Attendance::STATUS_ABSENT => 'Absent',
                    Attendance::STATUS_EXCUSED => 'Absent (excused)',
                    default => 'Attendance not marked yet',
                };
                return [
                    'student_id' => $child->id,
                    'date' => $now->toDateString(),
                    'status' => $status,
                    'label' => $label,
                    'in_school' => $inSchool,
                    'school_start' => '07:30',
                    'school_end' => $end,
                    'study' => $study !== null,
                    'voluntary_study' => (bool) ($study['voluntary'] ?? false),
                    'recorded_at' => $record?->updated_at?->toIso8601String(),
                ];
            })->values(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
