<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ])->onlyInput('email');
        }

        return match ($user->role) {
            UserRoles::ADMIN => redirect()->intended(route('admin.dashboard', false)),
            UserRoles::HEADMASTER => redirect()->intended(route('headmaster.dashboard', false)),
            UserRoles::TEACHER => redirect()->intended(route('teacher.dashboard', false)),
            UserRoles::STUDENT => redirect()->intended(route('student.dashboard', false)),
            UserRoles::PARENT => redirect()->intended(route('parent.dashboard', false)),
            UserRoles::ACCOUNTS_OFFICER => redirect()->intended(route('accounts-officer.dashboard', false)),
            UserRoles::LIBRARIAN => redirect()->intended(route('librarian.dashboard', false)),
            default => redirect('/'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
