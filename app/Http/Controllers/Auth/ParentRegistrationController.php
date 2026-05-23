<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\StudentParentCode;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ParentRegistrationController extends Controller
{
    /**
     * Show the parent registration form.
     */
    public function create(): View
    {
        return view('auth.parent-register');
    }

    /**
     * Handle the parent registration form submission.
     *
     * Flow:
     *  1. Validate all fields including the invite code.
     *  2. Resolve the invite code → student.
     *  3. Create User (role=parent, must_change_password=true) + ParentModel.
     *  4. Link the parent to the student via parent_student pivot.
     *  5. Mark the code as used.
     *  6. Log in the new parent and redirect to force-change-password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'        => ['required', 'string', 'max:30'],
            'address'      => ['required', 'string', 'max:500'],
            'invite_code'  => ['required', 'string', 'size:10'],
            'relationship' => ['required', 'string', 'in:father,mother,guardian,other'],
            'password'     => ['required', 'confirmed', Password::defaults()],
        ], [
            'invite_code.size'  => 'The parent code must be exactly 10 characters.',
            'email.unique'      => 'An account with this email already exists.',
            'invite_code.required' => 'Please enter the parent code from your child\'s login slip.',
        ]);

        // Resolve and validate the invite code
        $codeRecord = StudentParentCode::valid()
            ->where('code', strtoupper(trim($request->invite_code)))
            ->with('student.user')
            ->first();

        if (! $codeRecord) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'invite_code' => 'This code is invalid or has expired. Please ask the school to reprint the login slip.',
                ]);
        }

        $student = $codeRecord->student;

        // Wrap everything in a transaction — either all succeeds or nothing is saved
        $parent = DB::transaction(function () use ($request, $codeRecord, $student) {
            // 1. Create the User record
            $user = User::create([
                'name'                 => $request->name,
                'email'                => $request->email,
                'password'             => Hash::make($request->password),
                'role'                 => UserRoles::PARENT,
                'status'               => 'active',
                'must_change_password' => false, // parent chose their own password
            ]);

            // 2. Create the ParentModel profile
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'phone'   => $request->phone,
                'address' => $request->address,
            ]);

            // 3. Link parent ↔ student
            $parent->students()->attach($student->id, [
                'relationship' => $request->relationship,
            ]);

            // 4. Consume the invite code
            $codeRecord->markUsed();

            return $parent;
        });

        // Log in the new parent immediately
        Auth::login($parent->user);

        $request->session()->regenerate();

        return redirect()
            ->route('parent.dashboard')
            ->with('success', 'Welcome! Your account has been created and linked to ' . $student->user->name . '.');
    }
}
