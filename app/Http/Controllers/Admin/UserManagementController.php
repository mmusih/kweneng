<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountsOfficer;
use App\Models\Teacher;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $allowedRoles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        $query = User::query()
            ->whereIn('role', $allowedRoles)
            ->orderBy('name');

        if ($request->filled('role') && in_array($request->role, $allowedRoles, true)) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $allowedRoles,
        ]);
    }

    public function create()
    {
        $roles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $allowedRoles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $plainPassword = $validated['password'] ?? 'password';

        DB::transaction(function () use ($validated, $plainPassword) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            $this->createLinkedRoleRecordIfNeeded($user);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully. Default password: ' . $plainPassword);
    }

    public function edit(User $user)
    {
        $this->ensureManageableUser($user);

        $roles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureManageableUser($user);

        $allowedRoles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $oldRole = $user->role;

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->status = $validated['status'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $this->syncLinkedRoleRecords($user, $oldRole);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function activate(User $user)
    {
        $this->ensureManageableUser($user);

        $user->update([
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User activated successfully.');
    }

    public function deactivate(User $user)
    {
        $this->ensureManageableUser($user);

        $user->update([
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    public function resetPassword(User $user)
    {
        $this->ensureManageableUser($user);

        $temporaryPassword = 'password';

        $user->update([
            'password' => Hash::make($temporaryPassword),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Password reset successfully. Temporary password: ' . $temporaryPassword);
    }

    protected function ensureManageableUser(User $user): void
    {
        $allowedRoles = [
            UserRoles::ADMIN,
            UserRoles::TEACHER,
            UserRoles::HEADMASTER,
            UserRoles::LIBRARIAN,
            UserRoles::ACCOUNTS_OFFICER,
        ];

        abort_unless(in_array($user->role, $allowedRoles, true), 404);
    }

    protected function createLinkedRoleRecordIfNeeded(User $user): void
    {
        if (in_array($user->role, UserRoles::academicStaff(), true)) {
            Teacher::firstOrCreate([
                'user_id' => $user->id,
            ]);
        }

        if ($user->role === UserRoles::ACCOUNTS_OFFICER) {
            AccountsOfficer::firstOrCreate([
                'user_id' => $user->id,
            ]);
        }
    }

    protected function syncLinkedRoleRecords(User $user, string $oldRole): void
    {
        $oldWasAcademic = in_array($oldRole, UserRoles::academicStaff(), true);
        $newIsAcademic = in_array($user->role, UserRoles::academicStaff(), true);

        if (!$oldWasAcademic && $newIsAcademic) {
            Teacher::firstOrCreate([
                'user_id' => $user->id,
            ]);
        }

        if ($oldWasAcademic && !$newIsAcademic) {
            Teacher::where('user_id', $user->id)->delete();
        }

        $oldWasAccounts = $oldRole === UserRoles::ACCOUNTS_OFFICER;
        $newIsAccounts = $user->role === UserRoles::ACCOUNTS_OFFICER;

        if (!$oldWasAccounts && $newIsAccounts) {
            AccountsOfficer::firstOrCreate([
                'user_id' => $user->id,
            ]);
        }

        if ($oldWasAccounts && !$newIsAccounts) {
            AccountsOfficer::where('user_id', $user->id)->delete();
        }
    }
}
