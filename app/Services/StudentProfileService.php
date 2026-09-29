<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;

class StudentProfileService
{
    public function overview(Request $request, Student $student): array
    {
        $request->validate(['term_id' => ['nullable', 'integer', 'exists:terms,id']]);
        $terms = Term::with('academicYear')->orderByDesc('start_date')->get();
        $term = $request->filled('term_id') ? $terms->firstWhere('id', (int) $request->term_id) : (Term::current() ?? $terms->first());
        $marks = $term ? $student->marks()->with(['subject', 'class'])->where('term_id', $term->id)->get() : collect();
        $averages = collect(['midterm_score', 'endterm_score'])->mapWithKeys(fn ($field) => [$field => $marks->pluck($field)->filter(fn ($score) => $score !== null)->avg()]);
        $attendance = $term ? $student->attendances()->where('term_id', $term->id)->get()->countBy('status') : collect();
        $behaviour = $term ? $student->behaviourRecords()->where('term_id', $term->id)->latest('record_date')->get() : collect();
        $record = app(StudentAcademicRecordService::class)->build($student);

        return compact('terms', 'term', 'marks', 'averages', 'attendance', 'behaviour', 'record');
    }
}
