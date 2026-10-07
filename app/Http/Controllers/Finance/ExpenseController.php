<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StaffExpenseClaim;
use App\Models\StaffProfile;
use App\Services\SchoolPaymentService;
use App\Support\ErpAudit;
use App\Support\StaffWorkflows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::in(['submitted', 'approved', 'rejected', 'cancelled', 'paid'])]]);
        $manage = $request->routeIs('finance.*');
        $staff = StaffWorkflows::staff($request->user());
        $claims = StaffExpenseClaim::with('staff', 'department')->when(! $manage, fn ($q) => $q->whereHas('staff', fn ($q) => $q->where('user_id', $request->user()->id)))->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))->latest()->paginate(30)->withQueryString();

        return view('finance.expenses.index', compact('manage', 'staff', 'claims') + ['departments' => Department::orderBy('name')->get()]);
    }

    private function authorise(Request $request, StaffExpenseClaim $claim): void
    {
        abort_unless(StaffWorkflows::finance($request->user()) || StaffWorkflows::approvesSpending($request->user()) || $claim->staff->user_id === $request->user()->id, 403);
    }

    public function store(Request $request)
    {
        $staff = StaffWorkflows::staff($request->user());
        abort_unless($staff, 403, 'HR must link an active staff profile to your account.');
        $data = $request->validate(['submission_key' => 'required|uuid', 'description' => 'required|string|max:255', 'reason' => 'required|string|max:3000', 'category' => ['required', Rule::in(['travel', 'training', 'classroom', 'supplies', 'other'])], 'spent_on' => 'required|date_format:Y-m-d|before_or_equal:today', 'department_id' => 'nullable|exists:departments,id', 'amount' => 'required|decimal:0,2|gt:0|max:999999999', 'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $path = null;
        try {
            $claim = DB::transaction(function () use ($request, $staff, $data, &$path) {
                StaffProfile::whereKey($staff->id)->lockForUpdate()->firstOrFail();
                if ($existing = StaffExpenseClaim::where('submission_key', $data['submission_key'])->first()) {
                    $this->authorise($request, $existing);

                    return $existing;
                }
                $path = $request->file('document')->store('staff-expenses/'.$staff->id, 'local');
                abort_unless($path, 500, 'Could not store receipt.');
                $claim = StaffExpenseClaim::create(collect($data)->only(['submission_key', 'description', 'reason', 'category', 'spent_on', 'department_id'])->all() + ['staff_profile_id' => $staff->id, 'submitted_by' => $request->user()->id, 'amount_minor' => SchoolPaymentService::minor((string) $data['amount']), 'document_path' => $path, 'document_name' => $request->file('document')->getClientOriginalName()]);
                ErpAudit::record('finance.expense_submitted', $claim);

                return $claim;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return redirect()->route('staff.expenses.show', $claim)->with('success', 'Expense claim submitted for approval.');
    }

    public function show(Request $request, StaffExpenseClaim $claim)
    {
        $this->authorise($request, $claim);

        return view('finance.expenses.show', ['claim' => $claim->load('staff', 'department')]);
    }

    public function document(Request $request, StaffExpenseClaim $claim)
    {
        $this->authorise($request, $claim);
        ErpAudit::record('finance.expense_document_downloaded', $claim);

        return Storage::disk('local')->download($claim->document_path, $claim->document_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, StaffExpenseClaim $claim)
    {
        abort_unless(StaffWorkflows::approvesSpending($request->user()), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'note' => 'nullable|required_if:decision,rejected|string|max:3000']);
        DB::transaction(function () use ($request, $claim, $data) {
            $claim = StaffExpenseClaim::lockForUpdate()->findOrFail($claim->id);
            abort_if($claim->submitted_by === $request->user()->id || $claim->staff->user_id === $request->user()->id, 403, 'You cannot approve your own claim.');
            abort_unless($claim->status === 'submitted', 422, 'Only submitted claims can be reviewed.');
            $claim->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note'] ?? null]);
            ErpAudit::record('finance.expense_'.$data['decision'], $claim, ['note' => $data['note'] ?? null]);
        });

        return back()->with('success', 'Expense decision recorded.');
    }

    public function cancel(Request $request, StaffExpenseClaim $claim)
    {
        abort_unless($claim->staff->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($claim) {
            $claim = StaffExpenseClaim::lockForUpdate()->findOrFail($claim->id);
            abort_unless($claim->status === 'submitted', 422, 'Only a submitted claim can be withdrawn.');
            $claim->update(['status' => 'cancelled']);
            ErpAudit::record('finance.expense_cancelled', $claim);
        });

        return back()->with('success', 'Claim withdrawn.');
    }

    public function pay(Request $request, StaffExpenseClaim $claim)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        $data = $request->validate(['paid_on' => 'required|date_format:Y-m-d|before_or_equal:today', 'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque'])], 'payment_reference' => 'required|string|max:255']);
        DB::transaction(function () use ($request, $claim, $data) {
            $claim = StaffExpenseClaim::lockForUpdate()->findOrFail($claim->id);
            abort_if($claim->staff->user_id === $request->user()->id || $claim->submitted_by === $request->user()->id, 403, 'Another Accounts user must record your reimbursement.');
            if ($claim->status === 'paid') {
                return;
            }
            abort_unless($claim->status === 'approved', 422, 'Approve the claim before recording reimbursement.');
            abort_if($data['paid_on'] < $claim->reviewed_at->format('Y-m-d'), 422, 'Reimbursement cannot predate approval.');
            $claim->update($data + ['paid_by' => $request->user()->id, 'status' => 'paid']);
            ErpAudit::record('finance.expense_paid', $claim, ['reference' => $data['payment_reference']]);
        });

        return back()->with('success', 'Completed reimbursement recorded. No money was transferred by the ERP.');
    }
}
