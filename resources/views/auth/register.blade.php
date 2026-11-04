@extends('layouts.app')

@section('title', 'Register')

@section('content')
@include('auth.partials.auth-shell-open', ['authWidth' => 'max-w-2xl'])
                <div class="mb-6">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-person-plus"></i> Operator Registration
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Create Account</h4>
                    <p class="mt-1 text-sm text-slate-500">Register as a motorcycle operator and start tracking your ratings.</p>
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

                    <h5 class="tw-section-title">
                        <i class="bi bi-person-vcard"></i> Personal Information
                    </h5>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="tw-auth-field">
                            <label for="last_name" class="tw-label">Last Name <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                <input id="last_name" type="text" class="tw-input @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autofocus autocomplete="family-name" placeholder="e.g. Dela Cruz">
                            </div>
                            @error('last_name')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="first_name" class="tw-label">First Name <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                <input id="first_name" type="text" class="tw-input @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" placeholder="e.g. Juan">
                            </div>
                            @error('first_name')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="middle_name" class="tw-label">Middle Name <span class="font-normal text-slate-400">(Optional)</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                <input id="middle_name" type="text" class="tw-input @error('middle_name') is-invalid @enderror" name="middle_name" value="{{ old('middle_name') }}" autocomplete="additional-name" placeholder="e.g. Santos">
                            </div>
                            @error('middle_name')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="email" class="tw-label">Email Address <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                                <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">
                            </div>
                            @error('email')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="contact_number" class="tw-label">Contact Number <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-phone"></i></span>
                                <input id="contact_number" type="tel" inputmode="tel" class="tw-input @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" required autocomplete="tel" placeholder="e.g. 0917 123 4567">
                            </div>
                            @error('contact_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <h5 class="tw-section-title mt-2">
                        <i class="bi bi-bicycle"></i> Motorcycle &amp; TODA
                    </h5>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="tw-auth-field">
                            <label for="plate_number" class="tw-label">Plate Number <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-upc-scan"></i></span>
                                <input id="plate_number" type="text" class="tw-input @error('plate_number') is-invalid @enderror" name="plate_number" value="{{ old('plate_number') }}" required placeholder="e.g. AB-1234">
                            </div>
                            @error('plate_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="body_number" class="tw-label">Body Number <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-123"></i></span>
                                <input id="body_number" type="text" class="tw-input @error('body_number') is-invalid @enderror" name="body_number" value="{{ old('body_number') }}" required placeholder="Enter your body number">
                            </div>
                            @error('body_number')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="motorcycle_model" class="tw-label">Motorcycle Model <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-bicycle"></i></span>
                                <input id="motorcycle_model" type="text" class="tw-input @error('motorcycle_model') is-invalid @enderror" name="motorcycle_model" value="{{ old('motorcycle_model') }}" required placeholder="e.g. Honda Wave 125">
                            </div>
                            @error('motorcycle_model')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="toda_id" class="tw-label">TODA <span class="font-normal text-slate-400">(Optional)</span></label>
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
                    </div>

                    <h5 class="tw-section-title mt-2">
                        <i class="bi bi-shield-lock"></i> Account Security
                    </h5>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="tw-auth-field">
                            <label for="password" class="tw-label">Password <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                                <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="At least 8 characters">
                                <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="tw-error-text" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tw-auth-field">
                            <label for="password-confirm" class="tw-label">Confirm Password <span class="text-red-600">*</span></label>
                            <div class="tw-input-group">
                                <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                                <input id="password-confirm" type="password" class="tw-input" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm your password">
                                <button type="button" data-pw-toggle="#password-confirm" class="inline-flex items-center px-3.5 text-slate-400 transition hover:text-navy-600" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mb-4 mt-1 text-xs text-slate-400"><span class="text-red-600">*</span> Required fields</p>

                    <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Log In</a>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
