<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\Term;
use App\Services\StudentPerformanceService;
use Illuminate\Http\Request;

class ParentMarksController extends Controller
{
    public function __construct(
        protected StudentPerformanceService $studentPerformanceService
    ) {}

    public function index(Request $request)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $children = $parent->students()->with(['user', 'currentClass'])->get();

        $currentAcademicYear = AcademicYear::where(function ($q) {
            $q->where('status', 'open')->orWhere('status', 'active');
        })->orderByDesc('created_at')->first();

        $academicYears = AcademicYear::orderByDesc('created_at')->limit(5)->get()->map(fn($y) => [
            'id'        => $y->id,
            'year_name' => $y->year_name,
            'status'    => $y->status,
        ]);

        $result = $children->map(function ($child) use ($currentAcademicYear) {
            $isBlocked = (bool) $child->fees_blocked;

            if ($isBlocked) {
                return [
                    'id'           => $child->id,
                    'name'         => $child->user->name ?? 'Unknown',
                    'admission_no' => $child->admission_no,
                    'class'        => $child->currentClass->name ?? null,
                    'photo'        => $child->photo ? asset('storage/' . $child->photo) : null,
                    'is_blocked'   => true,
                    'terms'        => [],
                ];
            }

            $terms = $currentAcademicYear
                ? Term::where('academic_year_id', $currentAcademicYear->id)
                ->orderBy('start_date')
                ->get()
                : collect();

            $termsData = $terms->map(function ($term) use ($child, $currentAcademicYear) {
                $marks = Mark::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $term->id)
                    ->with('subject')
                    ->get();

                $performance = $this->studentPerformanceService->getStudentTermPerformance(
                    $child,
                    $currentAcademicYear->id,
                    $term->id
                );

                $midtermAverage = $marks->pluck('midterm_score')->filter()->avg();
                $endtermAverage = $marks->pluck('endterm_score')->filter()->avg();

                return [
                    'term_id'     => $term->id,
                    'term_name'   => $term->name,
                    'term_status' => $term->status,

                    'subjects' => $marks->map(fn($m) => [
                        'subject'       => $m->subject->name ?? 'Unknown',
                        'midterm_score' => $m->midterm_score,
                        'endterm_score' => $m->endterm_score,
                        'midterm_grade' => $m->midterm_grade ?? $m->grade ?? null,
                        'endterm_grade' => $m->endterm_grade ?? $m->grade ?? null,
                    ])->values(),

                    'midterm_average'  => $midtermAverage ? round($midtermAverage, 1) : null,
                    'endterm_average'  => $endtermAverage ? round($endtermAverage, 1) : null,
                    'midterm_position' => $performance['midterm_position'] ?? null,
                    'endterm_position' => $performance['endterm_position'] ?? null,
                    'trend'            => ($performance['trend'] ?? 'N/A') !== 'N/A' ? $performance['trend'] : null,
                    'performance_label' => $performance['performance_label'] ?? null,
                ];
            })->values();

            return [
                'id'           => $child->id,
                'name'         => $child->user->name ?? 'Unknown',
                'admission_no' => $child->admission_no,
                'class'        => $child->currentClass->name ?? null,
                'photo'        => $child->photo ? asset('storage/' . $child->photo) : null,
                'is_blocked'   => false,
                'terms'        => $termsData,
            ];
        })->values();

        return response()->json([
            'academic_year' => $currentAcademicYear ? [
                'id'        => $currentAcademicYear->id,
                'year_name' => $currentAcademicYear->year_name,
            ] : null,
            'academic_years' => $academicYears,
            'children'       => $result,
        ]);
    }

    public function show(Request $request, $studentId, $academicYearId, $termId)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $child = $parent->students()->with(['user', 'currentClass'])->find($studentId);

        if (!$child) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        if ((bool) $child->fees_blocked) {
            return response()->json([
                'message' => 'Results access is restricted due to an outstanding balance.',
            ], 403);
        }

        $marks = Mark::where('student_id', $child->id)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->with('subject')
            ->get();

        $performance = $this->studentPerformanceService->getStudentTermPerformance(
            $child,
            $academicYearId,
            $termId
        );

        $midtermAverage = $marks->pluck('midterm_score')->filter()->avg();
        $endtermAverage = $marks->pluck('endterm_score')->filter()->avg();

        return response()->json([
            'student' => [
                'id'           => $child->id,
                'name'         => $child->user->name ?? 'Unknown',
                'admission_no' => $child->admission_no,
                'class'        => $child->currentClass->name ?? null,
            ],

            'subjects' => $marks->map(fn($m) => [
                'subject'       => $m->subject->name ?? 'Unknown',
                'midterm_score' => $m->midterm_score,
                'endterm_score' => $m->endterm_score,
                'midterm_grade' => $m->midterm_grade ?? $m->grade ?? null,
                'endterm_grade' => $m->endterm_grade ?? $m->grade ?? null,
            ])->values(),

            'summary' => [
                'midterm_average'   => $midtermAverage ? round($midtermAverage, 1) : null,
                'endterm_average'   => $endtermAverage ? round($endtermAverage, 1) : null,
                'midterm_position'  => $performance['midterm_position'] ?? null,
                'endterm_position'  => $performance['endterm_position'] ?? null,
                'trend'             => ($performance['trend'] ?? 'N/A') !== 'N/A' ? $performance['trend'] : null,
                'performance_label' => $performance['performance_label'] ?? null,
            ],
        ]);
    }
}
