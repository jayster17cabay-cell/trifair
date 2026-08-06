@extends('layouts.superadmin')

@section('title', 'QR Code - ' . $operator->user->name)

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">QR Code</h1>
        <p class="tw-page-sub">{{ $operator->user->name }}</p>
    </div>
    <a href="{{ route('superadmin.operators') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back to Operators
    </a>
</div>

<div class="mx-auto max-w-lg">
    <div class="tw-card overflow-hidden text-center">
        <div class="bg-navy-600 px-5 py-4 text-white">
            <h5 class="text-lg font-bold">{{ $operator->user->name }}</h5>
            <small class="text-slate-300">{{ $operator->license_number ?? 'No License' }}</small>
        </div>
        <div class="px-5 py-6">
            <div class="mb-5 inline-block rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($url) }}"
                     alt="QR Code for {{ $operator->user->name }}"
                     style="width: 220px; height: 220px; display: block;">
            </div>
            <p class="mb-5 text-sm text-slate-500">Passengers scan this QR code to rate the operator</p>
            <div class="mb-5 rounded-xl bg-blue-50 p-3 text-left">
                <small class="mb-1 block text-slate-500">Rating URL:</small>
                <a href="{{ $url }}" target="_blank" rel="noopener" class="break-all text-xs font-semibold text-amber-700">{{ $url }}</a>
            </div>
            <button type="button" class="tw-btn tw-btn-gold px-5" onclick="window.print()">
                <i class="bi bi-printer"></i>Print QR Code
            </button>
        </div>
        <div class="border-t border-slate-100 bg-slate-50 px-5 py-3 text-sm text-slate-500">
            <i class="bi bi-info-circle mr-1"></i>Give this QR code to the operator to display on their tricycle.
        </div>
    </div>
</div>
@endsection
