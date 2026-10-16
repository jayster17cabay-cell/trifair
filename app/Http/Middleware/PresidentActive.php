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
        if ($user && $user->isOperatorPresident()) {
            if (!$user->presidentToda()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is not assigned to a TODA. Please contact support.',
                ]);
            }

            // The president also owns an Operator row, so an admin-issued
            // suspension must drop their session just like a regular operator.
            $user->loadMissing('operator.currentSuspension');
            $operator = $user->operator;
            if ($operator) {
                $operator->syncSuspensionStatus();
                if ($operator->isSuspended()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->withErrors([
                        'email' => 'Your account is currently suspended by the TFRB Office. Please contact support.',
                    ]);
                }
            }
        }
        return $next($request);
    }
}
