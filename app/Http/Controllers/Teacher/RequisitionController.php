<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\InventoryItem;
use App\Models\Requisition;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequisitionController extends Controller
{
    public function index()
    {
        $requisitions = Requisition::with(['items', 'handler'])
            ->where('requested_by', Auth::id())
            ->latest()
            ->paginate(15);

        return view('teacher.requisitions.index', [
            'requisitions' => $requisitions,
            'statuses' => Requisition::statuses(),
        ]);
    }

    public function create()
    {
        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : Term::current();
        $inventoryItems = InventoryItem::orderBy('name')->get();

        return view('teacher.requisitions.create', [
            'activeAcademicYear' => $activeAcademicYear,
            'activeTerm' => $activeTerm,
            'priorities' => Requisition::priorities(),
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:120'],
            'priority' => ['required', Rule::in(array_keys(Requisition::priorities()))],
            'needed_by' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeAcademicYear = AcademicYear::current();
        $activeTerm = $activeAcademicYear ? Term::current($activeAcademicYear->id) : Term::current();

        $requisition = DB::transaction(function () use ($validated, $activeAcademicYear, $activeTerm) {
            $requisition = Requisition::create([
                'requested_by' => Auth::id(),
                'academic_year_id' => $activeAcademicYear?->id,
                'term_id' => $activeTerm?->id,
                'title' => $validated['title'],
                'department' => $validated['department'] ?? null,
                'priority' => $validated['priority'],
                'needed_by' => $validated['needed_by'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'status' => Requisition::STATUS_SUBMITTED,
            ]);

            foreach ($validated['items'] as $item) {
                $requisition->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'category' => $item['category'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $requisition;
        });

        return redirect()->route('teacher.requisitions.show', $requisition)
            ->with('success', 'Requisition submitted successfully. Inventory will see it immediately.');
    }

    public function show(Requisition $requisition)
    {
        abort_unless((int) $requisition->requested_by === (int) Auth::id(), 403);

        $requisition->load(['items.inventoryItem', 'handler', 'academicYear', 'term']);

        return view('teacher.requisitions.show', [
            'requisition' => $requisition,
            'statuses' => Requisition::statuses(),
        ]);
    }

    public function cancel(Requisition $requisition)
    {
        abort_unless((int) $requisition->requested_by === (int) Auth::id(), 403);
        abort_unless(in_array($requisition->status, [Requisition::STATUS_SUBMITTED, Requisition::STATUS_ACKNOWLEDGED], true), 422, 'This requisition can no longer be cancelled by the requester.');

        $requisition->update([
            'status' => Requisition::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('teacher.requisitions.show', $requisition)
            ->with('success', 'Requisition cancelled successfully.');
    }
}
