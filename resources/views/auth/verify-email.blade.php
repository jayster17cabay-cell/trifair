@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-envelope-check"></i> Email Verification
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Verify Your Email</h4>
                    <p class="mt-1 text-sm text-slate-500">A verification code has been sent to your email address.</p>
                </div>

                @if (session('message'))
                    <div class="tw-alert tw-alert-success mt-4">
                        <i class="bi bi-check-circle-fill mt-0.5"></i>
                        <span>{{ session('message') }}</span>
                    </div>
                @endif

                @if ($errors->has('code'))
                    <div class="tw-alert tw-alert-danger mt-4">
                        <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                        <span>{{ $errors->first('code') }}</span>
                    </div>
                @endif

                <div class="mt-8 flex flex-col items-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gold/10 text-4xl text-gold-dark">
                        <i class="bi bi-envelope-open"></i>
                    </div>

                    <p class="mb-4 text-center text-sm text-slate-500">
                        Check your email inbox for a 6-digit verification code and enter it below.
                    </p>

                    <form method="POST" action="{{ route('verification.code') }}" class="w-full">
                        @csrf
                        <div class="mb-4">
                            <input type="text" name="code" id="code" maxlength="6" pattern="[0-9]{6}"
                                   inputmode="numeric" autocomplete="one-time-code"
                                   class="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-2xl font-bold tracking-widest text-navy-700 focus:border-gold focus:outline-none focus:ring-2 focus:ring-gold/30"
                                   placeholder="000000" required autofocus>
                        </div>
                        <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                            <i class="bi bi-check-lg"></i> Verify Email
                        </button>
                    </form>

                    <p class="mt-4 text-center text-xs text-slate-400">
                        Can't find the email? Check your Spam or Junk folder, or click below to resend.
                    </p>

                    <form method="POST" action="{{ route('verification.resend') }}" class="w-full mt-2">
                        @csrf
                        <button type="submit" class="tw-btn tw-btn-ghost w-full text-sm">
                            <i class="bi bi-arrow-repeat"></i> Resend Code
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