<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountsOfficer;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountsOfficerController extends Controller
{
    public function index()
    {
        $accountsOfficers = User::where('role', UserRoles::ACCOUNTS_OFFICER)
            ->latest()
            ->get();

        return view('admin.accounts-officers.index', compact('accountsOfficers'));
    }

    public function create()
    {
        return view('admin.accounts-officers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => UserRoles::ACCOUNTS_OFFICER,
            'status' => $validated['status'],
        ]);

        AccountsOfficer::create([
            'user_id' => $user->id,
        ]);

        return redirect()
            ->route('admin.accounts-officers.index')
            ->with('success', 'Accounts officer created successfully.');
    }

    public function edit(User $accounts_officer)
    {
        abort_unless($accounts_officer->role === UserRoles::ACCOUNTS_OFFICER, 404);

        return view('admin.accounts-officers.edit', [
            'accountsOfficer' => $accounts_officer,
        ]);
    }

    public function update(Request $request, User $accounts_officer)
    {
        abort_unless($accounts_officer->role === UserRoles::ACCOUNTS_OFFICER, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($accounts_officer->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $accounts_officer->name = $validated['name'];
        $accounts_officer->email = $validated['email'];
        $accounts_officer->status = $validated['status'];

        if (!empty($validated['password'])) {
            $accounts_officer->password = Hash::make($validated['password']);
        }

        $accounts_officer->save();

        return redirect()
            ->route('admin.accounts-officers.index')
            ->with('success', 'Accounts officer updated successfully.');
    }

    public function destroy(User $accounts_officer)
    {
        abort_unless($accounts_officer->role === UserRoles::ACCOUNTS_OFFICER, 404);

        AccountsOfficer::where('user_id', $accounts_officer->id)->delete();
        $accounts_officer->delete();

        return redirect()
            ->route('admin.accounts-officers.index')
            ->with('success', 'Accounts officer deleted successfully.');
    }
}
