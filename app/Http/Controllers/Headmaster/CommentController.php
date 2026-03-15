<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\ClassModel;
use App\Models\HeadmasterComment;
use App\Models\Punctuality;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();

        $classes = collect();
        $terms = collect();
        $students = collect();
        $selectedClassId = $request->input('class_id');
        $selectedTermId = $request->input('term_id');

        if ($activeAcademicYear) {
            $classes = ClassModel::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            $terms = Term::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('start_date')
                ->get();

            if ($selectedClassId && $selectedTermId) {
                $students = Student::with([
                    'user',
                    'currentClass',
                    'marks' => function ($query) use ($activeAcademicYear, $selectedTermId) {
                        $query->with('subject')
                            ->where('academic_year_id', $activeAcademicYear->id)
                            ->where('term_id', $selectedTermId)
                            ->orderBy('subject_id');
                    },
                    'headmasterComments' => function ($query) use ($selectedTermId) {
                        $query->where('term_id', $selectedTermId);
                    },
                    'behaviourRecords' => function ($query) use ($activeAcademicYear, $selectedTermId) {
                        $query->where('academic_year_id', $activeAcademicYear->id)
                            ->where('term_id', $selectedTermId)
                            ->latest('record_date')
                            ->latest('id');
                    },
                ])
                    ->where('current_class_id', $selectedClassId)
                    ->orderBy('admission_no')
                    ->get()
                    ->map(function ($student) use ($activeAcademicYear, $selectedTermId) {
                        $marks = $student->marks;

                        $subjectBreakdown = $marks->map(function ($mark) {
                            $midterm = $mark->midterm_score;
                            $endterm = $mark->endterm_score;

                            $average = null;
                            if ($midterm !== null && $endterm !== null) {
                                $average = ($midterm + $endterm) / 2;
                            } elseif ($midterm !== null) {
                                $average = $midterm;
                            } elseif ($endterm !== null) {
                                $average = $endterm;
                            }

                            return [
                                'subject_name' => $mark->subject->name ?? 'Unknown Subject',
                                'midterm_score' => $midterm,
                                'endterm_score' => $endterm,
                                'average' => $average,
                                'grade' => $mark->grade,
                                'remarks' => $mark->remarks,
                            ];
                        });

                        $averages = $subjectBreakdown->pluck('average')->filter(fn($value) => $value !== null);

                        $studentAverage = $averages->count() > 0
                            ? round($averages->avg(), 2)
                            : null;

                        $topSubjects = $subjectBreakdown
                            ->filter(fn($row) => $row['average'] !== null)
                            ->sortByDesc('average')
                            ->take(3)
                            ->values();

                        $weakSubjects = $subjectBreakdown
                            ->filter(fn($row) => $row['average'] !== null)
                            ->sortBy('average')
                            ->take(3)
                            ->values();

                        $attendanceRecords = Attendance::where('student_id', $student->id)
                            ->where('academic_year_id', $activeAcademicYear->id)
                            ->where('term_id', $selectedTermId)
                            ->get();

                        $attendanceTotal = $attendanceRecords->count();
                        $attendancePresentEquivalent = $attendanceRecords->whereIn('status', [
                            Attendance::STATUS_PRESENT,
                            Attendance::STATUS_LATE,
                            Attendance::STATUS_EXCUSED,
                        ])->count();

                        $attendanceRate = $attendanceTotal > 0
                            ? round(($attendancePresentEquivalent / $attendanceTotal) * 100, 1)
                            : null;

                        $attendanceSummary = [
                            'total' => $attendanceTotal,
                            'present' => $attendanceRecords->where('status', Attendance::STATUS_PRESENT)->count(),
                            'absent' => $attendanceRecords->where('status', Attendance::STATUS_ABSENT)->count(),
                            'late' => $attendanceRecords->where('status', Attendance::STATUS_LATE)->count(),
                            'excused' => $attendanceRecords->where('status', Attendance::STATUS_EXCUSED)->count(),
                            'rate' => $attendanceRate,
                        ];

                        $punctualityRecords = Punctuality::where('student_id', $student->id)
                            ->where('academic_year_id', $activeAcademicYear->id)
                            ->where('term_id', $selectedTermId)
                            ->get();

                        $punctualityTotal = $punctualityRecords->count();
                        $onTimeRate = $punctualityTotal > 0
                            ? round(($punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count() / $punctualityTotal) * 100, 1)
                            : null;

                        $punctualitySummary = [
                            'total' => $punctualityTotal,
                            'on_time' => $punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count(),
                            'late' => $punctualityRecords->where('status', Punctuality::STATUS_LATE)->count(),
                            'very_late' => $punctualityRecords->where('status', Punctuality::STATUS_VERY_LATE)->count(),
                            'absent' => $punctualityRecords->where('status', Punctuality::STATUS_ABSENT)->count(),
                            'on_time_rate' => $onTimeRate,
                        ];

                        $behaviourRecords = $student->behaviourRecords;

                        $behaviourSummary = [
                            'total' => $behaviourRecords->count(),
                            'minor' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MINOR)->count(),
                            'moderate' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MODERATE)->count(),
                            'major' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MAJOR)->count(),
                            'recent' => $behaviourRecords->take(5)->values(),
                        ];

                        $student->subject_breakdown = $subjectBreakdown;
                        $student->student_average = $studentAverage;
                        $student->top_subjects = $topSubjects;
                        $student->weak_subjects = $weakSubjects;
                        $student->attendance_summary = $attendanceSummary;
                        $student->punctuality_summary = $punctualitySummary;
                        $student->behaviour_summary = $behaviourSummary;

                        return $student;
                    });
            }
        }

        return view('headmaster.comments.index', compact(
            'activeAcademicYear',
            'classes',
            'terms',
            'students',
            'selectedClassId',
            'selectedTermId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $term = Term::findOrFail($validated['term_id']);
        $user = $request->user();

        if (!$user || $user->role !== 'headmaster' || !$user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        HeadmasterComment::updateOrCreate(
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
            ],
            [
                'academic_year_id' => $term->academic_year_id,
                'headmaster_id' => $user->teacher->id,
                'comment' => $validated['comment'],
            ]
        );

        return redirect()
            ->route('headmaster.comments.index', [
                'class_id' => $student->current_class_id,
                'term_id' => $term->id,
            ])
            ->with('success', 'Headmaster comment saved successfully.');
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'comments' => ['required', 'array'],
            'comments.*.student_id' => ['required', 'exists:students,id'],
            'comments.*.comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $term = Term::findOrFail($validated['term_id']);
        $user = $request->user();

        if (!$user || $user->role !== 'headmaster' || !$user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        foreach ($validated['comments'] as $row) {
            $comment = trim((string) ($row['comment'] ?? ''));

            if ($comment === '') {
                continue;
            }

            HeadmasterComment::updateOrCreate(
                [
                    'student_id' => $row['student_id'],
                    'term_id' => $term->id,
                ],
                [
                    'academic_year_id' => $term->academic_year_id,
                    'headmaster_id' => $user->teacher->id,
                    'comment' => $comment,
                ]
            );
        }

        return redirect()
            ->route('headmaster.comments.index', [
                'class_id' => $validated['class_id'],
                'term_id' => $term->id,
            ])
            ->with('success', 'Headmaster comments saved successfully.');
    }
}
