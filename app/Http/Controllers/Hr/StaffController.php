<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffDocument;
use App\Models\StaffDocumentType;
use App\Models\StaffProfile;
use App\Models\StaffRequirement;
use App\Models\User;
use App\Support\ErpAudit;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staff = StaffProfile::with('requirements.type', 'requirements.documents')->orderBy('name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))->get();
        $attention = $staff->where('status', 'active')->flatMap(fn ($s) => $s->requirements->filter(fn ($r) => ! in_array($r->status(), ['valid', 'not_applicable'])));
        $contracts = $staff->where('status', 'active')->filter(fn ($s) => $s->contract_ends_on && $s->contract_ends_on->lte(today()->addDays(90)));

        return view('hr.index', compact('staff', 'attention', 'contracts'));
    }

    public function create()
    {
        return $this->form(new StaffProfile(['status' => 'active']));
    }

    public function edit(StaffProfile $staff)
    {
        return $this->form($staff);
    }

    private function form(StaffProfile $staff)
    {
        $users = User::whereIn('role', UserRoles::manageableStaff())->orderBy('name')->get();

        return view('hr.form', compact('staff', 'users'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new StaffProfile);
    }

    public function update(Request $request, StaffProfile $staff)
    {
        return $this->save($request, $staff);
    }

    private function save(Request $request, StaffProfile $staff)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'employee_number' => ['required', 'string', 'max:80', Rule::unique('staff_profiles')->ignore($staff->id)],
            'user_id' => ['nullable', Rule::exists('users', 'id')->whereIn('role', UserRoles::manageableStaff()), Rule::unique('staff_profiles')->ignore($staff->id)],
            'position' => 'required|string|max:255', 'department' => 'nullable|string|max:255',
            'citizenship' => 'required|string|max:100', 'is_citizen' => 'required|boolean', 'is_teacher' => 'required|boolean',
            'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:60',
            'started_on' => 'nullable|date', 'contract_ends_on' => 'nullable|date|after_or_equal:started_on',
            'status' => ['required', Rule::in(['active', 'inactive'])], 'hr_access' => 'sometimes|boolean',
        ]);
        DB::transaction(function () use ($request, $staff, $data) {
            unset($data['hr_access']);
            $staff->fill($data)->save();
            foreach (StaffDocumentType::all() as $type) {
                if ($type->appliesTo($staff)) {
                    $staff->requirements()->firstOrCreate(['staff_document_type_id' => $type->id]);
                }
            }
            if ($request->user()->isAdmin() && $staff->user_id && $request->has('hr_access')) {
                $staff->user->forceFill(['hr_access' => $request->boolean('hr_access')])->save();
                ErpAudit::record('hr.permission_updated', $staff->user, ['hr_access' => $request->boolean('hr_access')]);
            }
            ErpAudit::record('hr.staff_saved', $staff);
        });

        return redirect()->route('hr.staff.show', $staff)->with('success', 'Staff profile saved. Review the document checklist for this employee.');
    }

    public function show(StaffProfile $staff)
    {
        $staff->load('requirements.type', 'requirements.documents');
        $types = StaffDocumentType::orderBy('name')->get();
        $owners = User::where('status', 'active')->where(fn ($q) => $q->where('role', 'admin')->orWhere('hr_access', true))->orderBy('name')->get();

        return view('hr.show', compact('staff', 'types', 'owners'));
    }

    public function requirement(Request $request, StaffProfile $staff)
    {
        $data = $request->validate(['staff_document_type_id' => 'required|exists:staff_document_types,id']);
        $requirement = $staff->requirements()->firstOrCreate($data);
        ErpAudit::record('hr.requirement_added', $requirement);

        return back()->with('success', 'Document requirement added.');
    }

    public function renewal(Request $request, StaffRequirement $requirement)
    {
        $data = $request->validate([
            'required' => 'required|boolean', 'exception_reason' => 'nullable|required_if:required,0|string|max:2000',
            'renewal_status' => ['required', Rule::in(['not_started', 'preparing', 'submitted', 'approved', 'rejected'])],
            'application_date' => 'nullable|date', 'application_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000', 'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 'active')->where(fn ($q) => $q->where('role', 'admin')->orWhere('hr_access', true)))],
        ]);
        $requirement->update($data);
        ErpAudit::record('hr.renewal_updated', $requirement, $data);

        return back()->with('success', 'Requirement and renewal details saved.');
    }

    public function upload(Request $request, StaffRequirement $requirement)
    {
        $data = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'reference' => 'nullable|string|max:255', 'issuer' => 'nullable|string|max:255',
            'issued_on' => 'nullable|date', 'does_not_expire' => 'required|boolean',
            'expires_on' => 'nullable|required_if:does_not_expire,0|prohibited_if:does_not_expire,1|date|after_or_equal:issued_on',
        ]);
        $file = $request->file('document');
        unset($data['document']);
        $path = $file->store('staff-documents/'.$requirement->staff_profile_id, 'local');
        abort_unless($path, 500, 'Document could not be stored.');
        try {
            DB::transaction(function () use ($data, $path, $file, $requirement, $request) {
                $document = $requirement->documents()->create($data + ['path' => $path, 'original_name' => $file->getClientOriginalName(), 'uploaded_by' => $request->user()->id]);
                ErpAudit::record('hr.document_uploaded', $document);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Document uploaded; verification is required. Previous versions are retained.');
    }

    public function verify(Request $request, StaffDocument $document)
    {
        if (! $document->verified_at) {
            $document->update(['verified_at' => now(), 'verified_by' => $request->user()->id]);
            ErpAudit::record('hr.document_verified', $document);
        }

        return back()->with('success', 'Document verified.');
    }

    public function download(StaffDocument $document)
    {
        ErpAudit::record('hr.document_downloaded', $document);

        return Storage::disk('local')->download($document->path, $document->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function types()
    {
        return view('hr.types', ['types' => StaffDocumentType::orderBy('name')->get()]);
    }

    public function saveType(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:staff_document_types', 'applies_to' => ['required', Rule::in(['all', 'teachers', 'citizens', 'non_citizens', 'optional'])], 'reminder_days' => 'required|integer|min:1|max:730', 'guidance' => 'nullable|string|max:3000']);
        $type = StaffDocumentType::create($data);
        StaffProfile::where('status', 'active')->each(function ($staff) use ($type) {
            if ($type->appliesTo($staff)) {
                $staff->requirements()->firstOrCreate(['staff_document_type_id' => $type->id]);
            }
        });
        ErpAudit::record('hr.document_type_created', $type);

        return back()->with('success', 'Document type added.');
    }

    public function updateType(Request $request, StaffDocumentType $type)
    {
        $data = $request->validate(['reminder_days' => 'required|integer|min:1|max:730', 'guidance' => 'nullable|string|max:3000']);
        $type->update($data);
        ErpAudit::record('hr.document_type_updated', $type, $data);

        return back()->with('success', 'Reminder settings saved.');
    }
}
