<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffLeaveAllowance;
use App\Models\StaffLeaveHoliday;
use App\Models\StaffLeaveRequest;
use App\Models\StaffLeaveType;
use App\Models\StaffProfile;
use App\Services\SchoolPaymentService;
use App\Services\StaffLeaveService;
use App\Support\ErpAudit;
use App\Support\StaffWorkflows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['year' => 'nullable|integer|between:2020,2100', 'status' => ['nullable', Rule::in(['submitted', 'approved', 'rejected', 'cancelled'])]]);
        $year = (int) $request->input('year', now()->year);
        $manage = $request->routeIs('hr.*');
        $staff = StaffWorkflows::staff($request->user());
        $query = StaffLeaveRequest::with('staff', 'type')->when(! $manage, fn ($q) => $q->whereHas('staff', fn ($s) => $s->where('user_id', $request->user()->id)));
        $requests = $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))->latest()->paginate(30)->withQueryString();
        $allowances = StaffLeaveAllowance::with('staff', 'type')->where('year', $year)->when(! $manage, fn ($q) => $q->where('staff_profile_id', $staff?->id ?? 0))->get();

        return view('hr.leave.index', compact('manage', 'staff', 'requests', 'allowances', 'year') + ['types' => StaffLeaveType::where('active', true)->get(), 'employees' => $manage ? StaffProfile::where('status', 'active')->orderBy('name')->get() : collect()]);
    }

    public function store(Request $request, StaffLeaveService $service)
    {
        $data = $request->validate(['submission_key' => 'required|uuid', 'staff_profile_id' => 'nullable|exists:staff_profiles,id', 'staff_leave_type_id' => ['required', Rule::exists('staff_leave_types', 'id')->where('active', true)], 'starts_on' => 'required|date_format:Y-m-d', 'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on', 'portion' => ['required', Rule::in(['full', 'am', 'pm'])], 'reason' => 'required|string|max:3000', 'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $staff = $request->user()->canManageHr() && ! empty($data['staff_profile_id']) ? StaffProfile::where('status', 'active')->findOrFail($data['staff_profile_id']) : StaffWorkflows::staff($request->user());
        abort_unless($staff, 403, 'HR must link an active staff profile to your account.');
        $type = StaffLeaveType::findOrFail($data['staff_leave_type_id']);
        if ($type->requires_document && ! $request->hasFile('document')) {
            throw ValidationException::withMessages(['document' => 'This leave type requires a supporting document.']);
        }
        $path = null;
        try {
            $leave = DB::transaction(function () use ($request, $data, $staff, $type, $service, &$path) {
                $staff = StaffProfile::whereKey($staff->id)->lockForUpdate()->firstOrFail();
                abort_unless($staff->status === 'active' && ($request->user()->canManageHr() || $staff->user_id === $request->user()->id), 403);
                if ($existing = StaffLeaveRequest::where('submission_key', $data['submission_key'])->first()) {
                    $this->authorise($request, $existing);

                    return $existing;
                }
                $type = StaffLeaveType::lockForUpdate()->findOrFail($type->id);
                if (! $type->active) {
                    $service->invalid('This leave type is no longer available.');
                }
                if ($type->requires_document && ! $request->hasFile('document')) {
                    throw ValidationException::withMessages(['document' => 'This leave type requires a supporting document.']);
                }
                $days = $service->halfDays($type, $data['starts_on'], $data['ends_on'], $data['portion']);
                $overlap = StaffLeaveRequest::where('staff_profile_id', $staff->id)->whereIn('status', ['submitted', 'approved'])->whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on']);
                if ($data['portion'] !== 'full') {
                    $overlap->where(fn ($q) => $q->where('portion', 'full')->orWhere('portion', $data['portion'])->orWhereDate('starts_on', '!=', $data['starts_on'])->orWhereDate('ends_on', '!=', $data['starts_on']));
                }
                if ($overlap->exists()) {
                    $service->invalid('This request overlaps existing pending or approved leave.');
                }
                if ($type->uses_allowance) {
                    $service->checkBalance($staff->id, $type->id, (int) date('Y', strtotime($data['starts_on'])), $days);
                }
                if ($request->hasFile('document')) {
                    $path = $request->file('document')->store('staff-leave/'.$staff->id, 'local');
                    abort_unless($path, 500, 'Could not store document.');
                }
                $leave = StaffLeaveRequest::create(collect($data)->only(['submission_key', 'staff_leave_type_id', 'starts_on', 'ends_on', 'portion', 'reason'])->all() + ['staff_profile_id' => $staff->id, 'submitted_by' => $request->user()->id, 'half_days' => $days, 'uses_allowance' => $type->uses_allowance, 'document_path' => $path, 'document_name' => $request->file('document')?->getClientOriginalName()]);
                ErpAudit::record('hr.leave_submitted', $leave, ['half_days' => $days]);

                return $leave;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return redirect()->route('staff.leave.show', $leave)->with('success', 'Leave request submitted for HR review.');
    }

    private function authorise(Request $request, StaffLeaveRequest $leave): void
    {
        abort_unless($request->user()->canManageHr() || $leave->staff->user_id === $request->user()->id, 403);
    }

    public function show(Request $request, StaffLeaveRequest $leave)
    {
        $this->authorise($request, $leave);
        $leave->load('staff', 'type');

        return view('hr.leave.show', compact('leave'));
    }

    public function document(Request $request, StaffLeaveRequest $leave)
    {
        $this->authorise($request, $leave);
        abort_unless($leave->document_path, 404);
        ErpAudit::record('hr.leave_document_downloaded', $leave);

        return Storage::disk('local')->download($leave->document_path, $leave->document_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, StaffLeaveRequest $leave, StaffLeaveService $service)
    {
        abort_unless($request->user()->canManageHr(), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'note' => 'nullable|required_if:decision,rejected|string|max:3000']);
        DB::transaction(function () use ($request, $leave, $data, $service) {
            StaffProfile::whereKey($leave->staff_profile_id)->lockForUpdate()->firstOrFail();
            $leave = StaffLeaveRequest::lockForUpdate()->findOrFail($leave->id);
            abort_if($leave->submitted_by === $request->user()->id || $leave->staff->user_id === $request->user()->id, 403, 'Another authorised HR user must review this request.');
            if ($leave->status !== 'submitted') {
                $service->invalid('Only submitted requests can be reviewed.');
            }
            if ($data['decision'] === 'approved') {
                if ($leave->staff->status !== 'active') {
                    $service->invalid('This employee is no longer active.');
                }
                if ($leave->uses_allowance) {
                    $service->checkBalance($leave->staff_profile_id, $leave->staff_leave_type_id, $leave->starts_on->year, $leave->half_days, $leave->id);
                }
            }
            $leave->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note'] ?? null]);
            ErpAudit::record('hr.leave_'.$data['decision'], $leave, ['note' => $data['note'] ?? null]);
        });

        return back()->with('success', 'Leave decision recorded.');
    }

    public function cancel(Request $request, StaffLeaveRequest $leave)
    {
        $this->authorise($request, $leave);
        $data = $request->validate(['note' => 'required|string|max:3000']);
        DB::transaction(function () use ($request, $leave, $data) {
            StaffProfile::whereKey($leave->staff_profile_id)->lockForUpdate()->firstOrFail();
            $leave = StaffLeaveRequest::lockForUpdate()->findOrFail($leave->id);
            abort_unless($leave->status === 'submitted' || ($leave->status === 'approved' && $request->user()->canManageHr() && $leave->staff->user_id !== $request->user()->id), 422, 'Only HR may cancel approved leave for another employee.');
            $leave->update(['status' => 'cancelled', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note']]);
            ErpAudit::record('hr.leave_cancelled', $leave, $data);
        });

        return back()->with('success', 'Leave cancelled and reserved allowance released.');
    }

    public function settings(Request $request)
    {
        $request->validate(['year' => 'nullable|integer|between:2020,2100']);
        $year = (int) $request->input('year', now()->year);

        return view('hr.leave.settings', ['year' => $year, 'types' => StaffLeaveType::all(), 'staff' => StaffProfile::where('status', 'active')->orderBy('name')->get(), 'holidays' => StaffLeaveHoliday::orderBy('date')->get(), 'allowances' => StaffLeaveAllowance::with('staff', 'type')->where('year', $year)->get()]);
    }

    public function type(Request $request, ?StaffLeaveType $type = null)
    {
        $type ??= new StaffLeaveType;
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('staff_leave_types')->ignore($type->id)], 'uses_allowance' => 'required|boolean', 'requires_document' => 'required|boolean', 'exclude_holidays' => 'required|boolean', 'active' => 'required|boolean', 'working_days' => 'required|array|min:1|max:7', 'working_days.*' => 'required|integer|between:1,7|distinct']);
        $data['working_days'] = array_map('intval', $data['working_days']);
        DB::transaction(function () use ($type, $data) {
            if ($type->exists) {
                $type = StaffLeaveType::lockForUpdate()->findOrFail($type->id);
            }
            if ($type->exists && StaffLeaveRequest::where('staff_leave_type_id', $type->id)->exists()) {
                foreach (['uses_allowance', 'requires_document', 'exclude_holidays', 'working_days'] as $field) {
                    if ($data[$field] != $type->$field) {
                        throw ValidationException::withMessages(['type' => 'This type has requests. Create a new type to change its counting or document rules.']);
                    }
                }
            }
            $type->fill($data)->save();
            ErpAudit::record('hr.leave_type_saved', $type);
        });

        return back()->with('success', 'Leave type saved.');
    }

    public function allowance(Request $request)
    {
        $data = $request->validate(['staff_profile_id' => 'required|exists:staff_profiles,id', 'staff_leave_type_id' => ['required', Rule::exists('staff_leave_types', 'id')->where('uses_allowance', true)], 'year' => 'required|integer|between:2020,2100', 'days' => 'required|decimal:0,1|between:0,366', 'notes' => 'required|string|max:2000']);
        $units = SchoolPaymentService::minor((string) $data['days']);
        if ($units % 50 !== 0) {
            throw ValidationException::withMessages(['days' => 'Use whole or half days.']);
        }
        DB::transaction(function () use ($data, $units) {
            StaffProfile::whereKey($data['staff_profile_id'])->lockForUpdate()->firstOrFail();
            $allowance = StaffLeaveAllowance::firstOrNew(collect($data)->only(['staff_profile_id', 'staff_leave_type_id', 'year'])->all());
            if ($allowance->usedHalfDays(['submitted', 'approved']) > $units / 50) {
                throw ValidationException::withMessages(['days' => 'Allowance cannot be lower than approved and reserved leave.']);
            }
            $allowance->fill(['half_days' => intdiv($units, 50), 'notes' => $data['notes']])->save();
            ErpAudit::record('hr.leave_allowance_saved', $allowance, ['half_days' => $allowance->half_days, 'notes' => $data['notes']]);
        });

        return back()->with('success', 'Leave allowance saved.');
    }

    public function holiday(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d', 'name' => 'required|string|max:200']);
        $holiday = StaffLeaveHoliday::updateOrCreate(['date' => $data['date']], ['name' => $data['name']]);
        ErpAudit::record('hr.leave_holiday_saved', $holiday);

        return back()->with('success', 'Non-working date saved. Existing request day counts remain unchanged.');
    }

    public function removeHoliday(StaffLeaveHoliday $holiday)
    {
        DB::transaction(function () use ($holiday) {
            ErpAudit::record('hr.leave_holiday_removed', $holiday, ['date' => $holiday->date->format('Y-m-d'), 'name' => $holiday->name]);
            $holiday->delete();
        });

        return back()->with('success', 'Non-working date removed. Existing requests retain their recorded day counts.');
    }
}
