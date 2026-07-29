<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentFeesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $parent = $user->parent ?? null;

        if (! $parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent profile not found.',
            ], 404);
        }

        $students = $parent->students()
            ->with(['user', 'currentClass'])
            ->get();

        $studentIds = $students->pluck('id')->values();

        $balances = StudentFeeBalance::with(['academicYear', 'term'])
            ->whereIn('student_id', $studentIds)
            ->latest('updated_at')
            ->get()
            ->groupBy('student_id');

        $children = $students->map(function ($student) use ($balances) {
            $latestBalance = $balances->get($student->id)?->first();

            $closingBalance = $latestBalance
                ? (float) $latestBalance->closing_balance
                : null;

            return [
                'student_id' => $student->id,
                'student_name' => $student->user->name ?? $student->full_name ?? 'Student',
                'class_name' => $student->currentClass->name ?? null,
                'closing_balance' => $closingBalance,
                'formatted_closing_balance' => $closingBalance === null
                    ? 'Not available'
                    : 'P' . number_format($closingBalance, 2),
                'status' => $closingBalance === null
                    ? 'not_available'
                    : ($closingBalance > 0 ? 'outstanding' : 'clear'),
                'academic_year' => $latestBalance?->academicYear?->year_name,
                'term' => $latestBalance?->term?->name,
                'last_updated' => $latestBalance?->updated_at?->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'children' => $children,
        ]);
    }
}
