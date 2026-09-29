<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrefectAppointment;
use App\Models\Student;
use App\Models\StudentAward;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ParentAwardsController extends Controller
{
    public function index(Request $request, ?Student $student = null)
    {
        $children = $request->user()->parent?->students();
        abort_unless($children, 404);
        if ($student) {
            abort_unless((clone $children)->whereKey($student->id)->exists(), 403);
        }

        $awards = StudentAward::with(['run.category', 'run.academicYear', 'run.term'])
            ->whereIn('student_id', $student ? [$student->id] : (clone $children)->pluck('students.id'))
            ->whereHas('run', fn ($q) => $q->where('status', 'published')->where('parent_visible', true))
            ->latest()->get()->map(fn (StudentAward $award) => $this->format($award));

        return response()->json(['awards' => $awards]);
    }

    public function certificate(Request $request, Student $student, StudentAward $studentAward)
    {
        abort_unless($request->user()->parent?->students()->whereKey($student->id)->exists(), 403);
        abort_unless($studentAward->student_id === $student->id, 404);
        $run = $studentAward->run()->with(['category', 'academicYear', 'term'])->where('status', 'published')->where('parent_visible', true)->firstOrFail();

        return Pdf::loadView('pdf.award-certificate', ['run' => $run, 'award' => $studentAward, 'logoPath' => extension_loaded('gd') ? public_path('images/logo.png') : ''])
            ->setPaper('a4', 'landscape')->download($studentAward->certificate_reference.'.pdf');
    }

    public function prefectCertificate(Request $request, Student $student, PrefectAppointment $prefect)
    {
        abort_unless($request->user()->parent?->students()->whereKey($student->id)->exists(), 403);
        abort_unless($prefect->student_id === $student->id, 404);
        abort_if($prefect->status === PrefectAppointment::STATUS_REVOKED, 404);
        $prefect->load(['student.user', 'student.currentClass', 'academicYear']);

        return Pdf::loadView('pdf.prefect-certificate', ['prefect' => $prefect, 'logoPath' => extension_loaded('gd') ? public_path('images/logo.png') : ''])
            ->setPaper('a4', 'landscape')->download($prefect->certificate_reference.'.pdf');
    }

    private function format(StudentAward $award): array
    {
        return [
            'id' => $award->id, 'student_id' => $award->student_id, 'title' => $award->award_title,
            'category' => $award->run->type, 'position' => $award->position,
            'percentage' => $award->main_score !== null ? (float) $award->main_score : null,
            'class_name' => $award->class_name_snapshot, 'academic_year' => $award->run->academicYear?->year_name,
            'term' => $award->run->term?->name, 'award_date' => $award->run->award_date?->toDateString(),
            'citation' => $award->citation, 'certificate_reference' => $award->certificate_reference,
            'badge' => ['label' => $award->position ? $this->ordinal($award->position) : $award->award_title, 'icon' => $award->run->category?->badge_icon ?? 'trophy', 'color' => $award->run->category?->badge_color ?? '#D4AF37'],
            'certificate_download_url' => url("/api/parent/children/{$award->student_id}/awards/{$award->id}/certificate"),
        ];
    }

    private function ordinal(int $number): string
    {
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            return $number.'th Place';
        }

        return $number.match ($number % 10) {
            1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
        }.' Place';
    }
}
