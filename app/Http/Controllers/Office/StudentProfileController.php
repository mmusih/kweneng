<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'currentClass']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhere('identity_document_number', 'like', "%{$search}%")
                    ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('current_class_id', $request->class_id);
        }

        if ($request->boolean('incomplete')) {
            $query->where(function ($q) {
                $q->whereNull('nationality')
                    ->orWhereNull('identity_document_type')
                    ->orWhereNull('identity_document_number')
                    ->orWhereNull('emergency_contact_name')
                    ->orWhereNull('emergency_contact_phone');
            });
        }

        $students = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $classes = ClassModel::orderBy('level')->orderBy('name')->get();

        return view('office.students.index', compact('students', 'classes'));
    }

    public function show(Student $student)
    {
        $student->load(['user', 'currentClass.academicYear', 'parents.user', 'studentSubjects.subject']);

        return view('office.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $student->load('user');
        $classes = ClassModel::orderBy('level')->orderBy('name')->get();
        $identityDocumentTypes = Student::identityDocumentTypes();

        return view('office.students.edit', compact('student', 'classes', 'identityDocumentTypes'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:120'],
            'identity_document_type' => ['required', Rule::in(array_keys(Student::identityDocumentTypes()))],
            'identity_document_number' => ['required', 'string', 'max:120'],
            'current_class_id' => ['nullable', 'exists:classes,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:60'],
            'emergency_contact_alt_phone' => ['nullable', 'string', 'max:60'],
            'emergency_contact_address' => ['nullable', 'string', 'max:1000'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $student, $validated) {
            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $photoPath = $student->photo;
            if ($request->hasFile('photo')) {
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }
                $photoPath = $request->file('photo')->store('students', 'public');
            }

            $student->update([
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'nationality' => $validated['nationality'],
                'identity_document_type' => $validated['identity_document_type'],
                'identity_document_number' => strtoupper(trim($validated['identity_document_number'])),
                'current_class_id' => $validated['current_class_id'] ?? null,
                'photo' => $photoPath,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_alt_phone' => $validated['emergency_contact_alt_phone'] ?? null,
                'emergency_contact_address' => $validated['emergency_contact_address'] ?? null,
                'medical_notes' => $validated['medical_notes'] ?? null,
            ]);
        });

        return redirect()->route('office.students.show', $student)->with('success', 'Student profile updated successfully.');
    }
}
