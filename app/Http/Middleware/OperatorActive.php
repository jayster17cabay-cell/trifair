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
        if ($user && $user->isOperator()) {
            $operator = $user->operator;
            if (!$operator) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is incomplete. Please contact support.',
                ]);
            }
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
                    'email' => 'Your account has been deactivated. Please contact support.',
                ]);
            }
        }
        return $next($request);
    }
}
