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

        // Superadmin can access staff (superadmin/TFRB officer) routes, but must
        // NOT bypass operator or TODA-president scoped routes: a superadmin has no
        // operator/president profile, and those pages rely on role-specific data.
        if ($userRole === 'superadmin') {
            $staffRoles = ['superadmin', 'tfrb_officer'];
            $scopedRoles = ['operator', 'operator_president'];
            if (array_intersect($roles, $staffRoles) && !array_intersect($roles, $scopedRoles)) {
                return $next($request);
            }
        }

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
