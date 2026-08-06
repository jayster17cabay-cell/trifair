@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <h4 class="tw-auth-title">Welcome to TriFair</h4>
        <p class="tw-auth-sub">Log in to manage your account</p>

        @if ($errors->any())
            <div class="tw-alert tw-alert-danger mt-4">
                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                <span>{{ $errors->first('email') ?: $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6">
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

            <div class="tw-auth-field">
                <label for="password" class="tw-label">Password</label>
                <div class="tw-input-group">
                    <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                    <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required placeholder="Enter your password">
                </div>
                @error('password')
                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <label class="mb-5 flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                <input type="checkbox" class="tw-check" id="remember" name="remember">
                Remember Me
            </label>

            <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Log In
            </button>
        </form>

        <div class="tw-auth-foot">
            <p class="mb-2 text-sm text-slate-500">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-semibold text-navy-600 hover:underline">Sign Up</a>
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
