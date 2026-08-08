@extends('layouts.app')

@section('title', 'Register')

@section('content')
@include('auth.partials.auth-shell-open', ['authWidth' => 'max-w-lg'])
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-person-plus"></i> Operator Registration
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Create Account</h4>
                    <p class="mt-1 text-sm text-slate-500">Register as a tricycle operator and start tracking your ratings.</p>
                </div>

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mt-4">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mt-1 list-inside list-disc space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="mt-6">
                    @csrf

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="tw-auth-field">
                            <label for="name" class="tw-label">Full Name</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                <input id="name" type="text" class="tw-input @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus placeholder="Enter your full name">
                            </div>
                            @error('name')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="email" class="tw-label">Email Address</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                                <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
                            </div>
                            @error('email')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="contact_number" class="tw-label">Contact Number</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-phone"></i></span>
                                <input id="contact_number" type="text" class="tw-input @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 0917 123 4567">
                            </div>
                            @error('contact_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="license_number" class="tw-label">License Number</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-card-text"></i></span>
                                <input id="license_number" type="text" class="tw-input @error('license_number') is-invalid @enderror" name="license_number" value="{{ old('license_number') }}" placeholder="Enter your license number">
                            </div>
                            @error('license_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="plate_number" class="tw-label">Plate Number</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-upc-scan"></i></span>
                                <input id="plate_number" type="text" class="tw-input @error('plate_number') is-invalid @enderror" name="plate_number" value="{{ old('plate_number') }}" placeholder="e.g. AB-1234">
                            </div>
                            @error('plate_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="body_number" class="tw-label">Body Number</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-123"></i></span>
                                <input id="body_number" type="text" class="tw-input @error('body_number') is-invalid @enderror" name="body_number" value="{{ old('body_number') }}" placeholder="Enter your body number">
                            </div>
                            @error('body_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field sm:col-span-2">
                            <label for="toda_id" class="tw-label">TODA</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-people"></i></span>
                                <select id="toda_id" name="toda_id" class="tw-select border-0 focus:ring-0 @error('toda_id') is-invalid @enderror">
                                    <option value="">None / Not yet a member</option>
                                    @foreach ($todas as $toda)
                                        <option value="{{ $toda->id }}" {{ old('toda_id') == $toda->id ? 'selected' : '' }}>
                                            {{ $toda->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('toda_id')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="password" class="tw-label">Password</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                                <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required placeholder="At least 8 characters">
                                <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="password-confirm" class="tw-label">Confirm Password</label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                                <input id="password-confirm" type="password" class="tw-input" name="password_confirmation" required placeholder="Confirm your password">
                                <button type="button" data-pw-toggle="#password-confirm" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Log In</a>
                </div>
            </div>
@include('auth.partials.auth-shell-close')
@endsection
