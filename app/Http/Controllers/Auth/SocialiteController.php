<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to sign in with Google. Please try again.',
            ]);
        }

        // Google's own email-verified flag must be true (account email confirmed by Google).
        // Default to false when the flag is absent, and use filter_var so the string
        // "false" is not treated as truthy (a naive (bool) cast would).
        $raw = (array) $googleUser->user;
        $emailVerified = array_key_exists('email_verified', $raw)
            ? filter_var($raw['email_verified'], FILTER_VALIDATE_BOOLEAN)
            : false;

        if (!$emailVerified) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your Google account email is not verified. Please verify it with Google first, then try again.',
            ]);
        }

        $email = strtolower(trim($googleUser->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Your Google account has no email address.',
            ]);
        }

        $user = User::where('email', $email)->first();

        // Only allow accounts that already exist in TriFair (superadmin, tfrb_officer, or operator).
        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => 'This Google account is not linked to any TriFair account. Please log in with your email and password.',
            ]);
        }

        if (!$user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is currently disabled. Please contact support.',
            ]);
        }

        $user->forceFill([
            'provider' => 'google',
            'provider_id' => (string) $googleUser->getId(),
        ])->save();

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        Auth::login($user, true);

        $request->session()->regenerate();

        if ($user->isSuperadmin()) {
            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->isTfrbOfficer()) {
            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('tfrb-officer.dashboard');
        }

        if ($user->isOperatorPresident()) {
            if (!$user->presidentToda()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is not assigned to a TODA. Please contact support.',
                ]);
            }
            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('president.dashboard');
        }

        if ($user->isOperator()) {
            $operator = $user->operator;

            if (!$operator) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is incomplete. Please contact support.',
                ]);
            }

            if ($operator->isArchived()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been archived. Please contact support.',
                ]);
            }

            if ($operator->status === 'pending') {
                ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
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

            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('operator.dashboard');
        }

        return redirect()->route('login');
    }
}
