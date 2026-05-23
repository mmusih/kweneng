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
        $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $slips = Student::with('user')
            ->whereIn('id', $request->student_ids)
            ->get()
            ->map(fn (Student $s) => GenerateLoginSlip::for($s));

        return view('admin.students.login-slips-bulk', compact('slips'));
    }
}
