@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="card-header">
            <div class="login-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <h4 class="gradient-text">Welcome to TriFair</h4>
            <p>Sign in to manage your account</p>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2" style="font-size: 0.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope" style="color: var(--gray-400);"></i>
                        </span>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock" style="color: var(--gray-400);"></i>
                        </span>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="Enter your password">
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember" style="font-size: 0.85rem; color: var(--gray-500);">Remember Me</label>
                </div>

                <button type="submit" class="btn btn-yellow w-100 btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <small class="d-block text-muted mb-2" style="font-size: 0.75rem;">Who can access this system?</small>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i> Superadmin
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        <i class="bi bi-shield me-1"></i> Admin
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        <i class="bi bi-bicycle me-1"></i> Driver
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
