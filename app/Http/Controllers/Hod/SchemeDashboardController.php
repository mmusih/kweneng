<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Scheme;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchemeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $academicYear = AcademicYear::current();
        $departmentIds = $this->accessibleDepartmentIds($academicYear?->id);

        abort_unless($user->isAdmin() || $user->isHeadmaster() || !empty($departmentIds), 403, 'You are not assigned as an HOD.');

        $schemes = Scheme::with([
                'teacherSubject.teacher.user.departmentAssignments.department',
                'teacherSubject.class',
                'teacherSubject.subject',
                'items.subtopics',
                'logs',
            ])
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when(! $user->isAdmin() && ! $user->isHeadmaster(), function ($query) use ($departmentIds, $academicYear) {
                $query->whereHas('teacherSubject.teacher.user.departmentAssignments', function ($q) use ($departmentIds, $academicYear) {
                    $q->whereIn('department_id', $departmentIds)
                        ->where(function ($inner) use ($academicYear) {
                            $inner->whereNull('academic_year_id');
                            if ($academicYear) {
                                $inner->orWhere('academic_year_id', $academicYear->id);
                            }
                        });
                });
            })
            ->latest()
            ->get();

        $departments = $user->isHeadmaster()
            ? Department::orderBy('name')->get()
            : Department::whereIn('id', $departmentIds)->orderBy('name')->get();

        if ($user->isAdmin()) {
            $departments = Department::orderBy('name')->get();
        }

        $summary = [
            'total' => $schemes->count(),
            'submitted' => $schemes->where('status', Scheme::STATUS_SUBMITTED)->count(),
            'approved' => $schemes->whereIn('status', [Scheme::STATUS_APPROVED, Scheme::STATUS_ACTIVE])->count(),
            'behind' => $schemes->filter(fn (Scheme $scheme) => in_array($scheme->pacingStatus(), ['behind', 'critical'], true))->count(),
            'average_completion' => $schemes->count() ? round($schemes->avg(fn (Scheme $scheme) => $scheme->completionPct()), 1) : 0,
        ];

        $schemeRoutePrefix = $this->schemeRoutePrefix();
        $schemeRoutes = $this->schemeRoutes();

        return view('hod.schemes.dashboard', compact('schemes', 'summary', 'departments', 'academicYear', 'schemeRoutePrefix', 'schemeRoutes'));
    }

    public function show(Scheme $scheme)
    {
        $this->authoriseHodAccess($scheme);

        $scheme->load([
            'teacherSubject.teacher.user',
            'teacherSubject.class',
            'teacherSubject.subject',
            'academicYear.terms',
            'items.subtopics',
            'items.term',
            'logs.user',
        ]);

        $terms = Term::where('academic_year_id', $scheme->academic_year_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $plannedItems = $scheme->items->whereNotNull('term_id')->groupBy(fn ($item) => $item->term_id . '-' . $item->week_number);

        $schemeRoutePrefix = $this->schemeRoutePrefix();
        $schemeRoutes = $this->schemeRoutes();

        return view('hod.schemes.show', compact('scheme', 'terms', 'plannedItems', 'schemeRoutePrefix', 'schemeRoutes'));
    }

    public function approve(Request $request, Scheme $scheme)
    {
        $this->authoriseHodAccess($scheme);

        $validated = $request->validate([
            'review_comment' => ['nullable', 'string'],
        ]);

        $scheme->update([
            'status' => Scheme::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $validated['review_comment'] ?? null,
        ]);

        return redirect()->route($this->schemeRoutePrefix() . '.show', $scheme)->with('success', 'Scheme approved.');
    }

    public function requestChanges(Request $request, Scheme $scheme)
    {
        $this->authoriseHodAccess($scheme);

        $validated = $request->validate([
            'review_comment' => ['required', 'string', 'max:2000'],
        ]);

        $scheme->update([
            'status' => Scheme::STATUS_CHANGES_REQUESTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $validated['review_comment'],
        ]);

        return redirect()->route($this->schemeRoutePrefix() . '.show', $scheme)->with('success', 'Changes requested.');
    }

    private function accessibleDepartmentIds(?int $academicYearId = null): array
    {
        return auth()->user()->hodDepartmentIds($academicYearId);
    }

    private function authoriseHodAccess(Scheme $scheme): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isHeadmaster()) {
            return;
        }

        $departmentIds = $this->accessibleDepartmentIds($scheme->academic_year_id);
        abort_unless(!empty($departmentIds), 403, 'You are not assigned as an HOD.');

        $scheme->loadMissing('teacherSubject.teacher.user.departmentAssignments');

        $teacherUser = $scheme->teacherSubject?->teacher?->user;
        abort_unless($teacherUser, 404);

        $hasTeacherInDepartment = $teacherUser->departmentAssignments()
            ->whereIn('department_id', $departmentIds)
            ->where(function ($query) use ($scheme) {
                $query->whereNull('academic_year_id')
                    ->orWhere('academic_year_id', $scheme->academic_year_id);
            })
            ->exists();

        abort_unless($hasTeacherInDepartment, 403, 'This scheme is outside your department.');
    }

    private function schemeRoutePrefix(): string
    {
        return request()->routeIs('admin.*') ? 'admin.schemes' : 'teacher.hod.schemes';
    }

    private function schemeRoutes(): array
    {
        if (request()->routeIs('admin.*')) {
            return [
                'index' => 'admin.schemes.index',
                'show' => 'admin.schemes.show',
                'approve' => 'admin.schemes.approve',
                'requestChanges' => 'admin.schemes.request-changes',
            ];
        }

        return [
            'index' => 'teacher.hod.schemes.dashboard',
            'show' => 'teacher.hod.schemes.show',
            'approve' => 'teacher.hod.schemes.approve',
            'requestChanges' => 'teacher.hod.schemes.request-changes',
        ];
    }
}
