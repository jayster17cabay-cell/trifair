@extends('layouts.app')

@section('title', 'Account Pending Approval')

@section('content')
<div class="tw-auth-shell">
    <div class="tw-auth-card">
        <div class="tw-auth-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <h4 class="tw-auth-title">Account Pending Approval</h4>
        <p class="tw-auth-sub">Your registration is under review</p>

        <div class="mt-8 flex flex-col items-center">
            <div class="mb-4 text-5xl text-gold">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <p class="mb-2 text-center text-sm text-slate-500">
                Thank you for registering! Your account is currently <strong class="text-slate-700">pending review</strong>.
            </p>

            <p class="mb-6 text-center text-sm text-slate-500">
                A TFRB Officer or Superadmin will review your information and approve your account. <br>
                You will be able to access your dashboard once approved.
            </p>

            <div class="mb-6 w-full rounded-2xl bg-slate-50 p-4 text-left">
                <p class="mb-2 text-xs font-semibold text-slate-500">
                    <i class="bi bi-info-circle me-1"></i> What happens next?
                </p>
                <ul class="list-inside list-disc space-y-1 pl-2 text-xs text-slate-500">
                    <li>Your details will be verified by a TFRB Officer</li>
                    <li>You'll receive a notification once approved</li>
                    <li>Then you can log in and access your dashboard</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="tw-btn tw-btn-gold w-full tw-btn-lg">
                    <i class="bi bi-box-arrow-left"></i> Back to Login
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
