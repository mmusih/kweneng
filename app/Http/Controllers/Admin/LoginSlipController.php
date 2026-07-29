<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateLoginSlip;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginSlipController extends Controller
{
    /**
     * Show the printable login slip for a student.
     * If ?regenerate=1 is passed (or no prior slip exists), fresh credentials
     * and a new parent code are generated first.
     */
    public function show(Request $request, Student $student): View|RedirectResponse
    {
        // Always regenerate when explicitly requested, or when no active code exists
        $hasActiveCode = $student->parentCodes()->valid()->exists();

        if ($request->boolean('regenerate') || ! $hasActiveCode) {
            $slip = GenerateLoginSlip::for($student);

            // Redirect so a page refresh doesn't regenerate again
            return redirect()
                ->route('admin.students.slip', $student)
                ->with('slip', $slip);
        }

        $slip = session('slip');

        if (! $slip) {
            // No session data — redirect to regenerate
            return redirect()
                ->route('admin.students.slip', ['student' => $student, 'regenerate' => 1]);
        }

        return view('admin.students.login-slip', [
            'student' => $student,
            'slip'    => $slip,
        ]);
    }

    /**
     * Bulk-generate slips for multiple students (e.g. after CSV import).
     * Returns a collection of slip data for a bulk print view.
     */
    public function bulk(Request $request): View
    {
        $validated = $request->validate([
            'selection_scope' => ['nullable', 'in:selected,filtered'],
            'student_ids'     => ['required_if:selection_scope,selected', 'array'],
            'student_ids.*'   => ['integer', 'exists:students,id'],
            'search'          => ['nullable', 'string', 'max:255'],
            'class_id'        => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        $scope = $validated['selection_scope'] ?? 'selected';

        $studentsQuery = Student::with(['user', 'currentClass']);

        if ($scope === 'filtered') {
            $search = trim((string) ($validated['search'] ?? ''));

            if ($search !== '') {
                $studentsQuery->where(function ($q) use ($search) {
                    $q->where('admission_no', 'like', "%{$search}%")
                        ->orWhere('identity_document_number', 'like', "%{$search}%")
                        ->orWhere('nationality', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if (! empty($validated['class_id'])) {
                $studentsQuery->where('current_class_id', $validated['class_id']);
            }
        } else {
            $studentsQuery->whereIn('id', $validated['student_ids'] ?? []);
        }

        $slips = $studentsQuery
            ->orderBy('current_class_id')
            ->orderBy('admission_no')
            ->get()
            ->map(fn(Student $s) => GenerateLoginSlip::for($s));

        return view('admin.students.print-logins', [
            'logins' => $slips,
        ]);
    }
}
