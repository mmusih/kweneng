<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use App\Models\SchoolPurchaseOrder;
use App\Models\SchoolSupplier;
use App\Services\SchoolPaymentService;
use App\Support\ErpAudit;
use App\Support\StaffWorkflows;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchasingController extends Controller
{
    public function index()
    {
        return view('finance.purchasing.index', ['orders' => SchoolPurchaseOrder::with('requisition')->latest()->paginate(30), 'requisitions' => Requisition::where('status', 'approved')->whereDoesntHave('purchaseOrders', fn ($q) => $q->whereNotIn('status', ['rejected', 'cancelled']))->with('requester')->latest()->get()]);
    }

    public function suppliers()
    {
        return view('finance.purchasing.suppliers', ['suppliers' => SchoolSupplier::orderBy('name')->get()]);
    }

    public function saveSupplier(Request $request, ?SchoolSupplier $supplier = null)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:60', 'address' => 'nullable|string|max:2000', 'active' => 'required|boolean']);
        $supplier ??= new SchoolSupplier;
        $supplier->fill($data)->save();
        ErpAudit::record('finance.supplier_saved', $supplier);

        return back()->with('success', 'Supplier saved. Existing purchase orders retain their original supplier details.');
    }

    public function create(Request $request, Requisition $requisition)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        abort_unless($requisition->status === 'approved', 422, 'Only approved requisitions can become purchase orders.');

        return view('finance.purchasing.create', ['requisition' => $requisition->load('items'), 'suppliers' => SchoolSupplier::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, Requisition $requisition)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        $data = $request->validate(['submission_key' => 'required|uuid', 'school_supplier_id' => ['required', Rule::exists('school_suppliers', 'id')->where('active', true)], 'needed_on' => 'nullable|date_format:Y-m-d', 'notes' => 'nullable|string|max:3000', 'prices' => 'required|array', 'prices.*' => 'required|decimal:0,2|gte:0|max:999999999', 'additional_cost' => 'required|decimal:0,2|gte:0|max:999999999', 'additional_cost_description' => 'nullable|string|max:255']);
        $order = DB::transaction(function () use ($request, $requisition, $data) {
            $requisition = Requisition::lockForUpdate()->findOrFail($requisition->id);
            if ($existing = SchoolPurchaseOrder::where('submission_key', $data['submission_key'])->first()) {
                return $existing;
            }
            abort_unless($requisition->status === 'approved', 422, 'This requisition is no longer approved.');
            abort_if($requisition->purchaseOrders()->whereNotIn('status', ['rejected', 'cancelled'])->exists(), 422, 'This requisition already has an active purchase order.');
            $supplier = SchoolSupplier::where('active', true)->findOrFail($data['school_supplier_id']);
            $lines = [];
            $extra = SchoolPaymentService::minor((string) $data['additional_cost']);
            $total = $extra;
            if ($extra > 0 && empty($data['additional_cost_description'])) {
                throw ValidationException::withMessages(['additional_cost_description' => 'Describe additional costs such as delivery or supplier tax.']);
            }
            foreach ($requisition->items as $item) {
                if (! array_key_exists($item->id, $data['prices'])) {
                    throw ValidationException::withMessages(['prices' => 'Provide a unit price for every requisition item.']);
                }
                $quantity = SchoolPaymentService::minor((string) $item->quantity);
                $price = SchoolPaymentService::minor((string) $data['prices'][$item->id]);
                // Quantities and prices are scaled integers. Round each line to the nearest thebe.
                if ($quantity <= 0 || $quantity > 99999999) {
                    throw ValidationException::withMessages(['prices' => 'Unsupported requisition quantity.']);
                }
                if ($price > 0 && $quantity > intdiv(PHP_INT_MAX - 50, $price)) {
                    throw ValidationException::withMessages(['prices' => 'The requested quantity and unit price exceed the order limit.']);
                }
                $line = intdiv($quantity * $price + 50, 100);
                if ($line > 99999999900 - $total) {
                    throw ValidationException::withMessages(['prices' => 'Order exceeds the maximum total of P999,999,999.']);
                }
                $total += $line;
                $lines[] = ['requisition_item_id' => $item->id, 'description' => $item->item_name, 'unit' => $item->unit, 'quantity_hundredths' => $quantity, 'unit_price_minor' => $price, 'total_minor' => $line];
            }
            if (! $lines || count($data['prices']) !== count($lines) || $total <= 0 || $total > 99999999900) {
                throw ValidationException::withMessages(['prices' => 'Check the order lines and total (maximum P999,999,999).']);
            }
            $order = SchoolPurchaseOrder::create(['submission_key' => $data['submission_key'], 'requisition_id' => $requisition->id, 'school_supplier_id' => $supplier->id, 'supplier_name' => $supplier->name, 'supplier_email' => $supplier->email, 'supplier_address' => $supplier->address, 'title' => $requisition->title, 'department' => $requisition->department, 'needed_on' => $data['needed_on'] ?? null, 'notes' => $data['notes'] ?? null, 'total_minor' => $total, 'additional_cost_minor' => $extra, 'additional_cost_description' => $data['additional_cost_description'] ?? null, 'created_by' => $request->user()->id]);
            $order->lines()->createMany($lines);
            ErpAudit::record('finance.purchase_order_created', $order);

            return $order;
        });

        return redirect()->route('finance.purchasing.show', $order)->with('success', 'Purchase order submitted for approval.');
    }

    public function show(SchoolPurchaseOrder $order)
    {
        return view('finance.purchasing.show', ['order' => $order->load('lines', 'payments', 'requisition')]);
    }

    public function review(Request $request, SchoolPurchaseOrder $order)
    {
        abort_unless(StaffWorkflows::approvesSpending($request->user()), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])], 'note' => 'nullable|required_unless:decision,approved|string|max:3000']);
        DB::transaction(function () use ($request, $order, $data) {
            $req = Requisition::lockForUpdate()->findOrFail($order->requisition_id);
            $order = SchoolPurchaseOrder::lockForUpdate()->findOrFail($order->id);
            abort_if($order->created_by === $request->user()->id || $req->requested_by === $request->user()->id, 403, 'Another approver must review this purchase.');
            abort_unless($order->status === 'submitted' || ($data['decision'] === 'cancelled' && $order->status === 'approved'), 422, 'This purchase order cannot be changed at its current stage.');
            abort_if($data['decision'] === 'approved' && $req->status !== 'approved', 422, 'Requisition approval is no longer valid.');
            $order->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note'] ?? null]);
            if ($data['decision'] === 'approved') {
                $req->update(['status' => 'ordered', 'ordered_at' => now()]);
            } elseif ($req->status === 'ordered') {
                $req->update(['status' => 'approved', 'ordered_at' => null]);
            }
            ErpAudit::record('finance.purchase_order_'.$data['decision'], $order, ['note' => $data['note'] ?? null]);
        });

        return back()->with('success', 'Purchase decision recorded.');
    }

    public function receive(Request $request, SchoolPurchaseOrder $order)
    {
        abort_unless(StaffWorkflows::receivesGoods($request->user()), 403);
        $data = $request->validate(['received_on' => 'required|date_format:Y-m-d|before_or_equal:today', 'delivery_note' => 'required|string|max:3000', 'all_received' => 'accepted']);
        DB::transaction(function () use ($request, $order, $data) {
            $req = Requisition::lockForUpdate()->findOrFail($order->requisition_id);
            $order = SchoolPurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if (in_array($order->status, ['received', 'paid'], true)) {
                return;
            }
            abort_unless($order->status === 'approved', 422, 'Only approved orders can be received.');
            abort_if($data['received_on'] < $order->reviewed_at->format('Y-m-d'), 422, 'Delivery cannot predate order approval.');
            $order->update(['status' => 'received', 'received_by' => $request->user()->id, 'received_on' => $data['received_on'], 'delivery_note' => $data['delivery_note']]);
            $req->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);
            ErpAudit::record('finance.purchase_received', $order, $data);
        });

        return back()->with('success', 'Full delivery recorded. Update inventory quantities separately after checking units and asset records.');
    }

    public function invoice(Request $request, SchoolPurchaseOrder $order)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        $data = $request->validate(['invoice_reference' => ['required', 'string', 'max:255', Rule::unique('school_purchase_orders')->where('school_supplier_id', $order->school_supplier_id)->ignore($order->id)], 'amount' => 'required|decimal:0,2|gt:0|max:999999999', 'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $path = null;
        try {
            DB::transaction(function () use ($request, $order, $data, &$path) {
                $order = SchoolPurchaseOrder::lockForUpdate()->findOrFail($order->id);
                abort_unless(in_array($order->status, ['approved', 'received'], true) && ! $order->invoice_path, 422, 'An approved order may have one verified invoice attached.');
                if (SchoolPaymentService::minor((string) $data['amount']) !== (int) $order->total_minor) {
                    throw ValidationException::withMessages(['amount' => 'The invoice total must match the approved order. Resolve differences before recording it.']);
                }
                $path = $request->file('document')->store('purchase-invoices/'.$order->id, 'local');
                abort_unless($path, 500, 'Could not store invoice.');
                $order->update(['invoice_reference' => $data['invoice_reference'], 'invoice_path' => $path, 'invoice_name' => $request->file('document')->getClientOriginalName()]);
                ErpAudit::record('finance.purchase_invoice_recorded', $order);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return back()->with('success', 'Supplier invoice attached.');
    }

    public function downloadInvoice(SchoolPurchaseOrder $order)
    {
        abort_unless($order->invoice_path, 404);
        ErpAudit::record('finance.purchase_invoice_downloaded', $order);

        return Storage::disk('local')->download($order->invoice_path, $order->invoice_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function pay(Request $request, SchoolPurchaseOrder $order)
    {
        abort_unless(StaffWorkflows::finance($request->user()), 403);
        $data = $request->validate(['submission_key' => 'required|uuid', 'paid_on' => 'required|date_format:Y-m-d|before_or_equal:today', 'amount' => 'required|decimal:0,2|gt:0|max:999999999', 'method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque'])], 'reference' => 'required|string|max:255']);
        DB::transaction(function () use ($request, $order, $data) {
            $order = SchoolPurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->payments()->where('submission_key', $data['submission_key'])->exists()) {
                return;
            }
            abort_unless($order->status === 'received' && $order->invoice_path, 422, 'Record full delivery and the supplier invoice before payment.');
            abort_if($data['paid_on'] < $order->received_on->format('Y-m-d'), 422, 'Payment cannot predate receipt of goods.');
            $minor = SchoolPaymentService::minor((string) $data['amount']);
            if ($minor > $order->outstandingMinor()) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the unpaid order balance.']);
            }
            if ($order->payments()->where('reference', $data['reference'])->exists()) {
                throw ValidationException::withMessages(['reference' => 'This payment reference is already recorded for the order.']);
            }
            $payment = $order->payments()->create(collect($data)->only(['submission_key', 'paid_on', 'method', 'reference'])->all() + ['amount_minor' => $minor, 'recorded_by' => $request->user()->id]);
            if ($order->outstandingMinor() === 0) {
                $order->update(['status' => 'paid']);
            }
            ErpAudit::record('finance.purchase_payment_recorded', $payment);
        });

        return back()->with('success', 'Completed supplier payment recorded. No money was transferred by the ERP.');
    }

    public function pdf(SchoolPurchaseOrder $order)
    {
        return Pdf::loadView('finance.purchasing.pdf', ['order' => $order->load('lines')])->setPaper('a4')->download($order->reference().'.pdf')->header('Cache-Control', 'private, no-store');
    }
}
