<?php

namespace App\Services;

use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\Tt\Room;
use App\Models\Tt\Lesson;

class SubjectOptionPlanner
{
    public function resources(array $config): array
    {
        $ids = array_column($config['options'], 'subject_id');
        $subjects = Subject::whereIn('id', $ids)->where('is_active', true)->pluck('name', 'id')->all();
        $teachers = TeacherSubject::where('academic_year_id', $config['academic_year_id'])
            ->whereIn('class_id', $config['class_ids'])->whereIn('subject_id', $ids)
            ->whereHas('teacher.user', fn ($q) => $q->where('status', 'active'))
            ->get()->groupBy('subject_id')->map(fn ($rows) => $rows->pluck('teacher_id')->unique()->values()->all())->all();
        $choices = StudentSubject::where('academic_year_id', $config['academic_year_id'])
            ->whereIn('class_id', $config['class_ids'])->whereIn('subject_id', $ids)
            ->get()->groupBy('student_id')->map(fn ($rows) => $rows->pluck('subject_id')->unique()->values()->all())->all();
        $rooms = Room::where('tt_setting_id', $config['tt_setting_id'])->get()->keyBy('id');
        $lessons = Lesson::with('rooms')->where('tt_setting_id', $config['tt_setting_id'])
            ->whereIn('subject_id', $ids)->whereHas('classes', fn ($query) => $query->whereIn('classes.id', $config['class_ids']))
            ->get()->groupBy('subject_id');
        $groups = [];
        $warnings = ['Learner demand uses existing enrolments, not a new preference survey. Teacher availability outside these blocks and weekly lesson requirements still need timetable verification.'];
        if (!$choices) {
            $warnings[] = 'No learner selections for this cohort. Learner satisfaction cannot be scored.';
        }
        foreach ($config['options'] as $option) {
            $id = $option['subject_id'];
            $name = $subjects[$id] ?? 'Unavailable subject #'.$id;
            $roomIds = $option['room_ids'] ?? [];
            if (!$roomIds) {
                $roomIds = ($lessons[$id] ?? collect())->flatMap(fn ($lesson) => $lesson->rooms->pluck('id'))->unique()->values()->all();
                $warnings[] = $name.($roomIds ? ': rooms inferred from existing lessons; confirm specialist suitability.' : ': suitable rooms have not been confirmed; all rooms are considered provisionally.');
                $roomIds = $roomIds ?: $rooms->keys()->all();
            }
            $demand = count(array_filter($choices, fn ($selected) => in_array($id, $selected)));
            $estimate = max($demand, $option['demand'] ?? 0);
            if (!$estimate) {
                $warnings[] = $name.': no demand estimate; capacity is unverified.';
            }
            foreach (range(1, $option['groups']) as $number) {
                $groups[$id.'-'.$number] = [
                    'subject_id' => (int) $id,
                    'name' => $name.($option['groups'] > 1 ? ' · group '.$number : ''),
                    'unavailable' => !isset($subjects[$id]),
                    'teachers' => $teachers[$id] ?? [],
                    'rooms' => array_map('intval', $roomIds),
                    'demand' => (int) ceil($estimate / $option['groups']),
                ];
            }
        }

        return ['groups' => $groups, 'choices' => $choices, 'rooms' => $rooms->map(fn ($room) => ['name' => $room->name, 'capacity' => (int) $room->capacity])->all(), 'warnings' => $warnings];
    }

    /** A bounded search: alternatives are recommendations, not a proof of optimality. */
    public function generate(array $data, int $blocks): array
    {
        $candidates = [];
        $keys = array_keys($data['groups']);
        for ($attempt = 0; $attempt < 45; $attempt++) {
            if ($attempt) {
                shuffle($keys);
            }
            $assignment = [];
            foreach ($keys as $index => $key) {
                $assignment[$key] = $index % $blocks;
            }
            $assessment = $this->assess($data, $assignment, $blocks);
            // Improve by moving one group, always retaining every option block.
            for ($step = 0; $step < 8; $step++) {
                $key = $keys[array_rand($keys)];
                $trial = $assignment;
                $trial[$key] = random_int(0, $blocks - 1);
                $trialAssessment = $this->assess($data, $trial, $blocks);
                if ($this->rank($trialAssessment) < $this->rank($assessment)) {
                    $assignment = $trial;
                    $assessment = $trialAssessment;
                }
            }
            $signature = array_fill(0, $blocks, []);
            foreach ($assignment as $key => $block) {
                $signature[$block][] = $key;
            }
            foreach ($signature as &$members) {
                sort($members);
            }
            unset($members);
            sort($signature);
            $candidates[json_encode($signature)] = ['assignment' => $assignment, 'assessment' => $assessment];
        }
        usort($candidates, fn ($a, $b) => $this->rank($a['assessment']) <=> $this->rank($b['assessment']));

        return array_slice($candidates, 0, 3);
    }

