@extends('layouts.superadmin')

@section('title', 'Settings')

@section('content')
<div class="tw-page-head">
    <h1 class="tw-page-title"><i class="bi bi-gear-fill mr-2 text-navy-600"></i>Settings</h1>
    <p class="tw-page-sub">Manage your account profile and security</p>
</div>

{{-- Complaint escalation SLA: solved complaints that are not re-opened within
     this many days are auto-escalated into the actionable TFRB / superadmin
     queue for a heavier re-review. --}}
<div class="tw-card mb-5 overflow-hidden">
    <div class="tw-card-pad border-b border-slate-100">
        <h5 class="tw-card-title"><i class="bi bi-hourglass-split mr-2 text-navy-600"></i>Complaint Escalation SLA</h5>
        <p class="text-xs leading-relaxed text-slate-500">
            When a solved complaint is not re-opened within this window, it is automatically escalated to the TFRB / superadmin for a heavier re-review. Set to 0 to disable auto-escalation.
        </p>
    </div>
    <div class="tw-card-pad">
        <form action="{{ route('superadmin.settings.complaintEscalation') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4 max-w-xs">
                <label for="complaint_sla_days" class="tw-label">Auto-escalate after (days)</label>
                <input type="number" name="sla_days" id="complaint_sla_days" min="0" max="365" value="{{ old('sla_days', \App\Models\Setting::getValue('complaint_escalation_sla_days', 7)) }}" class="tw-input" required>
            </div>
            <button type="submit" class="tw-btn tw-btn-navy">
                <i class="bi bi-check-lg"></i> Save SLA
            </button>
            @if ($errors->any())
                <span class="tw-error-text" role="alert">{{ $errors->first('sla_days') }}</span>
            @endif
        </form>
        @if (session('success'))
            <p class="mt-3 text-sm font-semibold text-emerald-600"><i class="bi bi-check2-circle mr-1"></i>{{ session('success') }}</p>
        @endif
    </div>
</div>

@include('partials.admin.account-settings', [
    'routePrefix' => 'superadmin',
    'roleLabel' => 'Superadmin',
    'operator' => null,
])
@endsection