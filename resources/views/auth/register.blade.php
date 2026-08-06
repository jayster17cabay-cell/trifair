@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-person-plus"></i>
        </div>
        <h4 class="tw-auth-title">Create Account</h4>
        <p class="tw-auth-sub">Register as a tricycle operator</p>

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
                    <input id="contact_number" type="text" class="tw-input @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" placeholder="Enter your contact number">
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
                    <input id="plate_number" type="text" class="tw-input @error('plate_number') is-invalid @enderror" name="plate_number" value="{{ old('plate_number') }}" placeholder="Enter your plate number">
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

            <div class="tw-auth-field">
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
                    <input id="password" type="password" class="tw-input @error('password') is-invalid @enderror" name="password" required placeholder="Enter your password">
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
                </div>
            </div>

            <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <div class="tw-auth-foot">
            <p class="mb-2 text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-navy-600 hover:underline">Log In</a>
            </p>
            <p class="mb-0 text-sm">
                <a href="/" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-arrow-left me-1"></i> Back to Home
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
