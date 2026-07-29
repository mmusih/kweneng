<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        // Return the same response whether or not the account exists so the
        // password form cannot be used to discover registered email addresses.
        return back()->with(
            'status',
            'If an account matches that email address, a password reset link has been sent.'
        );
    }
}
