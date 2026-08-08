@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="tw-auth-split">
    {{-- Brand panel --}}
    <div class="tw-auth-brand">
        <div class="pointer-events-none absolute inset-0 lp-bg-grid"></div>
        <div class="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(245,184,0,0.12)_0%,transparent_65%)]"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(46,125,209,0.18)_0%,transparent_65%)]"></div>

        <div class="relative z-10 mx-auto w-full max-w-md">
            <a href="/" class="mb-12 inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gold text-xl text-navy-800 shadow-goldlift">
                    <i class="bi bi-bicycle"></i>
                </span>
                <span class="text-2xl font-black tracking-tight text-white">Tri<span class="text-gold">Fair</span></span>
            </a>

            <h1 class="mb-4 text-4xl font-black leading-[1.12] tracking-tight text-white xl:text-[2.6rem]">
                Transparent rides,<br>
                <span class="text-gold">accountable operators.</span>
            </h1>
            <p class="mb-10 max-w-sm text-[0.95rem] leading-relaxed text-white/60">
                The community rating system for tricycle operators in Solano, Nueva Vizcaya. Every ride counted, every feedback heard.
            </p>

            <ul class="space-y-5">
                <li class="flex items-start gap-3.5">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gold/15 text-gold"><i class="bi bi-qr-code-scan"></i></span>
                    <div>
                        <div class="text-sm font-bold text-white">Scan to rate</div>
                        <p class="text-sm text-white/50">Passengers rate any ride in seconds — no app or account needed.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3.5">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gold/15 text-gold"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <div class="text-sm font-bold text-white">Verified feedback</div>
                        <p class="text-sm text-white/50">Low ratings can include photo or video proof for fair review.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3.5">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gold/15 text-gold"><i class="bi bi-bar-chart-line"></i></span>
                    <div>
                        <div class="text-sm font-bold text-white">Data-driven oversight</div>
                        <p class="text-sm text-white/50">TODA officers get real-time reports to raise service quality.</p>
                    </div>
                </li>
            </ul>

            <div class="mt-12 flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                <div class="flex -space-x-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-navy-800 bg-gradient-to-br from-gold to-gold-dark text-xs font-extrabold text-navy-800">SM</span>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-navy-800 bg-gradient-to-br from-navy-500 to-navy-600 text-xs font-extrabold text-white">JV</span>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-navy-800 bg-gradient-to-br from-emerald-500 to-emerald-600 text-xs font-extrabold text-white">RC</span>
                </div>
                <div class="text-xs">
                    <div class="font-bold text-white">Trusted by Solano commuters</div>
                    <div class="mt-0.5 text-gold"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> <span class="font-medium text-white/50">4.9 average rating</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="tw-auth-form-panel">
        <div class="mx-auto w-full max-w-md">
            {{-- Mobile brand --}}
            <div class="mb-8 flex flex-col items-center text-center lg:hidden">
                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold text-2xl text-navy-800 shadow-goldlift">
                    <i class="bi bi-bicycle"></i>
                </span>
                <span class="text-2xl font-black tracking-tight text-navy-700">Tri<span class="text-gold">Fair</span></span>
                <p class="mt-1 text-xs font-medium uppercase tracking-widest text-slate-400">Bayan ng Solano</p>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_24px_60px_rgba(15,42,74,0.14)] sm:p-9">
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.querySelector(btn.getAttribute('data-pw-toggle'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    });
</script>
@endsection
