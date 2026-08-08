@extends('layouts.app')

@section('title', 'Login')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6">
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Welcome back</h4>
                    <p class="mt-1 text-sm text-slate-500">Log in to your TriFair account to continue.</p>
                </div>

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

                <form method="POST" action="{{ route('login') }}" class="mt-6">
                    @csrf

                    <div class="tw-auth-field">
                        <label for="email" class="tw-label">Email Address</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
                        </div>
                        @error('email')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <div class="mb-1 flex items-center justify-between">
                            <label for="password" class="tw-label mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Forgot Password?</a>
                        </div>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required placeholder="Enter your password">
                            <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="mb-6 flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" class="tw-check" id="remember" name="remember">
                        Remember Me
                    </label>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Log In
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Sign Up</a>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
