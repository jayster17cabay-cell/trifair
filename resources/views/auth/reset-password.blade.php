@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-key"></i>
        </div>
        <h4 class="tw-auth-title">Reset Password</h4>
        <p class="tw-auth-sub">Choose a new password for your account</p>

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
                </div>
            </div>

            <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                <i class="bi bi-check-lg"></i> Reset Password
            </button>
        </form>

        <div class="tw-auth-foot">
            <p class="mb-0 text-sm">
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-arrow-left me-1"></i> Back to Login
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
