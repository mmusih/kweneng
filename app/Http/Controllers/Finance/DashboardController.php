<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceDashboardService;

class DashboardController extends Controller
{
    public function index(FinanceDashboardService $dashboard)
    {
        if (request()->user()->role === 'accounts_officer') {
            return redirect()->route('accounts-officer.dashboard');
        }

        return view('finance.dashboard', $dashboard->data());
    }
}