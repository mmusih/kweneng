<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    public function edit(Student $student)
    {
        $this->authorizeLinkedStudent($student);

        $identityDocumentTypes = Student::identityDocumentTypes();

        return view('parent.children.profile-edit', compact('student', 'identityDocumentTypes'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeLinkedStudent($student);

        $validated = $request->validate($this->rules($request, $student));

        $student->update(array_merge($validated, [
            'identity_document_number' => $this->normalizeDocumentNumber($validated['identity_document_number']),
            'profile_updated_by_parent_at' => now(),
        ]));

        return redirect()
            ->route('parent.dashboard')
            ->with('success', 'Student profile and emergency contact information updated successfully.');
    }

    private function authorizeLinkedStudent(Student $student): void
    {
        $parent = Auth::user()?->parent;

        abort_unless($parent, 404);

        abort_unless(
            $parent->students()->where('students.id', $student->id)->exists(),
            403,
            'This student is not linked to your parent account.'
        );
    }

    private function rules(Request $request, Student $student): array
    {
        $documentType = $request->input('identity_document_type');

        return [
            'nationality'                    => ['required', 'string', 'max:100'],
            'identity_document_type'         => ['required', Rule::in(array_keys(Student::identityDocumentTypes()))],
            'identity_document_number'       => [
                'required',
                'string',
                'max:100',
                Rule::unique('students', 'identity_document_number')
                    ->where(fn ($query) => $query->where('identity_document_type', $documentType))
                    ->ignore($student->id),
            ],
            'emergency_contact_name'         => ['required', 'string', 'max:255'],
            'emergency_contact_relationship' => ['required', 'string', 'max:100'],
            'emergency_contact_phone'        => ['required', 'string', 'max:50'],
            'emergency_contact_alt_phone'    => ['nullable', 'string', 'max:50'],
            'emergency_contact_address'      => ['nullable', 'string', 'max:1000'],
            'medical_notes'                  => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function normalizeDocumentNumber(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    }
}
