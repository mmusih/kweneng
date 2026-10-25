<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\Tt\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassParticipationService
{
    public function update(Setting $setting, array $excluded, int $version): void
    {
        DB::transaction(function () use ($setting, $excluded, $version) {
            $setting = Setting::whereKey($setting->id)->lockForUpdate()->firstOrFail();
            if ((int) $setting->preparation_version !== $version) {
                throw ValidationException::withMessages(['classes' => 'This timetable changed in another window. Reload before saving.']);
            }
            $excluded = array_values(array_unique(array_map('intval', $excluded)));
            $valid = ClassModel::where('academic_year_id', $setting->academic_year_id)->pluck('id')->all();
            if (array_diff($excluded, $valid)) {
                throw ValidationException::withMessages(['classes' => 'Choose classes from this timetable’s academic year.']);
            }
            $restored = array_diff($setting->excluded_class_ids ?? [], $excluded);
            $setting->update(['excluded_class_ids' => $excluded]);

            if ($restored !== []) {
                $placement = app(CardPlacementService::class);
                $checker = (new ConflictChecker)->withPool(ConflictChecker::poolFor($setting->id));
                $lessons = $setting->lessons()->with(['classes', 'groups', 'cards', 'setting'])->get();
                foreach ($lessons as $lesson) {
                    if ($lesson->classes->pluck('id')->merge($lesson->groups->pluck('class_id'))->intersect($restored)->isEmpty()) {
                        continue;
                    }
                    foreach ($placement->unitsFor($lesson) as $unit) {
                        foreach ($unit->first()->daysMask()->positions() as $day) {
                            $conflicts = $checker->check($lesson, $day, $unit->startPeriod(), $unit->roomId(), $unit->cardIds());
                            if ($conflicts !== []) {
                                throw ValidationException::withMessages(['classes' => 'Cannot restore the saved lessons: '.$conflicts[0]->message.' Move the conflicting lesson or return it to the tray, then try again. No class changes were saved.']);
                            }
                        }
                    }
                }
            }
            $setting->increment('preparation_version');
        });
    }
}
