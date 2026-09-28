<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\Tt\Setting;
use App\Services\Timetable\AssignmentPreparationService;
use Illuminate\Http\Request;

class PreparationController extends Controller
{
    public function index(Request $request, Setting $setting, AssignmentPreparationService $preparation)
    {
        if ($request->expectsJson()) return response()->json($preparation->payload($setting));
        return view('admin.timetable.prepare', ['setting' => $setting, 'preparation' => $preparation->payload($setting)]);
    }

    public function store(Request $request, Setting $setting, AssignmentPreparationService $preparation)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'manual_baselines' => ['sometimes', 'array'],
            'manual_baselines.*' => ['string', 'size:64'],
            'units' => ['present', 'array', 'max:2000'],
            'units.*.key' => ['required', 'uuid', 'distinct'],
            'units.*.span' => ['required', 'integer', 'in:1,2'],
            'units.*.room_id' => ['nullable', 'integer'],
            'units.*.split_key' => ['nullable', 'uuid'],
            'units.*.sources' => ['required', 'array', 'min:1', 'max:12'],
            'units.*.sources.*.key' => ['required', 'string', 'max:80'],
            'units.*.sources.*.group_id' => ['nullable', 'integer'],
        ]);
        $preparation->save($setting, $data);

        return response()->json(['message' => 'Lesson counts saved. New cards are ready in the timetable tray.',
            'grid' => app(\App\Services\Timetable\GridPayload::class)->build($setting->fresh()),
        ] + $preparation->payload($setting->fresh()));
    }
}
