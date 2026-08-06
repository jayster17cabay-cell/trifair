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

            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in", null, 'auth');

            if ($user->isSuperadmin()) {
                return redirect()->route('superadmin.dashboard');
            }

            if ($user->isTfrbOfficer()) {
                return redirect()->route('tfrb-officer.dashboard');
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
                if ($operator->status === 'pending') {
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
