<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $this->subjectService->createSubject($validated);

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
            'code' => 'required|string|unique:subjects,code,' . $subject->id . '|max:10',
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
        if ($subject->classSubjects()->count() > 0) {
            return redirect()->back()->withErrors([
                'subject' => 'Cannot delete subject that is assigned to classes.'
            ]);
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }

    public function manageClassAssignments(Request $request)
    {
        $classes = ClassModel::with('academicYear')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $subjects = Subject::where('is_active', true)
            ->orderBy('is_core', 'desc')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::where('status', 'open')
            ->orderByDesc('year_name')
            ->get();

        $selectedAcademicYearId = $request->input('academic_year_id');
        $selectedClassId = $request->input('class_id');

        $existingAssignments = collect();

        if ($selectedAcademicYearId && $selectedClassId) {
            $existingAssignments = ClassSubject::where('class_id', $selectedClassId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->get()
                ->keyBy('subject_id');
        }

        return view('admin.subjects.manage-classes', compact(
            'classes',
            'subjects',
            'academicYears',
            'selectedAcademicYearId',
            'selectedClassId',
            'existingAssignments'
        ));
    }

    public function bulkSaveClassAssignments(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'subjects' => 'nullable|array',
            'subjects.*.selected' => 'nullable|in:1',
            'subjects.*.max_marks' => 'nullable|numeric|min:0',
            'subjects.*.passing_marks' => 'nullable|numeric|min:0',
            'subjects.*.remarks' => 'nullable|string|max:255',
        ]);

        $academicYearId = $validated['academic_year_id'];
        $classId = $validated['class_id'];
        $submittedSubjects = $validated['subjects'] ?? [];

        $selectedSubjectIds = [];

        foreach ($submittedSubjects as $subjectId => $subjectData) {
            if (!isset($subjectData['selected']) || $subjectData['selected'] != 1) {
                continue;
            }

            $selectedSubjectIds[] = (int) $subjectId;

            ClassSubject::updateOrCreate(
                [
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'max_marks' => $subjectData['max_marks'] ?? 100,
                    'passing_marks' => $subjectData['passing_marks'] ?? 60,
                    'remarks' => $subjectData['remarks'] ?? null,
                ]
            );
        }

        ClassSubject::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->when(
                count($selectedSubjectIds) > 0,
                fn($query) => $query->whereNotIn('subject_id', $selectedSubjectIds)
            )
            ->delete();

        return redirect()->route('admin.subjects.manage-classes', [
            'academic_year_id' => $academicYearId,
            'class_id' => $classId,
        ])->with('success', 'Subject assignments saved successfully.');
    }

    public function manageTeacherAssignments(Request $request)
    {
        $teachers = Teacher::with('user')
            ->orderBy('id')
            ->get();

        $academicYears = AcademicYear::where('status', 'open')
            ->orderByDesc('year_name')
            ->get();

        $selectedAcademicYearId = $request->input('academic_year_id');
        $selectedTeacherId = $request->input('teacher_id');

        $classes = collect();
        $classSubjectsMap = collect();
        $existingTeacherAssignments = collect();

        if ($selectedAcademicYearId) {
            $classes = ClassModel::with('academicYear')
                ->where('academic_year_id', $selectedAcademicYearId)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            $classSubjects = ClassSubject::with('subject')
                ->where('academic_year_id', $selectedAcademicYearId)
                ->whereHas('subject', function ($query) {
                    $query->where('is_active', true);
                })
                ->get()
                ->groupBy('class_id');

            $classSubjectsMap = $classSubjects->map(function ($items) {
                return $items->sortBy(function ($item) {
                    return [
                        $item->subject->is_core ? 0 : 1,
                        $item->subject->display_order ?? 9999,
                        $item->subject->name,
                    ];
                })->values();
            });
        }

        if ($selectedAcademicYearId && $selectedTeacherId) {
            $existingTeacherAssignments = DB::table('teacher_subjects')
                ->where('teacher_id', $selectedTeacherId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->get()
                ->keyBy(function ($row) {
                    return $row->class_id . '_' . $row->subject_id;
                });
        }

        return view('admin.subjects.manage-teachers', compact(
            'teachers',
            'academicYears',
            'selectedAcademicYearId',
            'selectedTeacherId',
            'classes',
            'classSubjectsMap',
            'existingTeacherAssignments'
        ));
    }

    public function bulkSaveTeacherAssignments(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'assignments' => 'nullable|array',
            'assignments.*' => 'array',
            'assignments.*.*.selected' => 'nullable|in:1',
            'assignments.*.*.is_primary' => 'nullable|in:1',
        ]);

        $teacherId = (int) $validated['teacher_id'];
        $academicYearId = (int) $validated['academic_year_id'];
        $assignments = $validated['assignments'] ?? [];

        DB::transaction(function () use ($teacherId, $academicYearId, $assignments) {
            DB::table('teacher_subjects')
                ->where('teacher_id', $teacherId)
                ->where('academic_year_id', $academicYearId)
                ->delete();

            $rowsToInsert = [];

            foreach ($assignments as $classId => $subjects) {
                foreach ($subjects as $subjectId => $data) {
                    if (!isset($data['selected']) || $data['selected'] != 1) {
                        continue;
                    }

                    $rowsToInsert[] = [
                        'teacher_id' => $teacherId,
                        'subject_id' => (int) $subjectId,
                        'class_id' => (int) $classId,
                        'academic_year_id' => $academicYearId,
                        'is_primary' => isset($data['is_primary']) && $data['is_primary'] == 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($rowsToInsert)) {
                DB::table('teacher_subjects')->insert($rowsToInsert);
            }
        });

        return redirect()->route('admin.subjects.manage-teachers', [
            'academic_year_id' => $academicYearId,
            'teacher_id' => $teacherId,
        ])->with('success', 'Teacher assignments saved successfully.');
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
