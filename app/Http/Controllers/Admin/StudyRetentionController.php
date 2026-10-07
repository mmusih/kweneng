<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\StudyRule;
use App\Models\Student;
use App\Models\StudyEnrolment;
use App\Models\StudyRetentionSetting;
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
        $selection = $term ? StudyRetentionSetting::with('sourceTerm.academicYear')->where('term_id', $term->id)->first() : null;
        $students = $term ? Student::with(['user', 'currentClass'])
            ->whereHas('currentClass', fn ($q) => $q->where('academic_year_id', $term->academic_year_id))
            ->get()->sortBy(fn ($student) => $student->user?->name) : collect();
        $enrolments = $term ? StudyEnrolment::with('student.user')->where('term_id', $term->id)->get() : collect();

        return view('admin.study-retention.index', compact('terms', 'term', 'classes', 'rules', 'rows', 'selection', 'students', 'enrolments'));
    }

    public function selection(Request $request)
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'source_term_id' => ['required', 'integer', 'exists:terms,id'],
            'assessment' => ['required', Rule::in(['midterm', 'endterm'])],
        ]);
        $term = Term::findOrFail($data['term_id']);
        $source = Term::findOrFail($data['source_term_id']);
        if ($source->start_date->gt($term->start_date)) {
            throw ValidationException::withMessages(['source_term_id' => 'Choose this term or an earlier results term.']);
        }
        StudyRetentionSetting::updateOrCreate(['term_id' => $term->id], $data);
        return redirect()->route('admin.study-retention.index', ['term_id' => $term->id])
            ->with('success', 'Study results selection saved.');
    }

    public function enrol(Request $request)
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);
        $term = Term::findOrFail($data['term_id']);
        if (! Student::whereKey($data['student_id'])->whereHas('currentClass', fn ($q) => $q->where('academic_year_id', $term->academic_year_id))->exists()) {
            throw ValidationException::withMessages(['student_id' => 'Choose a student enrolled in this academic year.']);
        }
        StudyEnrolment::firstOrCreate($data, ['recorded_by' => $request->user()->id]);
        return redirect()->route('admin.study-retention.index', ['term_id' => $term->id])
            ->with('success', 'Voluntary study enrolment saved.');
    }

    public function unenrol(StudyEnrolment $studyEnrolment)
    {
        $termId = $studyEnrolment->term_id;
        $studyEnrolment->delete();
        return redirect()->route('admin.study-retention.index', ['term_id' => $termId])
            ->with('success', 'Voluntary enrolment removed. Students who meet the study rules still remain for study.');
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
        $selection = StudyRetentionSetting::with('sourceTerm.academicYear')->where('term_id', $term->id)->first();

        return view('admin.study-retention.print', compact('term', 'rows', 'selection'));
    }
}
