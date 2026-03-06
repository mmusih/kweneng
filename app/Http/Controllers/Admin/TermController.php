<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Services\AcademicStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TermController extends Controller
{
    protected $structureService;
    
    public function __construct(AcademicStructureService $structureService)
    {
        $this->structureService = $structureService;
    }

    public function index()
    {
        $terms = Term::with('academicYear')->latest()->paginate(15);
        return view('admin.terms.index', compact('terms'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        return view('admin.terms.create', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Set initial status to active for new terms
        $validated['status'] = 'active';
        // Set locked to false for new terms
        $validated['locked'] = false;

        $term = Term::create($validated);

        return redirect()->route('admin.terms.index')
                        ->with('success', 'Term created successfully.');
    }

    public function edit(Term $term)
    {
        // Check policy for update (if you have policies)
        // if (!Gate::allows('update', $term)) {
        //     abort(403);
        // }
        
        $academicYears = AcademicYear::all();
        return view('admin.terms.edit', compact('term', 'academicYears'));
    }

    public function update(Request $request, Term $term)
    {
        // Check policy for update (if you have policies)
        // if (!Gate::allows('update', $term)) {
        //     abort(403);
        // }
        
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'string|in:active,finalized,locked'
        ]);

        // Handle the locked field based on status
        $validated['locked'] = ($validated['status'] === 'locked');
        
        // Auto-deactivate other unlocked terms in the same academic year if this one is being activated
        if ($validated['status'] === 'active') {
            Term::where('academic_year_id', $validated['academic_year_id'])
                ->where('id', '!=', $term->id)
                ->where('status', 'active')
                ->update(['status' => 'locked']); // or 'finalized' depending on your preference
        }

        $term->update($validated);

        return redirect()->route('admin.terms.index')
                        ->with('success', 'Term updated successfully.');
    }

    public function destroy(Term $term)
    {
        // Check policy for delete (if you have policies)
        // if (!Gate::allows('delete', $term)) {
        //     abort(403);
        // }

        // Check if term has associated data (you might add this later)
        if ($term->status === 'locked') {
            return back()->withErrors([
                'term' => 'Cannot delete locked term.'
            ]);
        }

        $term->delete();

        return redirect()->route('admin.terms.index')
                        ->with('success', 'Term deleted successfully.');
    }

    public function finalize(Term $term)
    {
        // Check policy for finalize (if you have policies)
        // if (!Gate::allows('finalize', $term)) {
        //     abort(403);
        // }
        
        $result = $this->structureService->finalizeTerm($term->id);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['term' => $result['message']]);
    }

    public function lock(Term $term)
    {
        // Check policy for lock (if you have policies)
        // if (!Gate::allows('lock', $term)) {
        //     abort(403);
        // }
        
        $result = $this->structureService->lockTerm($term->id);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['term' => $result['message']]);
    }

    public function activate(Term $term)
    {
        // Check policy for activate (if you have policies)
        // if (!Gate::allows('activate', $term)) {
        //     abort(403);
        // }
        
        $result = $this->structureService->activateTerm($term->id);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['term' => $result['message']]);
    }
}
