<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentAbsenceNotice;
use Illuminate\Http\Request;

class ParentAbsenceNoticeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $query = ParentAbsenceNotice::with([
            'parent.user',
            'student.user',
            'student.currentClass',
        ])->latest();

        if (in_array($status, ['pending', 'seen', 'resolved'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('parent.user', fn ($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('student.currentClass', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $notices = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => ParentAbsenceNotice::count(),
            'pending' => ParentAbsenceNotice::where('status', 'pending')->count(),
            'seen' => ParentAbsenceNotice::where('status', 'seen')->count(),
            'resolved' => ParentAbsenceNotice::where('status', 'resolved')->count(),
        ];

        return view('admin.absence-notices.index', compact('notices', 'counts', 'status', 'search'));
    }

    public function show(ParentAbsenceNotice $absenceNotice)
    {
        $absenceNotice->load([
            'parent.user',
            'student.user',
            'student.currentClass',
            'seenBy',
            'resolvedBy',
        ]);

        return view('admin.absence-notices.show', compact('absenceNotice'));
    }

    public function markSeen(Request $request, ParentAbsenceNotice $absenceNotice)
    {
        $absenceNotice->markSeen($request->user());

        return redirect()
            ->route('admin.absence-notices.show', $absenceNotice)
            ->with('success', 'Absence notice marked as seen.');
    }

    public function markResolved(Request $request, ParentAbsenceNotice $absenceNotice)
    {
        $absenceNotice->markResolved($request->user());

        return redirect()
            ->route('admin.absence-notices.show', $absenceNotice)
            ->with('success', 'Absence notice marked as resolved.');
    }
}
