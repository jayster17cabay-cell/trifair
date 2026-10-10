<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetOtpController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a one-time code to the account email. The response is intentionally
     * identical whether or not the email exists, so the endpoint cannot be used
     * to enumerate registered accounts.
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->input('email')));

        PasswordResetCode::where('email', $email)->delete();

        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            PasswordResetCode::create([
                'email' => $email,
                'code' => Hash::make($otp),
                'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetOtp($user, $otp));
            } catch (\Throwable $e) {
                Log::error('Forgot-password OTP email failed: ' . get_class($e) . ': ' . $e->getMessage());
            }
        }

        return redirect()->route('password.otp', ['email' => $email])
            ->with('reset_email', $email)
            ->with('status', 'If an account exists with that email, a one-time code has been sent. Check your inbox and spam folder.');
    }

    public function showVerifyForm(Request $request)
    {
        $email = session('reset_email') ?: (string) $request->query('email', '');

        return view('auth.reset-with-otp', compact('email'));
    }

    public function verifyAndReset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim($data['email']));

        $code = PasswordResetCode::where('email', $email)->latest('id')->first();

        $fail = function ($message) use ($request) {
            return back()->withInput($request->only('email', 'otp'))
                ->withErrors(['otp' => $message]);
        };

        if (!$code || $code->used_at !== null) {
            return $fail('This code is invalid or has already been used.');
        }

        if ($code->expires_at->isPast()) {
            $code->delete();
            return $fail('This code has expired. Please request a new code.');
        }

        if ($code->attempts >= self::MAX_ATTEMPTS) {
            $code->delete();
            return $fail('Too many failed attempts. Please request a new code.');
        }

        if (!Hash::check($data['otp'], $code->code)) {
            $code->increment('attempts');
            return $fail('The code you entered is incorrect.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $code->delete();
            return $fail('No account was found for that email.');
        }

        $code->update(['used_at' => now()]);
        PasswordResetCode::where('email', $email)->where('id', '!=', $code->id)->delete();

        $user->password = Hash::make($data['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        event(new PasswordReset($user));

        ActivityLogger::log('password_reset', "{$user->name} reset their password via OTP", $user, 'auth', $request);

        $request->session()->forget('reset_email');

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in with your new password.');
    }
}