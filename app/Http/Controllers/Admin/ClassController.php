<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::with(['students', 'classTeacher.user', 'academicYear'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        $classTeachers = Teacher::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['teacher', 'headmaster'])
                    ->where('status', 'active');
            })
            ->get()
            ->sortBy(fn($teacher) => $teacher->user->name ?? '')
            ->values();

        return view('admin.classes.create', compact('academicYears', 'classTeachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:classes,name'],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
            'class_list_file' => ['nullable', 'file', 'mimes:csv,txt'],
        ]);

        if (!empty($validated['class_teacher_id'])) {
            $teacherExists = Teacher::where('id', $validated['class_teacher_id'])
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['teacher', 'headmaster'])
                        ->where('status', 'active');
                })
                ->exists();

            if (!$teacherExists) {
                return back()
                    ->withErrors(['class_teacher_id' => 'Selected class teacher is invalid.'])
                    ->withInput();
            }
        }

        try {
            DB::transaction(function () use ($request, $validated) {
                $class = ClassModel::create([
                    'name' => $validated['name'],
                    'level' => $validated['level'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'class_teacher_id' => $validated['class_teacher_id'] ?? null,
                ]);

                if ($request->hasFile('class_list_file')) {
                    $this->importClassListFromCsv(
                        $request->file('class_list_file')->getRealPath(),
                        $class,
                        (int) $validated['academic_year_id']
                    );
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['class_list_file' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.classes.index')
            ->with(
                'success',
                $request->hasFile('class_list_file')
                    ? 'Class created successfully and class list imported.'
                    : 'Class created successfully.'
            );
    }

    public function edit(Request $request, ClassModel $class)
    {
        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        $classTeachers = Teacher::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['teacher', 'headmaster'])
                    ->where('status', 'active');
            })
            ->get()
            ->sortBy(fn($teacher) => $teacher->user->name ?? '')
            ->values();

        $studentSearch = trim((string) $request->query('student_search', ''));

        $studentsQuery = $class->students()
            ->with('user')
            ->when($studentSearch !== '', function ($query) use ($studentSearch) {
                $query->where(function ($q) use ($studentSearch) {
                    $q->where('admission_no', 'like', '%' . $studentSearch . '%')
                        ->orWhereHas('user', function ($userQuery) use ($studentSearch) {
                            $userQuery->where('name', 'like', '%' . $studentSearch . '%');
                        });
                });
            })
            ->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'students.user_id')
                    ->limit(1)
            );

        $students = $studentsQuery->paginate(15)->appends($request->query());

        $class->load([
            'classTeacher.user',
            'academicYear',
        ]);

        return view('admin.classes.edit', compact(
            'class',
            'academicYears',
            'classTeachers',
            'students',
            'studentSearch'
        ));
    }

    public function update(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('classes', 'name')->ignore($class->id)],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        if (!empty($validated['class_teacher_id'])) {
            $teacherExists = Teacher::where('id', $validated['class_teacher_id'])
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['teacher', 'headmaster'])
                        ->where('status', 'active');
                })
                ->exists();

            if (!$teacherExists) {
                return back()
                    ->withErrors(['class_teacher_id' => 'Selected class teacher is invalid.'])
                    ->withInput();
            }
        }

        $class->update($validated);

        return redirect()
            ->route('admin.classes.edit', array_merge(
                ['class' => $class->id],
                $request->only(['student_search', 'page'])
            ))
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassModel $class)
    {
        $class->loadCount([
            'students',
            'historyRecords',
            'classSubjects',
            'teacherSubjects',
            'marks',
            'studentSubjects',
            'attendances',
            'punctualities',
            'behaviourRecords',
        ]);

        $blockingReasons = [];

        if ($class->students_count > 0) {
            $blockingReasons[] = 'it still has students assigned to it';
        }

        if ($class->history_records_count > 0) {
            $blockingReasons[] = 'it has student class history records';
        }

        if ($class->class_subjects_count > 0) {
            $blockingReasons[] = 'it has class subject assignments';
        }

        if ($class->teacher_subjects_count > 0) {
            $blockingReasons[] = 'it has teacher subject assignments';
        }

        if ($class->marks_count > 0) {
            $blockingReasons[] = 'it has marks records';
        }

        if ($class->student_subjects_count > 0) {
            $blockingReasons[] = 'it has student subject records';
        }

        if ($class->attendances_count > 0) {
            $blockingReasons[] = 'it has attendance records';
        }

        if ($class->punctualities_count > 0) {
            $blockingReasons[] = 'it has punctuality records';
        }

        if ($class->behaviour_records_count > 0) {
            $blockingReasons[] = 'it has behaviour records';
        }

        if (!empty($blockingReasons)) {
            return redirect()
                ->route('admin.classes.index')
                ->withErrors([
                    'delete' => 'This class cannot be deleted because ' . implode(', ', $blockingReasons) . '.',
                ]);
        }

        $class->delete();

        return redirect()
            ->route('admin.classes.index')
            ->with('success', 'Class deleted successfully.');
    }

    public function removeStudent(Request $request, ClassModel $class, Student $student)
    {
        if ((int) $student->current_class_id !== (int) $class->id) {
            return redirect()
                ->route('admin.classes.edit', array_merge(
                    ['class' => $class->id],
                    $request->only(['student_search', 'page'])
                ))
                ->withErrors([
                    'remove_student' => 'This student is not currently assigned to this class.',
                ]);
        }

        DB::transaction(function () use ($class, $student) {
            $student->update([
                'current_class_id' => null,
            ]);

            StudentClassHistory::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->where('academic_year_id', $class->academic_year_id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.classes.edit', array_merge(
                ['class' => $class->id],
                $request->only(['student_search', 'page'])
            ))
            ->with('success', 'Student removed from class successfully.');
    }

    public function bulkRemoveStudents(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.min' => 'Please select at least one student.',
        ]);

        $studentIds = collect($validated['student_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $students = Student::whereIn('id', $studentIds)
            ->where('current_class_id', $class->id)
            ->get();

        if ($students->isEmpty()) {
            return redirect()
                ->route('admin.classes.edit', array_merge(
                    ['class' => $class->id],
                    $request->only(['student_search', 'page'])
                ))
                ->withErrors([
                    'remove_student' => 'None of the selected students are currently assigned to this class.',
                ]);
        }

        DB::transaction(function () use ($class, $students) {
            $ids = $students->pluck('id');

            Student::whereIn('id', $ids)->update([
                'current_class_id' => null,
                'updated_at' => now(),
            ]);

            StudentClassHistory::whereIn('student_id', $ids)
                ->where('class_id', $class->id)
                ->where('academic_year_id', $class->academic_year_id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'updated_at' => now(),
                ]);
        });

        $removedCount = $students->count();

        $query = $request->only(['student_search', 'page']);

        if ($removedCount >= $students->count() && (int) ($query['page'] ?? 1) > 1 && $students->count() === 1) {
            $query['page'] = max(1, ((int) $query['page']) - 1);
        }

        return redirect()
            ->route('admin.classes.edit', array_merge(
                ['class' => $class->id],
                $query
            ))
            ->with('success', $removedCount . ' student(s) removed from class successfully.');
    }

    protected function importClassListFromCsv(string $filePath, ClassModel $class, int $academicYearId): void
    {
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new \RuntimeException('The CSV file is empty.');
        }

        $normalizedHeader = array_map(function ($value) {
            return Str::lower(trim((string) $value));
        }, $header);

        $surnameIndex = array_search('surname', $normalizedHeader, true);
        $nameIndex = array_search('name', $normalizedHeader, true);
        $genderIndex = array_search('gender', $normalizedHeader, true);
        $dobIndex = array_search('date_of_birth', $normalizedHeader, true);
        $admissionNoIndex = array_search('admission_no', $normalizedHeader, true);

        if ($surnameIndex === false || $nameIndex === false || $genderIndex === false || $dobIndex === false) {
            fclose($handle);
            throw new \RuntimeException(
                'CSV must contain these columns: surname, name, gender, date_of_birth. admission_no is optional.'
            );
        }

        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $surname = trim((string) ($row[$surnameIndex] ?? ''));
            $name = trim((string) ($row[$nameIndex] ?? ''));
            $gender = Str::lower(trim((string) ($row[$genderIndex] ?? '')));
            $dateOfBirth = trim((string) ($row[$dobIndex] ?? ''));
            $admissionNo = $admissionNoIndex !== false ? trim((string) ($row[$admissionNoIndex] ?? '')) : '';

            if ($surname === '' || $name === '' || $gender === '' || $dateOfBirth === '') {
                fclose($handle);
                throw new \RuntimeException("Row {$rowNumber}: surname, name, gender, and date_of_birth are required.");
            }

            if (!in_array($gender, ['male', 'female'], true)) {
                fclose($handle);
                throw new \RuntimeException("Row {$rowNumber}: gender must be either 'male' or 'female'.");
            }

            if (!$this->isValidDate($dateOfBirth)) {
                fclose($handle);
                throw new \RuntimeException("Row {$rowNumber}: date_of_birth must be in YYYY-MM-DD format.");
            }

            if ($admissionNo === '') {
                $admissionNo = $this->generateAdmissionNumber();
            } else {
                if (Student::where('admission_no', $admissionNo)->exists()) {
                    fclose($handle);
                    throw new \RuntimeException("Row {$rowNumber}: admission number '{$admissionNo}' already exists.");
                }
            }

            $fullName = trim($name . ' ' . $surname);
            $email = $this->generateUniqueStudentEmail($name, $surname);

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'student',
                'status' => 'active',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'admission_no' => $admissionNo,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'current_class_id' => $class->id,
                'results_access' => true,
                'fees_blocked' => false,
            ]);

            StudentClassHistory::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYearId,
                'is_current' => true,
            ]);
        }

        fclose($handle);
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

    protected function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    protected function generateUniqueStudentEmail(string $name, string $surname): string
    {
        $base = Str::lower(Str::slug($name . '.' . $surname, '.'));

        if ($base === '') {
            $base = 'student';
        }

        $email = $base . '@student.local';
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = $base . $counter . '@student.local';
            $counter++;
        }

        return $email;
    }

    protected function generateAdmissionNumber(): string
    {
        $prefix = 'ADM' . now()->format('y');
        $counter = 1;

        do {
            $candidate = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            $exists = Student::where('admission_no', $candidate)->exists();
            $counter++;
        } while ($exists);

        return $candidate;
    }
}
