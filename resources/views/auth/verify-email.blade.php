@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-envelope-check"></i>
        </div>
        <h4 class="tw-auth-title">Verify Your Email</h4>
        <p class="tw-auth-sub">A verification link has been sent to your email address</p>

        @if (session('message'))
            <div class="tw-alert tw-alert-success mt-4">
                <i class="bi bi-check-circle-fill mt-0.5"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        <div class="mt-8 flex flex-col items-center">
            <div class="mb-4 text-5xl text-navy-600">
                <i class="bi bi-envelope-open"></i>
            </div>

            <p class="mb-4 text-sm text-slate-500">
                Please check your email inbox and click the verification link to activate your account.
            </p>

            <p class="mb-6 text-sm text-slate-500">
                If you did not receive the email, click the button below to resend.
            </p>

            <form method="POST" action="{{ route('verification.resend') }}" class="w-full">
                @csrf
                <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                    <i class="bi bi-arrow-repeat"></i> Resend Verification Link
                </button>
            </form>

            <div class="tw-auth-foot w-full">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="tw-btn tw-btn-ghost text-sm">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
