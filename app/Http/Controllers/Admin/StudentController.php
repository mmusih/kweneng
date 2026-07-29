<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateLoginSlip;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentSubject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user', 'currentClass');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('admission_no', 'like', "%{$search}%")
                    ->orWhere('identity_document_number', 'like', "%{$search}%")
                    ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('current_class_id', $request->class_id);
        }

        $students = $query
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $classes = ClassModel::all();

        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create()
    {
        $classes = ClassModel::all();
        $identityDocumentTypes = Student::identityDocumentTypes();

        return view('admin.students.create', compact('classes', 'identityDocumentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules($request));

        try {
            DB::beginTransaction();

            $temporaryPassword = $this->generateTemporaryPassword();

            $user = User::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'password'             => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'role'                 => 'student',
                'status'               => 'active',
            ]);

            $photoPath = null;

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
            }

            $student = Student::create([
                'user_id'                        => $user->id,
                'admission_no'                   => $validated['admission_no'] ?? $this->generateLegacyAdmissionNo($validated),
                'gender'                         => $validated['gender'],
                'date_of_birth'                  => $validated['date_of_birth'],
                'nationality'                    => $validated['nationality'],
                'identity_document_type'         => $validated['identity_document_type'],
                'identity_document_number'       => $this->normalizeDocumentNumber($validated['identity_document_number']),
                'current_class_id'               => $validated['current_class_id'] ?? null,
                'photo'                          => $photoPath,
                'emergency_contact_name'         => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone'        => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_alt_phone'    => $validated['emergency_contact_alt_phone'] ?? null,
                'emergency_contact_address'      => $validated['emergency_contact_address'] ?? null,
                'medical_notes'                  => $validated['medical_notes'] ?? null,
                'results_access'                 => $request->has('results_access'),
                'fees_blocked'                   => $request->has('fees_blocked'),
            ]);

            if (! empty($validated['current_class_id'])) {
                $this->upsertCurrentClassHistory($student, (int) $validated['current_class_id']);
            }

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with(
                    'success',
                    'Student created successfully' .
                        (! empty($validated['current_class_id']) ? ' and enrolled in class.' : '.') .
                        ' Temporary password: ' . $temporaryPassword .
                        ' (Student must change it on first login.)'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to create student: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Student $student)
    {
        $student->load([
            'user',
            'currentClass.academicYear',
            'classHistory.class',
            'classHistory.academicYear',
            'parents.user',
            'studentSubjects.subject',
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = ClassModel::all();
        $identityDocumentTypes = Student::identityDocumentTypes();

        return view('admin.students.edit', compact('student', 'classes', 'identityDocumentTypes'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate($this->validationRules($request, $student));

        try {
            DB::beginTransaction();

            $student->user->update([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);

            $photoPath = $student->photo;

            if ($request->hasFile('photo')) {
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }

                $photoPath = $request->file('photo')->store('students', 'public');
            }

            $originalClassId = $student->current_class_id;
            $admissionNo = $validated['admission_no'] ?? $student->admission_no;

            if (! filled($admissionNo)) {
                $admissionNo = $this->generateLegacyAdmissionNo($validated);
            }

            $student->update([
                'admission_no'                   => $admissionNo,
                'gender'                         => $validated['gender'],
                'date_of_birth'                  => $validated['date_of_birth'],
                'nationality'                    => $validated['nationality'],
                'identity_document_type'         => $validated['identity_document_type'],
                'identity_document_number'       => $this->normalizeDocumentNumber($validated['identity_document_number']),
                'current_class_id'               => $validated['current_class_id'] ?? null,
                'photo'                          => $photoPath,
                'emergency_contact_name'         => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone'        => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_alt_phone'    => $validated['emergency_contact_alt_phone'] ?? null,
                'emergency_contact_address'      => $validated['emergency_contact_address'] ?? null,
                'medical_notes'                  => $validated['medical_notes'] ?? null,
                'results_access'                 => $request->has('results_access'),
                'fees_blocked'                   => $request->has('fees_blocked'),
            ]);

            if (! empty($validated['current_class_id']) && (int) $validated['current_class_id'] !== (int) $originalClassId) {
                $this->upsertCurrentClassHistory($student, (int) $validated['current_class_id']);
            }

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update student: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function resetPassword(Student $student)
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $student->user->update([
            'password'             => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return redirect()->back()->with(
            'success',
            'Password reset successfully. Temporary password: ' . $temporaryPassword . ' (Student must change it on first login.)'
        );
    }

    /**
     * Generate login slips for selected students.
     */
    public function printLogins(Request $request)
    {
        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.min'      => 'Please select at least one student.',
        ]);

        $students = Student::with(['user', 'currentClass'])
            ->whereIn('id', $validated['student_ids'])
            ->orderBy('admission_no')
            ->get();

        $logins = [];

        try {
            foreach ($students as $student) {
                if (! $student->user) {
                    continue;
                }

                $slip = GenerateLoginSlip::for($student);

                $logins[] = array_merge($slip, [
                    'class' => $student->currentClass?->name ?? 'N/A',
                ]);
            }

            return view('admin.students.print-logins', compact('logins'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to generate login slips: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Student $student)
    {
        try {
            DB::beginTransaction();

            $this->deleteStudentSafely($student);

            DB::commit();

            return redirect()->route('admin.students.index', $request->only([
                'search',
                'class_id',
                'page',
            ]))->with('success', 'Student deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete student: ' . $e->getMessage()]);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.min'      => 'Please select at least one student.',
        ]);

        try {
            $studentIds = collect($validated['student_ids'])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            DB::beginTransaction();

            $students = Student::with('user')
                ->whereIn('id', $studentIds)
                ->get();

            foreach ($students as $student) {
                $this->deleteStudentSafely($student);
            }

            DB::commit();

            return redirect()->route('admin.students.index', $request->only([
                'search',
                'class_id',
                'page',
            ]))->with('success', $students->count() . ' student(s) deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Bulk delete failed: ' . $e->getMessage()]);
        }
    }

    private function validationRules(Request $request, ?Student $student = null): array
    {
        $documentType = $request->input('identity_document_type');

        return [
            'name'                           => ['required', 'string', 'max:255'],
            'email'                          => ['required', 'email', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'admission_no'                   => ['nullable', 'string', 'max:255', Rule::unique('students', 'admission_no')->ignore($student?->id)],
            'gender'                         => ['required', Rule::in(['male', 'female'])],
            'date_of_birth'                  => ['required', 'date'],
            'nationality'                    => ['required', 'string', 'max:100'],
            'identity_document_type'         => ['required', Rule::in(array_keys(Student::identityDocumentTypes()))],
            'identity_document_number'       => [
                'required',
                'string',
                'max:100',
                Rule::unique('students', 'identity_document_number')
                    ->where(fn ($query) => $query->where('identity_document_type', $documentType))
                    ->ignore($student?->id),
            ],
            'current_class_id'               => ['nullable', 'exists:classes,id'],
            'photo'                          => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'emergency_contact_name'         => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone'        => ['nullable', 'string', 'max:50'],
            'emergency_contact_alt_phone'    => ['nullable', 'string', 'max:50'],
            'emergency_contact_address'      => ['nullable', 'string', 'max:1000'],
            'medical_notes'                  => ['nullable', 'string', 'max:2000'],
            'results_access'                 => ['nullable'],
            'fees_blocked'                   => ['nullable'],
        ];
    }

    private function upsertCurrentClassHistory(Student $student, int $classId): void
    {
        $currentAcademicYear = AcademicYear::where('status', 'open')->first();

        if (! $currentAcademicYear) {
            return;
        }

        StudentClassHistory::updateOrCreate(
            [
                'student_id'       => $student->id,
                'academic_year_id' => $currentAcademicYear->id,
            ],
            [
                'class_id'   => $classId,
                'status'     => 'active',
                'updated_at' => now(),
            ]
        );
    }

    private function deleteStudentSafely(Student $student): void
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        StudentClassHistory::where('student_id', $student->id)->delete();
        StudentSubject::where('student_id', $student->id)->delete();
        Mark::where('student_id', $student->id)->delete();

        if ($student->user) {
            $student->user->delete();
        } else {
            $student->delete();
        }
    }

    private function generateLegacyAdmissionNo(array $validated): string
    {
        $type = strtoupper(str_replace('_', '', (string) ($validated['identity_document_type'] ?? 'DOC')));
        $number = $this->normalizeDocumentNumber((string) ($validated['identity_document_number'] ?? Str::random(8)));
        $base = 'ID-' . $type . '-' . preg_replace('/[^A-Za-z0-9]/', '', $number);
        $base = Str::limit($base, 230, '');
        $candidate = $base;
        $counter = 1;

        while (Student::where('admission_no', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function normalizeDocumentNumber(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    }

    /**
     * Used by store() and resetPassword() only.
     * printLogins() delegates entirely to GenerateLoginSlip.
     */
    private function generateTemporaryPassword(int $length = 10): string
    {
        return Str::upper(Str::random($length));
    }
}
