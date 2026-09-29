<?php

namespace App\Http\Middleware;

use App\Support\UserRoles;
use Closure;
use Illuminate\Http\Request;

class StaffSelfService
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isActive() && in_array($request->user()->role, UserRoles::manageableStaff(), true), 403);

        return $next($request);
    }
}
