<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\ParentMessage;
use App\Models\Requisition;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'students' => Student::count(),
            'incomplete_profiles' => Student::where(function ($query) {
                $query->whereNull('nationality')
                    ->orWhereNull('identity_document_type')
                    ->orWhereNull('identity_document_number')
                    ->orWhereNull('emergency_contact_name')
                    ->orWhereNull('emergency_contact_phone');
            })->count(),
            'events' => Event::count(),
            'upcoming_events' => Event::where('start_datetime', '>=', now())->count(),
            'announcements' => Announcement::count(),
            'unread_messages' => ParentMessage::where('is_read_by_admin', false)->count(),
            'inventory_attention' => InventoryItem::whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
                ->orWhereIn('condition_status', [InventoryItem::CONDITION_DAMAGED, InventoryItem::CONDITION_NEEDS_REPAIR, InventoryItem::CONDITION_BROKEN])
                ->orWhere('procurement_status', InventoryItem::PROCUREMENT_NEEDS_BUYING)
                ->count(),
            'new_requisitions' => Requisition::where('status', Requisition::STATUS_SUBMITTED)->count(),
        ];

        return view('office.dashboard', compact('stats'));
    }
}
