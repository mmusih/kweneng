<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MarksService;
use App\Models\Mark;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarksController extends Controller
{
    protected $marksService;

    public function __construct(MarksService $marksService)
    {
        $this->marksService = $marksService;
    }

    public function index(Request $request)
    {
        $query = Mark::with(['student.user', 'subject', 'class', 'teacher.user', 'academicYear', 'term']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $marks = $query->latest()->paginate(50)->appends($request->query());

        $academicYears = AcademicYear::all();
        $classes = ClassModel::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();

        $marksImportPreview = session('marks_import_preview');

        return view('admin.marks.index', compact(
            'marks',
            'academicYears',
            'classes',
            'subjects',
            'teachers',
            'marksImportPreview'
        ));
    }

    public function show($id)
    {
        $mark = Mark::with(['student.user', 'subject', 'class', 'teacher.user', 'academicYear', 'term'])->findOrFail($id);
        return view('admin.marks.show', compact('mark'));
    }

    public function edit($id)
    {
        $mark = Mark::findOrFail($id);

        if (!Gate::allows('override', $mark)) {
            abort(403);
        }

        return view('admin.marks.edit', compact('mark'));
    }

    public function update(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);

        if (!Gate::allows('override', $mark)) {
            abort(403);
        }

        $validated = $request->validate([
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'endterm_score' => 'nullable|numeric|min:0|max:100',
            'grade' => 'nullable|string|max:10',
            'remarks' => 'nullable|string|max:500',
        ]);

        if (isset($validated['midterm_score']) || isset($validated['endterm_score'])) {
            $midterm = $validated['midterm_score'] ?? $mark->midterm_score;
            $endterm = $validated['endterm_score'] ?? $mark->endterm_score;
            $validated['grade'] = $this->calculateGradeForMark($midterm, $endterm);
        }

        $mark->update($validated);

        return redirect()->route('admin.marks.index')->with('success', 'Mark updated successfully.');
    }

    public function destroy($id)
    {
        $mark = Mark::findOrFail($id);

        if (!Gate::allows('delete', $mark)) {
            abort(403);
        }

        $mark->delete();

        return redirect()->route('admin.marks.index')->with('success', 'Mark deleted successfully.');
    }

    public function getStudentAverages(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $averages = $this->marksService->calculateStudentAverages(
            $validated['student_id'],
            $validated['term_id']
        );

        return response()->json($averages);
    }

    public function importPreview(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'class_id' => 'required|exists:classes,id',
            'assessment_type' => 'required|in:midterm,endterm',
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
            $term = Term::findOrFail($validated['term_id']);
            $class = ClassModel::findOrFail($validated['class_id']);

            $csv = $this->readMarksSummaryCsv($request->file('csv_file')->getRealPath());

            $classStudents = Student::whereHas('classHistory', function ($query) use ($validated) {
                $query->where('class_id', $validated['class_id'])
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
                ->with('user')
                ->get();

            $studentLookup = [];
            foreach ($classStudents as $student) {
                [$dbGivenNames, $dbSurname] = $this->splitDatabaseName($student->user->name ?? '');
                $studentLookup[$this->buildNameKey($dbSurname, $dbGivenNames)][] = $student;
            }

            $classSubjects = Subject::whereHas('classSubjects', function ($query) use ($validated) {
                $query->where('class_id', $validated['class_id'])
                    ->where('academic_year_id', $validated['academic_year_id']);
            })->get();

            $subjectLookup = [];
            foreach ($classSubjects as $subject) {
                $subjectLookup[$this->normalizeSubjectCode($subject->code)] = $subject;
            }

            $groupedByStudent = [];
            $issues = [];
            $unknownSubjectCodes = [];

            foreach ($csv['rows'] as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;

                if (!$this->rowMatchesSelectedClass($row['class'], $class->name)) {
                    $issues[] = [
                        'type' => 'class_mismatch',
                        'row_number' => $rowNumber,
                        'message' => "CSV row class '{$row['class']}' does not match selected class '{$class->name}'.",
                    ];
                    continue;
                }

                $student = $this->findStudentFromCsvRow($row['surname'], $row['name'], $studentLookup, $classStudents);

                if (!$student) {
                    $issues[] = [
                        'type' => 'student_not_found',
                        'row_number' => $rowNumber,
                        'message' => "No student match found for {$row['surname']}, {$row['name']}.",
                    ];
                    continue;
                }

                $studentKey = (string) $student->id;

                if (!isset($groupedByStudent[$studentKey])) {
                    $groupedByStudent[$studentKey] = [
                        'student_id' => $student->id,
                        'student_name' => $student->user->name ?? 'Unknown Student',
                        'admission_no' => $student->admission_no ?? 'N/A',
                        'row_numbers' => [$rowNumber],
                        'subjects' => [],
                    ];
                } else {
                    $groupedByStudent[$studentKey]['row_numbers'][] = $rowNumber;
                }

                foreach ($row['subjects'] as $csvCode => $cell) {
                    $normalizedCode = $this->normalizeSubjectCode($csvCode);

                    if (!isset($subjectLookup[$normalizedCode])) {
                        $unknownSubjectCodes[$csvCode] = true;
                        continue;
                    }

                    $score = $this->normalizeScore($cell['score']);
                    if ($score === null) {
                        continue;
                    }

                    $subject = $subjectLookup[$normalizedCode];
                    $teacherId = $this->resolveTeacherForStudentSubject(
                        $student->id,
                        $subject->id,
                        $validated['class_id'],
                        $validated['academic_year_id']
                    );

                    if (!$teacherId) {
                        $issues[] = [
                            'type' => 'teacher_not_resolved',
                            'row_number' => $rowNumber,
                            'message' => "Could not resolve teacher for {$student->user->name} - {$subject->code}.",
                        ];
                        continue;
                    }

                    $groupedByStudent[$studentKey]['subjects'][] = [
                        'subject_id' => $subject->id,
                        'subject_code' => $subject->code,
                        'subject_name' => $subject->name,
                        'teacher_id' => $teacherId,
                        'score' => $score,
                        'grade' => $this->normalizeGrade($cell['grade']),
                    ];
                }
            }

            $groupedPreview = array_values(array_filter($groupedByStudent, function ($studentRow) {
                return !empty($studentRow['subjects']);
            }));

            session([
                'marks_import_preview' => [
                    'context' => [
                        'academic_year_id' => $academicYear->id,
                        'academic_year_name' => $academicYear->year_name,
                        'term_id' => $term->id,
                        'term_name' => $term->name,
                        'class_id' => $class->id,
                        'class_name' => $class->name,
                        'assessment_type' => $validated['assessment_type'],
                    ],
                    'students' => $groupedPreview,
                    'matched_students_count' => count($groupedPreview),
                    'matched_cells_count' => collect($groupedPreview)->sum(fn($item) => count($item['subjects'])),
                    'unknown_subject_codes' => array_keys($unknownSubjectCodes),
                    'issues' => $issues,
                ]
            ]);

            return redirect()->route('admin.marks.index', [
                'academic_year_id' => $academicYear->id,
                'term_id' => $term->id,
                'class_id' => $class->id,
            ])->with('success', 'CSV preview generated successfully.');
        } catch (\Exception $e) {
            Log::error('Marks CSV preview failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['csv_file' => 'Failed to preview CSV import. Please check the file and try again.'])
                ->withInput();
        }
    }

    public function importApply(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'class_id' => 'required|exists:classes,id',
            'assessment_type' => 'required|in:midterm,endterm',
        ]);

        $preview = session('marks_import_preview');

        if (!$preview || empty($preview['students'])) {
            return redirect()->back()->withErrors([
                'csv_file' => 'No valid preview data found. Preview the CSV again before applying.',
            ]);
        }

        if (
            (int) $preview['context']['academic_year_id'] !== (int) $validated['academic_year_id'] ||
            (int) $preview['context']['term_id'] !== (int) $validated['term_id'] ||
            (int) $preview['context']['class_id'] !== (int) $validated['class_id'] ||
            (string) $preview['context']['assessment_type'] !== (string) $validated['assessment_type']
        ) {
            return redirect()->back()->withErrors([
                'csv_file' => 'Preview context does not match the current import selection. Preview again.',
            ]);
        }

        try {
            DB::transaction(function () use ($preview, $validated) {
                foreach ($preview['students'] as $studentRow) {
                    foreach ($studentRow['subjects'] as $subjectRow) {
                        $mark = Mark::firstOrNew([
                            'student_id' => $studentRow['student_id'],
                            'subject_id' => $subjectRow['subject_id'],
                            'class_id' => $validated['class_id'],
                            'teacher_id' => $subjectRow['teacher_id'],
                            'academic_year_id' => $validated['academic_year_id'],
                            'term_id' => $validated['term_id'],
                        ]);

                        if ($validated['assessment_type'] === 'midterm') {
                            $mark->midterm_score = $subjectRow['score'];
                        } else {
                            $mark->endterm_score = $subjectRow['score'];
                        }

                        if (!empty($subjectRow['grade'])) {
                            $mark->grade = $subjectRow['grade'];
                        } else {
                            $mark->grade = $this->calculateGradeForMark($mark->midterm_score, $mark->endterm_score);
                        }

                        $mark->save();
                    }
                }
            });

            session()->forget('marks_import_preview');

            return redirect()->route('admin.marks.index', [
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'class_id' => $validated['class_id'],
            ])->with('success', 'Marks imported successfully from CSV.');
        } catch (\Exception $e) {
            Log::error('Marks CSV apply failed: ' . $e->getMessage());

            return redirect()->back()->withErrors([
                'csv_file' => 'Failed to apply CSV import. Please try again.',
            ]);
        }
    }

    public function studentSubjectsIndex(Request $request)
    {
        $query = StudentSubject::with([
            'student.user',
            'subject',
            'class',
            'academicYear',
            'teacher.user',
        ]);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('admission_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('subject', function ($subjectQuery) use ($search) {
                        $subjectQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('teacher.user', function ($teacherQuery) use ($search) {
                        $teacherQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $studentSubjects = $query
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        $academicYears = AcademicYear::all();
        $classes = ClassModel::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();

        return view('admin.student-subjects.index', compact(
            'studentSubjects',
            'academicYears',
            'classes',
            'subjects',
            'teachers'
        ));
    }

    public function studentSubjectsCreate(Request $request)
    {
        $academicYears = AcademicYear::all();
        $importPreview = session('student_subject_import_preview');

        return view('admin.student-subjects.create', compact('academicYears', 'importPreview'));
    }

    public function studentSubjectsStore(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'is_elective' => 'boolean',
        ]);

        try {
            $teacherAssigned = DB::table('teacher_subjects')
                ->where('teacher_id', $validated['teacher_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('class_id', $validated['class_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->exists();

            if (!$teacherAssigned) {
                return redirect()->back()
                    ->withErrors([
                        'teacher_id' => 'The selected teacher is not assigned to this subject for this class and academic year.',
                    ])
                    ->withInput();
            }

            $selectedStudentIds = collect($validated['student_ids'])
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            $this->saveStudentTeacherAssignments(
                $validated['academic_year_id'],
                $validated['class_id'],
                $validated['subject_id'],
                $validated['teacher_id'],
                $selectedStudentIds,
                $request->boolean('is_elective')
            );

            return redirect()->route('admin.student-subjects.create', [
                'academic_year_id' => $validated['academic_year_id'],
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'is_elective' => $request->boolean('is_elective') ? 1 : 0,
            ])->with('success', 'Student subject assignments saved successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject assignment failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to save assignments. Please try again.'])
                ->withInput();
        }
    }

    public function studentSubjectsImportPreview(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'is_elective' => 'nullable|boolean',
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $teacherAssigned = DB::table('teacher_subjects')
                ->where('teacher_id', $validated['teacher_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('class_id', $validated['class_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->exists();

            if (!$teacherAssigned) {
                return redirect()->back()
                    ->withErrors([
                        'teacher_id' => 'The selected teacher is not assigned to this subject for this class and academic year.',
                    ])
                    ->withInput();
            }

            $classStudents = Student::whereHas('classHistory', function ($query) use ($validated) {
                $query->where('class_id', $validated['class_id'])
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
                ->with('user')
                ->get();

            $lookup = [];
            foreach ($classStudents as $student) {
                $fullName = $student->user->name ?? '';
                [$dbGivenNames, $dbSurname] = $this->splitDatabaseName($fullName);
                $exactKey = $this->buildNameKey($dbSurname, $dbGivenNames);
                $lookup[$exactKey][] = $student;
            }

            $rows = $this->readCsvRows($request->file('csv_file')->getRealPath());

            $matched = [];
            $unmatched = [];

            foreach ($rows as $index => $row) {
                $csvSurname = $row['surname'];
                $csvGivenNames = $row['name'];

                $key = $this->buildNameKey($csvSurname, $csvGivenNames);
                $exactMatches = $lookup[$key] ?? [];

                if (count($exactMatches) === 1) {
                    $student = $exactMatches[0];
                    $matched[] = $this->buildPreviewMatchRow($student, $csvSurname, $csvGivenNames, 'Exact', 100);
                    continue;
                }

                if (count($exactMatches) > 1) {
                    $unmatched[] = [
                        'row_number' => $index + 2,
                        'surname' => $csvSurname,
                        'name' => $csvGivenNames,
                        'reason' => 'Multiple students matched this name exactly.',
                    ];
                    continue;
                }

                $fuzzy = $this->findBestFuzzyStudent($classStudents, $csvSurname, $csvGivenNames);

                if ($fuzzy && $fuzzy['score'] >= 92) {
                    $student = $fuzzy['student'];
                    $matched[] = $this->buildPreviewMatchRow($student, $csvSurname, $csvGivenNames, 'Fuzzy', $fuzzy['score']);
                } else {
                    $unmatched[] = [
                        'row_number' => $index + 2,
                        'surname' => $csvSurname,
                        'name' => $csvGivenNames,
                        'reason' => 'No matching student found in the selected class.',
                    ];
                }
            }

            $matchedStudentIds = collect($matched)->pluck('student_id')->unique()->values()->all();

            $existingAssignments = DB::table('student_subjects')
                ->join('teachers', 'student_subjects.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->where('student_subjects.class_id', $validated['class_id'])
                ->where('student_subjects.academic_year_id', $validated['academic_year_id'])
                ->where('student_subjects.subject_id', $validated['subject_id'])
                ->whereIn('student_subjects.student_id', $matchedStudentIds)
                ->select('student_subjects.student_id', 'student_subjects.teacher_id', 'users.name as teacher_name')
                ->get()
                ->keyBy('student_id');

            $matched = collect($matched)->map(function ($row) use ($existingAssignments, $validated) {
                $assignment = $existingAssignments->get($row['student_id']);
                $row['current_teacher_name'] = $assignment->teacher_name ?? null;
                $row['already_with_selected_teacher'] = $assignment
                    ? (int) $assignment->teacher_id === (int) $validated['teacher_id']
                    : false;
                return $row;
            })->values()->all();

            return redirect()->route('admin.student-subjects.create', [
                'academic_year_id' => $validated['academic_year_id'],
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'is_elective' => $request->boolean('is_elective') ? 1 : 0,
            ])->with('success', count($matched) . ' row(s) matched in preview.')
                ->with('student_subject_import_preview', [
                    'context' => [
                        'academic_year_id' => $validated['academic_year_id'],
                        'class_id' => $validated['class_id'],
                        'subject_id' => $validated['subject_id'],
                        'teacher_id' => $validated['teacher_id'],
                        'is_elective' => $request->boolean('is_elective'),
                    ],
                    'matched' => $matched,
                    'unmatched' => $unmatched,
                ]);
        } catch (\Exception $e) {
            Log::error('Student subject CSV preview failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['csv_file' => 'Failed to preview CSV import. Please check the file format and try again.'])
                ->withInput();
        }
    }

    public function studentSubjectsImportApply(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'is_elective' => 'nullable|boolean',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        try {
            $teacherAssigned = DB::table('teacher_subjects')
                ->where('teacher_id', $validated['teacher_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('class_id', $validated['class_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->exists();

            if (!$teacherAssigned) {
                return redirect()->back()
                    ->withErrors([
                        'teacher_id' => 'The selected teacher is not assigned to this subject for this class and academic year.',
                    ]);
            }

            $studentIds = collect($validated['student_ids'])->map(fn($id) => (int) $id)->values()->all();

            $this->saveStudentTeacherAssignments(
                $validated['academic_year_id'],
                $validated['class_id'],
                $validated['subject_id'],
                $validated['teacher_id'],
                $studentIds,
                $request->boolean('is_elective')
            );

            return redirect()->route('admin.student-subjects.create', [
                'academic_year_id' => $validated['academic_year_id'],
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'is_elective' => $request->boolean('is_elective') ? 1 : 0,
            ])->with('success', 'CSV import applied successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject CSV apply failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to apply CSV import. Please try again.']);
        }
    }

    public function studentSubjectsDestroy(Request $request, $id)
    {
        try {
            StudentSubject::findOrFail($id)->delete();

            return redirect()->route('admin.student-subjects.index', $request->only([
                'academic_year_id',
                'class_id',
                'subject_id',
                'teacher_id',
                'search',
                'page',
            ]))->with('success', 'Assignment removed successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject removal failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to remove assignment. Please try again.']);
        }
    }

    public function studentSubjectsBulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['exists:student_subjects,id'],
        ], [
            'assignment_ids.required' => 'Please select at least one assignment to remove.',
            'assignment_ids.min' => 'Please select at least one assignment to remove.',
        ]);

        try {
            $assignmentIds = collect($validated['assignment_ids'])
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            DB::transaction(function () use ($assignmentIds) {
                StudentSubject::whereIn('id', $assignmentIds)->delete();
            });

            return redirect()->route('admin.student-subjects.index', $request->only([
                'academic_year_id',
                'class_id',
                'subject_id',
                'teacher_id',
                'search',
                'page',
            ]))->with('success', count($assignmentIds) . ' assignments removed successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject bulk removal failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to remove selected assignments. Please try again.']);
        }
    }

    public function getClassesByAcademicYear($academicYearId)
    {
        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        try {
            $classes = ClassModel::whereHas('classSubjects', function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })->orderBy('level')->orderBy('name')->get();

            return response()->json($classes);
        } catch (\Exception $e) {
            Log::error('Failed to load classes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load classes'], 500);
        }
    }

    public function getStudentsByClass($classId, $academicYearId)
    {
        if (!ClassModel::find($classId)) {
            return response()->json(['error' => 'Invalid class'], 404);
        }

        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        try {
            $students = Student::whereHas('classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
                ->with('user')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name ?? 'Unknown Student',
                        'admission_no' => $student->admission_no ?? 'N/A',
                    ];
                });

            return response()->json($students);
        } catch (\Exception $e) {
            Log::error('Failed to load students', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSubjectsByClass($classId, $academicYearId)
    {
        if (!ClassModel::find($classId)) {
            return response()->json(['error' => 'Invalid class'], 404);
        }

        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        try {
            $subjects = Subject::whereHas('classSubjects', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId);
            })->orderBy('name')->get();

            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Failed to load subjects: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load subjects'], 500);
        }
    }

    public function getTeachersBySubject($classId, $subjectId, $academicYearId)
    {
        if (!ClassModel::find($classId)) {
            return response()->json(['error' => 'Invalid class'], 404);
        }

        if (!Subject::find($subjectId)) {
            return response()->json(['error' => 'Invalid subject'], 404);
        }

        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        try {
            $teachers = DB::table('teacher_subjects')
                ->join('teachers', 'teacher_subjects.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->where('teacher_subjects.class_id', $classId)
                ->where('teacher_subjects.subject_id', $subjectId)
                ->where('teacher_subjects.academic_year_id', $academicYearId)
                ->select('teachers.id', 'users.name')
                ->orderBy('users.name')
                ->get();

            return response()->json($teachers);
        } catch (\Exception $e) {
            Log::error('Failed to load teachers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load teachers'], 500);
        }
    }

    public function getStudentsForSubjectTeacher($classId, $academicYearId, $subjectId, $teacherId)
    {
        if (!ClassModel::find($classId)) {
            return response()->json(['error' => 'Invalid class'], 404);
        }

        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        if (!Subject::find($subjectId)) {
            return response()->json(['error' => 'Invalid subject'], 404);
        }

        if (!Teacher::find($teacherId)) {
            return response()->json(['error' => 'Invalid teacher'], 404);
        }

        try {
            $students = Student::whereHas('classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
                ->with('user')
                ->get();

            $subjectAssignments = DB::table('student_subjects')
                ->join('teachers', 'student_subjects.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->where('student_subjects.class_id', $classId)
                ->where('student_subjects.academic_year_id', $academicYearId)
                ->where('student_subjects.subject_id', $subjectId)
                ->select('student_subjects.student_id', 'student_subjects.teacher_id', 'users.name as teacher_name')
                ->get()
                ->keyBy('student_id');

            $result = $students->map(function ($student) use ($subjectAssignments, $teacherId) {
                $assignment = $subjectAssignments->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->user->name ?? 'Unknown Student',
                    'admission_no' => $student->admission_no ?? 'N/A',
                    'assigned_teacher_id' => $assignment->teacher_id ?? null,
                    'assigned_teacher_name' => $assignment->teacher_name ?? null,
                    'assigned_to_current_teacher' => $assignment && (int) $assignment->teacher_id === (int) $teacherId,
                ];
            })->values();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to load assignment students: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load assignment students'], 500);
        }
    }

    public function getTermsByAcademicYear($academicYearId)
    {
        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }

        try {
            $terms = Term::where('academic_year_id', $academicYearId)
                ->orderBy('start_date')
                ->get(['id', 'name']);

            return response()->json($terms);
        } catch (\Exception $e) {
            Log::error('Failed to load terms: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load terms'], 500);
        }
    }

    private function saveStudentTeacherAssignments(
        int $academicYearId,
        int $classId,
        int $subjectId,
        int $teacherId,
        array $studentIds,
        bool $isElective
    ): void {
        DB::transaction(function () use ($academicYearId, $classId, $subjectId, $teacherId, $studentIds, $isElective) {
            DB::table('student_subjects')
                ->where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->where('subject_id', $subjectId)
                ->where('teacher_id', $teacherId)
                ->when(count($studentIds) > 0, function ($query) use ($studentIds) {
                    $query->whereNotIn('student_id', $studentIds);
                })
                ->delete();

            DB::table('student_subjects')
                ->where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->where('subject_id', $subjectId)
                ->whereIn('student_id', $studentIds)
                ->where('teacher_id', '!=', $teacherId)
                ->delete();

            $rows = [];
            foreach ($studentIds as $studentId) {
                $rows[] = [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacherId,
                    'class_id' => $classId,
                    'academic_year_id' => $academicYearId,
                    'is_elective' => $isElective,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('student_subjects')->upsert(
                    $rows,
                    ['student_id', 'subject_id', 'teacher_id', 'academic_year_id'],
                    ['class_id', 'is_elective', 'updated_at']
                );
            }
        });
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty.');
        }

        $normalizedHeader = array_map(fn($item) => strtolower(trim((string) $item)), $header);

        $surnameIndex = array_search('surname', $normalizedHeader);
        $nameIndex = array_search('name', $normalizedHeader);

        if ($surnameIndex === false || $nameIndex === false) {
            fclose($handle);
            throw new \RuntimeException('CSV must contain Surname and Name columns.');
        }

        while (($data = fgetcsv($handle)) !== false) {
            $surname = trim((string) ($data[$surnameIndex] ?? ''));
            $name = trim((string) ($data[$nameIndex] ?? ''));

            if ($surname === '' && $name === '') {
                continue;
            }

            $rows[] = [
                'surname' => $surname,
                'name' => $name,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function splitDatabaseName(?string $fullName): array
    {
        $fullName = $this->normalizeWhitespace($fullName ?? '');
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName);
        $surname = array_pop($parts);
        $givenNames = implode(' ', $parts);

        return [$givenNames, $surname];
    }

    private function buildNameKey(string $surname, string $givenNames): string
    {
        return $this->normalizeNamePart($surname) . '|' . $this->normalizeNamePart($givenNames);
    }

    private function normalizeNamePart(string $value): string
    {
        $value = mb_strtolower($this->normalizeWhitespace($value));
        $value = preg_replace('/[^a-z0-9 ]/u', '', $value) ?? $value;
        return trim($value);
    }

    private function normalizeWhitespace(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    private function buildPreviewMatchRow(Student $student, string $csvSurname, string $csvGivenNames, string $matchType, int $score): array
    {
        return [
            'student_id' => $student->id,
            'student_name' => $student->user->name ?? 'Unknown Student',
            'admission_no' => $student->admission_no ?? 'N/A',
            'csv_surname' => $csvSurname,
            'csv_name' => $csvGivenNames,
            'match_type' => $matchType,
            'score' => $score,
        ];
    }

    private function findBestFuzzyStudent($students, string $csvSurname, string $csvGivenNames): ?array
    {
        $target = $this->buildNameKey($csvSurname, $csvGivenNames);

        $best = null;
        $bestScore = 0;
        $secondBestScore = 0;

        foreach ($students as $student) {
            [$dbGivenNames, $dbSurname] = $this->splitDatabaseName($student->user->name ?? '');
            $candidate = $this->buildNameKey($dbSurname, $dbGivenNames);

            similar_text($target, $candidate, $score);

            if ($score > $bestScore) {
                $secondBestScore = $bestScore;
                $bestScore = (int) round($score);
                $best = $student;
            } elseif ($score > $secondBestScore) {
                $secondBestScore = (int) round($score);
            }
        }

        if ($best && $bestScore >= 92 && ($bestScore - $secondBestScore) >= 3) {
            return [
                'student' => $best,
                'score' => $bestScore,
            ];
        }

        return null;
    }

    private function readMarksSummaryCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty.');
        }

        $header = array_map(fn($item) => trim((string) $item), $header);
        $normalized = array_map(fn($item) => strtolower(trim((string) $item)), $header);

        $surnameIndex = array_search('surname', $normalized);
        $nameIndex = array_search('name', $normalized);
        $classIndex = array_search('class', $normalized);

        if ($surnameIndex === false || $nameIndex === false || $classIndex === false) {
            fclose($handle);
            throw new \RuntimeException('CSV must contain Surname, Name, and Class columns.');
        }

        $subjectColumns = [];
        for ($i = 0; $i < count($header); $i++) {
            $raw = $header[$i];
            $norm = $normalized[$i];

            if (in_array($norm, ['surname', 'name', 'class'], true)) {
                continue;
            }

            if ($raw === '' || str_starts_with($norm, 'unnamed:')) {
                continue;
            }

            $gradeIndex = null;
            if (isset($normalized[$i + 1]) && str_starts_with($normalized[$i + 1], 'unnamed:')) {
                $gradeIndex = $i + 1;
            }

            $subjectColumns[] = [
                'code' => $raw,
                'score_index' => $i,
                'grade_index' => $gradeIndex,
            ];
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [
                'surname' => trim((string) ($data[$surnameIndex] ?? '')),
                'name' => trim((string) ($data[$nameIndex] ?? '')),
                'class' => trim((string) ($data[$classIndex] ?? '')),
                'subjects' => [],
            ];

            foreach ($subjectColumns as $column) {
                $row['subjects'][$column['code']] = [
                    'score' => trim((string) ($data[$column['score_index']] ?? '')),
                    'grade' => $column['grade_index'] !== null
                        ? trim((string) ($data[$column['grade_index']] ?? ''))
                        : null,
                ];
            }

            if ($row['surname'] === '' && $row['name'] === '') {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'header' => $header,
            'subject_columns' => $subjectColumns,
            'rows' => $rows,
        ];
    }

    private function normalizeSubjectCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9&]/', '', $value) ?? $value;

        $map = [
            'EN' => 'EN',
            'ENG' => 'EN',
            'ENGLISH' => 'EN',

            'EFL' => 'EFL',
            'ENGLISHFIRSTLANGUAGE' => 'EFL',
            'ENGLISHASAFIRSTLANGUAGE' => 'EFL',

            'ESL' => 'ESL',
            'ENGLISHSECONDLANGUAGE' => 'ESL',
            'ENGLISHASASECONDLANGUAGE' => 'ESL',

            'EL' => 'EL',
            'LIT' => 'EL',
            'LITERATURE' => 'EL',
            'ENGLISHLITERATURE' => 'EL',

            'MAE' => 'MAE',
            'EXTMATH' => 'MAE',
            'EXTENDEDMATH' => 'MAE',
            'EXTENDEDMATHEMATICS' => 'MAE',

            'MAC' => 'MAC',
            'COREMATH' => 'MAC',
            'COREMATHEMATICS' => 'MAC',

            'MATH' => 'MATH',
            'MATHS' => 'MATH',
            'MATHEMATICS' => 'MATH',

            'AM' => 'AM',
            'ADDM' => 'AM',
            'ADDMATH' => 'AM',
            'ADDMATHS' => 'AM',
            'ADDITIONALMATHEMATICS' => 'AM',
            'ADDITIONALMATH' => 'AM',

            'BIO' => 'BIO',
            'BI' => 'BIO',
            'BIOLOGY' => 'BIO',

            'PHY' => 'PHY',
            'PH' => 'PHY',
            'PHYS' => 'PHY',
            'PHYSICS' => 'PHY',

            'CHEM' => 'CHEM',
            'CH' => 'CHEM',
            'CHEMISTRY' => 'CHEM',

            'CS' => 'CS',
            'COMPSCI' => 'CS',
            'COMPUTERSCIENCE' => 'CS',
            'CSC' => 'CS',

            'GEO' => 'GEO',
            'GEOG' => 'GEO',
            'GEOGRAPHY' => 'GEO',

            'SET' => 'SET',
            'SE' => 'SET',
            'SETSWANA' => 'SET',

            'BS' => 'BS',
            'BUSINESS' => 'BS',
            'BUSINESSSTUDIES' => 'BS',

            'ACC' => 'ACC',
            'AC' => 'ACC',
            'ACCOUNTING' => 'ACC',

            'EC' => 'EC',
            'ECO' => 'EC',
            'ECON' => 'EC',
            'ECONOMICS' => 'EC',

            'FR' => 'FR',
            'FRENCH' => 'FR',

            'AG' => 'AG',
            'AGRIC' => 'AG',
            'AGRICULTURE' => 'AG',

            'TT' => 'T&T',
            'T&T' => 'T&T',
            'TANDT' => 'T&T',
            'TRAVELTOURISM' => 'T&T',
        ];

        return $map[$value] ?? $value;
    }

    private function normalizeClassLabel(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = str_replace('FORM', '', $value);
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        return trim($value);
    }

    private function rowMatchesSelectedClass(?string $csvClass, string $selectedClassName): bool
    {
        return $this->normalizeClassLabel((string) $csvClass) === $this->normalizeClassLabel($selectedClassName);
    }

    private function findStudentFromCsvRow(string $surname, string $givenNames, array $studentLookup, $classStudents): ?Student
    {
        $key = $this->buildNameKey($surname, $givenNames);
        $exactMatches = $studentLookup[$key] ?? [];

        if (count($exactMatches) === 1) {
            return $exactMatches[0];
        }

        if (count($exactMatches) > 1) {
            return null;
        }

        $fuzzy = $this->findBestFuzzyStudent($classStudents, $surname, $givenNames);
        if ($fuzzy && $fuzzy['score'] >= 92) {
            return $fuzzy['student'];
        }

        return null;
    }

    private function resolveTeacherForStudentSubject(int $studentId, int $subjectId, int $classId, int $academicYearId): ?int
    {
        $studentSubject = StudentSubject::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if ($studentSubject && $studentSubject->teacher_id) {
            return (int) $studentSubject->teacher_id;
        }

        $teacherIds = DB::table('teacher_subjects')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('teacher_id')
            ->unique()
            ->values();

        if ($teacherIds->count() === 1) {
            return (int) $teacherIds->first();
        }

        return null;
    }

    private function normalizeScore(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $score = (float) $value;

        if ($score < 0 || $score > 100) {
            return null;
        }

        return $score;
    }

    private function normalizeGrade(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        return $value === '' ? null : $value;
    }

    private function calculateGradeForMark($midterm, $endterm): ?string
    {
        if ($midterm === null && $endterm === null) {
            return null;
        }

        if ($midterm !== null && $endterm !== null) {
            $average = ((float) $midterm + (float) $endterm) / 2;
            return $this->marksService->calculateGrade($average);
        }

        $singleScore = $midterm ?? $endterm;
        return $singleScore !== null ? $this->marksService->calculateGrade((float) $singleScore) : null;
    }
}
