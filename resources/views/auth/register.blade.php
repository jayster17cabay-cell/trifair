@extends('layouts.app')

@section('title', 'Register')

@section('content')
@include('auth.partials.auth-shell-open', ['authWidth' => 'max-w-2xl'])
                <div class="mb-6 text-center">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gold/10 px-3 py-1 text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">
                        <i class="bi bi-person-plus"></i> Operator Registration
                    </div>
                    <h4 class="text-2xl font-extrabold tracking-tight text-navy-700">Create Account</h4>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Register as a motorcycle operator and start tracking your ratings.</p>
                </div>

                @if ($errors->any())
                    <div class="tw-alert tw-alert-danger mb-5">
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

                    <section>
                        <div class="mb-4 flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-sm text-navy-600">
                                <i class="bi bi-person-vcard"></i>
                            </span>
                            <div>
                                <h5 class="text-sm font-bold leading-tight text-navy-700">Personal Information</h5>
                                <p class="text-xs text-slate-500">Enter your name and contact details</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <label for="last_name" class="tw-label">Last Name <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('last_name') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                    <input id="last_name" type="text" class="tw-input @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autofocus autocomplete="family-name" placeholder="Dela Cruz" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}">
                                </div>
                                @error('last_name')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="first_name" class="tw-label">First Name <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('first_name') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                    <input id="first_name" type="text" class="tw-input @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" placeholder="Juan" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}">
                                </div>
                                @error('first_name')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="middle_name" class="tw-label">Middle Name</label>
                                <div class="tw-input-group @error('middle_name') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-person"></i></span>
                                    <input id="middle_name" type="text" class="tw-input @error('middle_name') is-invalid @enderror" name="middle_name" value="{{ old('middle_name') }}" autocomplete="additional-name" placeholder="Santos" aria-invalid="{{ $errors->has('middle_name') ? 'true' : 'false' }}">
                                </div>
                                @error('middle_name')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="email" class="tw-label">Email Address <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('email') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-envelope"></i></span>
                                    <input id="email" type="email" class="tw-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                                </div>
                                @error('email')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="contact_number" class="tw-label">Contact Number <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('contact_number') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-phone"></i></span>
                                    <input id="contact_number" type="tel" inputmode="tel" class="tw-input @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" required autocomplete="tel" placeholder="0917 123 4567" aria-invalid="{{ $errors->has('contact_number') ? 'true' : 'false' }}">
                                </div>
                                @error('contact_number')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="mt-6 border-t border-slate-200 pt-6">
                        <div class="mb-4 flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-sm text-navy-600">
                                <i class="bi bi-bicycle"></i>
                            </span>
                            <div>
                                <h5 class="text-sm font-bold leading-tight text-navy-700">Motorcycle &amp; TODA</h5>
                                <p class="text-xs text-slate-500">Your registered motorcycle and organization</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="plate_number" class="tw-label">Plate Number <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('plate_number') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-upc-scan"></i></span>
                                    <input id="plate_number" type="text" class="tw-input @error('plate_number') is-invalid @enderror" name="plate_number" value="{{ old('plate_number') }}" required placeholder="AB-1234" aria-invalid="{{ $errors->has('plate_number') ? 'true' : 'false' }}">
                                </div>
                                @error('plate_number')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="body_number" class="tw-label">Body Number <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('body_number') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-123"></i></span>
                                    <input id="body_number" type="text" class="tw-input @error('body_number') is-invalid @enderror" name="body_number" value="{{ old('body_number') }}" required placeholder="e.g. 1234" aria-invalid="{{ $errors->has('body_number') ? 'true' : 'false' }}">
                                </div>
                                @error('body_number')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="motorcycle_model" class="tw-label">Motorcycle Model <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('motorcycle_model') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-bicycle"></i></span>
                                    <input id="motorcycle_model" type="text" class="tw-input @error('motorcycle_model') is-invalid @enderror" name="motorcycle_model" value="{{ old('motorcycle_model') }}" required placeholder="Honda Wave 125" aria-invalid="{{ $errors->has('motorcycle_model') ? 'true' : 'false' }}">
                                </div>
                                @error('motorcycle_model')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="toda_id" class="tw-label">TODA <span class="font-normal text-slate-400">(Optional)</span></label>
                                <div class="tw-input-group @error('toda_id') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-people"></i></span>
                                    <select id="toda_id" name="toda_id" class="tw-select border-0 focus:ring-0 @error('toda_id') is-invalid @enderror" aria-invalid="{{ $errors->has('toda_id') ? 'true' : 'false' }}">
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
                                <p class="mt-1 text-xs text-slate-400">You can join a TODA later from your profile.</p>
                            </div>
                        </div>
                    </section>

                    <section class="mt-6 border-t border-slate-200 pt-6">
                        <div class="mb-4 flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-sm text-navy-600">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <div>
                                <h5 class="text-sm font-bold leading-tight text-navy-700">Account Security</h5>
                                <p class="text-xs text-slate-500">Use at least 8 characters for your password</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="tw-label">Password <span class="text-red-500">*</span></label>
                                <div class="tw-input-group @error('password') border-red-300 @enderror">
                                    <span class="tw-input-group-icon"><i class="bi bi-lock"></i></span>
                                    <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="At least 8 characters" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                                    <button type="button" data-pw-toggle="#password" class="inline-flex items-center px-3.5 text-slate-400 transition hover:bg-slate-50 hover:text-navy-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy-600/30" tabindex="-1" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="tw-error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="password-confirm" class="tw-label">Confirm Password <span class="text-red-500">*</span></label>
                                <div class="tw-input-group">
                                    <span class="tw-input-group-icon"><i class="bi bi-lock-fill"></i></span>
                                    <input id="password-confirm" type="password" class="tw-input" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter your password">
                                    <button type="button" data-pw-toggle="#password-confirm" class="inline-flex items-center px-3.5 text-slate-400 transition hover:bg-slate-50 hover:text-navy-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy-600/30" tabindex="-1" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <p class="mt-5 text-xs text-slate-400"><span class="text-red-500">*</span> Required fields</p>

                    <button type="submit" class="tw-btn tw-btn-gold mt-3 w-full tw-btn-lg">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-navy-600 transition hover:text-navy-700 hover:underline">Log In</a>
                </div>
@include('auth.partials.auth-shell-close')
@endsection
