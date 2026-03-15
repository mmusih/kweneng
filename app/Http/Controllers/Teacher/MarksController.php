<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\MarksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    protected $marksService;
    protected $activityLogService;

    public function __construct(
        MarksService $marksService,
        ActivityLogService $activityLogService
    ) {
        $this->marksService = $marksService;
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $teacher = Auth::user()->teacher;
        $classes = $this->marksService->getTeacherClassesForMarks($teacher);

        return view('teacher.marks.index', compact('classes'));
    }

    public function showClassSubjects(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id'
        ]);

        $subjects = Subject::whereHas('teacherSubjects', function ($query) use ($validated) {
            $query->where('teacher_id', Auth::user()->teacher->id)
                ->where('class_id', $validated['class_id'])
                ->where('academic_year_id', $validated['academic_year_id']);
        })->get();

        return response()->json([
            'subjects' => $subjects
        ]);
    }

    public function showStudents(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id'
        ]);

        $students = $this->marksService->getStudentsForMarksEntry(
            $validated['class_id'],
            $validated['subject_id'],
            $validated['academic_year_id']
        );

        $existingMarks = Mark::where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->get()
            ->keyBy('student_id');

        $term = Term::findOrFail($validated['term_id']);

        return response()->json([
            'students' => $students,
            'existing_marks' => $existingMarks,
            'locks' => [
                'term_locked' => $term->isLocked(),
                'midterm_locked' => $term->isMidtermLocked(),
                'endterm_locked' => $term->isEndtermLocked(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'marks' => 'array',
            'marks.*' => 'array',
            'marks.*.midterm' => 'nullable|numeric|min:0|max:100',
            'marks.*.endterm' => 'nullable|numeric|min:0|max:100',
            'marks.*.remarks' => 'nullable|string|max:500'
        ]);

        $teacher = Auth::user()->teacher;
        $term = Term::findOrFail($validated['term_id']);

        if (!$this->marksService->validateMarksEntry(
            $teacher,
            $validated['class_id'],
            $validated['subject_id'],
            $validated['academic_year_id'],
            $validated['term_id']
        )) {
            return redirect()->back()->withErrors([
                'marks' => 'You do not have permission to enter marks for this class/subject/term combination or the term is not active.'
            ]);
        }

        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'marks' => 'This term is locked. No marks can be changed.'
            ]);
        }

        $marksData = [];

        foreach ($validated['marks'] as $studentId => $scores) {
            $studentExists = Student::where('id', $studentId)->exists();
            if (!$studentExists) {
                return redirect()->back()->withErrors([
                    'marks' => "Invalid student ID: {$studentId}"
                ]);
            }

            $existingMark = Mark::where('student_id', $studentId)
                ->where('subject_id', $validated['subject_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('term_id', $validated['term_id'])
                ->first();

            $incomingMidterm = $scores['midterm'] ?? null;
            $incomingEndterm = $scores['endterm'] ?? null;

            if ($term->isMidtermLocked()) {
                $incomingMidterm = $existingMark?->midterm_score;
            }

            if ($term->isEndtermLocked()) {
                $incomingEndterm = $existingMark?->endterm_score;
            }

            $marksData[] = [
                'student_id' => $studentId,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'],
                'teacher_id' => $teacher->id,
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'midterm_score' => $incomingMidterm,
                'endterm_score' => $incomingEndterm,
                'remarks' => $scores['remarks'] ?? null,
            ];
        }

        $result = $this->marksService->bulkUpsertMarks($marksData);

        if ($result['success']) {
            $this->activityLogService->log(
                'marks.bulk_saved',
                'Teacher saved marks in bulk',
                null,
                [
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'term_id' => $validated['term_id'],
                    'records_count' => count($marksData),
                ],
                request()
            );

            $messages = [];

            if ($term->isMidtermLocked()) {
                $messages[] = 'Midterm marks were locked and were not changed.';
            }

            if ($term->isEndtermLocked()) {
                $messages[] = 'Endterm marks were locked and were not changed.';
            }

            $baseMessage = $result['message'];
            $extraMessage = count($messages) ? ' ' . implode(' ', $messages) : '';

            return redirect()->back()->with('success', $baseMessage . $extraMessage);
        }

        return redirect()->back()->withErrors([
            'marks' => $result['message']
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'exam_type' => 'required|in:midterm,endterm',
            'marks_import_file' => 'required|file|mimes:csv,txt',
        ]);

        $teacher = Auth::user()->teacher;
        $term = Term::findOrFail($validated['term_id']);

        if (!$this->marksService->validateMarksEntry(
            $teacher,
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            (int) $validated['academic_year_id'],
            (int) $validated['term_id']
        )) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'You do not have permission to import marks for this class, subject, and term.'
            ]);
        }

        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'This term is locked. No marks can be imported.'
            ]);
        }

        if ($validated['exam_type'] === 'midterm' && $term->isMidtermLocked()) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'Midterm marks are locked for this term.'
            ]);
        }

        if ($validated['exam_type'] === 'endterm' && $term->isEndtermLocked()) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'Endterm marks are locked for this term.'
            ]);
        }

        $students = $this->marksService->getStudentsForMarksEntry(
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            (int) $validated['academic_year_id']
        );

        $studentMap = collect($students)->mapWithKeys(function ($studentData) {
            $student = $studentData['student'];
            $fullName = trim((string) ($student->user->name ?? ''));

            $parts = preg_split('/\s+/', $fullName);
            $firstSurname = strtolower(trim(array_pop($parts) ?? ''));
            $otherNames = strtolower(trim(implode(' ', $parts)));

            return [
                $firstSurname . '|' . $otherNames => $student
            ];
        });

        $filePath = $request->file('marks_import_file')->getRealPath();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'Unable to open the uploaded CSV file.'
            ]);
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return redirect()->back()->withErrors([
                'marks_import_file' => 'The uploaded CSV file is empty.'
            ]);
        }

        $normalizedHeader = array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $header);

        $surnameIndex = array_search('surname', $normalizedHeader, true);
        $nameIndex = array_search('name', $normalizedHeader, true);
        $scoreIndex = array_search('score', $normalizedHeader, true);
        $remarksIndex = array_search('remarks', $normalizedHeader, true);

        if ($surnameIndex === false || $nameIndex === false || $scoreIndex === false) {
            fclose($handle);
            return redirect()->back()->withErrors([
                'marks_import_file' => 'CSV must contain these columns: surname, name, score. remarks is optional.'
            ]);
        }

        $marksData = [];
        $rowNumber = 1;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $surname = strtolower(trim((string) ($row[$surnameIndex] ?? '')));
            $names = strtolower(trim((string) ($row[$nameIndex] ?? '')));
            $scoreRaw = trim((string) ($row[$scoreIndex] ?? ''));
            $remarks = $remarksIndex !== false ? trim((string) ($row[$remarksIndex] ?? '')) : null;

            if ($surname === '' || $names === '' || $scoreRaw === '') {
                $errors[] = "Row {$rowNumber}: surname, name, and score are required.";
                continue;
            }

            if (!is_numeric($scoreRaw)) {
                $errors[] = "Row {$rowNumber}: score must be numeric.";
                continue;
            }

            $score = (float) $scoreRaw;

            if ($score < 0 || $score > 100) {
                $errors[] = "Row {$rowNumber}: score must be between 0 and 100.";
                continue;
            }

            $studentKey = $surname . '|' . $names;
            $student = $studentMap->get($studentKey);

            if (!$student) {
                $errors[] = "Row {$rowNumber}: student '{$surname}, {$names}' was not found in the selected class.";
                continue;
            }

            $existingMark = Mark::where('student_id', $student->id)
                ->where('subject_id', $validated['subject_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('term_id', $validated['term_id'])
                ->first();

            $midtermScore = $existingMark?->midterm_score;
            $endtermScore = $existingMark?->endterm_score;

            if ($validated['exam_type'] === 'midterm') {
                $midtermScore = $score;
            }

            if ($validated['exam_type'] === 'endterm') {
                $endtermScore = $score;
            }

            $marksData[] = [
                'student_id' => $student->id,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'],
                'teacher_id' => $teacher->id,
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'midterm_score' => $midtermScore,
                'endterm_score' => $endtermScore,
                'remarks' => $remarks !== '' ? $remarks : ($existingMark?->remarks),
            ];
        }

        fclose($handle);

        if (count($errors) > 0) {
            return redirect()->back()->withErrors($errors);
        }

        if (count($marksData) === 0) {
            return redirect()->back()->withErrors([
                'marks_import_file' => 'No valid rows were found in the uploaded file.'
            ]);
        }

        $result = $this->marksService->bulkUpsertMarks($marksData);

        if ($result['success']) {
            $this->activityLogService->log(
                'marks.imported',
                'Teacher imported marks from CSV',
                null,
                [
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'term_id' => $validated['term_id'],
                    'exam_type' => $validated['exam_type'],
                    'records_count' => count($marksData),
                ],
                request()
            );

            return redirect()->back()->with('success', 'Marks imported successfully.');
        }

        return redirect()->back()->withErrors([
            'marks_import_file' => $result['message']
        ]);
    }

    public function edit($id)
    {
        $mark = Mark::findOrFail($id);

        if ($mark->teacher_id !== Auth::user()->teacher->id) {
            abort(403);
        }

        $term = Term::findOrFail($mark->term_id);

        return view('teacher.marks.edit', compact('mark', 'term'));
    }

    public function update(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);

        if ($mark->teacher_id !== Auth::user()->teacher->id) {
            abort(403);
        }

        $term = Term::findOrFail($mark->term_id);

        if ($term->isLocked()) {
            return redirect()->back()->withErrors([
                'marks' => 'This term is locked. No marks can be changed.'
            ]);
        }

        $validated = $request->validate([
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'endterm_score' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:500'
        ]);

        $updateData = [
            'remarks' => $validated['remarks'] ?? $mark->remarks,
        ];

        $updateData['midterm_score'] = $term->isMidtermLocked()
            ? $mark->midterm_score
            : ($validated['midterm_score'] ?? null);

        $updateData['endterm_score'] = $term->isEndtermLocked()
            ? $mark->endterm_score
            : ($validated['endterm_score'] ?? null);

        $midtermScore = $updateData['midterm_score'];
        $endtermScore = $updateData['endterm_score'];

        $average = null;
        if ($midtermScore !== null && $endtermScore !== null) {
            $average = ($midtermScore + $endtermScore) / 2;
        } elseif ($midtermScore !== null) {
            $average = $midtermScore;
        } elseif ($endtermScore !== null) {
            $average = $endtermScore;
        }

        $updateData['grade'] = $average !== null
            ? $this->marksService->calculateGrade($average)
            : null;

        $mark->update($updateData);

        $this->activityLogService->log(
            'marks.updated',
            'Teacher updated a mark',
            $mark,
            [
                'student_id' => $mark->student_id,
                'subject_id' => $mark->subject_id,
                'term_id' => $mark->term_id,
            ],
            request()
        );

        $messages = [];
        if ($term->isMidtermLocked()) {
            $messages[] = 'Midterm marks are locked.';
        }
        if ($term->isEndtermLocked()) {
            $messages[] = 'Endterm marks are locked.';
        }

        $message = 'Mark updated successfully.';
        if (count($messages)) {
            $message .= ' ' . implode(' ', $messages);
        }

        return redirect()->route('teacher.marks.index')->with('success', $message);
    }

    public function loadTerms($academicYearId)
    {
        try {
            $terms = Term::where('academic_year_id', $academicYearId)
                ->where('status', '!=', 'locked')
                ->select('id', 'name', 'midterm_locked', 'endterm_locked')
                ->orderBy('name')
                ->get();

            return response()->json($terms);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
