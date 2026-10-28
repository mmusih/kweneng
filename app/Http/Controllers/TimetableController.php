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
            'schedule' => $this->timetables->forTeacher($teacher, $request->date, true),
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
            'schedule' => $this->timetables->forStudent($student, $request->date, true),
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
                ? $this->timetables->forStudent($selectedStudent, $request->date, true)
                : $this->timetables->emptySchedule($request->date),
            'children' => $children,
            'selectedStudent' => $selectedStudent,
        ]);
    }
    public function parentCurrent(Request $request)
    {
        $data = $request->validate(['student_id' => ['required', 'integer']]);
        $student = $request->user()->parent?->students()->find($data['student_id']);
        abort_unless($student, 403);
        return response()->json($this->timetables->currentActivity($student))->header('Cache-Control', 'no-store');
    }

    public function parentDownload(Request $request, \App\Services\StudentTimetablePdfService $pdfs)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'schedule_type' => ['required', 'in:day,afternoon'],
        ]);
        $student = $request->user()->parent?->students()->with(['user', 'currentClass'])->find($data['student_id']);
        abort_unless($student, 403);
        return $pdfs->download($student, $data['schedule_type']);
    }

}
