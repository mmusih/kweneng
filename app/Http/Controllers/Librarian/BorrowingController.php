<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use App\Models\ClassModel;
use App\Models\LibraryBorrowing;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $borrowings = LibraryBorrowing::with([
            'bookCopy.book',
            'student.user',
            'teacher.user',
            'issuer',
        ])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('librarian.borrowings.index', compact('borrowings', 'status'));
    }

    public function create()
    {
        $classes = ClassModel::orderBy('level')->orderBy('name')->get();

        return view('librarian.borrowings.create', compact('classes'));
    }

    public function studentsByClass(ClassModel $class): JsonResponse
    {
        $students = Student::with('user')
            ->where('current_class_id', $class->id)
            ->orderBy('admission_no')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name ?? 'Unknown Student',
                    'admission_no' => $student->admission_no ?? 'N/A',
                ];
            })
            ->values();

        return response()->json([
            'students' => $students,
        ]);
    }

    public function searchTeachers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        if ($search === '') {
            return response()->json([
                'teachers' => [],
            ]);
        }

        $teachers = Teacher::with('user')
            ->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name ?? 'Unknown Teacher',
                ];
            })
            ->values();

        return response()->json([
            'teachers' => $teachers,
        ]);
    }

    public function lookupBookCopy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string'],
        ]);

        $bookCopy = BookCopy::with(['book', 'activeBorrowing.student.user', 'activeBorrowing.teacher.user'])
            ->where('barcode', $validated['barcode'])
            ->first();

        if (!$bookCopy) {
            return response()->json([
                'found' => false,
                'message' => 'No book copy found for that barcode.',
            ], 404);
        }

        $activeBorrowing = $bookCopy->activeBorrowing;

        return response()->json([
            'found' => true,
            'copy' => [
                'id' => $bookCopy->id,
                'barcode' => $bookCopy->barcode,
                'accession_no' => $bookCopy->accession_no,
                'status' => $bookCopy->status,
                'is_available' => (bool) $bookCopy->is_available,
                'shelf_location' => $bookCopy->shelf_location,
                'book' => [
                    'title' => $bookCopy->book->title ?? 'N/A',
                    'author' => $bookCopy->book->author ?? 'N/A',
                    'isbn' => $bookCopy->book->isbn ?? 'N/A',
                ],
                'active_borrowing' => $activeBorrowing ? [
                    'status' => $activeBorrowing->status,
                    'issued_at' => optional($activeBorrowing->issued_at)->format('Y-m-d'),
                    'due_at' => optional($activeBorrowing->due_at)->format('Y-m-d'),
                    'borrower_name' => $activeBorrowing->student?->user?->name
                        ?? $activeBorrowing->teacher?->user?->name
                        ?? 'Unknown Borrower',
                    'borrower_type' => $activeBorrowing->student_id ? 'Student' : 'Teacher',
                ] : null,
            ],
        ]);
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string'],
            'borrower_type' => ['required', Rule::in(['student', 'teacher'])],
            'student_id' => ['nullable', 'exists:students,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validated['borrower_type'] === 'student' && empty($validated['student_id'])) {
            return back()->withErrors([
                'student_id' => 'Please select a student.',
            ])->withInput();
        }

        if ($validated['borrower_type'] === 'teacher' && empty($validated['teacher_id'])) {
            return back()->withErrors([
                'teacher_id' => 'Please select a teacher.',
            ])->withInput();
        }

        if ($validated['borrower_type'] === 'student' && !empty($validated['teacher_id'])) {
            return back()->withErrors([
                'teacher_id' => 'Teacher must be empty when issuing to a student.',
            ])->withInput();
        }

        if ($validated['borrower_type'] === 'teacher' && !empty($validated['student_id'])) {
            return back()->withErrors([
                'student_id' => 'Student must be empty when issuing to a teacher.',
            ])->withInput();
        }

        return DB::transaction(function () use ($validated, $request) {
            $bookCopy = BookCopy::with('book')
                ->where('barcode', $validated['barcode'])
                ->lockForUpdate()
                ->first();

            if (!$bookCopy) {
                return back()->withErrors([
                    'barcode' => 'No book copy found for that barcode.',
                ])->withInput();
            }

            if (!$bookCopy->is_available || $bookCopy->status !== 'available') {
                return back()->withErrors([
                    'barcode' => 'This book copy is not currently available for issue.',
                ])->withInput();
            }

            $studentId = $validated['borrower_type'] === 'student'
                ? (int) $validated['student_id']
                : null;

            $teacherId = $validated['borrower_type'] === 'teacher'
                ? (int) $validated['teacher_id']
                : null;

            LibraryBorrowing::create([
                'book_copy_id' => $bookCopy->id,
                'student_id' => $studentId,
                'teacher_id' => $teacherId,
                'issued_by' => $request->user()->id,
                'issued_at' => $validated['issued_at'],
                'due_at' => $validated['due_at'],
                'status' => 'borrowed',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $bookCopy->update([
                'status' => 'borrowed',
                'is_available' => false,
            ]);

            return redirect()
                ->route('librarian.borrowings.index')
                ->with('success', 'Book issued successfully.');
        });
    }

    public function returnBook(Request $request)
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string'],
            'returned_at' => ['required', 'date'],
        ]);

        return DB::transaction(function () use ($validated) {
            $bookCopy = BookCopy::where('barcode', $validated['barcode'])
                ->lockForUpdate()
                ->first();

            if (!$bookCopy) {
                return back()->withErrors([
                    'barcode' => 'No book copy found for that barcode.',
                ])->withInput();
            }

            $borrowing = LibraryBorrowing::where('book_copy_id', $bookCopy->id)
                ->whereIn('status', ['borrowed', 'overdue'])
                ->whereNull('returned_at')
                ->latest()
                ->first();

            if (!$borrowing) {
                return back()->withErrors([
                    'barcode' => 'No active borrowing record was found for this barcode.',
                ])->withInput();
            }

            $borrowing->update([
                'returned_at' => $validated['returned_at'],
                'status' => 'returned',
            ]);

            $bookCopy->update([
                'status' => 'available',
                'is_available' => true,
            ]);

            return redirect()
                ->route('librarian.borrowings.index')
                ->with('success', 'Book returned successfully.');
        });
    }

    public function markOverdue()
    {
        $updated = LibraryBorrowing::where('status', 'borrowed')
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        return back()->with('success', "{$updated} borrowing record(s) marked as overdue.");
    }
}
