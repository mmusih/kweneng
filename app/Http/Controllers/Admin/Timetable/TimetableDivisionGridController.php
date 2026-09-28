<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Tt\Division;
use App\Models\Tt\Setting;
use App\Services\Timetable\GridPayload;
use App\Services\Timetable\SettingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TimetableDivisionGridController extends Controller
{
    public function __construct(
        private readonly SettingResolver $settings,
        private readonly GridPayload $payload,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'class_ids' => ['required', 'array', 'min:1', 'max:12'],
            'class_ids.*' => ['required', 'integer', 'distinct', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'groups' => ['required', 'array', 'min:2', 'max:12'],
            'groups.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
        ]);

        $setting = $this->setting($request);
        $classes = $this->timetableClasses($data['class_ids'], $setting);

        DB::transaction(function () use ($classes, $data) {
            $divisionKey = (string) Str::uuid();
            $groups = collect($data['groups'])->map(fn (string $name) => [
                'shared_key' => (string) Str::uuid(),
                'name' => trim($name),
            ]);

            foreach ($classes as $class) {
                $division = Division::create([
                    'class_id' => $class->id,
                    'division_tag' => $this->nextTag($class),
                    'name' => trim($data['name']),
                    'shared_key' => $divisionKey,
                ]);

                foreach ($groups as $group) {
                    $division->groups()->create([
                        'class_id' => $class->id,
                        'name' => $group['name'],
                        'shared_key' => $group['shared_key'],
                        'entire_class' => false,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Division added to '.$classes->pluck('name')->implode(', ').'.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    public function update(Request $request, Division $division): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'class_ids' => ['required', 'array', 'min:1', 'max:12'],
            'class_ids.*' => ['required', 'integer', 'distinct', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'groups' => ['required', 'array', 'min:2', 'max:12'],
            'groups.*.id' => ['nullable', 'integer', 'distinct', 'exists:tt_groups,id'],
            'groups.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
        ]);

        $setting = $this->setting($request);
        $this->divisionInSetting($division, $setting);
        $classes = $this->timetableClasses($data['class_ids'], $setting);

        DB::transaction(function () use ($division, $classes, $data) {
            $division = Division::query()->with('groups')->lockForUpdate()->findOrFail($division->id);
            $divisionKey = $division->shared_key ?: (string) Str::uuid();
            $division->update(['shared_key' => $divisionKey]);

            $family = Division::query()
                ->with('groups')
                ->where('shared_key', $divisionKey)
                ->lockForUpdate()
                ->get();
            $source = $family->firstWhere('id', $division->id);
            $sourceGroups = $source->groups->keyBy('id');
            $definitions = collect();

            foreach ($data['groups'] as $groupData) {
                $id = isset($groupData['id']) ? (int) $groupData['id'] : null;

                if ($id !== null) {
                    $group = $sourceGroups->get($id);

                    if ($group === null) {
                        throw ValidationException::withMessages(['groups' => 'Every group must belong to this division.']);
                    }

                    $groupKey = $group->shared_key ?: (string) Str::uuid();
                    $group->update(['shared_key' => $groupKey]);
                } else {
                    $groupKey = (string) Str::uuid();
                }

                $definitions->push([
                    'shared_key' => $groupKey,
                    'name' => trim($groupData['name']),
                ]);
            }

            $keptKeys = $definitions->pluck('shared_key');

            foreach ($family as $familyDivision) {
                foreach ($familyDivision->groups as $group) {
                    if ($group->shared_key !== null && $keptKeys->contains($group->shared_key)) {
                        continue;
                    }

                    if ($group->lessons()->exists()) {
                        throw ValidationException::withMessages([
                            'groups' => $group->name.' is used by a lesson. Reassign that lesson before removing the group.',
                        ]);
                    }

                    $group->delete();
                }
            }

            $requestedClassIds = $classes->pluck('id')->map(fn ($id) => (int) $id);

            foreach ($family as $familyDivision) {
                if ($requestedClassIds->contains((int) $familyDivision->class_id)) {
                    continue;
                }

                if ($familyDivision->groups()->whereHas('lessons')->exists()) {
                    throw ValidationException::withMessages([
                        'class_ids' => $familyDivision->class?->name.' has lessons using this division. Reassign them before removing the class.',
                    ]);
                }

                $familyDivision->delete();
            }

            foreach ($classes as $class) {
                $familyDivision = Division::query()
                    ->where('shared_key', $divisionKey)
                    ->where('class_id', $class->id)
                    ->first();

                if ($familyDivision === null) {
                    $familyDivision = Division::create([
                        'class_id' => $class->id,
                        'division_tag' => $this->nextTag($class),
                        'name' => trim($data['name']),
                        'shared_key' => $divisionKey,
                    ]);
                } else {
                    $familyDivision->update(['name' => trim($data['name'])]);
                }

                foreach ($definitions as $definition) {
                    $familyDivision->groups()->updateOrCreate(
                        ['shared_key' => $definition['shared_key']],
                        [
                            'class_id' => $class->id,
                            'name' => $definition['name'],
                            'entire_class' => false,
                        ],
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Shared division updated.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    public function destroy(Request $request, Division $division): JsonResponse
    {
        $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
        ]);

        $setting = $this->setting($request);
        $this->divisionInSetting($division, $setting);

        DB::transaction(function () use ($division) {
            $family = $division->shared_key
                ? Division::query()->where('shared_key', $division->shared_key)->with('groups')->get()
                : collect([$division->load('groups')]);

            foreach ($family as $familyDivision) {
                if ($familyDivision->groups()->whereHas('lessons')->exists()) {
                    throw ValidationException::withMessages([
                        'division' => 'This division is used by lessons. Reassign those lessons before deleting it.',
                    ]);
                }
            }

            Division::query()->whereKey($family->pluck('id'))->delete();
        });

        return response()->json([
            'message' => 'Division deleted from every linked class.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    private function setting(Request $request): Setting
    {
        return $this->settings->working($request->integer('setting_id') ?: null);
    }

    /** @param array<int, int|string> $classIds @return Collection<int, ClassModel> */
    private function timetableClasses(array $classIds, Setting $setting): Collection
    {
        $ids = collect($classIds)->map(fn ($id) => (int) $id)->unique()->values();
        $classes = ClassModel::query()->whereKey($ids)->orderBy('level')->orderBy('name')->get();

        if ($classes->count() !== $ids->count() || $classes->contains(
            fn (ClassModel $class) => (int) $class->academic_year_id !== (int) $setting->academic_year_id
        )) {
            throw ValidationException::withMessages(['class_ids' => 'Every selected class must belong to this timetable year.']);
        }

        return $classes;
    }

    private function nextTag(ClassModel $class): int
    {
        $nextTag = (int) Division::query()
            ->where('class_id', $class->id)
            ->lockForUpdate()
            ->max('division_tag') + 1;

        if ($nextTag > 255) {
            throw ValidationException::withMessages(['name' => $class->name.' cannot have any more divisions.']);
        }

        return $nextTag;
    }

    private function divisionInSetting(Division $division, Setting $setting): void
    {
        $division->loadMissing('class');

        if ((int) $division->class?->academic_year_id !== (int) $setting->academic_year_id) {
            throw ValidationException::withMessages(['division' => 'That division belongs to another academic year.']);
        }
    }
}
