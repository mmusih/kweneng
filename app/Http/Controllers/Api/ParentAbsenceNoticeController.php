<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAbsenceNotice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParentAbsenceNoticeController extends Controller
{
    /**
     * GET /api/parent/absence-notices
     */
    public function index(Request $request)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $children = $parent->students()
            ->with(['user', 'currentClass'])
            ->orderBy('id')
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Unnamed student',
                'admission_no' => $student->admission_no ?? '',
                'class' => $student->currentClass->name ?? null,
                'is_blocked' => (bool) ($student->fees_blocked ?? false),
            ])
            ->values();

        $notices = ParentAbsenceNotice::with(['student.user', 'student.currentClass'])
            ->where('parent_id', $parent->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($notice) => $this->formatNotice($notice))
            ->values();

        return response()->json([
            'children' => $children,
            'notices' => $notices,
            'reasons' => $this->reasons(),
        ]);
    }

    /**
     * POST /api/parent/absence-notices
     */
    public function store(Request $request)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $childIds = $parent->students()->pluck('students.id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($childIds)],
            'absence_date' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:absence_date'],
            'reason' => ['required', 'string', 'max:80', Rule::in($this->reasons())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $notice = ParentAbsenceNotice::create([
            'parent_id' => $parent->id,
            'student_id' => $validated['student_id'],
            'absence_date' => $validated['absence_date'],
            'expected_return_date' => $validated['expected_return_date'] ?? null,
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        $notice->load(['student.user', 'student.currentClass']);

        return response()->json([
            'message' => 'Absence notice submitted successfully.',
            'notice' => $this->formatNotice($notice),
        ], 201);
    }

    private function reasons(): array
    {
        return [
            'Sick',
            'Medical appointment',
            'Family matter',
            'Travel',
            'Emergency',
            'Other',
        ];
    }

    private function formatNotice(ParentAbsenceNotice $notice): array
    {
        return [
            'id' => $notice->id,
            'student_id' => $notice->student_id,
            'student_name' => $notice->student->user->name ?? 'Unnamed student',
            'class_name' => $notice->student->currentClass->name ?? null,
            'absence_date' => optional($notice->absence_date)->toDateString(),
            'expected_return_date' => optional($notice->expected_return_date)->toDateString(),
            'reason' => $notice->reason,
            'note' => $notice->note,
            'status' => $notice->status,
            'status_label' => ParentAbsenceNotice::statusLabel($notice->status),
            'submitted_at' => optional($notice->created_at)->toIso8601String(),
            'seen_at' => optional($notice->seen_at)->toIso8601String(),
            'resolved_at' => optional($notice->resolved_at)->toIso8601String(),
        ];
    }
}
