<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Tt\Setting;
use App\Services\Timetable\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'schedule_type' => ['required', Rule::in(Setting::TYPES)],
            'cycle_length' => ['required', 'integer', 'min:1', 'max:14'],
            'cycle_anchor_date' => ['required', 'date'],
            'cycle_anchor_day' => ['required', 'integer', 'min:1', 'lte:cycle_length'],
        ]);
        $year = AcademicYear::current() ?? AcademicYear::query()->latest('id')->firstOrFail();

        $setting = Setting::create($data + [
            'academic_year_id' => $year->id,
            'term_label' => $year->year_name,
            'revision' => (int) Setting::where('academic_year_id', $year->id)->max('revision') + 1,
            'is_active' => false,
            'is_published' => false,
        ]);

        return redirect()->route('admin.timetable.index', ['setting' => $setting->id])
            ->with('success', $setting->typeLabel().' timetable created. Configure its periods and lessons.');
    }

    public function publish(Setting $setting): RedirectResponse
    {
        if ($setting->periods()->doesntExist() || $setting->lessons->isEmpty()) {
            throw ValidationException::withMessages([
                'publish' => 'Add the day structure and lessons before publishing this timetable.',
            ]);
        }

        $report = $this->verification->verify($setting);

        if (! $report['clean']) {
            $failures = (int) $report['summary']['hard_failures'];
            throw ValidationException::withMessages([
                'publish' => "Publishing is blocked by {$failures} hard timetable ".str('failure')->plural($failures).'. Open Verification for details.',
            ]);
        }

        DB::transaction(function () use ($setting) {
            Setting::query()
                ->where('academic_year_id', $setting->academic_year_id)
                ->where('schedule_type', $setting->schedule_type)
                ->whereKeyNot($setting->id)
                ->update(['is_active' => false, 'is_published' => false]);

            $setting->update(['is_active' => true, 'is_published' => true]);
        });

        return redirect()
            ->route('admin.timetable.index', ['setting' => $setting->id])
            ->with('success', 'Timetable published to teachers, students, parents, and the mobile apps.');
    }
}
