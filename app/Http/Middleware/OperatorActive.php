<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        // TODA presidents hold an Operator row and use these same screens, so a
        // lapsed franchise locks them out here too (the /president group runs
        // PresidentActive, which enforces the same rule).
        if ($user && ($user->isOperator() || $user->isOperatorPresident())) {
            $user->loadMissing('operator.currentTerm', 'operator.currentSuspension');
            $operator = $user->operator;
            if (!$operator) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is incomplete. Please contact support.',
                ]);
            }
            // Drop the session the moment the franchise lapses mid-session.
            $operator->syncFranchiseStatus();
            // Lift a TODA suspension the moment its end date has passed.
            $operator->syncSuspensionStatus();
            if ($operator->isArchived()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been archived. Please contact support.',
                ]);
            }
            // A suspension is not a session-killer: the account stays signed in
            // and the TODA it opens on reports "You are suspended" instead. A
            // lapsed suspension is still lifted here via syncSuspensionStatus().
            if ($operator->status === 'pending') {
                return redirect()->route('operator.pending');
            }
            if ($operator->status === 'rejected') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been rejected. Contact support for more information.',
                ]);
            }
            if ($operator->status === 'inactive') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => $operator->hasExpiredFranchise()
                        ? 'Your franchise has expired. Please contact the TFRB Office to renew it.'
                        : 'Your account has been deactivated. Please contact support.',
                ]);
            }
        }
        return $next($request);
    }
}
