<?php

namespace App\Http\Middleware;

use App\Support\TodaContext;
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

        $operator->syncSuspensionStatus();

        // The console is always scoped to a TODA the user actually presides
        // over. If that presidency is suspended, the president is held: they
        // move to another TODA they still preside over, or — when this was
        // their only presidency — the session is dropped like any suspended
        // operator.
        $toda = $user->presidentToda();

        if ($toda && $operator->isSuspendedForToda((int) $toda->id)) {
            $fallback = $user->presidentTodas()->first(function ($presidency) use ($operator) {
                return !$operator->isSuspendedForToda((int) $presidency->id);
            });

            if ($fallback) {
                TodaContext::set((int) $fallback->id);

                return redirect()->route('president.dashboard')->with('error', 'Your presidency of ' . $toda->name . ' is currently suspended. Showing your other TODA.');
            }

            return $this->logout($request, 'Your account is currently suspended by the TFRB Office. Please contact support.');
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
