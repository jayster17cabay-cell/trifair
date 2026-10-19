@extends('layouts.app')

@section('title', 'Login')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6">
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Welcome</h4>
                    <p class="mt-1 text-sm text-slate-500">Log in to your TriFair account to continue.</p>
                    <div class="mt-4 h-1 w-16 rounded-full bg-gradient-to-r from-gold to-navy-600"></div>
                </div>

                @if (session('status'))
                    <div class="tw-alert tw-alert-success mb-4">
                        <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mb-4">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <span>{{ $errors->first('email') ?: $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="tw-auth-field">
                        <label for="email" class="tw-label">Email Address</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="tw-auth-field">
                        <div class="mb-1 flex items-center justify-between">
                            <label for="password" class="tw-label mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-navy-600 transition hover:text-gold-dark hover:underline" title="Reset your password">
                                <i class="bi bi-unlock"></i> Forgot Password?
                            </a>
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

                @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                    <div class="mt-7">
                        <div class="flex items-center gap-3">
                            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-slate-200"></span>
                            <span class="text-[0.7rem] font-extrabold uppercase tracking-widest text-slate-400">Para sa Pasahero</span>
                            <span class="h-px flex-1 bg-gradient-to-l from-transparent to-slate-200"></span>
                        </div>

                        {{-- Connect mode: a first-time passenger is created on the
                             spot and lands straight on their complaints, where they
                             can follow each one up. Staff emails are refused. --}}
                        <a href="{{ route('login.google', ['intended' => 'passenger/dashboard']) }}"
                           class="group mt-4 grid w-full grid-cols-[1.25rem_1fr_1.25rem] items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-soft transition duration-200 hover:-translate-y-0.5 hover:border-navy-500 hover:shadow-lift focus:outline-none focus:ring-4 focus:ring-navy-600/15 active:translate-y-0"
                           id="googleLoginBtn">
                            @include('partials.google-g', ['class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110'])
                            <span class="text-center text-sm font-extrabold text-slate-700 group-hover:text-navy-700" data-google-label>Continue with Google</span>
                            <i class="bi bi-arrow-right-circle text-navy-500 transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true"></i>
                        </a>

                        <p class="mt-3 text-center text-[0.7rem] leading-relaxed text-slate-400">
                            Walang bagong password na kailangan — sign in lang gamit ang Google.
                        </p>

                        <script>
                            (function () {
                                var btn = document.getElementById('googleLoginBtn');
                                if (!btn) return;
                                btn.addEventListener('click', function () {
                                    if (btn.getAttribute('aria-busy') === 'true') return;
                                    btn.setAttribute('aria-busy', 'true');
                                    var label = btn.querySelector('[data-google-label]');
                                    if (label) label.textContent = 'Binubuksan ang Google account chooser...';
                                });
                            })();
                        </script>
                    </div>
                @endif

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Sign Up</a>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
