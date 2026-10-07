<?php

namespace App\Http\Controllers;

use App\Models\StaffExpenseClaim;
use App\Models\StaffLeaveRequest;
use App\Support\StaffWorkflows;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $staff = StaffWorkflows::staff($request->user());
        $leave = StaffLeaveRequest::whereHas('staff', fn ($q) => $q->where('user_id', $request->user()->id));
        $expenses = StaffExpenseClaim::whereHas('staff', fn ($q) => $q->where('user_id', $request->user()->id));

        return view('staff.dashboard', [
            'staff' => $staff,
            'pendingLeave' => (clone $leave)->where('status', 'submitted')->count(),
            'pendingExpenses' => (clone $expenses)->whereIn('status', ['submitted', 'approved'])->sum('amount_minor'),
            'leaveRequests' => $leave->with('type')->latest()->limit(5)->get(),
            'expenses' => $expenses->latest()->limit(5)->get(),
        ]);
    }
}
