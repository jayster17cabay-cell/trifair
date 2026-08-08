@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-envelope-check"></i> Email Verification
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Verify Your Email</h4>
                    <p class="mt-1 text-sm text-slate-500">A verification link has been sent to your email address.</p>
                </div>

                @if (session('message'))
                    <div class="tw-alert tw-alert-success mt-4">
                        <i class="bi bi-check-circle-fill mt-0.5"></i>
                        <span>{{ session('message') }}</span>
                    </div>
                @endif

                <div class="mt-8 flex flex-col items-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gold/10 text-4xl text-gold-dark">
                        <i class="bi bi-envelope-open"></i>
                    </div>

                    <p class="mb-4 text-center text-sm text-slate-500">
                        Please check your email inbox and click the verification link to activate your account.
                    </p>

                    <p class="mb-6 text-center text-sm text-slate-500">
                        If you did not receive the email, click the button below to resend.
                    </p>

                    <form method="POST" action="{{ route('verification.resend') }}" class="w-full">
                        @csrf
                        <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                            <i class="bi bi-arrow-repeat"></i> Resend Verification Link
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4 w-full">
                        @csrf
                        <button type="submit" class="tw-btn tw-btn-ghost w-full text-sm">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </button>
                    </form>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
