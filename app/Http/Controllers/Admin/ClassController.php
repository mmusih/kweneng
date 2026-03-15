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

    public function edit(ClassModel $class)
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

        return view('admin.classes.edit', compact('class', 'academicYears', 'classTeachers'));
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
            ->route('admin.classes.index')
            ->with('success', 'Class updated successfully.');
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
