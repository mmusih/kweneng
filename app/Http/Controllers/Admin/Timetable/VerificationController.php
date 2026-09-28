<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Tt\Setting;
use App\Services\Timetable\VerificationService;
use Illuminate\Contracts\View\View;

class VerificationController extends Controller
{
    public function __invoke(Setting $setting, VerificationService $verification): View
    {
        return view('admin.timetable.verify', [
            'setting' => $setting,
            'report' => $verification->verify($setting),
            'classes' => ClassModel::query()->where('academic_year_id', $setting->academic_year_id)->orderBy('name')->get(),
            'teachers' => Teacher::query()->with('user')->whereIn('id', $setting->lessons()
                ->join('tt_lesson_teacher', 'tt_lessons.id', '=', 'tt_lesson_teacher.tt_lesson_id')
                ->pluck('tt_lesson_teacher.teacher_id'))->get()->sortBy('user.name'),
            'rooms' => $setting->rooms()->orderBy('name')->get(),
        ]);
    }
}
