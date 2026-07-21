@extends('layouts.admin')

@section('title', 'QR Code - ' . $driver->user->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">QR Code</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">{{ $driver->user->name }}</p>
    </div>
    <a href="{{ route('admin.drivers') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Drivers
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-header qr-card-header">
                <h5 class="mb-0" style="color: white !important;">{{ $driver->user->name }}</h5>
                <small style="opacity: 0.85;">{{ $driver->license_number ?? 'No License' }}</small>
            </div>
            <div class="card-body py-4">
                <div class="mb-4 qr-code-container">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($url) }}"
                         alt="QR Code for {{ $driver->user->name }}"
                         style="width: 220px; height: 220px; display: block;">
                </div>
                <p class="text-muted" style="font-size: 0.9rem;">Passengers scan this QR code to rate the driver</p>
                <div class="mb-3 p-3 rounded" style="background: var(--primary-50);">
                    <small class="text-muted d-block mb-1">Rating URL:</small>
                    <a href="{{ $url }}" target="_blank" style="font-size: 0.8rem; word-break: break-all; color: var(--secondary-dark);">{{ $url }}</a>
                </div>
                <button type="button" class="btn btn-yellow px-4" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print QR Code
                </button>
            </div>
            <div class="card-footer" style="background: var(--gray-50); color: var(--gray-500); font-size: 0.85rem;">
                <i class="bi bi-info-circle me-1"></i> Give this QR code to the driver to display on their tricycle.
            </div>
        </div>
    </div>
</div>
@endsection
