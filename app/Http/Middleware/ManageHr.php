<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ManageHr
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canManageHr(), 403);

        return $next($request);
    }
}
