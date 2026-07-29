<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function __construct(private readonly TimetableService $timetables) {}

    public function teacher(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['nullable', 'date']]);
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404);

        return response()->json($this->timetables->forTeacher($teacher, $data['date'] ?? null));
    }

    public function student(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['nullable', 'date']]);
        $student = $request->user()->student;
        abort_unless($student, 404);

        return response()->json($this->timetables->forStudent($student, $data['date'] ?? null));
    }

    public function parent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $parent = $request->user()->parent;
        abort_unless($parent, 404);

        $student = $parent->students()
            ->with(['user', 'currentClass'])
            ->where('students.id', $data['student_id'])
            ->first();

        abort_unless($student, 403);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name,
                'class' => $student->currentClass?->name,
            ],
            ...$this->timetables->forStudent($student, $data['date'] ?? null),
        ]);
    }
}
