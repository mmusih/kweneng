<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFeeBalance;
use App\Models\StudentFinanceAccount;
use App\Services\SchoolPaymentService;
use App\Support\ErpAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index()
    {
        return view('finance.accounts', ['students' => Student::with('user')->get(), 'accounts' => StudentFinanceAccount::with('student.user')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['student_id' => 'required|exists:students,id|unique:student_finance_accounts', 'opened_on' => 'required|date|before_or_equal:today', 'opening_balance' => 'required|decimal:0,2|between:-999999999,999999999']);
        $student = Student::findOrFail($data['student_id']);
        $account = StudentFinanceAccount::create(['student_id' => $student->id, 'opened_on' => $data['opened_on'], 'opening_balance_minor' => SchoolPaymentService::minor((string) $data['opening_balance']), 'created_by' => $request->user()->id]);
        ErpAudit::record('finance.ledger_opened', $account, ['opening_balance_minor' => $account->opening_balance_minor]);

        return redirect()->route('finance.accounts.show', $account)->with('success', 'Ledger opened. Imported balances will no longer determine this student’s displayed balance.');
    }

    public function show(StudentFinanceAccount $account)
    {
        return view('finance.account', ['account' => $account->load('student.user', 'charges'), 'payments' => $account->payments()->with('payment')->get(), 'balance' => $account->balanceMinor(), 'imported' => StudentFeeBalance::where('student_id', $account->student_id)->latest('updated_at')->first()]);
    }

    public function charge(Request $request, StudentFinanceAccount $account)
    {
        $data = $request->validate(['submission_key' => 'required|uuid', 'charged_on' => 'required|date|after_or_equal:'.$account->opened_on->format('Y-m-d').'|before_or_equal:today', 'description' => 'required|string|max:255', 'amount' => 'required|decimal:0,2|between:-999999999,999999999']);
        $amount = SchoolPaymentService::minor((string) $data['amount']);
        if (! $amount) {
            throw ValidationException::withMessages(['amount' => 'Amount cannot be zero.']);
        }
        if ($amount < 0) {
            abort_unless($request->user()->isAdmin(), 403, 'Only administrators may post credits.');
        }
        DB::transaction(function () use ($account, $data, $amount, $request) {
            $charge = $account->charges()->firstOrCreate(['submission_key' => $data['submission_key']], ['charged_on' => $data['charged_on'], 'description' => $data['description'], 'amount_minor' => $amount, 'created_by' => $request->user()->id]);
            if ($charge->wasRecentlyCreated) {
                ErpAudit::record('finance.charge_posted', $charge);
            }
        });

        return back()->with('success', 'Account entry recorded.');
    }
}
