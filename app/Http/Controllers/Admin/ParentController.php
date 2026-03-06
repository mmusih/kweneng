<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentModel::with('user', 'students.user');
        
        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $parents = $query->get();
        return view('admin.parents.index', compact('parents'));
    }

    public function create()
    {
        $students = Student::with('user')->get();
        return view('admin.parents.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // Create user account
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        // Create parent record
        $parent = ParentModel::create([
            'user_id' => $user->id,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        // Link students if provided
        if (!empty($validated['student_ids'])) {
            $parentStudentRecords = [];
            foreach ($validated['student_ids'] as $studentId) {
                $parentStudentRecords[] = [
                    'parent_id' => $parent->id,
                    'student_id' => $studentId,
                    'relationship' => 'parent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('parent_student')->insert($parentStudentRecords);
        }

        return redirect()->route('admin.parents.index')
                        ->with('success', 'Parent created successfully');
    }

    public function edit(ParentModel $parent)
    {
        $students = Student::with('user')->get();
        $parent->load('students');
        return view('admin.parents.edit', compact('parent', 'students'));
    }

    public function update(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($parent->user_id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // Update user
        $parent->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Update parent record
        $parent->update([
            'phone' => $validated['phone'] ?? $parent->phone,
            'address' => $validated['address'] ?? $parent->address,
        ]);

        // Update student relationships
        if (isset($validated['student_ids'])) {
            // Remove existing relationships
            DB::table('parent_student')->where('parent_id', $parent->id)->delete();
            
            // Add new relationships
            $parentStudentRecords = [];
            foreach ($validated['student_ids'] as $studentId) {
                $parentStudentRecords[] = [
                    'parent_id' => $parent->id,
                    'student_id' => $studentId,
                    'relationship' => 'parent',
                    'updated_at' => now(),
                ];
            }
            if (!empty($parentStudentRecords)) {
                DB::table('parent_student')->insert($parentStudentRecords);
            }
        } else {
            // If no students selected, remove all relationships
            DB::table('parent_student')->where('parent_id', $parent->id)->delete();
        }

        return redirect()->route('admin.parents.index')
                        ->with('success', 'Parent updated successfully');
    }

    public function destroy(ParentModel $parent)
    {
        // Delete the user account (this will also delete the parent due to foreign key constraint)
        $parent->user->delete();

        return redirect()->route('admin.parents.index')
                        ->with('success', 'Parent deleted successfully');
    }
}
