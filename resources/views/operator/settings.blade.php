@extends('layouts.operator')

@section('title', 'Settings')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">Settings</h1>
    <p class="op-header-sub">Account and security</p>
</div>
@endsection

@section('content')
<div class="grid gap-5 lg:grid-cols-2">
    <div class="tw-card">
        <div class="tw-card-pad border-b border-slate-100">
            <h5 class="tw-card-title"><i class="bi bi-shield-lock-fill mr-2 text-navy-600"></i>Change Password</h5>
        </div>
        <div class="tw-card-pad">
            <form action="{{ route('operator.settings.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="current_password" class="tw-label">Current Password</label>
                    <input type="password" name="current_password" id="current_password"
                        class="tw-input @error('current_password') is-invalid @enderror"
                        placeholder="Enter current password" required>
                    @error('current_password')
                        <span class="tw-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="new_password" class="tw-label">New Password</label>
                    <input type="password" name="new_password" id="new_password"
                        class="tw-input @error('new_password') is-invalid @enderror"
                        placeholder="At least 8 characters" required>
                    @error('new_password')
                        <span class="tw-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="new_password_confirmation" class="tw-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                        class="tw-input"
                        placeholder="Re-enter new password" required>
                </div>

                <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                    <i class="bi bi-check-lg"></i> Update Password
                </button>
            </form>
        </div>
    </div>

    <div class="tw-card">
        <div class="tw-card-pad border-b border-slate-100">
            <h5 class="tw-card-title"><i class="bi bi-person-circle mr-2 text-navy-600"></i>Account Info</h5>
        </div>
        <div class="tw-card-pad">
            <div class="mb-4">
                <label class="tw-label text-[0.8rem] uppercase tracking-widest text-slate-500">Name</label>
                <p class="font-semibold text-slate-800">{{ Auth::user()->name }}</p>
            </div>
            <div class="mb-4">
                <label class="tw-label text-[0.8rem] uppercase tracking-widest text-slate-500">Email</label>
                <p class="font-semibold text-slate-800">{{ Auth::user()->email }}</p>
            </div>
            @if($operator)
            <div class="mb-4">
                <label class="tw-label text-[0.8rem] uppercase tracking-widest text-slate-500">License Number</label>
                <p class="font-semibold text-slate-800">{{ $operator->license_number ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="tw-label text-[0.8rem] uppercase tracking-widest text-slate-500">Body Number</label>
                <p class="font-semibold text-slate-800">{{ $operator->body_number ?? 'N/A' }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
