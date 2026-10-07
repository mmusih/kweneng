<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffLeaveRequest;
use App\Models\StaffProfile;
use App\Models\StaffRequirement;

class DashboardController extends Controller
{
    public function index()
    {
        $attention = StaffRequirement::with('staff', 'type', 'documents')
            ->whereHas('staff', fn ($q) => $q->where('status', 'active'))
            ->get()->filter(fn ($requirement) => ! in_array($requirement->status(), ['valid', 'not_applicable']));
        $leave = StaffLeaveRequest::with('staff', 'type')->where('status', 'submitted');
        $away = StaffLeaveRequest::with('staff', 'type')->where('status', 'approved')
            ->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today());
        $contracts = StaffProfile::where('status', 'active')->whereDate('contract_ends_on', '<=', today()->addDays(90));

        return view('hr.dashboard', [
            'activeStaff' => StaffProfile::where('status', 'active')->count(),
            'pendingLeave' => (clone $leave)->count(),
            'awayCount' => (clone $away)->distinct()->count('staff_profile_id'),
            'contractCount' => (clone $contracts)->count(),
            'attentionCount' => $attention->count(),
            'attention' => $attention->sortBy(fn ($r) => array_search($r->status(), ['expired', 'missing', 'awaiting_verification', 'expiring_soon']))->take(6),
            'leaveRequests' => $leave->oldest()->limit(6)->get(),
            'away' => $away->orderBy('ends_on')->limit(6)->get(),
            'contracts' => $contracts->orderBy('contract_ends_on')->limit(6)->get(),
        ]);
    }
}
