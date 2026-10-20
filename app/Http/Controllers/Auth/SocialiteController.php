<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Lifetime (seconds) of the signed OAuth state. A normal Google round-trip
     * completes well within this window; anything older is refused.
     */
    private const STATE_TTL = 600;

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

        // Carry the "where to send the passenger back" info in the OAuth state
        // so it survives even when a phone browser drops the session cookie.
        $state = $this->encodeConnectState($intended, $isConnect);

        // Socialite's redirect() stores its own random state in the session and
        // builds the authorize URL with it. We swap that value for our payload so
        // the URL and the session agree; otherwise the callback's hash_equals()
        // check would always fail and silently disable CSRF validation.
        //
        // prompt=select_account forces Google's account chooser open on every
        // tap. A passenger who complains from two or three Google accounts in
        // the same browser must pick the one they want — otherwise Google
        // silently continues with whatever it considers the primary account and
        // they land on an empty (or wrong) complaint list. It is set through
        // with() rather than glued onto the URL, so a `prompt` entry can never
        // end up duplicated with Google honouring the wrong one.
        $url = Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect()
            ->getTargetUrl();
        $url = preg_replace('/([?&])state=[^&]*/', '$1state=' . urlencode($state), $url);

        $request->session()->put('state', $state);

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
                ? 'You cancelled the Google sign-in. You can try again using the form below.'
                : 'The Google sign-in did not complete. Please try again.';
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

        // Complaints filed before signing in only carry the typed email. Now
        // that Google has proved ownership of that address, hand those tickets
        // over to the account so they show up under "My Complaints" instead of
        // staying invisible behind a passenger_user_id of null.
        $claimed = $user->isPassenger() ? $this->claimGuestComplaints($user) : 0;
        if ($claimed > 0) {
            ActivityLogger::log('login', "{$user->email} claimed {$claimed} guest complaint(s) after signing in with Google", $user, 'auth');
        }

        if ($isConnect) {
            ActivityLogger::log('login', "{$user->name} ({$user->email}) connected via Google as passenger", null, 'auth');

            // Land the passenger back on the exact screen they left and say
            // what to do next: the complaint form keeps its draft, so the only
            // thing left is attaching proof and submitting.
            $target = $this->validIntended($intended);
            $status = str_starts_with(ltrim($target, '/'), 'rate/')
                ? "You are now connected as {$user->email}. Continue your complaint — add proof, then submit the rating."
                : "You are now signed in as {$user->email}.";

            return redirect()->to($target)->with('status', $status);
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
            if ($user->presidentTodas()->isEmpty()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is not assigned to a TODA. Please contact support.',
                ]);
            }

            // A president is an operator too: a lapsed or archived franchise
            // keeps them out until an admin renews it. A suspension never
            // blocks sign-in — the TODA console reports it instead.
            $operator = $user->operator;
            if ($operator) {
                $operator->syncFranchiseStatus();
                $operator->syncSuspensionStatus();

                if ($operator->isArchived()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->withErrors([
                        'email' => 'Your account has been archived. Please contact support.',
                    ]);
                }

                if ($operator->status === 'inactive') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->withErrors([
                        'email' => $operator->hasExpiredFranchise()
                            ? 'Your franchise has expired. Please contact the TFRB Office to renew it.'
                            : 'Your account has been deactivated. Please contact support.',
                    ]);
                }
            }

            ActivityLogger::log('login', "{$user->name} ({$user->email}) logged in via Google", null, 'auth');
            return redirect()->route('dashboard');
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

            // A lapsed franchise keeps the operator out until it is renewed.
            $operator->syncFranchiseStatus();
            // A lapsed TODA suspension is lifted automatically.
            $operator->syncSuspensionStatus();

            if ($operator->isSuspended()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $suspension = $operator->currentSuspension;
                $until = $suspension ? $suspension->suspended_until : null;
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account is currently suspended by your TODA. '
                        . ($until ? 'The suspension ends on ' . $until->format('M d, Y') . '. ' : '')
                        . 'Please contact your TODA president or the TFRB Office.',
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
                    'email' => $operator->hasExpiredFranchise()
                        ? 'Your franchise has expired. Please contact the TFRB Office to renew it.'
                        : 'Your account has been deactivated. Please contact support.',
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
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'provider' => 'google',
            'provider_id' => (string) $googleUser->getId(),
        ]);
        $user->forceFill(['role' => 'passenger', 'is_active' => true])->save();

        ActivityLogger::log('register', "Passenger account created for {$email} via Google", $user, 'auth');

        return $user;
    }

    /**
     * Attach guest-filed complaints that carry this address to the account, so
     * a passenger who typed their email before signing in sees them as soon as
     * they pick that Google account. Comparison is case-insensitive because the
     * typed field is free text while Google always returns a canonical form.
     */
    private function claimGuestComplaints(User $user): int
    {
        $email = strtolower(trim((string) $user->email));

        if ($email === '') {
            return 0;
        }

        return Rating::query()
            ->whereNull('passenger_user_id')
            ->whereRaw('LOWER(passenger_email) = ?', [$email])
            ->update(['passenger_user_id' => $user->id]);
    }

    private function connectFailure(Request $request, string $message, $isConnect, ?string $intended)
    {
        if ($isConnect && $intended) {
            $target = $this->validIntended($intended);

            // An auth-only page cannot render for a signed-out visitor: the
            // guard would bounce them to /login and spend the flash message on
            // the way there, so the explanation would never be read. Go
            // straight to the screen that can show it instead. The complaint
            // form is public, so it keeps the passenger exactly where they were.
            $needsAuth = str_starts_with(ltrim($target, '/'), 'passenger/');
            if (Auth::check() || !$needsAuth) {
                return redirect($target)->withErrors(['email' => $message]);
            }
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function validIntended(?string $intended): string
    {
        $intended = trim((string) $intended);
        // A leading "//" or any backslash (browsers normalise "\" to "/")
        // would turn into a protocol-relative off-site redirect. Reject both,
        // plus any absolute URL, so Google sign-in can only return to our own
        // pages.
        if (
            $intended === ''
            || str_starts_with($intended, '//')
            || str_contains($intended, '://')
            || str_contains($intended, '\\')
        ) {
            return '/';
        }
        return '/' . ltrim($intended, '/');
    }

    private function encodeConnectState(string $intended, bool $connect): string
    {
        $payload = [
            'i' => $intended,
            'c' => $connect,
            'n' => \Illuminate\Support\Str::random(40),
            'e' => time() + self::STATE_TTL,
        ];
        // Bind the payload to the app key so a caller cannot hand-craft a state
        // (the stateless fallback below trusts this payload when the session is
        // gone, so an unsigned state would be forgeable).
        $payload['s'] = $this->signState($payload);

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

        // Refuse forged or expired states. Without this check an attacker could
        // craft a plain-base64 state to drive the "connect" fallback when the
        // session is missing.
        if (!isset($data['s'], $data['e']) || !is_string($data['s'])) {
            return null;
        }
        if (!hash_equals($this->signState($data), $data['s'])) {
            return null;
        }
        if ((int) $data['e'] < time()) {
            return null;
        }

        return [
            'connect' => (bool) $data['c'],
            'intended' => isset($data['i']) ? (string) $data['i'] : '',
        ];
    }

    /**
     * Deterministic HMAC over the state fields, keyed by the application key.
     */
    private function signState(array $payload): string
    {
        $canonical = implode('|', [
            (string) ($payload['i'] ?? ''),
            !empty($payload['c']) ? '1' : '0',
            (string) ($payload['n'] ?? ''),
            (string) ($payload['e'] ?? ''),
        ]);
        return hash_hmac('sha256', $canonical, (string) config('app.key'));
    }
}
