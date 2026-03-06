<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassModel;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::with('students')->get();
        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        return view('admin.classes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classes',
            'level' => 'required|integer|min:1|max:12',
        ]);

        ClassModel::create($validated);

        return redirect()->route('admin.classes.index')
                        ->with('success', 'Class created successfully');
    }

    public function edit(ClassModel $class)
    {
        return view('admin.classes.edit', compact('class'));
    }

    public function update(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classes,name,'.$class->id,
            'level' => 'required|integer|min:1|max:12',
        ]);

        $class->update($validated);

        return redirect()->route('admin.classes.index')
                        ->with('success', 'Class updated successfully');
    }
}
