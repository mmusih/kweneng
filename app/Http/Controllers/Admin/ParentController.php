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
use Illuminate\Support\Str;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentModel::with('user', 'students.user');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('user', function ($q) use ($search) {
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

        DB::beginTransaction();

        try {
            $temporaryPassword = $this->generateTemporaryPassword();

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'role' => 'parent',
                'status' => 'active',
            ]);

            // Create parent
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            // Link students
            if (!empty($validated['student_ids'])) {
                $this->syncStudents($parent, $validated['student_ids']);
            }

            DB::commit();

            return redirect()->route('admin.parents.index')
                ->with(
                    'success',
                    'Parent created successfully. Temporary password: ' .
                        $temporaryPassword .
                        ' (Parent must change it on first login.)'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to create parent: ' . $e->getMessage()
            ])->withInput();
        }
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

        DB::beginTransaction();

        try {
            // Update user
            $parent->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update parent
            $parent->update([
                'phone' => $validated['phone'] ?? $parent->phone,
                'address' => $validated['address'] ?? $parent->address,
            ]);

            // Sync students
            if (isset($validated['student_ids'])) {
                $this->syncStudents($parent, $validated['student_ids']);
            } else {
                DB::table('parent_student')->where('parent_id', $parent->id)->delete();
            }

            DB::commit();

            return redirect()->route('admin.parents.index')
                ->with('success', 'Parent updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to update parent: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function resetPassword(ParentModel $parent)
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $parent->user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return back()->with(
            'success',
            'Password reset successfully. Temporary password: ' .
                $temporaryPassword .
                ' (Parent must change it on first login.)'
        );
    }

    public function destroy(ParentModel $parent)
    {
        DB::beginTransaction();

        try {
            // Remove relationships
            DB::table('parent_student')->where('parent_id', $parent->id)->delete();

            // Delete user (cascades parent)
            $parent->user->delete();

            DB::commit();

            return redirect()->route('admin.parents.index')
                ->with('success', 'Parent deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to delete parent: ' . $e->getMessage()
            ]);
        }
    }

    private function syncStudents(ParentModel $parent, array $studentIds): void
    {
        DB::table('parent_student')->where('parent_id', $parent->id)->delete();

        $records = [];

        foreach ($studentIds as $studentId) {
            $records[] = [
                'parent_id' => $parent->id,
                'student_id' => $studentId,
                'relationship' => 'parent',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($records)) {
            DB::table('parent_student')->insert($records);
        }
    }

    private function generateTemporaryPassword(int $length = 10): string
    {
        return Str::upper(Str::random($length));
    }
}
