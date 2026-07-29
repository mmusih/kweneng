<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['assignments.user', 'assignments.academicYear'])
            ->orderBy('name')
            ->get();

        $users = User::whereIn('role', UserRoles::academicStaff())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::orderByDesc('id')->get();
        $roles = DepartmentUser::roles();

        return view('admin.departments.index', compact('departments', 'users', 'academicYears', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['code'] = $validated['code'] ?: Str::upper(Str::slug($validated['name'], '_'));

        if (Department::where('code', $validated['code'])->exists()) {
            throw ValidationException::withMessages([
                'code' => 'This department code is already in use. Please enter a unique code.',
            ]);
        }

        Department::create($validated);

        return redirect()->route('admin.departments.index')->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($department->id)],
            'description' => ['nullable', 'string'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department deleted.');
    }

    public function assign(Request $request, Department $department)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'role_in_department' => ['required', Rule::in(array_keys(DepartmentUser::roles()))],
        ]);

        $academicYearId = $validated['academic_year_id'] ?? null;

        $existingAssignment = DepartmentUser::query()
            ->where('department_id', $department->id)
            ->where('user_id', $validated['user_id'])
            ->where('role_in_department', $validated['role_in_department'])
            ->when(
                $academicYearId,
                fn ($query) => $query->where('academic_year_id', $academicYearId),
                fn ($query) => $query->whereNull('academic_year_id')
            )
            ->first();

        if (! $existingAssignment) {
            DepartmentUser::create([
                'department_id' => $department->id,
                'user_id' => $validated['user_id'],
                'academic_year_id' => $academicYearId,
                'role_in_department' => $validated['role_in_department'],
            ]);
        }

        return redirect()->route('admin.departments.index')->with('success', 'Department assignment saved.');
    }

    public function removeAssignment(Department $department, DepartmentUser $assignment)
    {
        abort_unless($assignment->department_id === $department->id, 404);

        $assignment->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department assignment removed.');
    }
}
