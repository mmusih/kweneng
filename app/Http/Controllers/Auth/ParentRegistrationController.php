<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\ParentStudent;
use App\Models\Student;
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
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'invite_code' => ['required', 'string', 'max:30'],
            'relationship' => ['required', 'string', 'in:father,mother,guardian,other'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'invite_code.required' => 'Please enter the parent code from your child\'s login slip.',
        ]);

        $code = $this->normaliseCode($validated['invite_code']);

        if (strlen($code) !== 10) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['invite_code' => 'The parent code must be exactly 10 characters.']);
        }

        $email = strtolower(trim($validated['email']));

        $result = DB::transaction(function () use ($request, $validated, $code, $email) {
            $codeRecord = StudentParentCode::where('code', $code)
                ->with(['student.user', 'student.currentClass'])
                ->lockForUpdate()
                ->first();

            if (! $codeRecord || ! $codeRecord->student) {
                return [null, null, 'This code is invalid or has expired. Please ask the school to reprint the login slip.'];
            }

            /** @var Student $student */
            $student = $codeRecord->student;
            $existingUser = User::where('email', $email)->lockForUpdate()->first();

            if ($existingUser && $existingUser->role !== UserRoles::PARENT) {
                return [null, null, 'This email is already used by another account. Please contact the school.'];
            }

            if (! $codeRecord->isValid()) {
                if ($existingUser && $existingUser->parent && $this->isParentLinkedToStudent($existingUser->parent, $student)) {
                    if (! Hash::check((string) $request->input('password'), $existingUser->password)) {
                        return [null, null, 'This parent account is already active. Enter the same password again or sign in.'];
                    }

                    $this->updateParentRelationship(
                        $existingUser->parent,
                        $student,
                        $validated['phone'],
                        $validated['relationship'],
                        $validated['address'] ?? ''
                    );

                    if (! $codeRecord->used) {
                        $codeRecord->markUsed();
                    }

                    return [$existingUser->fresh(), $student->fresh(['user']), 'Welcome back! Your parent account is already active.'];
                }

                return [null, null, 'This code is invalid, has expired, or has already been used. Please contact the school.'];
            }

            if ($existingUser) {
                if ($existingUser->parent && $this->isParentLinkedToStudent($existingUser->parent, $student)) {
                    if (! Hash::check((string) $request->input('password'), $existingUser->password)) {
                        return [null, null, 'This parent account is already active. Enter the same password again or sign in.'];
                    }

                    $this->updateParentRelationship(
                        $existingUser->parent,
                        $student,
                        $validated['phone'],
                        $validated['relationship'],
                        $validated['address'] ?? ''
                    );
                    $codeRecord->markUsed();

                    return [$existingUser->fresh(), $student->fresh(['user']), 'Welcome back! Your parent account is already active.'];
                }

                return [null, null, 'An account with this email already exists. Please sign in or contact the school to link another child.'];
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make((string) $request->input('password')),
                'role' => UserRoles::PARENT,
                'status' => 'active',
                'must_change_password' => false,
            ]);

            $parent = ParentModel::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? '',
            ]);

            $parent->students()->syncWithoutDetaching([
                $student->id => ['relationship' => $validated['relationship']],
            ]);

            $codeRecord->markUsed();

            return [$user->fresh(), $student->fresh(['user']), 'Welcome! Your account has been created and linked to ' . ($student->user->name ?? 'your child') . '.'];
        });

        [$user, $student, $message] = $result;

        if (! $user) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['invite_code' => $message]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('parent.dashboard')
            ->with('success', $message);
    }

    private function updateParentRelationship(
        ParentModel $parent,
        Student $student,
        string $phone,
        string $relationship,
        string $address
    ): void {
        $parent->update([
            'phone' => $phone,
            'address' => $address !== '' ? $address : ($parent->address ?? ''),
        ]);

        $parent->students()->syncWithoutDetaching([
            $student->id => ['relationship' => $relationship],
        ]);

        ParentStudent::where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->update(['relationship' => $relationship]);
    }

    private function isParentLinkedToStudent(ParentModel $parent, Student $student): bool
    {
        return ParentStudent::where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
    }

    private function normaliseCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code) ?? '');
    }
}
