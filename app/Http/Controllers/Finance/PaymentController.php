<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PaymentCategory;
use App\Models\SchoolPayment;
use App\Models\Student;
use App\Models\StudentFinanceAccount;
use App\Models\User;
use App\Services\SchoolPaymentService;
use App\Support\ErpAudit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['date' => 'nullable|date', 'status' => ['nullable', Rule::in(['pending', 'confirmed', 'reversed'])], 'search' => 'nullable|string|max:255']);
        $query = SchoolPayment::query()->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('paid_on', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $text) => $q->where(fn ($q) => $q->where('payer_name', 'like', '%'.$text.'%')->orWhere('receipt_number', 'like', '%'.$text.'%')));
        $totals = (clone $query)->where('status', 'confirmed')->selectRaw('method, SUM(amount_minor) as total')->groupBy('method')->get();

        return view('finance.index', ['payments' => $query->latest('id')->paginate(30)->withQueryString(), 'totals' => $totals]);
    }

    public function create()
    {
        return view('finance.create', ['categories' => PaymentCategory::orderBy('name')->get(), 'students' => Student::with('user')->get(), 'parents' => User::where('role', 'parent')->where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(Request $request, SchoolPaymentService $service)
    {
        $data = $request->validate([
            'submission_key' => 'required|uuid', 'payer_name' => 'required|string|max:255', 'payer_email' => 'nullable|email|max:255',
            'parent_user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'parent')->where('status', 'active')],
            'paid_on' => 'required|date|before_or_equal:today', 'method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque'])],
            'reference' => 'nullable|required_unless:method,cash|string|max:255', 'amount' => 'required|decimal:0,2|gt:0|max:999999999',
            'items' => 'required|array|min:1|max:50', 'items.*.payment_category_id' => 'required|exists:payment_categories,id',
            'items.*.student_id' => ['nullable', Rule::exists('students', 'id')->whereNull('deleted_at')], 'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|decimal:0,2|gt:0|max:999999999', 'confirm_now' => 'sometimes|boolean',
        ]);
        if ($existing = SchoolPayment::where('submission_key', $data['submission_key'])->first()) {
            return redirect()->route('finance.payments.show', $existing);
        }
        $items = [];
        $total = 0;
        $parent = empty($data['parent_user_id']) ? null : User::findOrFail($data['parent_user_id']);
        $allowedStudents = $parent?->parent?->students()->pluck('students.id')->all() ?? [];
        foreach ($data['items'] as $item) {
            $category = PaymentCategory::findOrFail($item['payment_category_id']);
            $student = empty($item['student_id']) ? null : Student::with('user')->findOrFail($item['student_id']);
            if ($parent && $student && ! in_array($student->id, $allowedStudents)) {
                throw ValidationException::withMessages(['parent_user_id' => 'The selected parent must be linked to every student on this receipt. Issue separate receipts for unrelated families.']);
            }
            if ($category->kind === 'fees') {
                $account = $student ? StudentFinanceAccount::where('student_id', $student->id)->first() : null;
                if (! $account || $data['paid_on'] < $account->opened_on->format('Y-m-d')) {
                    throw ValidationException::withMessages(['items' => 'Tuition allocations require an open student ledger and a payment date on or after its opening date.']);
                }
            }
            $minor = $service::minor((string) $item['amount']);
            $total += $minor;
            $items[] = ['payment_category_id' => $category->id, 'student_id' => $student?->id, 'student_name' => $student?->user?->name, 'category_name' => $category->name, 'kind' => $category->kind, 'description' => $item['description'], 'amount_minor' => $minor];
        }
        if ($total !== $service::minor((string) $data['amount'])) {
            throw ValidationException::withMessages(['amount' => 'The payment total must exactly equal the allocation total.']);
        }
        try {
            $payment = DB::transaction(function () use ($request, $data, $total, $items) {
                $payment = SchoolPayment::create(collect($data)->only(['submission_key', 'payer_name', 'payer_email', 'parent_user_id', 'paid_on', 'method', 'reference'])->all() + ['amount_minor' => $total, 'created_by' => $request->user()->id]);
                $payment->items()->createMany($items);
                ErpAudit::record('finance.payment_recorded', $payment);

                return $payment;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A simultaneous retry may have passed the earlier lookup before the first request committed.
            $existing = SchoolPayment::where('submission_key', $data['submission_key'])->first();
            if (! $existing) {
                throw $e;
            }

            return redirect()->route('finance.payments.show', $existing);
        }
        if ($request->boolean('confirm_now')) {
            $payment = $service->confirm($payment, $request->user()->id);
        }

        return redirect()->route('finance.payments.show', $payment)->with('success', 'Payment recorded. '.($payment->email_status === 'failed' ? 'Receipt email failed; you can retry below.' : ''));
    }

    public function show(SchoolPayment $payment)
    {
        return view('finance.show', ['payment' => $payment->load('items')]);
    }

    public function confirm(Request $request, SchoolPayment $payment, SchoolPaymentService $service)
    {
        $payment = $service->confirm($payment, $request->user()->id);

        return back()->with('success', 'Payment confirmed. '.($payment->email_status === 'failed' ? 'Receipt email failed; retry below.' : 'Receipt is available.'));
    }

    public function reverse(Request $request, SchoolPayment $payment)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['reason' => 'required|string|min:5|max:2000']);
        DB::transaction(function () use ($payment, $request, $data) {
            $payment = SchoolPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'reversed') {
                return;
            }
            $payment->update(['status' => 'reversed', 'reversal_reason' => $data['reason'], 'reversed_by' => $request->user()->id, 'reversed_at' => now()]);
            ErpAudit::record('finance.payment_reversed', $payment, $data);
        });

        return back()->with('success', 'Payment reversed. The original record and receipt number are retained.');
    }

    public function download(SchoolPayment $payment, SchoolPaymentService $service)
    {
        return $service->pdf($payment)->download($payment->receipt_number.'.pdf')->header('Cache-Control', 'private, no-store');
    }

    public function email(SchoolPayment $payment, SchoolPaymentService $service)
    {
        return back()->with('success', $service->email($payment) ? 'Receipt handed to the mail service. This is not confirmation of inbox delivery.' : 'Email failed. Check mail configuration and retry.');
    }

    public function category(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:payment_categories', 'kind' => ['required', Rule::in(['fees', 'income', 'deposit'])]]);
        $category = PaymentCategory::create($data);
        ErpAudit::record('finance.category_created', $category);

        return back()->with('success', 'Payment category added.');
    }
}
