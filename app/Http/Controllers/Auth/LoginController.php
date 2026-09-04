<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($credentials['email']));

        // Try the normalized (lowercase) email first; fall back to the raw
        // input so legacy accounts registered with mixed-case emails still work.
        $attempted = Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))
            || ($email !== $credentials['email']
                && Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember')));

        if ($attempted) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Your account is currently disabled. Please contact support.',
                ])->onlyInput('email');
            }

            if ($user->isSuperadmin()) {
                ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');
                return redirect()->route('superadmin.dashboard');
            }

            if ($user->isTfrbOfficer()) {
                ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');
                return redirect()->route('tfrb-officer.dashboard');
            }

            if ($user->isOperatorPresident()) {
                if (!$user->presidentToda()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Your account is not assigned to a TODA. Please contact support.',
                    ])->onlyInput('email');
                }
                ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');
                return redirect()->route('president.dashboard');
            }

            if ($user->isOperator()) {
                $operator = $user->operator;
                if (!$operator) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Your account is incomplete. Please contact support.',
                    ])->onlyInput('email');
                }
                if ($operator->isArchived()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Your account has been archived. Please contact support.',
                    ])->onlyInput('email');
                }
                if ($operator->status === 'pending') {
                    ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');
                    return redirect()->route('operator.pending');
                }
                if ($operator->status === 'rejected') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Your account has been rejected. Contact support for more information.',
                    ])->onlyInput('email');
                }
                if ($operator->status === 'inactive') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()->withErrors([
                        'email' => 'Your account has been deactivated. Please contact support.',
                    ])->onlyInput('email');
                }
            }

            // Unknown/legacy role (e.g. the old `driver` default): don't send the
            // user into the role-gated operator dashboard (which would 403) — log
            // them out and ask them to contact support.
            if (!$user->isOperator()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account role is not recognized. Please contact support.',
                ]);
            }

            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');
            return redirect()->route('operator.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        ActivityLogger::log('logout', (Auth::user()->name ?? 'Unknown') . " logged out", null, 'auth');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
