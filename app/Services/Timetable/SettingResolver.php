<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\Tt\Setting;
use RuntimeException;

/**
 * Which timetable the admin is editing.
 *
 * `Setting::current()` deliberately only returns a *published* timetable, because that is
 * what students and parents should be shown. The editor needs the opposite: the draft
 * being worked on. An explicit id wins, then the active setting for the current year,
 * then the newest one — and if there is none at all, the editor makes it rather than
 * showing an empty screen with nothing to click.
 */
class SettingResolver
{
    public function working(?int $settingId = null): Setting
    {
        if ($settingId !== null) {
            return Setting::query()->findOrFail($settingId);
        }

        $year = AcademicYear::current() ?? AcademicYear::query()->latest('id')->first();

        if ($year === null) {
            throw new RuntimeException('Create an academic year before building a timetable.');
        }

        return Setting::query()
            ->where('academic_year_id', $year->id)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first()
            ?? Setting::create([
                'academic_year_id' => $year->id,
                'name' => 'Master Timetable',
                'term_label' => $year->year_name,
                'revision' => 1,
                'schedule_type' => Setting::TYPE_DAY,
                'cycle_length' => 6,
                'cycle_anchor_date' => now()->startOfWeek(),
                'cycle_anchor_day' => 1,
                'is_active' => true,
                'is_published' => false,
            ]);
    }

    /**
     * Every timetable of the current year, newest first — for the picker in the editor.
     *
     * @return \Illuminate\Support\Collection<int, Setting>
     */
    public function options(): \Illuminate\Support\Collection
    {
        return Setting::query()
            ->with('academicYear')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
    }
}
