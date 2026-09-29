<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\PrefectAppointment;
use App\Models\Student;
use App\Services\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PrefectController extends Controller
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $prefects = $this->filteredQuery($request)
            ->paginate(20)
            ->withQueryString();

        return view('prefects.index', [
            'prefects' => $prefects,
            'academicYears' => AcademicYear::latest('id')->get(),
            'statuses' => PrefectAppointment::statuses(),
        ]);
    }

    public function create()
    {
        return view('prefects.form', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $student = Student::with('currentClass')->findOrFail($validated['student_id']);
        $prefect = PrefectAppointment::create([
            ...$validated,
            'class_name_snapshot' => $student->currentClass?->name,
            'certificate_reference' => 'KIS-PREF-'.$validated['academic_year_id'].'-'.Str::upper(Str::random(10)),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->activityLog->log('prefects.appointed', 'Appointed '.$student->user?->name.' as '.$prefect->title, $prefect, [], $request);

        return redirect()->route($this->routeName($request, 'prefects.index'))->with('success', 'Prefect appointment created. The certificate is ready to print.');
    }

    public function edit(PrefectAppointment $prefect)
    {
        return view('prefects.form', [...$this->formData(), 'prefect' => $prefect]);
    }

    public function update(Request $request, PrefectAppointment $prefect)
    {
        $validated = $this->validated($request, $prefect);
        $prefect->update([...$validated, 'updated_by' => $request->user()->id]);
        $this->activityLog->log('prefects.updated', 'Updated prefect appointment: '.$prefect->title, $prefect, [], $request);

        return redirect()->route($this->routeName($request, 'prefects.index'))->with('success', 'Prefect appointment updated.');
    }

    public function destroy(Request $request, PrefectAppointment $prefect)
    {
        $name = $prefect->student?->user?->name ?? 'Student';
        $this->activityLog->log('prefects.deleted', 'Deleted prefect appointment for '.$name, $prefect, ['title' => $prefect->title], $request);
        $prefect->delete();

        return redirect()->route($this->routeName($request, 'prefects.index'))->with('success', 'Prefect appointment deleted.');
    }

    public function certificate(PrefectAppointment $prefect)
    {
        $this->authorizeCertificate($prefect);
        $prefect->load(['student.user', 'student.currentClass', 'academicYear']);

        return Pdf::loadView('pdf.prefect-certificate', [
            'prefect' => $prefect,
            'logoPath' => extension_loaded('gd') ? public_path('images/logo.png') : '',
        ])->setPaper('a4', 'landscape')->download($prefect->certificate_reference.'.pdf');
    }

    public function bulkCertificatesPdf(Request $request)
    {
        $prefects = $this->filteredQuery($request)
            ->where('status', '!=', PrefectAppointment::STATUS_REVOKED)
            ->get();

        if ($prefects->isEmpty()) {
            return redirect()
                ->route($this->routeName($request, 'prefects.index'), $request->query())
                ->with('warning', 'No active or completed prefect appointments match the selected filters.');
        }

        return Pdf::loadView('pdf.prefect-certificates-bulk', [
            'prefects' => $prefects,
            'logoPath' => extension_loaded('gd') ? public_path('images/logo.png') : '',
        ])->setPaper('a4', 'landscape')
            ->download('prefect-certificates-'.now()->format('Y-m-d').'.pdf');
    }

    private function validated(Request $request, ?PrefectAppointment $prefect = null): array
    {
        return $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'title' => [
                'required', 'string', 'max:150',
                Rule::unique('prefect_appointments', 'title')
                    ->where(fn ($query) => $query->where('student_id', $request->input('student_id'))->where('academic_year_id', $request->input('academic_year_id')))
                    ->ignore($prefect?->id),
            ],
            'duties' => ['required', 'string', 'max:600'],
            'appointed_on' => ['required', 'date'],
            'service_ends_on' => ['nullable', 'date', 'after_or_equal:appointed_on'],
            'status' => ['required', Rule::in(array_keys(PrefectAppointment::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function formData(): array
    {
        return [
            'students' => Student::with(['user', 'currentClass'])->orderBy('admission_no')->get(),
            'academicYears' => AcademicYear::latest('id')->get(),
            'statuses' => PrefectAppointment::statuses(),
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return PrefectAppointment::query()
            ->with(['student.user', 'student.currentClass', 'academicYear'])
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('duties', 'like', "%{$search}%")
                        ->orWhereHas('student.user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('student', fn ($student) => $student->where('admission_no', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->latest('appointed_on');
    }

    private function authorizeCertificate(PrefectAppointment $prefect): void
    {
        $user = auth()->user();
        if (in_array($user->role, ['admin', 'headmaster'], true)) {
            return;
        }
        abort_if($prefect->status === PrefectAppointment::STATUS_REVOKED, 404);
        if ($user->role === 'student' && $user->student?->id === $prefect->student_id) {
            return;
        }
        if ($user->role === 'parent' && $user->parent?->students()->whereKey($prefect->student_id)->exists()) {
            return;
        }

        abort(403);
    }

    private function routeName(Request $request, string $suffix): string
    {
        return ($request->user()->role === 'headmaster' ? 'headmaster.' : 'admin.').$suffix;
    }
}
