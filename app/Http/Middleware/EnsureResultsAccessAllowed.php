<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResultsAccessAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        // Student trying to access own academic pages
        if ($user->role === 'student') {
            $student = $user->student;

            if (!$student) {
                abort(403, 'Student record not found.');
            }

            if ((bool) $student->fees_blocked) {
                abort(403, 'Results are currently unavailable. Please contact the accounts office.');
            }

            return $next($request);
        }

        // Parent trying to access a child's detailed marks/results page
        if ($user->role === 'parent') {
            $routeStudent = $request->route('student');

            if (!$routeStudent) {
                return $next($request);
            }

            $student = $routeStudent instanceof Student
                ? $routeStudent
                : Student::find($routeStudent);

            if (!$student) {
                abort(404, 'Student not found.');
            }

            $parent = $user->parent;

            if (!$parent) {
                abort(403, 'Parent record not found.');
            }

            $isLinked = $parent->students()->where('students.id', $student->id)->exists();

            if (!$isLinked) {
                abort(403, 'This student is not linked to your account.');
            }

            if ((bool) $student->fees_blocked) {
                abort(403, 'Results are currently unavailable for this student. Please contact the accounts office.');
            }

            return $next($request);
        }

        // Other roles should not use this middleware
        abort(403, 'Unauthorized access.');
    }
}
