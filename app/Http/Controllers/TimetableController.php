<?php

namespace App\Http\Controllers;

use App\Services\TimetableService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __construct(private readonly TimetableService $timetables) {}

    public function teacher(Request $request): View
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404);

        return view('timetable.viewer', [
            'title' => 'My teaching timetable',
            'schedule' => $this->timetables->forTeacher($teacher, $request->date),
            'children' => collect(),
            'selectedStudent' => null,
        ]);
    }

    public function student(Request $request): View
    {
        $student = $request->user()->student;
        abort_unless($student, 404);

        return view('timetable.viewer', [
            'title' => 'My timetable',
            'schedule' => $this->timetables->forStudent($student, $request->date),
            'children' => collect(),
            'selectedStudent' => $student,
        ]);
    }

    public function parent(Request $request): View
    {
        $parent = $request->user()->parent;
        abort_unless($parent, 404);

        $children = $parent->students()->with(['user', 'currentClass'])->get();
        $selectedStudent = $request->filled('student_id')
            ? $children->firstWhere('id', $request->integer('student_id'))
            : $children->first();

        if ($request->filled('student_id') && ! $selectedStudent) {
            abort(403);
        }

        return view('timetable.viewer', [
            'title' => $selectedStudent
                ? ($selectedStudent->user?->name."'s timetable")
                : 'Child timetable',
            'schedule' => $selectedStudent
                ? $this->timetables->forStudent($selectedStudent, $request->date)
                : $this->timetables->emptySchedule($request->date),
            'children' => $children,
            'selectedStudent' => $selectedStudent,
        ]);
    }
}
