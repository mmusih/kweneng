<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\ParentStudent;
use App\Models\Student;
use App\Models\StudentParentCode;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ParentRegistrationApiController extends Controller
{
    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'max:30'],
        ], [
            'invite_code.required' => 'Please enter the parent code from your child\'s login slip.',
        ]);

        $code = $this->normaliseCode($validated['invite_code']);
        $this->ensureValidCodeFormat($code);

        $codeRecord = StudentParentCode::where('code', $code)
            ->with(['student.user', 'student.currentClass'])
            ->latest('id')
            ->first();

        if (! $codeRecord || ! $codeRecord->student || ! $codeRecord->isValid()) {
            throw ValidationException::withMessages([
                'invite_code' => 'The code is invalid, expired, or already used. Please contact the school.',
            ]);
        }

        return $this->verificationResponse($codeRecord, $code);
    }

    public function complete(Request $request)
    {
        $base = $request->validate([
            'invite_code' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'relationship' => ['required', 'string', 'in:father,mother,guardian,other'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'use_existing_account' => ['nullable', 'boolean'],
        ], [
            'invite_code.required' => 'Please enter the parent code from your child\'s login slip.',
        ]);

        $code = $this->normaliseCode($base['invite_code']);
        $this->ensureValidCodeFormat($code);

        $email = strtolower(trim($base['email']));
        $useExistingAccount = $request->boolean('use_existing_account');

        if ($useExistingAccount) {
            $request->validate([
                'existing_password' => ['required', 'string'],
            ], [
                'existing_password.required' => 'Enter your existing parent account password to link this child.',
            ]);
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
        }

        $result = DB::transaction(function () use ($request, $base, $code, $email, $useExistingAccount) {
            $codeRecord = StudentParentCode::where('code', $code)
                ->with(['student.user', 'student.currentClass'])
                ->lockForUpdate()
                ->first();

            if (! $codeRecord || ! $codeRecord->student) {
                throw ValidationException::withMessages([
                    'invite_code' => 'The code is invalid, expired, or already used. Please contact the school.',
                ]);
            }

            /** @var Student $student */
            $student = $codeRecord->student;

            $existingUser = User::where('email', $email)->lockForUpdate()->first();

            if ($existingUser && $existingUser->role !== UserRoles::PARENT) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already used by another account. Please contact the school.',
                ]);
            }

            if (! $codeRecord->isValid()) {
                return $this->resolveAlreadyCompletedRegistration(
                    request: $request,
                    codeRecord: $codeRecord,
                    student: $student,
                    existingUser: $existingUser,
                    useExistingAccount: $useExistingAccount,
                    phone: $base['phone'],
                    relationship: $base['relationship']
                );
            }

            if ($existingUser && ! $useExistingAccount) {
                $existingParent = $existingUser->parent;

                if ($existingParent && $this->isParentLinkedToStudent($existingParent, $student)) {
                    $this->assertPasswordMatches(
                        user: $existingUser,
                        password: (string) $request->input('password'),
                        field: 'password',
                        message: 'This child is already linked to this parent account. Enter the same password again or sign in.'
                    );

                    $this->updateParentRelationship($existingParent, $student, $base['phone'], $base['relationship']);
                    $codeRecord->markUsed();

                    return [$existingUser->fresh(), $student->fresh(['user', 'currentClass']), 'Parent account already activated. You have been signed in.'];
                }

                throw ValidationException::withMessages([
                    'email' => 'This email already belongs to a parent account. Select “I already have a parent account” and enter your existing password.',
                ]);
            }

            if (! $existingUser && $useExistingAccount) {
                throw ValidationException::withMessages([
                    'email' => 'No parent account was found with this email. Create a new parent account instead.',
                ]);
            }

            if ($existingUser) {
                $this->assertPasswordMatches(
                    user: $existingUser,
                    password: (string) $request->input('existing_password'),
                    field: 'existing_password',
                    message: 'The password for this existing parent account is incorrect.'
                );

                $user = $existingUser;
                $parent = $user->parent ?: ParentModel::create([
                    'user_id' => $user->id,
                    'phone' => $base['phone'],
                    'address' => '',
                ]);

                $this->updateParentRelationship($parent, $student, $base['phone'], $base['relationship']);
            } else {
                $user = User::create([
                    'name' => trim((string) $request->input('name')),
                    'email' => $email,
                    'password' => Hash::make((string) $request->input('password')),
                    'role' => UserRoles::PARENT,
                    'status' => 'active',
                    'must_change_password' => false,
                ]);

                $parent = ParentModel::create([
                    'user_id' => $user->id,
                    'phone' => $base['phone'],
                    'address' => '',
                ]);

                $parent->students()->syncWithoutDetaching([
                    $student->id => ['relationship' => $base['relationship']],
                ]);
            }

            $codeRecord->markUsed();

            return [$user->fresh(), $student->fresh(['user', 'currentClass']), 'Parent account activated successfully.'];
        });

        /** @var User $user */
        /** @var Student $student */
        [$user, $student, $message] = $result;

        $deviceName = $base['device_name'] ?? 'kweneng-parent-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'must_change_password' => (bool) $user->must_change_password,
            ],
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Student',
                'class' => $student->currentClass->name ?? null,
            ],
        ]);
    }

    private function resolveAlreadyCompletedRegistration(
        Request $request,
        StudentParentCode $codeRecord,
        Student $student,
        ?User $existingUser,
        bool $useExistingAccount,
        string $phone,
        string $relationship
    ): array {
        if (! $existingUser || ! $existingUser->parent || ! $this->isParentLinkedToStudent($existingUser->parent, $student)) {
            throw ValidationException::withMessages([
                'invite_code' => 'The code is invalid, expired, or already used. Please contact the school.',
            ]);
        }

        $passwordField = $useExistingAccount ? 'existing_password' : 'password';
        $password = (string) $request->input($passwordField);

        $this->assertPasswordMatches(
            user: $existingUser,
            password: $password,
            field: $passwordField,
            message: 'This parent account is already active. Enter the same password again or sign in.'
        );

        $this->updateParentRelationship($existingUser->parent, $student, $phone, $relationship);

        if (! $codeRecord->used) {
            $codeRecord->markUsed();
        }

        return [$existingUser->fresh(), $student->fresh(['user', 'currentClass']), 'Parent account already activated. You have been signed in.'];
    }

    private function updateParentRelationship(ParentModel $parent, Student $student, string $phone, string $relationship): void
    {
        $parent->update([
            'phone' => $phone,
            'address' => $parent->address ?? '',
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

    private function assertPasswordMatches(User $user, string $password, string $field, string $message): void
    {
        if ($password === '' || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }

    private function verificationResponse(StudentParentCode $codeRecord, string $code)
    {
        $student = $codeRecord->student;

        return response()->json([
            'message' => 'Code verified.',
            'invite_code' => $code,
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Student',
                'class' => $student->currentClass->name ?? null,
            ],
        ]);
    }

    private function normaliseCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code) ?? '');
    }

    private function ensureValidCodeFormat(string $code): void
    {
        if (strlen($code) !== 10) {
            throw ValidationException::withMessages([
                'invite_code' => 'The parent code must be exactly 10 characters.',
            ]);
        }
    }
}
