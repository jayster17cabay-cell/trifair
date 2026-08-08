@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="tw-auth-split">
    @include('auth.partials.brand-panel')

    {{-- Form panel --}}
    <div class="tw-auth-form-panel">
        <div class="mx-auto w-full max-w-md">
            @include('auth.partials.mobile-brand')

            <div class="rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_24px_60px_rgba(15,42,74,0.14)] sm:p-9">
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-key"></i> Account Recovery
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Forgot Password</h4>
                    <p class="mt-1 text-sm text-slate-500">Enter your email and we'll send you a reset link.</p>
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

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Remembered your password?
                    <a href="{{ route('login') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Log In</a>
                </div>
            </div>

            <p class="mt-6 text-center text-sm">
                <a href="/" class="text-slate-400 transition hover:text-slate-600">
                    <i class="bi bi-arrow-left mr-1"></i> Back to Home
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
