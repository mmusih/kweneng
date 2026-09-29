<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AwardCategory;
use App\Models\AwardRun;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentAward;
use App\Models\Subject;
use App\Services\ActivityLogService;
use App\Services\AwardCalculationService;
use App\Services\AwardCertificateArchiveService;
use App\Services\AwardExcelExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AwardController extends Controller
{
    public function __construct(
        protected AwardCalculationService $calculator,
        protected AwardCertificateArchiveService $certificateArchive,
        protected AwardExcelExportService $excelExporter,
        protected ActivityLogService $activityLog,
    ) {}

    public function index()
    {
        $runs = AwardRun::with(['category', 'academicYear', 'term'])->withCount('awards')->latest()->paginate(20);

        return view('awards.index', compact('runs'));
    }

    public function create()
    {
        return view('awards.create', [
            'academicYears' => AcademicYear::with('terms')->latest('id')->get(),
            'classes' => ClassModel::with('academicYear')->orderBy('level')->orderBy('name')->get(),
            'categories' => AwardCategory::where('active', true)->orderBy('type')->orderBy('name')->get(),
            'students' => Student::with(['user', 'currentClass'])->orderBy('admission_no')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function edit(AwardRun $award)
    {
        abort_if($award->status !== 'draft', 422, 'Only draft awards can be edited.');
        $award->load('awards');

        return view('awards.create', [
            'run' => $award,
            'academicYears' => AcademicYear::with('terms')->latest('id')->get(),
            'classes' => ClassModel::with('academicYear')->orderBy('level')->orderBy('name')->get(),
            'categories' => AwardCategory::where('active', true)->orderBy('type')->orderBy('name')->get(),
            'students' => Student::with(['user', 'currentClass'])->orderBy('admission_no')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:academic,custom'],
            'title' => ['required', 'string', 'max:255'],
            'award_category_id' => ['nullable', 'exists:award_categories,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'scope_type' => ['required', 'in:school,all_levels,level,class,selected'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'level' => ['nullable', 'integer', 'min:1'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cutoff_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'calculation_mode' => ['nullable', 'in:selected_midterm,selected_endterm,selected_term_average,cumulative_to_term,all_terms,custom'],
            'missing_marks_policy' => ['nullable', 'in:exclude,available'],
            'tie_policy' => ['nullable', 'in:include_all,strict_count,manual'],
            'ranking_basis' => ['nullable', 'in:overall,subject'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'award_date' => ['required', 'date'],
            'parent_visible' => ['nullable', 'boolean'],
            'citation' => ['nullable', 'string', 'max:2000'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
            'components' => ['nullable', 'array'],
            'components.*.term_id' => ['nullable', 'exists:terms,id'],
            'components.*.assessment' => ['nullable', 'in:midterm,endterm'],
            'components.*.weight' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'tie_breakers' => ['nullable', 'array'],
            'tie_breakers.*.term_id' => ['nullable', 'exists:terms,id'],
            'tie_breakers.*.assessment' => ['nullable', 'in:midterm,endterm'],
        ]);

        if ($validated['type'] === 'academic') {
            $request->validate([
                'positions' => ['required', 'integer', 'min:1'],
                'cutoff_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'calculation_mode' => ['required'],
            ]);
            if (($validated['calculation_mode'] ?? null) !== 'all_terms' && ($validated['calculation_mode'] ?? null) !== 'custom' && empty($validated['term_id'])) {
                return back()->withErrors(['term_id' => 'Choose a term for this calculation mode.'])->withInput();
            }
        } else {
            $request->validate(['student_ids' => ['required', 'array', 'min:1']]);
        }

        $validated['components'] = collect($validated['components'] ?? [])->filter(fn ($row) => ! empty($row['term_id']) && ! empty($row['assessment']))->values()->all();
        $validated['tie_breakers'] = collect($validated['tie_breakers'] ?? [])->filter(fn ($row) => ! empty($row['term_id']) && ! empty($row['assessment']))->values()->all();
        if (($validated['calculation_mode'] ?? null) === 'custom' && empty($validated['components'])) {
            return back()->withErrors(['components' => 'Choose at least one weighted assessment.'])->withInput();
        }

        $category = ! empty($validated['award_category_id']) ? AwardCategory::find($validated['award_category_id']) : null;
        if ($category?->headmaster_only && ! in_array($request->user()->role, ['admin', 'headmaster'], true)) {
            abort(403);
        }

        $run = DB::transaction(function () use ($validated, $request, $category) {
            $run = AwardRun::create([
                ...collect($validated)->only([
                    'award_category_id', 'type', 'title', 'academic_year_id', 'term_id', 'scope_type',
                    'class_id', 'level', 'positions', 'cutoff_percentage', 'calculation_mode',
                    'missing_marks_policy', 'tie_policy', 'award_date',
                ])->all(),
                'missing_marks_policy' => $validated['missing_marks_policy'] ?? 'exclude',
                'tie_policy' => $validated['tie_policy'] ?? 'include_all',
                'parent_visible' => $request->boolean('parent_visible', true),
                'calculation_config' => [
                    'class_ids' => $validated['class_ids'] ?? [],
                    'ranking_basis' => $validated['ranking_basis'] ?? 'overall',
                    'subject_ids' => $validated['subject_ids'] ?? [],
                    'components' => $validated['components'] ?? [],
                    'tie_breakers' => $validated['tie_breakers'] ?? [],
                ],
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            if ($run->type === 'academic') {
                $result = $this->calculator->generate($run->load(['term', 'academicYear']));
                foreach ($result['recipients'] as $row) {
                    $this->createStudentAward($run, $row['student'], [
                        'subject_id' => $row['subject_id'] ?? null,
                        'subject_name_snapshot' => $row['subject_name'] ?? null,
                        'award_title' => $row['award_title'] ?? $run->title,
                        'position' => $row['position'],
                        'main_score' => $row['main_score'],
                        'tie_breaker_scores' => $row['tie_breakers'],
                        'citation' => $validated['citation'] ?? $category?->default_citation,
                        'selection_source' => 'generated',
                    ]);
                }
                $run->update(['generation_summary' => [
                    'considered_count' => $result['considered_count'],
                    'qualified_count' => count($result['recipients']),
                    'excluded' => $result['excluded']->all(),
                    'calculation_description' => $this->calculator->calculationDescription($run),
                ]]);
            } else {
                Student::with(['user', 'currentClass'])->whereIn('id', $validated['student_ids'])->get()
                    ->each(fn (Student $student) => $this->createStudentAward($run, $student, [
                        'citation' => $validated['citation'] ?? $category?->default_citation,
                        'selection_source' => 'manual',
                    ]));
                $run->update(['generation_summary' => ['qualified_count' => count($validated['student_ids'])]]);
            }

            return $run;
        });

        $this->activityLog->log('awards.draft_created', 'Created award draft: '.$run->title, $run, [], $request);

        return redirect()->route($this->routeName($request, 'awards.show'), $run)->with('success', 'Award draft generated. Review, print, or publish it.');
    }

    public function update(Request $request, AwardRun $award)
    {
        abort_if($award->status !== 'draft', 422, 'Only draft awards can be edited.');
        $validated = $this->validateAwardUpdate($request);
        $category = ! empty($validated['award_category_id']) ? AwardCategory::find($validated['award_category_id']) : null;
        if ($category?->headmaster_only && ! in_array($request->user()->role, ['admin', 'headmaster'], true)) {
            abort(403);
        }

        DB::transaction(function () use ($award, $validated, $request, $category) {
            $award->update([
                ...collect($validated)->only([
                    'award_category_id', 'type', 'title', 'academic_year_id', 'term_id', 'scope_type',
                    'class_id', 'level', 'positions', 'cutoff_percentage', 'calculation_mode',
                    'missing_marks_policy', 'tie_policy', 'award_date',
                ])->all(),
                'missing_marks_policy' => $validated['missing_marks_policy'] ?? 'exclude',
                'tie_policy' => $validated['tie_policy'] ?? 'include_all',
                'parent_visible' => $request->boolean('parent_visible', true),
                'calculation_config' => [
                    'class_ids' => $validated['class_ids'] ?? [],
                    'ranking_basis' => $validated['ranking_basis'] ?? 'overall',
                    'subject_ids' => $validated['subject_ids'] ?? [],
                    'components' => $validated['components'] ?? [],
                    'tie_breakers' => $validated['tie_breakers'] ?? [],
                ],
            ]);

            // Criteria edits must not leave stale positions, scores, or certificates behind.
            $award->awards()->delete();
            $award->unsetRelation('term')->unsetRelation('academicYear');

            if ($award->type === 'academic') {
                $result = $this->calculator->generate($award->load(['term', 'academicYear']));
                foreach ($result['recipients'] as $row) {
                    $this->createStudentAward($award, $row['student'], [
                        'subject_id' => $row['subject_id'] ?? null,
                        'subject_name_snapshot' => $row['subject_name'] ?? null,
                        'award_title' => $row['award_title'] ?? $award->title,
                        'position' => $row['position'],
                        'main_score' => $row['main_score'],
                        'tie_breaker_scores' => $row['tie_breakers'],
                        'citation' => $validated['citation'] ?? $category?->default_citation,
                        'selection_source' => 'generated',
                    ]);
                }
                $award->update(['generation_summary' => [
                    'considered_count' => $result['considered_count'],
                    'qualified_count' => count($result['recipients']),
                    'excluded' => $result['excluded']->all(),
                    'calculation_description' => $this->calculator->calculationDescription($award),
                ]]);
            } else {
                Student::with(['user', 'currentClass'])->whereIn('id', $validated['student_ids'])->get()
                    ->each(fn (Student $student) => $this->createStudentAward($award, $student, [
                        'citation' => $validated['citation'] ?? $category?->default_citation,
                        'selection_source' => 'manual',
                    ]));
                $award->update(['generation_summary' => ['qualified_count' => count($validated['student_ids'])]]);
            }
        });

        $this->activityLog->log('awards.draft_updated', 'Updated award draft: '.$award->title, $award, [], $request);

        return redirect()->route($this->routeName($request, 'awards.show'), $award)
            ->with('success', 'Award draft updated and recipients recalculated.');
    }

    public function show(AwardRun $award)
    {
        $this->refreshLegacyExclusionDetails($award);
        $award->load(['category', 'academicYear', 'term', 'awards.student.user', 'awards.student.currentClass', 'creator', 'publisher']);
        $availableStudents = Student::with(['user', 'currentClass'])->whereNotIn('id', $award->awards->pluck('student_id'))->orderBy('admission_no')->get();

        return view('awards.show', ['run' => $award, 'availableStudents' => $availableStudents]);
    }

    public function publish(Request $request, AwardRun $award)
    {
        abort_if($award->status !== 'draft', 422, 'Only draft awards can be published.');
        abort_if($award->awards()->count() === 0, 422, 'Add at least one recipient before publishing.');
        $award->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
        $this->activityLog->log('awards.published', 'Published award: '.$award->title, $award, [], $request);

        return back()->with('success', 'Awards published and now visible on student and parent profiles.');
    }

    public function destroy(Request $request, AwardRun $award)
    {
        abort_if($award->status !== 'draft', 422, 'Only draft award lists can be deleted.');

        $title = $award->title;
        $recipientCount = $award->awards()->count();
        $this->activityLog->log(
            'awards.draft_deleted',
            'Deleted award draft: '.$title,
            $award,
            ['recipient_count' => $recipientCount],
            $request,
        );
        $award->delete();

        return redirect()->route($this->routeName($request, 'awards.index'))
            ->with('success', 'Award draft deleted.');
    }

    public function addRecipient(Request $request, AwardRun $award)
    {
        abort_if($award->status !== 'draft', 422, 'Published awards cannot be edited.');
        $validated = $request->validate(['student_id' => ['required', 'exists:students,id'], 'override_reason' => ['required', 'string', 'max:500']]);
        abort_if($award->awards()->where('student_id', $validated['student_id'])->whereNull('subject_id')->exists(), 422, 'This student is already a recipient.');
        $student = Student::with(['user', 'currentClass'])->findOrFail($validated['student_id']);
        $this->createStudentAward($award, $student, ['selection_source' => 'manual', 'override_reason' => $validated['override_reason'], 'citation' => $award->category?->default_citation]);

        return back()->with('success', 'Recipient added to the draft.');
    }

    public function removeRecipient(Request $request, AwardRun $award, StudentAward $studentAward)
    {
        abort_if($award->status !== 'draft' || $studentAward->award_run_id !== $award->id, 422);
        $studentAward->delete();

        return back()->with('success', 'Recipient removed from the draft.');
    }

    public function printRun(AwardRun $award)
    {
        $award->load(['category', 'academicYear', 'term', 'awards.student']);

        return Pdf::loadView('pdf.award-list', ['run' => $award, 'logoPath' => $this->pdfLogoPath()])
            ->setPaper('a4', 'portrait')->stream(Str::slug($award->title).'-awards.pdf');
    }

    public function exportExcel(AwardRun $award)
    {
        $award->load(['category', 'academicYear', 'term', 'classModel', 'awards']);
        $filename = Str::slug($award->title.'-'.$award->academicYear?->year_name).'-awards.xlsx';

        return response($this->excelExporter->build($award), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function certificate(AwardRun $award, StudentAward $studentAward)
    {
        abort_unless($studentAward->award_run_id === $award->id, 404);
        $award->load(['category', 'academicYear', 'term']);

        return Pdf::loadView('pdf.award-certificate', ['run' => $award, 'award' => $studentAward, 'logoPath' => $this->pdfLogoPath()])
            ->setPaper('a4', 'landscape')->stream($studentAward->certificate_reference.'.pdf');
    }

    public function bulkCertificates(AwardRun $award)
    {
        abort_if($award->awards()->count() === 0, 422, 'There are no certificates to download.');
        $award->load(['category', 'academicYear', 'term', 'awards']);
        $filename = Str::slug($award->title.'-'.$award->academicYear?->year_name).'-certificates.zip';

        return response()->streamDownload(function () use ($award) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            foreach ($this->certificateArchive->stream($award, $this->pdfLogoPath()) as $chunk) {
                echo $chunk;

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function bulkCertificatesPdf(AwardRun $award)
    {
        abort_if($award->awards()->count() === 0, 422, 'There are no certificates to download.');

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $award->load(['category', 'academicYear', 'term', 'awards']);
        $awards = $award->awards
            ->sortBy(fn (StudentAward $studentAward) => [$studentAward->position ?? PHP_INT_MAX, $studentAward->student_name_snapshot])
            ->values();
        $filename = Str::slug($award->title.'-'.$award->academicYear?->year_name).'-certificates.pdf';

        return Pdf::loadView('pdf.award-certificates-bulk', [
            'run' => $award,
            'awards' => $awards,
            'logoPath' => $this->pdfLogoPath(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    private function createStudentAward(AwardRun $run, Student $student, array $values): StudentAward
    {
        return $run->awards()->create([
            'student_id' => $student->id,
            'award_title' => $run->title,
            'student_name_snapshot' => $student->user?->name ?? 'Unknown Student',
            'admission_no_snapshot' => $student->admission_no,
            'class_name_snapshot' => $student->currentClass?->name,
            'level_snapshot' => $student->currentClass?->level,
            'certificate_reference' => 'KIS-'.$run->academic_year_id.'-'.Str::upper(Str::random(10)),
            ...$values,
        ]);
    }

    private function validateAwardUpdate(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', 'in:academic,custom'],
            'title' => ['required', 'string', 'max:255'],
            'award_category_id' => ['nullable', 'exists:award_categories,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'scope_type' => ['required', 'in:school,all_levels,level,class,selected'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'level' => ['nullable', 'integer', 'min:1'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cutoff_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'calculation_mode' => ['nullable', 'in:selected_midterm,selected_endterm,selected_term_average,cumulative_to_term,all_terms,custom'],
            'missing_marks_policy' => ['nullable', 'in:exclude,available'],
            'tie_policy' => ['nullable', 'in:include_all,strict_count,manual'],
            'ranking_basis' => ['nullable', 'in:overall,subject'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'award_date' => ['required', 'date'],
            'parent_visible' => ['nullable', 'boolean'],
            'citation' => ['nullable', 'string', 'max:2000'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
            'components' => ['nullable', 'array'],
            'components.*.term_id' => ['nullable', 'exists:terms,id'],
            'components.*.assessment' => ['nullable', 'in:midterm,endterm'],
            'components.*.weight' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'tie_breakers' => ['nullable', 'array'],
            'tie_breakers.*.term_id' => ['nullable', 'exists:terms,id'],
            'tie_breakers.*.assessment' => ['nullable', 'in:midterm,endterm'],
        ]);

        if ($validated['type'] === 'academic') {
            $request->validate([
                'positions' => ['required', 'integer', 'min:1'],
                'cutoff_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'calculation_mode' => ['required'],
            ]);
            if (! in_array($validated['calculation_mode'] ?? null, ['all_terms', 'custom'], true) && empty($validated['term_id'])) {
                throw ValidationException::withMessages(['term_id' => 'Choose a term for this calculation mode.']);
            }
        } else {
            $request->validate(['student_ids' => ['required', 'array', 'min:1']]);
        }

        $validated['components'] = collect($validated['components'] ?? [])->filter(fn ($row) => ! empty($row['term_id']) && ! empty($row['assessment']))->values()->all();
        $validated['tie_breakers'] = collect($validated['tie_breakers'] ?? [])->filter(fn ($row) => ! empty($row['term_id']) && ! empty($row['assessment']))->values()->all();
        if (($validated['calculation_mode'] ?? null) === 'custom' && empty($validated['components'])) {
            throw ValidationException::withMessages(['components' => 'Choose at least one weighted assessment.']);
        }

        return $validated;
    }

    private function routeName(Request $request, string $suffix): string
    {
        return ($request->user()->role === 'headmaster' ? 'headmaster.' : 'admin.').$suffix;
    }

    private function refreshLegacyExclusionDetails(AwardRun $award): void
    {
        $excluded = $award->generation_summary['excluded'] ?? [];
        $needsDetails = $award->type === 'academic'
            && $award->status === 'draft'
            && collect($excluded)->contains(fn (array $row) => ! array_key_exists('missing', $row) || ! array_key_exists('class_name', $row));

        if (! $needsDetails) {
            return;
        }

        $award->loadMissing(['term', 'academicYear']);
        $result = $this->calculator->generate($award);
        $summary = $award->generation_summary ?? [];
        $summary['excluded'] = $result['excluded']->all();
        $award->update(['generation_summary' => $summary]);
    }

    private function pdfLogoPath(): string
    {
        return extension_loaded('gd') ? public_path('images/logo.png') : '';
    }
}
