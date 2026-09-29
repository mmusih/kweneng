<?php

namespace App\Services;

use App\Models\AwardRun;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Support\Collection;

class AwardCalculationService
{
    public function generate(AwardRun $run): array
    {
        if ($run->scope_type === 'all_levels') {
            $combined = ['recipients' => collect(), 'ranked' => collect(), 'excluded' => collect(), 'components' => [], 'considered_count' => 0];
            $levels = ClassModel::where('academic_year_id', $run->academic_year_id)->whereNotNull('level')->distinct()->orderBy('level')->pluck('level');
            foreach ($levels as $level) {
                $levelRun = clone $run;
                $levelRun->scope_type = 'level';
                $levelRun->level = $level;
                $result = $this->generate($levelRun);
                $combined['recipients'] = $combined['recipients']->concat($result['recipients']);
                $combined['ranked'] = $combined['ranked']->concat($result['ranked']);
                $combined['excluded'] = $combined['excluded']->concat($result['excluded']);
                $combined['components'] = $result['components'];
                $combined['considered_count'] += $result['considered_count'];
            }

            return $combined;
        }

        $classes = $this->classesFor($run);
        $currentSubjectId = (int) ($run->calculation_config['current_subject_id'] ?? 0);

        if (($run->calculation_config['ranking_basis'] ?? 'overall') === 'subject' && $currentSubjectId === 0) {
            return $this->generatePerSubject($run, $classes);
        }

        $registeredStudentIds = $currentSubjectId > 0
            ? StudentSubject::where('academic_year_id', $run->academic_year_id)
                ->where('subject_id', $currentSubjectId)
                ->whereIn('class_id', $classes->pluck('id'))
                ->pluck('student_id')
            : null;
        $students = Student::query()
            ->with(['user', 'currentClass'])
            ->whereIn('current_class_id', $classes->pluck('id'))
            ->when($registeredStudentIds !== null, fn ($query) => $query->whereIn('id', $registeredStudentIds))
            ->get();

        $components = $this->mainComponents($run);
        $tieBreakers = collect($run->calculation_config['tie_breakers'] ?? [])->values();
        $excluded = collect();

        $ranked = $students->map(function (Student $student) use ($run, $components, $tieBreakers, $excluded) {
            $main = $this->combinedScore($student, $run, $components);
            if ($main['score'] === null || ($main['incomplete'] && $run->missing_marks_policy === 'exclude')) {
                $excluded->push([
                    'student_id' => $student->id,
                    'student_name' => $student->user?->name ?? 'Unknown Student',
                    'class_name' => $student->currentClass?->name ?? 'Class not assigned',
                    'subject_name' => $run->calculation_config['current_subject_name'] ?? null,
                    'reason' => $main['score'] === null ? 'No usable marks' : 'Incomplete required marks',
                    'missing' => $main['missing'],
                ]);

                return null;
            }

            $tieScores = $tieBreakers->map(function (array $component) use ($student, $run) {
                $result = $this->componentScore($student, $run, $component);

                return [
                    'label' => $component['label'] ?? $this->tieBreakerLabel($run, $component),
                    'score' => $result['score'],
                ];
            })->all();

            return [
                'student' => $student,
                'student_id' => $student->id,
                'student_name' => $student->user?->name ?? 'Unknown Student',
                'subject_id' => $run->calculation_config['current_subject_id'] ?? null,
                'subject_name' => $run->calculation_config['current_subject_name'] ?? null,
                'award_title' => isset($run->calculation_config['current_subject_name'])
                    ? 'Best in '.$run->calculation_config['current_subject_name']
                    : $run->title,
                'main_score' => round($main['score'], 2),
                'tie_breakers' => $tieScores,
                'incomplete' => $main['incomplete'],
            ];
        })->filter()->sort(function (array $a, array $b) {
            $comparison = $b['main_score'] <=> $a['main_score'];
            if ($comparison !== 0) {
                return $comparison;
            }
            $count = max(count($a['tie_breakers']), count($b['tie_breakers']));
            for ($i = 0; $i < $count; $i++) {
                $comparison = ($b['tie_breakers'][$i]['score'] ?? -1) <=> ($a['tie_breakers'][$i]['score'] ?? -1);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcmp($a['student_name'], $b['student_name']);
        })->values();

        $lastRankKey = null;
        $position = 0;
        $ranked = $ranked->map(function (array $row, int $index) use (&$lastRankKey, &$position) {
            $rankKey = json_encode([$row['main_score'], collect($row['tie_breakers'])->pluck('score')->all()]);
            if ($rankKey !== $lastRankKey) {
                $position = $index + 1;
                $lastRankKey = $rankKey;
            }
            $row['position'] = $position;

            return $row;
        });

        $qualified = $ranked->filter(fn (array $row) => $row['main_score'] >= (float) $run->cutoff_percentage && $row['position'] <= (int) $run->positions
        )->values();

        if ($run->tie_policy === 'strict_count') {
            $qualified = $qualified->take((int) $run->positions)->values();
        }

        return [
            'recipients' => $qualified,
            'ranked' => $ranked,
            'excluded' => $excluded->values(),
            'components' => $components,
            'considered_count' => $students->count(),
        ];
    }

    public function calculationDescription(AwardRun $run): string
    {
        $description = match ($run->calculation_mode) {
            'selected_midterm' => ($run->term?->name ?? 'Selected term').' midterm average',
            'selected_endterm' => ($run->term?->name ?? 'Selected term').' end-of-term average',
            'selected_term_average' => 'Average of midterm and end-of-term for '.($run->term?->name ?? 'the selected term'),
            'cumulative_to_term' => 'Cumulative average through '.($run->term?->name ?? 'the selected term'),
            'all_terms' => 'Average across all terms in '.$run->academicYear?->year_name,
            'custom' => 'Custom weighted assessment combination',
            default => 'Academic average',
        };

        return ($run->calculation_config['ranking_basis'] ?? 'overall') === 'subject'
            ? 'Top students per subject using '.$description
            : $description;
    }

    private function generatePerSubject(AwardRun $run, Collection $classes): array
    {
        $configuredSubjectIds = collect($run->calculation_config['subject_ids'] ?? [])->filter()->map(fn ($id) => (int) $id);
        $subjectIds = StudentSubject::query()
            ->where('academic_year_id', $run->academic_year_id)
            ->whereIn('class_id', $classes->pluck('id'))
            ->when($configuredSubjectIds->isNotEmpty(), fn ($query) => $query->whereIn('subject_id', $configuredSubjectIds))
            ->distinct()
            ->pluck('subject_id');
        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
        $combined = ['recipients' => collect(), 'ranked' => collect(), 'excluded' => collect(), 'components' => [], 'considered_count' => 0];

        foreach ($subjects as $subject) {
            $subjectRun = clone $run;
            $subjectRun->calculation_config = [
                ...($run->calculation_config ?? []),
                'ranking_basis' => 'overall',
                'current_subject_id' => $subject->id,
                'current_subject_name' => $subject->name,
            ];
            $result = $this->generate($subjectRun);
            $combined['recipients'] = $combined['recipients']->concat($result['recipients']);
            $combined['ranked'] = $combined['ranked']->concat($result['ranked']);
            $combined['excluded'] = $combined['excluded']->concat($result['excluded']);
            $combined['components'] = $result['components'];
            $combined['considered_count'] += $result['considered_count'];
        }

        return $combined;
    }

    private function classesFor(AwardRun $run): Collection
    {
        return ClassModel::query()
            ->where('academic_year_id', $run->academic_year_id)
            ->when($run->scope_type === 'class', fn ($q) => $q->whereKey($run->class_id))
            ->when($run->scope_type === 'level', fn ($q) => $q->where('level', $run->level))
            ->when($run->scope_type === 'selected', fn ($q) => $q->whereIn('id', $run->calculation_config['class_ids'] ?? []))
            ->get();
    }

    private function mainComponents(AwardRun $run): array
    {
        if ($run->calculation_mode === 'custom') {
            return $run->calculation_config['components'] ?? [];
        }

        $selected = $run->term;
        $terms = Term::where('academic_year_id', $run->academic_year_id)->orderBy('start_date')->orderBy('id')->get();

        return match ($run->calculation_mode) {
            'selected_midterm' => [['term_id' => $selected?->id, 'assessment' => 'midterm', 'weight' => 1]],
            'selected_endterm' => [['term_id' => $selected?->id, 'assessment' => 'endterm', 'weight' => 1]],
            'selected_term_average' => [
                ['term_id' => $selected?->id, 'assessment' => 'midterm', 'weight' => 1],
                ['term_id' => $selected?->id, 'assessment' => 'endterm', 'weight' => 1],
            ],
            'cumulative_to_term' => $terms->filter(fn ($term) => $selected && ($term->start_date <= $selected->start_date || $term->id === $selected->id))
                ->flatMap(fn ($term) => [
                    ['term_id' => $term->id, 'assessment' => 'midterm', 'weight' => 1],
                    ['term_id' => $term->id, 'assessment' => 'endterm', 'weight' => 1],
                ])->values()->all(),
            'all_terms' => $terms->flatMap(fn ($term) => [
                ['term_id' => $term->id, 'assessment' => 'midterm', 'weight' => 1],
                ['term_id' => $term->id, 'assessment' => 'endterm', 'weight' => 1],
            ])->values()->all(),
            default => [],
        };
    }

    private function combinedScore(Student $student, AwardRun $run, array $components): array
    {
        $weighted = 0;
        $weightTotal = 0;
        $incomplete = false;
        $missing = [];

        foreach ($components as $component) {
            $result = $this->componentScore($student, $run, $component);
            $missing = [...$missing, ...$result['missing']];
            if ($result['score'] === null) {
                $incomplete = true;

                continue;
            }
            $incomplete = $incomplete || $result['incomplete'];
            $weight = max(0.01, (float) ($component['weight'] ?? 1));
            $weighted += $result['score'] * $weight;
            $weightTotal += $weight;
        }

        return [
            'score' => $weightTotal > 0 ? $weighted / $weightTotal : null,
            'incomplete' => $incomplete,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    private function componentScore(Student $student, AwardRun $run, array $component): array
    {
        $termId = (int) ($component['term_id'] ?? 0);
        $column = ($component['assessment'] ?? 'endterm') === 'midterm' ? 'midterm_score' : 'endterm_score';
        $subjectIds = StudentSubject::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $run->academic_year_id)
            ->when(! empty($run->calculation_config['current_subject_id']), fn ($query) => $query->where('subject_id', $run->calculation_config['current_subject_id']))
            ->pluck('subject_id')->unique();

        $marks = Mark::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $run->academic_year_id)
            ->where('term_id', $termId)
            ->whereIn('subject_id', $subjectIds)
            ->get(['subject_id', $column]);
        $scores = $marks->pluck($column)->filter(fn ($score) => $score !== null);
        $markedSubjectIds = $marks
            ->filter(fn (Mark $mark) => $mark->{$column} !== null)
            ->pluck('subject_id')
            ->unique();
        $missingSubjectIds = $subjectIds->diff($markedSubjectIds);
        $subjectNames = Subject::whereIn('id', $missingSubjectIds)->pluck('name', 'id');
        $componentLabel = $component['label'] ?? $this->componentLabel($component);
        $missing = $subjectIds->isEmpty()
            ? ["{$componentLabel}: no registered subjects"]
            : $missingSubjectIds->map(fn ($subjectId) => "{$componentLabel}: ".($subjectNames[$subjectId] ?? "subject #{$subjectId}").' mark missing')->values()->all();

        return [
            'score' => $scores->isNotEmpty() ? (float) $scores->avg() : null,
            'incomplete' => $subjectIds->isEmpty() || $scores->count() < $subjectIds->count(),
            'missing' => $missing,
        ];
    }

    private function componentLabel(array $component): string
    {
        $term = Term::find($component['term_id'] ?? null);

        return trim(($term?->name ?? 'Term').' '.(($component['assessment'] ?? 'endterm') === 'midterm' ? 'Midterm' : 'End-term'));
    }

    private function tieBreakerLabel(AwardRun $run, array $component): string
    {
        $label = $this->componentLabel($component);
        $subjectName = $run->calculation_config['current_subject_name'] ?? null;

        return $subjectName ? $label.' '.$subjectName.' mark' : $label.' average';
    }
}
