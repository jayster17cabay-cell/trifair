<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresidentActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->isOperatorPresident()) {
            return $next($request);
        }

        // A president must preside over at least one TODA (either through an
        // is_president membership or the legacy users.toda_id assignment).
        if ($user->presidentTodas()->isEmpty()) {
            return $this->logout($request, 'Your account is not assigned to a TODA. Please contact support.');
        }

        $operator = $user->operator;
        if (!$operator) {
            return $next($request);
        }

        // Reconcile a lapsed suspension. A suspension itself no longer ends the
        // session or silently moves the president to another TODA: whichever
        // TODA they chose is the one they stay on, and that dashboard reports
        // "You are suspended" (see partials/toda-suspended-screen).
        $operator->syncSuspensionStatus();

        // A president rides with their members, so a lapsed franchise closes
        // their session exactly like it does for a regular operator — the
        // renewal reopens it. Archiving works the same way.
        $operator->syncFranchiseStatus();

        if ($operator->isArchived()) {
            return $this->logout($request, 'Your account has been archived. Please contact support.');
        }

        if ($operator->status === 'inactive') {
            return $this->logout($request, $operator->hasExpiredFranchise()
                ? 'Your franchise has expired. Please contact the TFRB Office to renew it.'
                : 'Your account has been deactivated. Please contact support.');
        }

        return $next($request);
    }

    private function logout(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
