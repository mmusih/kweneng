<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', UserRoles::academicStaff());
            })
            ->latest()
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        $roles = UserRoles::academicStaff();

        return view('admin.teachers.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:' . implode(',', UserRoles::academicStaff())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        Teacher::create([
            'user_id' => $user->id,
        ]);

        $roleLabel = $validated['role'] === UserRoles::HEADMASTER ? 'Headmaster' : 'Teacher';

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', $roleLabel . ' created successfully');
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load('user');
        $roles = UserRoles::academicStaff();

        return view('admin.teachers.edit', compact('teacher', 'roles'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->load('user');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($teacher->user->id),
            ],
            'role' => ['required', 'in:' . implode(',', UserRoles::academicStaff())],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $teacher->user->name = $validated['name'];
        $teacher->user->email = $validated['email'];
        $teacher->user->role = $validated['role'];
        $teacher->user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $teacher->user->password = Hash::make($validated['password']);
        }

        $teacher->user->save();

        $roleLabel = $validated['role'] === UserRoles::HEADMASTER ? 'Headmaster' : 'Teacher';

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', $roleLabel . ' updated successfully');
    }
}