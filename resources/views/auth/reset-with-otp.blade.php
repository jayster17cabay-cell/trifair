@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6 text-center">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-shield-check"></i> Reset Password
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Enter Recovery Code</h4>
                    <p class="mt-1.5 text-sm text-slate-500">Enter the 6-digit code we emailed you, then choose a new password.</p>
                </div>

                <div class="mb-5 h-1 w-16 rounded-full bg-gradient-to-r from-gold to-navy-600"></div>

                @if (session('status'))
                    <div class="tw-alert tw-alert-success mt-4">
                        <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mt-4">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <span>{{ $errors->first('otp') ?: $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.otp.verify') }}" class="mt-6">
                    @csrf

                    <div class="tw-auth-field">
                        <label for="email" class="tw-label">Account Email</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email', $email) }}" {{ $email !== '' ? 'readonly' : '' }} required autofocus placeholder="you@example.com">
                        </div>
                        @error('email')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="otp" class="tw-label">One-Time Code</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-asterisk"></i></span>
                            <input id="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="tw-input @error('otp') is-invalid @enderror" name="otp" value="{{ old('otp') }}" required placeholder="000000" style="text-align:center;letter-spacing:0.5em;font-weight:700;">
                        </div>
                        @error('otp')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="password" class="tw-label">New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required placeholder="At least 8 characters">
                            <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="password_confirmation" class="tw-label">Confirm New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                            <input id="password_confirmation" type="password" class="tw-input @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required placeholder="Repeat your new password">
                            <button type="button" data-pw-toggle="#password_confirmation" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-shield-lock"></i> Reset Password
                    </button>
                </form>

                <div class="mt-7 space-y-2 text-center text-sm text-slate-500">
                    <p>
                        Didn't get the code?
                        <a href="{{ route('password.request') }}{{ $email !== '' ? '?email=' . urlencode($email) : '' }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Resend Code</a>
                    </p>
                    <p>
                        Remembered your password?
                        <a href="{{ route('login') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Log In</a>
                    </p>
                </div>
@include('auth.partials.auth-shell-close')
@endsection