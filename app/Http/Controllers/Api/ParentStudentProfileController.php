<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParentStudentProfileController extends Controller
{
    public function update(Request $request, Student $student)
    {
        $parent = $request->user()?->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        if (! $parent->students()->where('students.id', $student->id)->exists()) {
            return response()->json(['message' => 'This student is not linked to your parent account.'], 403);
        }

        $documentType = $request->input('identity_document_type');

        $validated = $request->validate([
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
        ]);

        $student->update(array_merge($validated, [
            'identity_document_number' => strtoupper(trim(preg_replace('/\s+/', ' ', $validated['identity_document_number']))),
            'profile_updated_by_parent_at' => now(),
        ]));

        return response()->json([
            'message' => 'Student profile updated successfully.',
            'student' => [
                'id' => $student->id,
                'nationality' => $student->nationality,
                'identity_document_type' => $student->identity_document_type,
                'identity_document_number' => $student->identity_document_number,
                'emergency_contact_name' => $student->emergency_contact_name,
                'emergency_contact_relationship' => $student->emergency_contact_relationship,
                'emergency_contact_phone' => $student->emergency_contact_phone,
                'emergency_contact_alt_phone' => $student->emergency_contact_alt_phone,
                'emergency_contact_address' => $student->emergency_contact_address,
                'medical_notes' => $student->medical_notes,
                'profile_complete' => $student->isProfileComplete(),
                'missing_fields' => $student->profileCompletionIssues(),
            ],
        ]);
    }
}
