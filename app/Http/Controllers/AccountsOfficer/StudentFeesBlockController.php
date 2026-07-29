<?php

namespace App\Http\Controllers\AccountsOfficer;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Student;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentFeesBlockController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'balance_over' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $query = Student::with(['user', 'currentClass', 'latestFeeBalance']);

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

        if ($request->filled('balance_over')) {
            $this->applyLatestBalanceThreshold($query, (float) $request->balance_over);
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

        return $this->setStudentAccess($student, (bool) $validated['fees_blocked'], $request);
    }

    public function block(Request $request, Student $student)
    {
        return $this->setStudentAccess($student, true, $request);
    }

    public function unblock(Request $request, Student $student)
    {
        return $this->setStudentAccess($student, false, $request);
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in([
                'block_above_threshold',
                'block_all',
                'unblock_all',
            ])],
            'threshold' => [
                'nullable',
                'required_if:action,block_above_threshold',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'class_id' => ['nullable', 'exists:classes,id'],
        ]);

        $query = Student::query();
        $class = null;

        if (! empty($validated['class_id'])) {
            $class = ClassModel::findOrFail($validated['class_id']);
            $query->where('current_class_id', $class->id);
        }

        $threshold = isset($validated['threshold']) ? (float) $validated['threshold'] : null;
        $blocked = $validated['action'] !== 'unblock_all';

        if ($validated['action'] === 'block_above_threshold') {
            $this->applyLatestBalanceThreshold($query, $threshold);
        }

        if ($blocked) {
            $query->where(function ($studentQuery) {
                $studentQuery->where('fees_blocked', false)
                    ->orWhereNull('fees_blocked');
            });
        } else {
            $query->where('fees_blocked', true);
        }

        $affected = DB::transaction(function () use (
            $query,
            $blocked,
            $validated,
            $threshold,
            $class,
            $request
        ) {
            $affected = $query->update([
                'fees_blocked' => $blocked,
                'updated_at' => now(),
            ]);

            $this->activityLogService->log(
                'results.bulk-access-updated',
                'Student results access updated in bulk',
                null,
                [
                    'operation' => $validated['action'],
                    'fees_blocked' => $blocked,
                    'threshold' => $threshold,
                    'class_id' => $class?->id,
                    'class_name' => $class?->name,
                    'affected_students' => $affected,
                ],
                $request
            );

            return $affected;
        });

        $scope = $class ? " in {$class->name}" : '';

        $message = match ($validated['action']) {
            'block_above_threshold' => sprintf(
                '%d student(s)%s owing more than P%s were blocked from results access.',
                $affected,
                $scope,
                number_format($threshold, 2)
            ),
            'block_all' => "{$affected} student(s){$scope} were blocked from results access.",
            'unblock_all' => "{$affected} student(s){$scope} were unblocked.",
        };

        return redirect()
            ->back()
            ->with('success', $message);
    }

    private function setStudentAccess(Student $student, bool $blocked, Request $request)
    {
        if ((bool) $student->fees_blocked === $blocked) {
            return redirect()->back()->withErrors([
                'student' => $blocked
                    ? 'This student is already blocked from results access.'
                    : 'This student is already unblocked.',
            ]);
        }

        $student->update([
            'fees_blocked' => $blocked,
        ]);

        $this->activityLogService->log(
            $blocked ? 'results.blocked' : 'results.unblocked',
            $blocked ? 'Student results access blocked' : 'Student results access unblocked',
            $student,
            [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'student_name' => $student->user?->name,
                'fees_blocked' => $blocked,
            ],
            $request
        );

        $message = $blocked
            ? 'Student results access blocked successfully.'
            : 'Student results access restored successfully.';

        return redirect()
            ->back()
            ->with('success', $message);
    }

    private function applyLatestBalanceThreshold($query, float $threshold): void
    {
        $query->whereHas('latestFeeBalance', function ($balanceQuery) use ($threshold) {
            $balanceQuery->where('closing_balance', '>', $threshold);
        });
    }
}
