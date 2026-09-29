<?php

namespace App\Support;

use Illuminate\Http\Request;

class StudentProfileNavigation
{
    public static function backUrl(Request $request): ?string
    {
        $student = $request->route('student');
        $filters = array_filter($request->only(['search', 'class_id', 'page', 'term_id', 'incomplete']), 'is_scalar');

        foreach (['admin', 'headmaster', 'office'] as $role) {
            if ($request->routeIs($role.'.students.show', $role.'.students.create')) {
                return route($role.'.students.index', $filters);
            }
            if ($student && $request->routeIs($role.'.students.edit', $role.'.students.academic-record.show')) {
                return route($role.'.students.show', array_merge(['student' => $student], $filters));
            }
        }

        return null;
    }
}
