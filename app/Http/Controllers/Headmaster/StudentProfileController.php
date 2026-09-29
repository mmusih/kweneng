<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:150'], 'term_id' => ['nullable', 'integer', 'exists:terms,id']]);
        $students = Student::with(['user', 'currentClass'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->search.'%'))
                ->orWhere('admission_no', 'like', '%'.$request->search.'%')))
            ->orderBy('admission_no')->paginate(30)->withQueryString();

        return view('headmaster.students.index', compact('students'));
    }

    public function show(Request $request, Student $student)
    {
        $student->load(['user', 'currentClass', 'parents.user', 'classHistory.class', 'classHistory.academicYear',
            'awards' => fn ($query) => $query->whereHas('run', fn ($run) => $run->where('status', 'published'))->with(['run.academicYear', 'run.term'])->latest(),
            'prefectAppointments.academicYear',
        ]);

        return view('headmaster.students.show', array_merge(
            ['student' => $student],
            app(\App\Services\StudentProfileService::class)->overview($request, $student)
        ));
    }
}
