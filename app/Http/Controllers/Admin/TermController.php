<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Services\AcademicStructureService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class TermController extends Controller
{
    protected $structureService;
    protected $activityLogService;

    public function __construct(
        AcademicStructureService $structureService,
        ActivityLogService $activityLogService
    ) {
        $this->structureService = $structureService;
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : null;
        $terms = Term::with('academicYear')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('start_date')
            ->paginate(15);

        return view('admin.terms.index', compact('terms', 'activeAcademicYear', 'activeTerm'));
    }

    public function create()
    {
        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : null;
        $academicYears = AcademicYear::where('status', '!=', AcademicYear::STATUS_CLOSED)
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();

        return view('admin.terms.create', compact('academicYears', 'activeAcademicYear', 'activeTerm'));
    }

    public function store(Request $request)
    {
        $activeAcademicYear = AcademicYear::current();

        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'nullable|string|in:active,finalized,locked',
            'report_title' => 'nullable|string|max:255',
            'report_footer_note' => 'nullable|string',
            'report_office_note' => 'nullable|string',
            'report_extra_note' => 'nullable|string',
        ]);

        if (! isset($validated['academic_year_id'])) {
            if (! $activeAcademicYear) {
                return back()->withErrors([
                    'academic_year_id' => 'Create or activate an academic year before creating a term.',
                ])->withInput();
            }

            $validated['academic_year_id'] = $activeAcademicYear->id;
        }

        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : null;
        $targetStatus = $validated['status'] ?? ($activeTerm ? Term::STATUS_FINALIZED : Term::STATUS_ACTIVE);
        $validated['status'] = $targetStatus;
        $validated['locked'] = $targetStatus === Term::STATUS_LOCKED;
        $validated['midterm_locked'] = false;
        $validated['endterm_locked'] = false;

        $term = Term::create($validated);

        $activationResult = null;
        if ($targetStatus === Term::STATUS_ACTIVE) {
            $activationResult = $this->structureService->activateTerm($term->id);

            if (! $activationResult['success']) {
                $term->delete();

                return back()->withErrors(['term' => $activationResult['message']])->withInput();
            }
        }

        $this->activityLogService->log(
            'term.created',
            "Term created: {$term->name}",
            $term,
            ['term_id' => $term->id, 'status' => $targetStatus],
            request()
        );

        return redirect()->route('admin.terms.index')
            ->with('success', $activationResult['message'] ?? 'Term created successfully.');
    }

    public function edit(Term $term)
    {
        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : null;
        $academicYears = AcademicYear::where('status', '!=', AcademicYear::STATUS_CLOSED)
            ->orWhere('id', $term->academic_year_id)
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();

        return view('admin.terms.edit', compact('term', 'academicYears', 'activeAcademicYear', 'activeTerm'));
    }

    public function update(Request $request, Term $term)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'string|in:active,finalized,locked',
            'report_title' => 'nullable|string|max:255',
            'report_footer_note' => 'nullable|string',
            'report_office_note' => 'nullable|string',
            'report_extra_note' => 'nullable|string',
        ]);

        $targetStatus = $validated['status'];
        unset($validated['status']);

        $term->update($validated);
        $term->refresh();

        $statusResult = ['success' => true, 'message' => 'Term updated successfully.'];

        if ($targetStatus === Term::STATUS_ACTIVE) {
            $statusResult = $this->structureService->activateTerm($term->id);
        } elseif ($targetStatus === Term::STATUS_FINALIZED && ! $term->isFinalized()) {
            $statusResult = $this->structureService->finalizeTerm($term->id);
        } elseif ($targetStatus === Term::STATUS_LOCKED && ! $term->isLocked()) {
            $statusResult = $this->structureService->lockTerm($term->id);
        }

        if (! $statusResult['success']) {
            return back()->withErrors(['term' => $statusResult['message']])->withInput();
        }

        $this->activityLogService->log(
            'term.updated',
            "Term updated: {$term->name}",
            $term->fresh(),
            ['term_id' => $term->id, 'target_status' => $targetStatus],
            request()
        );

        return redirect()->route('admin.terms.index')
            ->with('success', $statusResult['message']);
    }

    public function destroy(Term $term)
    {
        if ($term->status === 'locked') {
            return back()->withErrors([
                'term' => 'Cannot delete locked term.'
            ]);
        }

        $termName = $term->name;
        $termId = $term->id;

        $term->delete();

        $this->activityLogService->log(
            'term.deleted',
            "Term deleted: {$termName}",
            null,
            ['term_id' => $termId, 'term_name' => $termName],
            request()
        );

        return redirect()->route('admin.terms.index')
            ->with('success', 'Term deleted successfully.');
    }

    public function finalize(Term $term)
    {
        $result = $this->structureService->finalizeTerm($term->id);

        if ($result['success']) {
            $this->activityLogService->log(
                'term.finalized',
                "Term finalized: {$term->name}",
                $term,
                ['term_id' => $term->id],
                request()
            );

            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors(['term' => $result['message']]);
    }

    public function lock(Term $term)
    {
        $result = $this->structureService->lockTerm($term->id);

        if ($result['success']) {
            $this->activityLogService->log(
                'term.locked',
                "Term locked: {$term->name}",
                $term,
                ['term_id' => $term->id],
                request()
            );

            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors(['term' => $result['message']]);
    }

    public function activate(Term $term)
    {
        $result = $this->structureService->activateTerm($term->id);

        if ($result['success']) {
            $this->activityLogService->log(
                'term.activated',
                "Term activated: {$term->name}",
                $term,
                ['term_id' => $term->id],
                request()
            );

            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors(['term' => $result['message']]);
    }

    public function lockMidterm(Term $term)
    {
        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'term' => 'This term is fully locked.'
            ]);
        }

        if ($term->midterm_locked) {
            return redirect()->back()->withErrors([
                'term' => 'Midterm marks are already locked.'
            ]);
        }

        $term->update([
            'midterm_locked' => true,
        ]);

        $this->activityLogService->log(
            'term.midterm_locked',
            "Midterm locked for {$term->name}",
            $term,
            ['term_id' => $term->id],
            request()
        );

        return redirect()->back()->with('success', 'Midterm marks locked successfully.');
    }

    public function unlockMidterm(Term $term)
    {
        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'term' => 'This term is fully locked and cannot be changed.'
            ]);
        }

        $term->update([
            'midterm_locked' => false,
        ]);

        $this->activityLogService->log(
            'term.midterm_unlocked',
            "Midterm unlocked for {$term->name}",
            $term,
            ['term_id' => $term->id],
            request()
        );

        return redirect()->back()->with('success', 'Midterm marks unlocked successfully.');
    }

    public function lockEndterm(Term $term)
    {
        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'term' => 'This term is fully locked.'
            ]);
        }

        if ($term->endterm_locked) {
            return redirect()->back()->withErrors([
                'term' => 'Endterm marks are already locked.'
            ]);
        }

        $term->update([
            'endterm_locked' => true,
        ]);

        $this->activityLogService->log(
            'term.endterm_locked',
            "Endterm locked for {$term->name}",
            $term,
            ['term_id' => $term->id],
            request()
        );

        return redirect()->back()->with('success', 'Endterm marks locked successfully.');
    }

    public function unlockEndterm(Term $term)
    {
        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'term' => 'This term is fully locked and cannot be changed.'
            ]);
        }

        $term->update([
            'endterm_locked' => false,
        ]);

        $this->activityLogService->log(
            'term.endterm_unlocked',
            "Endterm unlocked for {$term->name}",
            $term,
            ['term_id' => $term->id],
            request()
        );

        return redirect()->back()->with('success', 'Endterm marks unlocked successfully.');
    }
}
