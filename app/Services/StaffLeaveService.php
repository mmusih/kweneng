<?php

namespace App\Services;

use App\Models\StaffLeaveAllowance;
use App\Models\StaffLeaveHoliday;
use App\Models\StaffLeaveRequest;
use App\Models\StaffLeaveType;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class StaffLeaveService
{
    public function halfDays(StaffLeaveType $type, string $start, string $end, string $portion): int
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->startOfDay();
        if ($from->year !== $to->year) {
            $this->invalid('Split leave across calendar years into separate requests.');
        }
        if ($portion !== 'full' && ! $from->equalTo($to)) {
            $this->invalid('Half-day leave must start and end on the same date.');
        }
        $holidays = $type->exclude_holidays ? StaffLeaveHoliday::whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->all() : [];
        $count = 0;
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if (in_array($day->dayOfWeekIso, $type->working_days) && ! in_array($day->format('Y-m-d'), $holidays, true)) {
                $count += 2;
            }
        }
        if ($count === 0) {
            $this->invalid('The selected dates contain no leave days under this leave type’s calendar.');
        }

        return $portion === 'full' ? $count : 1;
    }

    public function checkBalance(int $staffId, int $typeId, int $year, int $requested, ?int $exclude = null): void
    {
        $allowance = StaffLeaveAllowance::where('staff_profile_id', $staffId)->where('staff_leave_type_id', $typeId)->where('year', $year)->lockForUpdate()->first();
        if (! $allowance) {
            $this->invalid('HR must allocate a leave allowance for this type and year first.');
        }
        $reserved = StaffLeaveRequest::where('staff_profile_id', $staffId)->where('staff_leave_type_id', $typeId)->whereYear('starts_on', $year)->where('uses_allowance', true)->whereIn('status', ['submitted', 'approved'])->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))->sum('half_days');
        if ($reserved + $requested > $allowance->half_days) {
            $this->invalid('Insufficient leave balance after approved and pending requests.');
        }
    }

    public function invalid(string $message): never
    {
        throw ValidationException::withMessages(['leave' => $message]);
    }
}
