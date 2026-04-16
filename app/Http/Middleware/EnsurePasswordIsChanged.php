<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'password.edit',
            'password.update',
            'logout',
        ];

        if ($request->route() && in_array($request->route()->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()
            ->route('password.edit')
            ->with('warning', 'You must change your temporary password before continuing.');
    }
}
