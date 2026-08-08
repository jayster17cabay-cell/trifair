@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-shield-lock"></i> Account Security
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Reset Password</h4>
                    <p class="mt-1 text-sm text-slate-500">Choose a new password for your account.</p>
                </div>

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mt-4">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="tw-auth-field">
                        <label for="email" class="tw-label">Email Address</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autofocus>
                        </div>
                        @error('email')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="password" class="tw-label">New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="At least 8 characters">
                            <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="password-confirm" class="tw-label">Confirm New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                            <input id="password-confirm" type="password" class="tw-input" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter new password">
                            <button type="button" data-pw-toggle="#password-confirm" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-check-lg"></i> Reset Password
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm">
                    <a href="{{ route('login') }}" class="text-slate-400 transition hover:text-slate-600">
                        <i class="bi bi-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
