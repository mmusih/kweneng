<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AcademicStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicYearController extends Controller
{
    protected $structureService;
    
    public function __construct(AcademicStructureService $structureService)
    {
        $this->structureService = $structureService;
    }

    public function index()
    {
        $academicYears = AcademicYear::latest()->paginate(15); // Increased pagination
        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year_name' => 'required|string|unique:academic_years|max:255',
            'active' => 'boolean',
        ]);

        // Auto-deactivate other academic years if this one is being activated
        if (!empty($validated['active']) && $validated['active']) {
            AcademicYear::where('active', true)->update(['active' => false]);
        }

        // Set initial status based on active flag
        $status = 'open'; // Default to open for new academic years
        if (empty($validated['active']) || !$validated['active']) {
            $status = 'closed';
        }

        $academicYear = AcademicYear::create(array_merge($validated, [
            'status' => $status
        ]));

        return redirect()->route('admin.academic-years.index')
                        ->with('success', 'Academic year created successfully.');
    }

    public function edit(AcademicYear $academicYear)
    {
        // Check policy for update
        if (!Gate::allows('update', $academicYear)) {
            abort(403);
        }
        
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        // Check policy for update
        if (!Gate::allows('update', $academicYear)) {
            abort(403);
        }
        
        $validated = $request->validate([
            'year_name' => 'required|string|unique:academic_years,year_name,' . $academicYear->id,
            'active' => 'boolean',
            'status' => 'string|in:open,closed,locked' // Add status validation
        ]);

        // Auto-deactivate other academic years if this one is being activated
        if (!empty($validated['active']) && $validated['active']) {
            AcademicYear::where('active', true)
                       ->where('id', '!=', $academicYear->id)
                       ->update(['active' => false]);
        }

        // Update the academic year
        $academicYear->update($validated);

        return redirect()->route('admin.academic-years.index')
                        ->with('success', 'Academic year updated successfully.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        // Check policy for delete
        if (!Gate::allows('delete', $academicYear)) {
            abort(403);
        }

        // Check if academic year has associated data
        if ($academicYear->classes()->count() > 0 || 
            $academicYear->terms()->count() > 0 ||
            $academicYear->studentClassHistories()->count() > 0) {
            return back()->withErrors([
                'academic_year' => 'Cannot delete academic year with associated data (classes, terms, or student records).'
            ]);
        }

        // Don't allow deletion of locked years
        if ($academicYear->status === 'locked') {
            return back()->withErrors([
                'academic_year' => 'Cannot delete locked academic year.'
            ]);
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
                        ->with('success', 'Academic year deleted successfully.');
    }
    
    /**
     * Close an academic year
     */
    public function close(AcademicYear $academicYear)
    {
        // Check policy for close
        if (!Gate::allows('close', $academicYear)) {
            abort(403);
        }
        
        $result = $this->structureService->closeAcademicYear($academicYear->id);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['academic_year' => $result['message']]);
    }
    
    /**
     * Lock an academic year
     */
    public function lock(AcademicYear $academicYear)
    {
        // Check policy for lock
        if (!Gate::allows('lock', $academicYear)) {
            abort(403);
        }
        
        $result = $this->structureService->lockAcademicYear($academicYear->id);
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['academic_year' => $result['message']]);
    }
}
