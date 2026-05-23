<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryBorrowing;
use Illuminate\Http\Request;

class ParentLibraryController extends Controller
{
    /**
     * GET /api/parent/library
     * All children's current borrowings.
     */
    public function index(Request $request)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $children = $parent->students()->with(['user', 'currentClass'])->get();

        $data = $children->map(function ($child) {
            $borrowings = LibraryBorrowing::with(['bookCopy.book'])
                ->where('student_id', $child->id)
                ->whereNull('returned_at')
                ->latest('issued_at')
                ->get();

            $overdue = $borrowings->filter(fn($b) => $b->due_at && $b->due_at->isPast());

            return [
                'student_id'   => $child->id,
                'student_name' => $child->user->name ?? 'Unknown',
                'class'        => $child->currentClass->name ?? null,
                'photo'        => $child->photo ? asset('storage/' . $child->photo) : null,
                'borrowed'     => $borrowings->count(),
                'overdue'      => $overdue->count(),
                'books'        => $borrowings->map(fn($b) => [
                    'id'        => $b->id,
                    'title'     => $b->bookCopy->book->title ?? 'Unknown',
                    'author'    => $b->bookCopy->book->author ?? null,
                    'issued_at' => $b->issued_at?->toDateString(),
                    'due_at'    => $b->due_at?->toDateString(),
                    'overdue'   => $b->due_at && $b->due_at->isPast(),
                    'days_overdue' => ($b->due_at && $b->due_at->isPast())
                        ? (int) now()->diffInDays($b->due_at)
                        : 0,
                ])->values(),
            ];
        });

        return response()->json([
            'children'      => $data,
            'total_borrowed' => $data->sum('borrowed'),
            'total_overdue'  => $data->sum('overdue'),
        ]);
    }

    /**
     * GET /api/parent/library/history/{studentId}
     * Borrowing history for one child.
     */
    public function history(Request $request, $studentId)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $child = $parent->students()->with(['user'])->find($studentId);

        if (!$child) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $history = LibraryBorrowing::with(['bookCopy.book'])
            ->where('student_id', $child->id)
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json([
            'student' => [
                'id'   => $child->id,
                'name' => $child->user->name ?? 'Unknown',
            ],
            'borrowings' => collect($history->items())->map(fn($b) => [
                'id'          => $b->id,
                'title'       => $b->bookCopy->book->title ?? 'Unknown',
                'author'      => $b->bookCopy->book->author ?? null,
                'issued_at'   => $b->issued_at?->toDateString(),
                'due_at'      => $b->due_at?->toDateString(),
                'returned_at' => $b->returned_at?->toDateString(),
                'overdue'     => !$b->returned_at && $b->due_at && $b->due_at->isPast(),
            ])->values(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
                'total'        => $history->total(),
            ],
        ]);
    }
}
