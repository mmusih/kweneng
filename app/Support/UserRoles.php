<?php

namespace App\Support;

class UserRoles
{
    public const ADMIN = 'admin';
    public const TEACHER = 'teacher';
    public const HEADMASTER = 'headmaster';
    public const STUDENT = 'student';
    public const PARENT = 'parent';
    public const ACCOUNTS_OFFICER = 'accounts_officer';
    public const LIBRARIAN = 'librarian';

    public static function all(): array
    {
        return [
            self::ADMIN,
            self::TEACHER,
            self::HEADMASTER,
            self::STUDENT,
            self::PARENT,
            self::ACCOUNTS_OFFICER,
            self::LIBRARIAN,
        ];
    }

    public static function academicStaff(): array
    {
        return [
            self::TEACHER,
            self::HEADMASTER,
        ];
    }

    public static function teacherAccessible(): array
    {
        return [
            self::TEACHER,
            self::HEADMASTER,
        ];
    }
}