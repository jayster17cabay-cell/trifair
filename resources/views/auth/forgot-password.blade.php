@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h4 class="tw-auth-title">Forgot Password</h4>
        <p class="tw-auth-sub">Enter your email and we'll send you a reset link</p>

        @if (session('status'))
            <div class="tw-alert tw-alert-success mt-4">
                <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="tw-alert tw-alert-danger mt-4">
                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                <span>{{ $errors->first('email') ?: $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6">
            @csrf

            <div class="tw-auth-field">
                <label for="email" class="tw-label">Email Address</label>
                <div class="tw-input-group">
                    <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                    <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                </div>
                @error('email')
                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                <i class="bi bi-envelope-arrow-up"></i> Send Reset Link
            </button>
        </form>

        <div class="tw-auth-foot">
            <p class="mb-2 text-sm text-slate-500">
                Remembered your password?
                <a href="{{ route('login') }}" class="font-semibold text-navy-600 hover:underline">Log In</a>
            </p>
            <p class="mb-0 text-sm">
                <a href="/" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-arrow-left me-1"></i> Back to Home
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
