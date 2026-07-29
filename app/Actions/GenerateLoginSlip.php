<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\StudentParentCode;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Generates (or regenerates) login credentials for a student and a fresh
 * parent invite code, then returns a DTO used by both the manual admin UI
 * and the CSV import pipeline.
 *
 * Usage (manual):
 *   $slip = GenerateLoginSlip::for($student);
 *
 * Usage (CSV import — pass a pre-built plain password so you can print it):
 *   $slip = GenerateLoginSlip::for($student, plainPassword: 'Temp@1234');
 */
class GenerateLoginSlip
{
    /**
     * @return array{
     *   student_name: string,
     *   admission_no: string,
     *   identity_display: string,
     *   student_email: string,
     *   student_password: string,
     *   parent_code: string,
     *   parent_code_expires_at: \Illuminate\Support\Carbon,
     * }
     */
    public static function for(Student $student, ?string $plainPassword = null): array
    {
        $plainPassword ??= self::generatePassword();

        return DB::transaction(function () use ($student, $plainPassword) {
            /** @var User $user */
            $user = $student->user;

            // Update the student's user account with a fresh temp password
            $user->update([
                'password'             => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            // Invalidate any previous unused codes for this student and create a fresh one
            StudentParentCode::where('student_id', $student->id)
                ->where('used', false)
                ->delete();

            $code      = self::generateParentCode();
            $expiresAt = Carbon::now()->addDays(7);

            StudentParentCode::create([
                'student_id' => $student->id,
                'code'       => $code,
                'expires_at' => $expiresAt,
            ]);

            return [
                'student_name'           => $user->name,
                'admission_no'           => $student->admission_no,
                'identity_display'      => $student->identityDisplay(),
                'class_name'             => $student->currentClass->name ?? null,
                'student_email'          => $user->email,
                'student_password'       => $plainPassword,
                'parent_code'            => $code,
                'parent_code_expires_at' => $expiresAt,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generates a memorable password: Word + 4-digit number + symbol.
     * Example: "River4821!"
     */
    private static function generatePassword(): string
    {
        $words = [
            'River',
            'Cloud',
            'Storm',
            'Eagle',
            'Flame',
            'Stone',
            'Ocean',
            'Light',
            'Swift',
            'Brave',
            'Cedar',
            'Frost',
            'Maple',
            'Amber',
            'Coral',
            'Delta',
        ];

        return $words[array_rand($words)]
            . random_int(1000, 9999)
            . Str::of('!@#$%')->split(1)->random();
    }

    /**
     * Generates a 10-character alphanumeric parent code (uppercase, no ambiguous chars).
     * Example: "K3X7PM2WQN"
     */
    private static function generateParentCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/I/1

        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (StudentParentCode::where('code', $code)->exists());

        return $code;
    }
}
