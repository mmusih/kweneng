<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Services\Timetable\DayStructureRefused;
use App\Services\Timetable\DayStructureService;
use App\Services\Timetable\SettingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "How many periods in a day?" — the one number the grid layout is built from.
 *
 * Everything else on this form has a sane default, because the point is that nobody has
 * to define sixteen period times by hand before they can start dragging cards.
 */
class PeriodController extends Controller
{
    public function __construct(
        private readonly DayStructureService $structure,
        private readonly SettingResolver $settings,
    ) {}

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'periods_per_day' => ['required', 'integer', 'min:1', 'max:20'],
            'days_per_cycle' => ['required', 'integer', 'min:1', 'max:14'],
            'first_period_start' => ['required', 'date_format:H:i'],
            'period_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'breaks' => ['array', 'max:6'],
            'breaks.*.after_period' => ['required', 'integer', 'min:1'],
            'breaks.*.minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'breaks.*.name' => ['nullable', 'string', 'max:60'],
            'breaks.*.short_name' => ['nullable', 'string', 'max:20'],
        ]);

        $setting = $this->settings->working($data['setting_id'] ?? null);

        try {
            $periods = $this->structure->apply(
                $setting,
                periodsPerDay: $data['periods_per_day'],
                daysPerCycle: $data['days_per_cycle'],
                firstPeriodStart: $data['first_period_start'],
                periodMinutes: $data['period_minutes'],
                breaks: $data['breaks'] ?? [],
            );
        } catch (DayStructureRefused $refused) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $refused->getMessage(),
                    'occupied_periods' => $refused->occupiedPeriods,
                    'occupied_days' => $refused->occupiedDays,
                ], 422);
            }

            throw ValidationException::withMessages(['periods_per_day' => $refused->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'setting_id' => $setting->id,
                'cycle_length' => (int) $setting->fresh()->cycle_length,
                'periods' => $periods->map(fn ($period) => [
                    'number' => (int) $period->period_number,
                    'name' => $period->name,
                    'short_name' => $period->short_name,
                    'start' => substr((string) $period->start_time, 0, 5),
                    'end' => substr((string) $period->end_time, 0, 5),
                ])->values(),
            ]);
        }

        return redirect()
            ->route('admin.timetable.grid', ['setting' => $setting->id])
            ->with('success', $periods->count().' periods a day, '.$setting->fresh()->cycle_length.' days a cycle.');
    }
}
