<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\Student;
use App\Models\StudyEnrolment;
use App\Models\StudyRetentionSetting;
use App\Models\StudyRule;
use App\Models\Term;
use Illuminate\Support\Collection;

class StudyRetentionService
{
    /** Rules belong to the attendance term; marks can come from an earlier term. */
    public function report(Term $term, ?Collection $studentIds = null): Collection
    {
        $setting = StudyRetentionSetting::where('term_id', $term->id)->first();
        $sourceTermId = $setting?->source_term_id ?? $term->id;
        $scoreColumn = ($setting?->assessment ?? 'endterm').'_score';
        $rules = StudyRule::where('term_id', $term->id)->get();
        $classRules = $rules->where('scope_type', 'class')->keyBy('scope_value');
        $formRules = $rules->where('scope_type', 'form')->keyBy('scope_value');
        $students = Student::with(['user', 'currentClass'])
            ->whereHas('currentClass', fn ($q) => $q->where('academic_year_id', $term->academic_year_id))
            ->when($studentIds !== null, fn ($q) => $q->whereIn('id', $studentIds))
            ->get();
        $marks = Mark::where('term_id', $sourceTermId)->whereIn('student_id', $students->pluck('id'))
            ->with('subject')->get()->groupBy('student_id');
        $volunteers = StudyEnrolment::where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))->pluck('student_id')->flip();

        return $students->map(function (Student $student) use ($marks, $volunteers, $classRules, $formRules, $scoreColumn) {
            $class = $student->currentClass;
            $rule = $classRules->get((string) $class->id) ?? $formRules->get((string) $class->level);
            $scored = $marks->get($student->id, collect())->filter(fn (Mark $mark) => $mark->$scoreColumn !== null);
            $average = $scored->isEmpty() ? null : (float) $scored->avg($scoreColumn);
            $overall = $rule && $rule->overall_enabled && $average !== null && $average < (float) $rule->overall_threshold;
            $subjects = $rule?->subject_enabled
                ? $scored->filter(fn (Mark $mark) => $mark->$scoreColumn < (float) $rule->subject_threshold)
                    ->map(fn (Mark $mark) => [
                        'name' => $mark->subject?->name ?? 'Subject',
                        'score' => round((float) $mark->$scoreColumn, 1),
                    ])->sortBy('name')->values()
                : collect();
            $voluntary = $volunteers->has($student->id);
            if (! $overall && $subjects->isEmpty() && ! $voluntary) {
                return null;
            }
            return [
                'student' => $student, 'class' => $class,
                'average' => $average === null ? null : round($average, 1),
                'overall' => (bool) $overall,
                'overall_threshold' => (float) ($rule?->overall_threshold ?? 0),
                'subjects' => $subjects,
                'subject_threshold' => (float) ($rule?->subject_threshold ?? 0),
                'rule' => $rule, 'voluntary' => $voluntary,
            ];
        })->filter()->sortBy(fn ($row) => $row['class']->name.'|'.$row['student']->user?->name)->values();
    }
}
