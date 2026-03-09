<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LibrarianController extends Controller
{
    public function index()
    {
        $librarians = User::where('role', UserRoles::LIBRARIAN)
            ->latest()
            ->get();

        return view('admin.librarians.index', compact('librarians'));
    }

    public function create()
    {
        return view('admin.librarians.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => UserRoles::LIBRARIAN,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.librarians.index')
            ->with('success', 'Librarian created successfully.');
    }

    public function edit(User $librarian)
    {
        abort_unless($librarian->role === UserRoles::LIBRARIAN, 404);

        return view('admin.librarians.edit', compact('librarian'));
    }

    public function update(Request $request, User $librarian)
    {
        abort_unless($librarian->role === UserRoles::LIBRARIAN, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($librarian->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $librarian->name = $validated['name'];
        $librarian->email = $validated['email'];
        $librarian->status = $validated['status'];

        if (!empty($validated['password'])) {
            $librarian->password = Hash::make($validated['password']);
        }

        $librarian->save();

        return redirect()
            ->route('admin.librarians.index')
            ->with('success', 'Librarian updated successfully.');
    }

    public function destroy(User $librarian)
    {
        abort_unless($librarian->role === UserRoles::LIBRARIAN, 404);

        $librarian->delete();

        return redirect()
            ->route('admin.librarians.index')
            ->with('success', 'Librarian deleted successfully.');
    }
}