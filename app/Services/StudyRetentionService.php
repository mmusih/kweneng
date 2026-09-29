<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\StudyRule;
use App\Models\Term;
use Illuminate\Support\Collection;

class StudyRetentionService
{
    /** @return Collection<int, array<string, mixed>> */
    public function report(Term $term): Collection
    {
        $rules = StudyRule::query()->where('term_id', $term->id)->get();
        $classRules = $rules->where('scope_type', 'class')->keyBy('scope_value');
        $formRules = $rules->where('scope_type', 'form')->keyBy('scope_value');

        return Mark::query()
            ->where('term_id', $term->id)
            ->with(['student.user', 'class', 'subject'])
            ->get()
            ->groupBy(fn (Mark $mark) => $mark->class_id.':'.$mark->student_id)
            ->map(function (Collection $marks) use ($classRules, $formRules) {
                /** @var Mark $first */
                $first = $marks->first();
                $class = $first->class;
                $rule = $classRules->get((string) $first->class_id)
                    ?? $formRules->get((string) $class?->level);

                if (! $rule) {
                    return null;
                }

                $scored = $marks->filter(fn (Mark $mark) => $mark->average !== null);
                if ($scored->isEmpty()) {
                    return null;
                }

                $average = (float) $scored->avg(fn (Mark $mark) => $mark->average);
                $overall = $rule->overall_enabled && $average < (float) $rule->overall_threshold;
                $subjects = $rule->subject_enabled
                    ? $scored->filter(fn (Mark $mark) => $mark->average < (float) $rule->subject_threshold)
                        ->map(fn (Mark $mark) => [
                            'name' => $mark->subject?->name ?? 'Subject',
                            'score' => round((float) $mark->average, 1),
                        ])->sortBy('name')->values()
                    : collect();

                if (! $overall && $subjects->isEmpty()) {
                    return null;
                }

                return [
                    'student' => $first->student,
                    'class' => $class,
                    'average' => round($average, 1),
                    'overall' => $overall,
                    'overall_threshold' => (float) $rule->overall_threshold,
                    'subjects' => $subjects,
                    'subject_threshold' => (float) $rule->subject_threshold,
                    'rule' => $rule,
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) => ($row['class']?->name ?? '').'|'.($row['student']?->user?->name ?? ''))
            ->values();
    }
}
