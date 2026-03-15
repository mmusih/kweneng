<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function edit()
    {
        return view('profile.password');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])->withInput();
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        $this->activityLogService->log(
            'user.password_changed',
            'User changed password',
            $user,
            [
                'role' => $user->role,
                'email' => $user->email,
            ],
            $request
        );

        return redirect()
            ->route('password.edit')
            ->with('success', 'Password changed successfully.');
    }
}
