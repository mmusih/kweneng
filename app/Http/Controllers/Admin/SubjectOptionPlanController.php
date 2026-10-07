<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\SubjectOptionPlan;
use App\Models\Teacher;
use App\Models\Tt\Setting;
use App\Services\SubjectOptionPlanner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectOptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::with('academicYear')->orderByDesc('id')->get();
        $source = $request->filled('from') ? SubjectOptionPlan::findOrFail($request->integer('from')) : null;
        $defaults = $source?->configuration ?? [];
        $setting = $settings->firstWhere('id', $request->old('tt_setting_id', $source?->tt_setting_id ?? $request->integer('setting'))) ?? $settings->first();
        $classes = ClassModel::query()
            ->when($setting, fn ($q) => $q->where(fn ($q) => $q->where('academic_year_id', $setting->academic_year_id)->orWhereNull('academic_year_id')))
            ->orderBy('level')->orderBy('name')->get();

        return view('admin.subject-option-plans.index', [
            'settings' => $settings, 'setting' => $setting, 'classes' => $classes,
            'defaults' => $defaults, 'source' => $source,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'rooms' => $setting?->rooms()->orderBy('name')->get() ?? collect(),
            'plans' => SubjectOptionPlan::latest('id')->limit(30)->get(),
        ]);
    }

    public function generate(Request $request, SubjectOptionPlanner $planner)
    {
        $input = $request->validate([
            'name' => 'required|string|max:120', 'tt_setting_id' => 'required|integer|exists:tt_settings,id',
            'class_ids' => 'required|array|min:1|max:20', 'class_ids.*' => 'required|integer|distinct|exists:classes,id',
            'block_count' => 'required|integer|min:2|max:8',
            'subjects' => 'required|array|min:1|max:60',
            'subjects.*.subject_id' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')->where('is_active', true)],
            'subjects.*.use' => ['required', Rule::in(['exclude', 'core', 'option'])],
            'subjects.*.groups' => 'required|integer|min:1|max:4',
            'subjects.*.demand' => 'nullable|integer|min:0|max:2000',
            'subjects.*.room_ids' => 'sometimes|array|max:100',
            'subjects.*.room_ids.*' => 'integer|exists:tt_rooms,id',
        ]);
        $setting = Setting::findOrFail($input['tt_setting_id']);
        if (!$setting->academic_year_id) {
            throw ValidationException::withMessages(['tt_setting_id' => 'Link this timetable to an academic year first.']);
        }
        $classes = ClassModel::whereIn('id', $input['class_ids'])->get();
        if ($classes->count() !== count($input['class_ids']) || $classes->contains(fn ($class) => $class->academic_year_id && $class->academic_year_id != $setting->academic_year_id)) {
            throw ValidationException::withMessages(['class_ids' => 'Choose classes belonging to this academic year.']);
        }
        $options = array_values(array_filter($input['subjects'], fn ($subject) => $subject['use'] === 'option'));
        $roomIds = $setting->rooms()->pluck('id')->all();
        foreach ($options as $option) {
            if (array_diff($option['room_ids'] ?? [], $roomIds)) {
                throw ValidationException::withMessages(['subjects' => 'Choose rooms belonging to this timetable.']);
            }
        }
        $groupCount = array_sum(array_column($options, 'groups'));
        if (!$options || $groupCount < $input['block_count'] || $groupCount > 40) {
            throw ValidationException::withMessages(['subjects' => 'Select enough option groups to fill every block, with at most 40 groups in total.']);
        }
        $config = [
            'academic_year_id' => $setting->academic_year_id, 'tt_setting_id' => $setting->id,
            'class_ids' => $input['class_ids'], 'block_count' => (int) $input['block_count'],
            'compulsory_ids' => array_column(array_filter($input['subjects'], fn ($subject) => $subject['use'] === 'core'), 'subject_id'),
            'options' => $options,
        ];
        $data = $planner->resources($config);
        $ids = [];
        // Persist the best candidate last so the newest-first list shows it first.
        foreach (array_reverse($planner->generate($data, $config['block_count']), true) as $index => $candidate) {
            $ids[] = SubjectOptionPlan::create([
                'name' => $input['name'].' · Alternative '.($index + 1), 'created_by' => $request->user()->id,
                'academic_year_id' => $setting->academic_year_id, 'tt_setting_id' => $setting->id,
                'configuration' => $config, 'arrangement' => $candidate['assignment'], 'assessment' => $candidate['assessment'], 'status' => 'draft',
            ])->id;
        }

        return redirect()->route('admin.subject-option-plans.index', ['setting' => $setting->id])->with('success', count($ids).' distinct suggestions saved as drafts. Open an arrangement to modify, rescore, or accept it.');
    }

    public function show(SubjectOptionPlan $plan, SubjectOptionPlanner $planner)
    {
        $data = $planner->resources($plan->configuration);

        return view('admin.subject-option-plans.show', [
            'plan' => $plan, 'data' => $data,
            'cohort' => ClassModel::whereIn('id', $plan->configuration['class_ids'])->pluck('name')->implode(', '),
            'assessment' => $planner->assess($data, $plan->arrangement, $plan->configuration['block_count']),
            'teachers' => Teacher::with('user')->get()->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->user?->name ?? 'Unavailable teacher']),
        ]);
    }

    public function update(Request $request, SubjectOptionPlan $plan, SubjectOptionPlanner $planner)
    {
        $config = $plan->configuration;
        $data = $planner->resources($config);
        $input = $request->validate([
            'arrangement' => 'required|array',
            'arrangement.*' => 'required|integer|min:0|max:'.($config['block_count'] - 1),
            'action' => ['required', Rule::in(['save', 'accept'])], 'acknowledge' => 'sometimes|accepted',
        ]);
        $expected = array_keys($data['groups']);
        $actual = array_keys($input['arrangement']);
        sort($expected);
        sort($actual);
        if ($actual !== $expected) {
            throw ValidationException::withMessages(['arrangement' => 'Every subject group must appear exactly once.']);
        }
        $arrangement = array_map('intval', $input['arrangement']);
        $assessment = $planner->assess($data, $arrangement, $config['block_count']);
        if ($input['action'] === 'accept' && ($assessment['hard_conflicts'] || !$request->boolean('acknowledge'))) {
            throw ValidationException::withMessages(['arrangement' => 'Resolve teacher, room and empty-block conflicts, then acknowledge the learner clashes and planning assumptions before accepting.']);
        }
        $plan->update(['arrangement' => $arrangement, 'assessment' => $assessment, 'status' => $input['action'] === 'accept' ? 'accepted' : 'draft']);

        return redirect()->route('admin.subject-option-plans.show', $plan)->with('success', $input['action'] === 'accept' ? 'Plan accepted. Timetable and learner records have not been changed.' : 'Draft saved and rescored using current school records.');
    }
}
