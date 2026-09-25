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
        $intended = '';
        $isConnect = false;

        if ($intended = trim((string) $request->query('intended'))) {
            $isConnect = true;
            $request->session()->put('google_connect_intended', $intended);
            $request->session()->put('google_connect_mode', true);
        }

        // Override Socialite's random state with our own payload so the
        // "where to send the passenger back" info survives the round-trip
        // even when a phone browser drops the session cookie. Socialite still
        // verifies it against the session when that is intact.
        $state = $this->encodeConnectState($intended, $isConnect);
        $request->session()->put('state', $state);

        $url = Socialite::driver('google')->redirect()->getTargetUrl();
        $url = preg_replace('/([?&])state=[^&]*/', '$1state=' . urlencode($state), $url);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $stateFromQuery = (string) $request->query('state');
        $meta = $this->decodeConnectState($stateFromQuery);

        $isConnect = $request->session()->pull('google_connect_mode');
        $intended = $request->session()->pull('google_connect_intended');

        // Session may have died on a phone while Google took over — fall back
        // to the state payload we embedded in the redirect URL.
        if ($meta) {
            if ($isConnect === null) {
                $isConnect = $meta['connect'];
            }
            if ($intended === null) {
                $intended = $meta['intended'];
            }
        }

        // Google bounces back without a code when the passenger cancels or
        // denies the consent prompt. Surface a clear message instead of a
        // generic failure, and keep them on the complaint form they were on.
        $oauthError = trim((string) $request->query('error'));
        if ($oauthError !== '') {
            $message = $oauthError === 'access_denied'
                ? 'Kinansela mo ang Google sign-in. Pwede mong subukan muli gamit ang form sa ibaba.'
                : 'Hindi natuloy ang Google sign-in. Pakisubukan muli.';
            return $this->connectFailure($request, $message, $isConnect, $intended);
        }

        // When the session survived, let Socialite validate our injected state
        // normally. When it did not (common on in-app browsers), skip the
        // session-backed state check — the code exchange itself is still
        // authenticated by the client secret + registered redirect URI.
        $sessionState = (string) $request->session()->get('state');
        $stateLost = $stateFromQuery !== ''
            && ($sessionState === '' || !hash_equals($sessionState, $stateFromQuery));

        try {
            $provider = Socialite::driver('google');
            $googleUser = $stateLost ? $provider->stateless()->user() : $provider->user();
        } catch (\Exception $e) {
            return $this->connectFailure($request, 'Unable to sign in with Google. Please try again.', $isConnect, $intended);
        }

        // Google's own email-verified flag must be true (account email confirmed by Google).
        // Default to false when the flag is absent, and use filter_var so the string
        // "false" is not treated as truthy (a naive (bool) cast would).
        $raw = (array) $googleUser->user;
        $emailVerified = array_key_exists('email_verified', $raw)
            ? filter_var($raw['email_verified'], FILTER_VALIDATE_BOOLEAN)
            : false;

        if (!$emailVerified) {
            return $this->connectFailure($request, 'Your Google account email is not verified. Please verify it with Google first, then try again.', $isConnect, $intended);
        }

        $email = strtolower(trim($googleUser->getEmail()));

        if ($email === '') {
            return $this->connectFailure($request, 'Your Google account has no email address.', $isConnect, $intended);
        }

        $user = User::where('email', $email)->first();

        // Passenger "Continue with Google" flow: a matching Google email is NOT
        // required, a brand-new passenger account is created on first connect.
        if ($isConnect) {
            if ($user && $user->role !== 'passenger') {
                return $this->connectFailure($request, 'This Google account belongs to a TriFair staff account. Please use the staff sign-in page instead.', $isConnect, $intended);
            }
            if ($user && !$user->is_active) {
                return $this->connectFailure($request, 'Your account is currently disabled. Please contact support.', $isConnect, $intended);
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
            return $this->connectFailure($request, 'Your account is currently disabled. Please contact support.', $isConnect, $intended);
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

    private function connectFailure(Request $request, string $message, $isConnect, ?string $intended)
    {
        if ($isConnect && $intended) {
            return redirect($this->validIntended($intended))->withErrors(['email' => $message]);
        }
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

    private function encodeConnectState(string $intended, bool $connect): string
    {
        $payload = [
            'i' => $intended,
            'c' => $connect,
        ];
        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }

    private function decodeConnectState(string $state): ?array
    {
        if ($state === '') {
            return null;
        }
        $json = base64_decode(strtr($state, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['c'])) {
            return null;
        }
        return [
            'connect' => (bool) $data['c'],
            'intended' => isset($data['i']) ? (string) $data['i'] : '',
        ];
    }
}
