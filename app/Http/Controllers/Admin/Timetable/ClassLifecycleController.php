<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Tt\Setting;
use App\Services\Timetable\ClassParticipationService;
use App\Services\Timetable\YearRolloverService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassLifecycleController extends Controller
{
    public function index(Request $request, Setting $setting)
    {
        $years = AcademicYear::where('status', AcademicYear::STATUS_OPEN)
            ->where('id', '!=', $setting->academic_year_id)->orderByDesc('year_name')->get();
        $targetYear = $years->firstWhere('id', (int) $request->input('target_year_id', old('target_year_id'))) ?? $years->first();

        return view('admin.timetable.classes', [
            'setting' => $setting,
            'classes' => ClassModel::where('academic_year_id', $setting->academic_year_id)->orderBy('level')->orderBy('name')->get(),
            'years' => $years,
            'targetYear' => $targetYear,
            'targetClasses' => $targetYear ? $targetYear->classes()->orderBy('level')->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request, Setting $setting, ClassParticipationService $participation)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'excluded_class_ids' => ['sometimes', 'array'],
            'excluded_class_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $participation->update($setting, $data['excluded_class_ids'] ?? [], $data['version']);

        return back()->with('success', 'Class participation saved. Timetables and teacher loads now reflect the enabled classes.');
    }

    public function rollover(Request $request, Setting $setting, YearRolloverService $rollover)
    {
        $data = $request->validate([
            'target_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('status', AcademicYear::STATUS_OPEN)],
            'name' => ['required', 'string', 'max:120'],
            'cycle_anchor_date' => ['required', 'date'],
            'cycle_anchor_day' => ['required', 'integer', 'min:1', 'max:'.$setting->cycle_length],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['required', 'integer', 'min:0'],
        ]);
        $copy = $rollover->copy($setting, $data);

        return redirect()->route('admin.timetable.index', ['setting' => $copy->id])
            ->with('success', 'New-year timetable draft created with your class mapping. Review subjects, teachers and groups, then verify and publish it.');
    }
}
