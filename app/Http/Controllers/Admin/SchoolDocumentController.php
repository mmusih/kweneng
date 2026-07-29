<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SchoolDocumentController extends Controller
{
    public function index()
    {
        $documents = SchoolDocument::with(['academicYear', 'uploadedBy'])
            ->orderBy('category')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('category');

        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        return view('admin.documents.index', compact('documents', 'academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'category'         => ['required', 'in:timetable,prospectus,booklist,uniform'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'file'             => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'], // 10 MB
        ]);

        $file = $request->file('file');
        $path = $file->store('school-documents', 'public');

        SchoolDocument::create([
            'title'              => $validated['title'],
            'category'           => $validated['category'],
            'file_path'          => $path,
            'original_filename'  => $file->getClientOriginalName(),
            'academic_year_id'   => $validated['academic_year_id'] ?? null,
            'uploaded_by'        => Auth::id(),
            'is_active'          => true,
        ]);

        return redirect()->route('admin.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function toggleActive(SchoolDocument $document)
    {
        $document->update(['is_active' => ! $document->is_active]);

        return back()->with('success', 'Document visibility updated.');
    }

    public function destroy(SchoolDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('admin.documents.index')
            ->with('success', 'Document deleted.');
    }
}
