<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\StudentAward;
use Barryvdh\DomPDF\Facade\Pdf;

class AwardController extends Controller
{
    public function index()
    {
        $parent = auth()->user()->parent;
        $children = $parent->students()->with(['user', 'currentClass', 'awards' => fn ($q) => $q
            ->whereHas('run', fn ($run) => $run->where('status', 'published')->where('parent_visible', true))
            ->with(['run.category', 'run.academicYear', 'run.term'])->latest(),
            'prefectAppointments' => fn ($query) => $query->where('status', '!=', 'revoked')->with('academicYear')->latest('appointed_on'),
        ])->get();

        return view('parent.awards.index', compact('children'));
    }

    public function certificate(StudentAward $studentAward)
    {
        abort_unless(auth()->user()->parent?->students()->whereKey($studentAward->student_id)->exists(), 403);
        $run = $studentAward->run()->with(['category', 'academicYear', 'term'])->where('status', 'published')->where('parent_visible', true)->firstOrFail();

        return Pdf::loadView('pdf.award-certificate', ['run' => $run, 'award' => $studentAward, 'logoPath' => extension_loaded('gd') ? public_path('images/logo.png') : ''])
            ->setPaper('a4', 'landscape')->download($studentAward->certificate_reference.'.pdf');
    }
}
