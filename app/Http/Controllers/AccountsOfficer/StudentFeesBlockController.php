<?php

namespace App\Http\Controllers\AccountsOfficer;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentFeesBlockController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'currentClass']);

        if ($request->filled('class_id')) {
            $query->where('current_class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'blocked') {
                $query->where('fees_blocked', true);
            } elseif ($request->status === 'unblocked') {
                $query->where(function ($q) {
                    $q->where('fees_blocked', false)
                        ->orWhereNull('fees_blocked');
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('admission_no', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $students = $query
            ->orderBy('admission_no')
            ->paginate(20)
            ->withQueryString();

        $classes = ClassModel::orderBy('name')->get();

        return view('accounts-officer.students.index', compact('students', 'classes'));
    }

    public function toggle(Request $request, Student $student)
    {
        $validated = $request->validate([
            'fees_blocked' => ['required', 'boolean'],
        ]);

        $student->update([
            'fees_blocked' => $validated['fees_blocked'],
        ]);

        $message = $validated['fees_blocked']
            ? 'Student results access blocked successfully.'
            : 'Student results access restored successfully.';

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
