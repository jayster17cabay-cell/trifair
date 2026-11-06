<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks an account whose password was set by an administrator (or TODA
 * president) until the owner replaces it. Only the forced-change page and
 * logout remain reachable, so the default password cannot be used to browse
 * the rest of the application.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->requiresPasswordChange()) {
            return $next($request);
        }

        if ($request->routeIs('password.force', 'password.force.update', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You must change your password before continuing.',
            ], 423);
        }

        return redirect()
            ->route('password.force')
            ->with('status', 'Please choose a new password before continuing.');
    }
}
