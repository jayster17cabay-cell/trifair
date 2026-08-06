@extends('layouts.tfrb-officer')

@section('title', 'Edit Operator')

@section('content')
    @php $routePrefix = 'tfrb-officer'; @endphp
    <div class="tw-page-head">
        <div>
            <h1 class="tw-page-title"><i class="bi bi-pencil mr-2 text-navy-600"></i>Edit Operator</h1>
            <p class="tw-page-sub">Updating: <strong>{{ $operator->user->name }}</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route($routePrefix . '.operators.qrcode', $operator) }}" class="tw-btn tw-btn-sm tw-btn-outline text-gold-dark">
                <i class="bi bi-qr-code"></i>QR Code
            </a>
            <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-outline">
                <i class="bi bi-arrow-left"></i>Back
            </a>
        </div>
    </div>

    <div class="tw-card tw-card-pad">
        @include('partials.admin.operator-edit-form')
    </div>
@endsection
