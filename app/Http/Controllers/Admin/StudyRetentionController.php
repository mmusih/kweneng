<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\StudyRule;
use App\Models\Term;
use App\Services\StudyRetentionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudyRetentionController extends Controller
{
    public function index(Request $request, StudyRetentionService $service)
    {
        $terms = Term::with('academicYear')->latest('start_date')->get();
        $term = $request->integer('term_id')
            ? Term::findOrFail($request->integer('term_id'))
            : (Term::current() ?? $terms->first());
        $classes = $term
            ? ClassModel::where('academic_year_id', $term->academic_year_id)->orderBy('level')->orderBy('name')->get()
            : collect();
        $rules = $term ? StudyRule::where('term_id', $term->id)->get() : collect();
        $rows = $term ? $service->report($term) : collect();

        return view('admin.study-retention.index', compact('terms', 'term', 'classes', 'rules', 'rows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'scope_type' => ['required', Rule::in(['form', 'class'])],
            'scope_value' => ['required', 'string', 'max:255'],
            'overall_enabled' => ['nullable', 'boolean'],
            'overall_threshold' => ['required', 'numeric', 'between:0,100'],
            'subject_enabled' => ['nullable', 'boolean'],
            'subject_threshold' => ['required', 'numeric', 'between:0,100'],
        ]);
        $term = Term::findOrFail($data['term_id']);

        if ($data['scope_type'] === 'class') {
            $valid = ClassModel::whereKey((int) $data['scope_value'])
                ->where('academic_year_id', $term->academic_year_id)->exists();
        } else {
            $valid = ClassModel::where('academic_year_id', $term->academic_year_id)
                ->where('level', $data['scope_value'])->exists();
        }

        if (! $valid) {
            throw ValidationException::withMessages(['scope_value' => 'Choose a valid form or class for this term.']);
        }

        $overallEnabled = $request->boolean('overall_enabled');
        $subjectEnabled = $request->boolean('subject_enabled');
        if (! $overallEnabled && ! $subjectEnabled) {
            throw ValidationException::withMessages(['rules' => 'Enable at least one study condition.']);
        }

        StudyRule::updateOrCreate(
            ['term_id' => $term->id, 'scope_type' => $data['scope_type'], 'scope_value' => $data['scope_value']],
            [
                'academic_year_id' => $term->academic_year_id,
                'overall_enabled' => $overallEnabled,
                'overall_threshold' => $data['overall_threshold'],
                'subject_enabled' => $subjectEnabled,
                'subject_threshold' => $data['subject_threshold'],
            ]
        );

        return redirect()->route('admin.study-retention.index', ['term_id' => $term->id])
            ->with('success', 'Study conditions saved.');
    }

    public function destroy(StudyRule $studyRule)
    {
        $termId = $studyRule->term_id;
        $studyRule->delete();

        return redirect()->route('admin.study-retention.index', ['term_id' => $termId])
            ->with('success', 'Study conditions removed.');
    }

    public function print(Request $request, StudyRetentionService $service)
    {
        $term = Term::with('academicYear')->findOrFail($request->integer('term_id'));
        $rows = $service->report($term);

        return view('admin.study-retention.print', compact('term', 'rows'));
    }
}
