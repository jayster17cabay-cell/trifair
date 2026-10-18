<?php

namespace App\Http\Middleware;

use App\Support\TodaContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Holds a multi-TODA account on a "which TODA are you acting as?" screen until
 * it picks one, so the dashboard and every number on it follow the role it has
 * in that specific organisation. A single-TODA account has nothing to choose
 * and sails straight through; the choice made here lives in the session for the
 * rest of the login (see TodaContext::set).
 *
 * Registered only on the operator and president route groups — the selection
 * routes themselves sit outside it, so it can never loop.
 */
class TodaSelectionRequired
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || (!$user->isOperator() && !$user->isOperatorPresident())) {
            return $next($request);
        }

        if (TodaContext::hasChoice($user) && !TodaContext::isChosen($user)) {
            return redirect()->route('toda.select');
        }

        return $next($request);
    }
}
