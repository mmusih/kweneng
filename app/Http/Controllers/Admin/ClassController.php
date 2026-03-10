<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::with(['students', 'classTeacher.user', 'academicYear'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        $classTeachers = Teacher::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['teacher', 'headmaster'])
                    ->where('status', 'active');
            })
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user->name ?? '')
            ->values();

        return view('admin.classes.create', compact('academicYears', 'classTeachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:classes,name'],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        if (!empty($validated['class_teacher_id'])) {
            $teacherExists = Teacher::where('id', $validated['class_teacher_id'])
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['teacher', 'headmaster'])
                        ->where('status', 'active');
                })
                ->exists();

            if (!$teacherExists) {
                return back()
                    ->withErrors(['class_teacher_id' => 'Selected class teacher is invalid.'])
                    ->withInput();
            }
        }

        ClassModel::create($validated);

        return redirect()
            ->route('admin.classes.index')
            ->with('success', 'Class created successfully.');
    }

    public function edit(ClassModel $class)
    {
        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        $classTeachers = Teacher::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['teacher', 'headmaster'])
                    ->where('status', 'active');
            })
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user->name ?? '')
            ->values();

        return view('admin.classes.edit', compact('class', 'academicYears', 'classTeachers'));
    }

    public function update(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('classes', 'name')->ignore($class->id)],
            'level' => ['required', 'integer', 'min:1', 'max:12'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        if (!empty($validated['class_teacher_id'])) {
            $teacherExists = Teacher::where('id', $validated['class_teacher_id'])
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['teacher', 'headmaster'])
                        ->where('status', 'active');
                })
                ->exists();

            if (!$teacherExists) {
                return back()
                    ->withErrors(['class_teacher_id' => 'Selected class teacher is invalid.'])
                    ->withInput();
            }
        }

        $class->update($validated);

        return redirect()
            ->route('admin.classes.index')
            ->with('success', 'Class updated successfully.');
    }
}