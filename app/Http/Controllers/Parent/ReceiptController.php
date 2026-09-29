<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\SchoolPayment;
use App\Models\StudentFinanceAccount;
use App\Services\SchoolPaymentService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    private function query(Request $request)
    {
        abort_unless($request->user()->isActive(), 403);
        $studentIds = $request->user()->parent?->students()->pluck('students.id')->all() ?? [];

        // Explicit recipient plus current family membership prevents cross-family disclosure.
        return SchoolPayment::where('parent_user_id', $request->user()->id)->whereNotNull('receipt_number')
            ->whereDoesntHave('items', fn ($q) => $q->whereNotNull('student_id')->whereNotIn('student_id', $studentIds));
    }

    public function index(Request $request)
    {
        $receipts = $this->query($request)->with('items')->latest('id')->paginate(30);
        if ($request->is('api/*')) {
            return response()->json(['success' => true, 'receipts' => $receipts->through(fn ($p) => [
                'id' => $p->id, 'receipt_number' => $p->receipt_number, 'paid_on' => $p->paid_on->format('Y-m-d'),
                'amount_minor' => $p->amount_minor, 'currency' => 'BWP', 'status' => $p->status, 'method' => $p->method,
                'items' => $p->items->map(fn ($i) => ['description' => $i->description, 'category' => $i->category_name, 'student_name' => $i->student_name, 'amount_minor' => $i->amount_minor]),
            ])]);
        }
        $studentIds = $request->user()->parent?->students()->pluck('students.id') ?? [];

        return view('parent.receipts.index', ['receipts' => $receipts, 'accounts' => StudentFinanceAccount::with('student.user')->whereIn('student_id', $studentIds)->get()]);
    }

    public function download(Request $request, SchoolPayment $payment, SchoolPaymentService $service)
    {
        abort_unless($this->query($request)->whereKey($payment->id)->exists(), 404);

        return $service->pdf($payment)->download($payment->receipt_number.'.pdf')->header('Cache-Control', 'private, no-store');
    }
}
