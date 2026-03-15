<?php

namespace App\Http\Controllers\AccountsOfficer;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request)
    {
        $query = Student::with(['user', 'currentClass'])
            ->orderBy('admission_no');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', '%' . $search . '%');
            });
        }

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

        $students = $query->paginate(20)->withQueryString();

        $stats = [
            'totalStudents' => Student::count(),
            'blockedStudents' => Student::where('fees_blocked', true)->count(),
            'unblockedStudents' => Student::where(function ($query) {
                $query->where('fees_blocked', false)
                    ->orWhereNull('fees_blocked');
            })->count(),
        ];

        $classes = ClassModel::orderBy('level')->orderBy('name')->get();

        return view('accounts-officer.dashboard', compact('stats', 'students', 'classes'));
    }

    public function block(Student $student)
    {
        if ($student->fees_blocked) {
            return redirect()->back()->withErrors([
                'student' => 'This student is already blocked from results access.',
            ]);
        }

        $student->update([
            'fees_blocked' => true,
        ]);

        $this->activityLogService->log(
            'results.blocked',
            'Student results access blocked',
            $student,
            [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'student_name' => $student->user->name ?? null,
                'fees_blocked' => true,
            ],
            request()
        );

        return redirect()->back()->with('success', 'Student results access blocked successfully.');
    }

    public function unblock(Student $student)
    {
        if (!$student->fees_blocked) {
            return redirect()->back()->withErrors([
                'student' => 'This student is already unblocked.',
            ]);
        }

        $student->update([
            'fees_blocked' => false,
        ]);

        $this->activityLogService->log(
            'results.unblocked',
            'Student results access unblocked',
            $student,
            [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'student_name' => $student->user->name ?? null,
                'fees_blocked' => false,
            ],
            request()
        );

        return redirect()->back()->with('success', 'Student results access unblocked successfully.');
    }
}
