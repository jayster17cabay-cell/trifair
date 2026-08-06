<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = Auth::user()->role;

        // Superadmin can access admin/officer routes, but NOT operator routes:
        // a superadmin has no operator profile and entering those pages used to
        // cause a 500 error (Auth::user()->operator was null).
        if ($userRole === 'superadmin' && !in_array('operator', $roles, true)) {
            return $next($request);
        }

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