    private function rank(array $assessment): array
    {
        return [$assessment['hard_conflicts'], $assessment['clashing_learners'], $assessment['imbalance']];
    }

    /** Maximum bipartite matching; each resource can serve one group per block. */
    private function matchResources(array $options): array
    {
        $owners = [];
        $visit = function ($key, array &$seen) use (&$visit, &$owners, $options): bool {
            foreach ($options[$key] as $resource) {
                if (isset($seen[$resource])) {
                    continue;
                }
                $seen[$resource] = true;
                if (!isset($owners[$resource]) || $visit($owners[$resource], $seen)) {
                    $owners[$resource] = $key;
                    return true;
                }
            }
            return false;
        };
        foreach (array_keys($options) as $key) {
            $seen = [];
            $visit($key, $seen);
        }

        return array_flip($owners);
    }

    public function assess(array $data, array $assignment, int $blocks): array
    {
        $loads = array_fill_keys(array_keys($data['groups']), 0);
        $clashes = 0;
        foreach ($data['choices'] as $choices) {
            $options = [];
            foreach ($choices as $subject) {
                $options[$subject] = [];
                foreach ($assignment as $key => $block) {
                    if ($data['groups'][$key]['subject_id'] === (int) $subject) {
                        $options[$subject][] = $block;
                    }
                }
                $options[$subject] = array_values(array_unique($options[$subject]));
            }
            $matched = $this->matchResources($options);
            if (count($matched) < count($choices)) {
                $clashes++;
            }
            foreach ($matched as $subject => $block) {
                $eligible = array_keys(array_filter($assignment, fn ($b, $key) => $b === $block && $data['groups'][$key]['subject_id'] === (int) $subject, ARRAY_FILTER_USE_BOTH));
                usort($eligible, fn ($a, $b) => $loads[$a] <=> $loads[$b]);
                $loads[$eligible[0]]++;
            }
        }
        $issues = [];
        $hard = 0;
        $allocations = [];
        $blockLoads = [];
        for ($block = 0; $block < $blocks; $block++) {
            $keys = array_keys(array_filter($assignment, fn ($value) => $value === $block));
            $label = chr(65 + $block);
            if (!$keys) {
                $issues[] = 'Block '.$label.' is empty.';
                $hard++;
            }
            $teacherOptions = $roomOptions = [];
            foreach ($keys as $key) {
                $group = $data['groups'][$key];
                if ($group['unavailable'] ?? false) {
                    $issues[] = $group['name'].': subject was removed or deactivated. Regenerate this plan.';
                    $hard++;
                }
                $required = max($loads[$key], $group['demand']);
                $teacherOptions[$key] = $group['teachers'];
                $roomOptions[$key] = array_values(array_filter($group['rooms'], fn ($id) => isset($data['rooms'][$id]) && $data['rooms'][$id]['capacity'] >= max(1, $required)));
                $blockLoads[$block] = ($blockLoads[$block] ?? 0) + $required;
            }
            $teachers = $this->matchResources($teacherOptions);
            $rooms = $this->matchResources($roomOptions);
            foreach ($keys as $key) {
                if (!isset($teachers[$key])) {
                    $issues[] = 'Block '.$label.' · '.$data['groups'][$key]['name'].': no distinct available teacher.';
                    $hard++;
                }
                if (!isset($rooms[$key])) {
                    $issues[] = 'Block '.$label.' · '.$data['groups'][$key]['name'].': no distinct suitable room with sufficient capacity.';
                    $hard++;
                }
                $allocations[$key] = ['teacher_id' => $teachers[$key] ?? null, 'room_id' => $rooms[$key] ?? null, 'learners' => max($loads[$key], $data['groups'][$key]['demand'])];
            }
        }
        $total = count($data['choices']);

        return [
            'score' => $total ? round(100 * ($total - $clashes) / $total, 1) : null,
            'learner_count' => $total, 'clashing_learners' => $clashes,
            'hard_conflicts' => $hard, 'issues' => $issues, 'allocations' => $allocations,
            'imbalance' => $blockLoads ? max($blockLoads) - min($blockLoads) : 0,
            'warnings' => $data['warnings'],
        ];
    }
}
