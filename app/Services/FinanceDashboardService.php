<?php

namespace App\Services;


use App\Models\Requisition;
use App\Models\SchoolPayment;
use App\Models\SchoolPurchaseOrder;
use App\Models\SchoolPurchasePayment;
use App\Models\StaffExpenseClaim;
use Illuminate\Support\Facades\DB;

class FinanceDashboardService
{
    public function data(): array
    {
        $start = today()->startOfMonth()->toDateString();
        $end = today()->toDateString();
        $collected = SchoolPayment::where('status', 'confirmed')->whereDate('paid_on', '>=', $start)->whereDate('paid_on', '<=', $end);
        $expensePaid = StaffExpenseClaim::where('status', 'paid')->whereDate('paid_on', '>=', $start)->whereDate('paid_on', '<=', $end)->sum('amount_minor');
        $supplierPaid = SchoolPurchasePayment::whereDate('paid_on', '>=', $start)->whereDate('paid_on', '<=', $end)->sum('amount_minor');

        // Aggregate each ledger independently, so credits never hide another student's debt.
        $charges = DB::table('student_finance_charges')->selectRaw('student_finance_account_id, SUM(amount_minor) as total')->groupBy('student_finance_account_id');
        $payments = DB::table('school_payment_items as items')
            ->join('school_payments as payments', 'payments.id', '=', 'items.school_payment_id')
            ->join('student_finance_accounts as accounts', 'accounts.student_id', '=', 'items.student_id')
            ->where('items.kind', 'fees')->where('payments.status', 'confirmed')
            ->whereColumn('payments.paid_on', '>=', 'accounts.opened_on')
            ->selectRaw('accounts.id as account_id, SUM(items.amount_minor) as total')->groupBy('accounts.id');
        $balances = DB::table('student_finance_accounts as accounts')
            ->leftJoinSub($charges, 'charges', 'charges.student_finance_account_id', '=', 'accounts.id')
            ->leftJoinSub($payments, 'payments', 'payments.account_id', '=', 'accounts.id')
            ->selectRaw('accounts.opening_balance_minor + COALESCE(charges.total, 0) - COALESCE(payments.total, 0) as balance')->pluck('balance');
        $orders = SchoolPurchaseOrder::whereIn('status', ['approved', 'received'])->withSum('payments', 'amount_minor')->get();

        return [
            'collected' => (clone $collected)->sum('amount_minor'),
            'outgoing' => $expensePaid + $supplierPaid,
            'outstandingFees' => $balances->filter(fn ($amount) => $amount > 0)->sum(),
            'ledgerCount' => $balances->count(),
            'supplierOutstanding' => $orders->sum(fn ($order) => max(0, $order->total_minor - ($order->payments_sum_amount_minor ?? 0))),
            'pendingPayments' => SchoolPayment::where('status', 'pending')->count(),
            'pendingExpenses' => StaffExpenseClaim::where('status', 'submitted')->count(),
            'approvedExpenses' => StaffExpenseClaim::where('status', 'approved')->sum('amount_minor'),
            'pendingOrders' => SchoolPurchaseOrder::where('status', 'submitted')->count(),
            'readyRequisitions' => Requisition::where('status', 'approved')->whereDoesntHave('purchaseOrders', fn ($q) => $q->whereNotIn('status', ['rejected', 'cancelled']))->count(),
            'payments' => SchoolPayment::latest('id')->limit(6)->get(),
            'expenses' => StaffExpenseClaim::with('staff')->whereIn('status', ['submitted', 'approved'])->oldest()->limit(6)->get(),
            'orders' => SchoolPurchaseOrder::whereIn('status', ['submitted', 'approved', 'received'])->oldest()->limit(6)->get(),
            'methods' => $collected->selectRaw('method, SUM(amount_minor) as total')->groupBy('method')->get(),
        ];
    }
}
