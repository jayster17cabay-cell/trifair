@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
@include('auth.partials.auth-shell-open')
                <div class="mb-6 text-center">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-shield-lock"></i> First Sign-In
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Choose a New Password</h4>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Your account was created with a temporary password. For your security, set your own password before continuing.
                    </p>
                </div>

                <div class="mb-5 h-1 w-16 rounded-full bg-gradient-to-r from-gold to-navy-600"></div>

                @if (session('status'))
                    <div class="tw-alert tw-alert-success mt-4">
                        <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mt-4">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.force.update') }}" class="mt-6">
                    @csrf
                    @method('PUT')

                    <div class="tw-auth-field">
                        <label for="new_password" class="tw-label">New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                            <input id="new_password" type="password" class="tw-input @error('new_password') is-invalid @enderror"
                                   name="new_password" required minlength="8" autocomplete="new-password" autofocus
                                   placeholder="At least 8 characters">
                        </div>
                        @error('new_password')
                            <span class="tw-error-text" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-auth-field">
                        <label for="new_password_confirmation" class="tw-label">Confirm New Password</label>
                        <div class="tw-input-group">
                            <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                            <input id="new_password_confirmation" type="password" class="tw-input"
                                   name="new_password_confirmation" required minlength="8" autocomplete="new-password"
                                   placeholder="Re-enter your new password">
                        </div>
                    </div>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-check-lg"></i> Save New Password
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Signed in as <strong class="text-slate-700">{{ $user->email }}</strong>.
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">
                            Log Out
                        </button>
                    </form>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
