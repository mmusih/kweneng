<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.force-change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        $redirectTo = match ($user->role) {
            UserRoles::ADMIN => route('admin.dashboard', false),
            UserRoles::HEADMASTER => route('headmaster.dashboard', false),
            UserRoles::TEACHER => route('teacher.dashboard', false),
            UserRoles::STUDENT => route('student.dashboard', false),
            UserRoles::PARENT => route('parent.dashboard', false),
            UserRoles::ACCOUNTS_OFFICER => route('accounts-officer.dashboard', false),
            UserRoles::LIBRARIAN => route('librarian.dashboard', false),
            default => '/',
        };

        return redirect($redirectTo)->with('success', 'Password changed successfully.');
    }
}
