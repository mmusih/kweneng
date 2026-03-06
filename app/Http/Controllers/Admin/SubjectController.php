<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Services\SubjectService;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    protected $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->subjectService = $subjectService;
    }

    public function index()
    {
        $subjects = Subject::orderBy('display_order')->paginate(15);
        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:subjects,code|max:10',
            'description' => 'nullable|string',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $subject = $this->subjectService->createSubject($validated);

        return redirect()->route('admin.subjects.index')
                        ->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:subjects,code,'.$subject->id.'|max:10',
            'description' => 'nullable|string',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $this->subjectService->updateSubject($subject, $validated);

        return redirect()->route('admin.subjects.index')
                        ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        // Check if subject is assigned to any classes
        if ($subject->classSubjects()->count() > 0) {
            return redirect()->back()->withErrors([
                'subject' => 'Cannot delete subject that is assigned to classes.'
            ]);
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')
                        ->with('success', 'Subject deleted successfully.');
    }

    // Subject-Class Assignment Interface
    public function manageClassAssignments()
    {
        $classes = ClassModel::with('academicYear')->get();
        $subjects = Subject::where('is_active', true)->orderBy('display_order')->get();
        $academicYears = AcademicYear::where('status', 'open')->get();
        
        return view('admin.subjects.manage-classes', compact('classes', 'subjects', 'academicYears'));
    }

    public function assignSubjectToClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'max_marks' => 'numeric|min:0',
            'passing_marks' => 'numeric|min:0',
        ]);

        try {
            $this->subjectService->assignSubjectToClass(
                $validated['class_id'],
                $validated['subject_id'],
                $validated['academic_year_id'],
                [
                    'max_marks' => $validated['max_marks'] ?? 100,
                    'passing_marks' => $validated['passing_marks'] ?? 40,
                ]
            );

            return redirect()->back()->with('success', 'Subject assigned to class successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['assignment' => $e->getMessage()]);
        }
    }

    public function removeSubjectFromClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $this->subjectService->removeSubjectFromClass(
            $validated['class_id'],
            $validated['subject_id'],
            $validated['academic_year_id']
        );

        return redirect()->back()->with('success', 'Subject removed from class successfully.');
    }

    // Teacher-Subject Assignment Interface
    public function manageTeacherAssignments()
    {
        $teachers = Teacher::with('user')->get();
        $subjects = Subject::where('is_active', true)->orderBy('display_order')->get();
        $classes = ClassModel::with('academicYear')->get();
        $academicYears = AcademicYear::where('status', 'open')->get();
        
        return view('admin.subjects.manage-teachers', compact('teachers', 'subjects', 'classes', 'academicYears'));
    }

    public function assignTeacherToSubject(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_primary' => 'boolean',
        ]);

        try {
            $this->subjectService->assignTeacherToSubject(
                $validated['teacher_id'],
                $validated['subject_id'],
                $validated['class_id'],
                $validated['academic_year_id'],
                [
                    'is_primary' => $validated['is_primary'] ?? false,
                ]
            );

            return redirect()->back()->with('success', 'Teacher assigned to subject successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['assignment' => $e->getMessage()]);
        }
    }

    public function removeTeacherFromSubject(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $this->subjectService->removeTeacherFromSubject(
            $validated['teacher_id'],
            $validated['subject_id'],
            $validated['class_id'],
            $validated['academic_year_id']
        );

        return redirect()->back()->with('success', 'Teacher removed from subject successfully.');
    }
}
