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
    /**
     * Remember where the passenger wants to return after linking Google, so the
     * callback knows it is a passenger "connect" flow (not the staff login) and
     * can land them back at the complaint form they were filling out.
     */
    public function redirect(Request $request)
    {
        if ($intended = trim((string) $request->query('intended'))) {
            $request->session()->put('google_connect_intended', $intended);
            $request->session()->put('google_connect_mode', true);
        }

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
            return $this->backWithError($request, 'Your Google account email is not verified. Please verify it with Google first, then try again.');
        }

        $email = strtolower(trim($googleUser->getEmail()));

        if ($email === '') {
            return $this->backWithError($request, 'Your Google account has no email address.');
        }

        $isConnect = $request->session()->pull('google_connect_mode');
        $intended = $request->session()->pull('google_connect_intended');

        $user = User::where('email', $email)->first();

        // Passenger "Continue with Google" flow: a matching Google email is NOT
        // required, a brand-new passenger account is created on first connect.
        if ($isConnect) {
            if ($user && $user->role !== 'passenger') {
                return $this->backWithError($request, 'This Google account belongs to a TriFair staff account. Please use the staff sign-in page instead.');
            }
            if ($user && !$user->is_active) {
                return $this->backWithError($request, 'Your account is currently disabled. Please contact support.');
            }
            if (!$user) {
                $user = $this->createPassengerAccount($email, $googleUser);
            }
        } else {
            // Only allow accounts that already exist in TriFair (original behavior).
            if (!$user) {
                return redirect()->route('login')->withErrors([
                    'email' => 'This Google account is not linked to any TriFair account. Please log in with your email and password.',
                ]);
            }
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

        if ($isConnect) {
            ActivityLogger::log('login', "{$user->name} ({$user->email}) connected via Google as passenger", null, 'auth');
            return redirect()->to($this->validIntended($intended));
        }

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

        if ($user->isPassenger()) {
            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('passenger.dashboard');
        }

        return redirect()->route('login');
    }

    private function createPassengerAccount(string $email, $googleUser): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $googleUser->getName() ?: ucfirst(strstr($email, '@', true) ?: 'Passenger'),
            'email' => $email,
            'password' => \Illuminate\Support\Str::random(32),
            'email_verified_at' => now(),
            'provider' => 'google',
            'provider_id' => (string) $googleUser->getId(),
        ]);
        $user->forceFill(['role' => 'passenger', 'is_active' => true])->save();

        ActivityLogger::log('register', "Passenger account created for {$email} via Google", $user, 'auth');

        return $user;
    }

    private function backWithError(Request $request, string $message)
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function validIntended(?string $intended): string
    {
        $intended = trim((string) $intended);
        if ($intended === '' || str_starts_with($intended, '//') || str_contains($intended, '://')) {
            return '/';
        }
        return '/' . ltrim($intended, '/');
    }
}
