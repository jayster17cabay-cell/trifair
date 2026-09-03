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

        $email = strtolower(trim($googleUser->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Your Google account has no verified email address.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !$user->isTfrbOfficerOrSuperadmin()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This Google account is not linked to a TriFair admin account. Please use your email and password to log in, or contact support.',
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

        ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return redirect()->route('tfrb-officer.dashboard');
    }
}