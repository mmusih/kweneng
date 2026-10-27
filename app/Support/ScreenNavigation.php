<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ScreenNavigation
{
    public static function hasBackLink(string $html): bool
    {
        preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $html, $links);

        foreach ($links[1] as $label) {
            $text = trim(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (preg_match('/^(?:[←‹«]\s*)?Back(?:\s|$)/iu', $text)) {
                return true;
            }
        }

        return false;
    }

    public static function backUrl(Request $request): ?string
    {
        if ($url = StudentProfileNavigation::backUrl($request)) {
            return $url;
        }

        $leave = $request->route()?->parameter('leave');
        if ($request->user()?->canManageHr() && $leave instanceof \App\Models\StaffLeaveRequest && $leave->staff->user_id !== $request->user()->id) {
            return route('hr.leave.index');
        }
        $claim = $request->route()?->parameter('claim');
        if ($claim instanceof \App\Models\StaffExpenseClaim && (StaffWorkflows::finance($request->user()) || StaffWorkflows::approvesSpending($request->user())) && $claim->staff->user_id !== $request->user()->id) {
            return route('finance.expenses.index');
        }
        if ($request->routeIs('hr.staff.index', 'hr.leave.index', 'hr.types')) {
            return route('hr.dashboard');
        }
        if ($request->routeIs('finance.payments.index', 'finance.accounts.index', 'finance.expenses.index', 'finance.purchasing.index') && StaffWorkflows::finance($request->user())) {
            return route($request->user()->role === 'accounts_officer' ? 'accounts-officer.dashboard' : 'finance.dashboard');
        }
        if ($request->routeIs('staff.leave.index', 'staff.expenses.index')) {
            return route('staff.dashboard');
        }

        if ($request->routeIs('admin.timetable.*') && ! $request->routeIs('admin.timetable.index', 'admin.timetable.grid')) {
            $setting = $request->route('setting') ?? $request->integer('setting');
            return route('admin.timetable.index', array_filter(['setting' => $setting]));
        }

        $name = $request->route()?->getName();
        if ($name) {
            $parts = explode('.', $name);
            array_pop($parts);
            while (count($parts) > 1) {
                $parent = implode('.', $parts).'.index';
                if ($parent !== $name && Route::has($parent) && Route::getRoutes()->getByName($parent)->parameterNames() === []) {
                    return route($parent);
                }
                array_pop($parts);
            }
        }

        return null;
    }
}
