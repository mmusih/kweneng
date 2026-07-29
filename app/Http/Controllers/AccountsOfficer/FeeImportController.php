<?php

namespace App\Http\Controllers\AccountsOfficer;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeImportBatch;
use App\Models\FeeImportRow;
use App\Models\Student;
use App\Models\StudentFeeBalance;
use App\Models\Term;
use App\Services\FeeExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FeeImportController extends Controller
{
    public function index()
    {
        $batches = FeeImportBatch::with(['academicYear', 'term', 'uploader'])
            ->latest()
            ->paginate(15);

        $stats = [
            'students_with_balances' => StudentFeeBalance::distinct('student_id')->count('student_id'),
            'outstanding_students' => StudentFeeBalance::where('closing_balance', '>', 0)
                ->distinct('student_id')
                ->count('student_id'),
            'total_outstanding' => StudentFeeBalance::where('closing_balance', '>', 0)
                ->sum('closing_balance'),
            'last_import' => FeeImportBatch::where('status', 'imported')
                ->latest('imported_at')
                ->first(),
        ];

        return view('accounts-officer.fees.index', compact('batches', 'stats'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('created_at')->get();
        $terms = Term::orderByDesc('created_at')->get();

        return view('accounts-officer.fees.import', compact('academicYears', 'terms'));
    }

    public function preview(Request $request, FeeExcelImportService $service)
    {
        $validated = $request->validate([
            'fee_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $file = $request->file('fee_file');
        $originalName = $file->getClientOriginalName();

        $path = $file->store('fee-imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        if (! is_file($fullPath)) {
            return back()->withErrors([
                'fee_file' => 'Uploaded file could not be found after saving. Please try again.',
            ])->withInput();
        }

        try {
            $parsedRows = $service->parse($fullPath);
            $matchedRows = $service->matchRows($parsedRows);
        } catch (Throwable $e) {
            return back()->withErrors([
                'fee_file' => $e->getMessage(),
            ])->withInput();
        }

        if (empty($matchedRows)) {
            return back()->withErrors([
                'fee_file' => 'No valid student fee rows were found in the Excel file.',
            ])->withInput();
        }

        $batch = DB::transaction(function () use ($validated, $originalName, $matchedRows) {
            $batch = FeeImportBatch::create([
                'file_name' => $originalName,
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'uploaded_by' => Auth::id(),
                'status' => 'previewed',
                'total_rows' => count($matchedRows),
                'matched_rows' => collect($matchedRows)->where('match_status', 'matched')->count(),
                'unmatched_rows' => collect($matchedRows)->where('match_status', 'unmatched')->count(),
                'ambiguous_rows' => collect($matchedRows)->where('match_status', 'ambiguous')->count(),
            ]);

            foreach ($matchedRows as $row) {
                $batch->rows()->create($row);
            }

            return $batch;
        });

        return redirect()->route('accounts-officer.fees.preview', $batch);
    }

    public function showPreview(FeeImportBatch $batch)
    {
        $batch->load([
            'academicYear',
            'term',
            'rows.matchedStudent.user',
            'rows.matchedStudent.currentClass',
        ]);

        $students = Student::with(['user', 'currentClass'])
            ->get()
            ->sortBy(function (Student $student) {
                return strtolower((string) ($student->user->name ?? 'Student ' . $student->id));
            })
            ->values();

        return view('accounts-officer.fees.preview', compact('batch', 'students'));
    }

    public function manualMatch(Request $request, FeeImportRow $row)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $row->load('batch');

        if (! $row->batch) {
            return back()->withErrors([
                'student_id' => 'The import batch for this row could not be found.',
            ]);
        }

        if ($row->batch->status === 'imported') {
            return back()->withErrors([
                'student_id' => 'This import has already been confirmed and can no longer be edited.',
            ]);
        }

        $student = Student::with(['user', 'currentClass'])->findOrFail($validated['student_id']);
        $studentName = $student->user->name ?? 'Student #' . $student->id;
        $className = $student->currentClass->name ?? 'No class';

        $row->update([
            'matched_student_id' => $student->id,
            'match_status' => 'matched',
            'match_notes' => 'Manually matched to ' . $studentName . ' (' . $className . ') by accounts officer.',
        ]);

        $this->refreshBatchCounts($row->batch);

        return back()->with('success', 'Student matched successfully.');
    }

    public function confirm(FeeImportBatch $batch)
    {
        if ($batch->status === 'imported') {
            return redirect()->route('accounts-officer.fees.imports.show', $batch)
                ->with('success', 'This fee import has already been confirmed.');
        }

        $matchedRows = FeeImportRow::where('fee_import_batch_id', $batch->id)
            ->where('match_status', 'matched')
            ->whereNotNull('matched_student_id')
            ->get();

        if ($matchedRows->isEmpty()) {
            return back()->withErrors([
                'batch' => 'There are no matched rows to import. Resolve unmatched or ambiguous rows first.',
            ]);
        }

        DB::transaction(function () use ($batch, $matchedRows) {
            foreach ($matchedRows as $row) {
                StudentFeeBalance::updateOrCreate(
                    [
                        'student_id' => $row->matched_student_id,
                        'academic_year_id' => $batch->academic_year_id,
                        'term_id' => $batch->term_id,
                    ],
                    [
                        'closing_balance' => $row->closing_balance ?? 0,
                        'source_file_name' => $batch->file_name,
                        'fee_import_batch_id' => $batch->id,
                        'updated_by' => Auth::id(),
                    ]
                );
            }

            $batch->update([
                'status' => 'imported',
                'imported_at' => now(),
            ]);
        });

        return redirect()->route('accounts-officer.fees.imports.show', $batch)
            ->with('success', $matchedRows->count() . ' fee balance(s) imported successfully. Unmatched and ambiguous rows were not imported.');
    }

    public function show(FeeImportBatch $batch)
    {
        $batch->load([
            'academicYear',
            'term',
            'uploader',
            'rows.matchedStudent.user',
            'rows.matchedStudent.currentClass',
        ]);

        return view('accounts-officer.fees.show', compact('batch'));
    }

    private function refreshBatchCounts(FeeImportBatch $batch): void
    {
        $batch->update([
            'matched_rows' => $batch->rows()->where('match_status', 'matched')->count(),
            'unmatched_rows' => $batch->rows()->where('match_status', 'unmatched')->count(),
            'ambiguous_rows' => $batch->rows()->where('match_status', 'ambiguous')->count(),
        ]);
    }
}
