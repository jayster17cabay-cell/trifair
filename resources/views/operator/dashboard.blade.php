@extends('layouts.operator')

@section('title', 'Dashboard')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">Welcome, {{ explode(' ', Auth::user()->name)[0] }}</h1>
    <p class="op-header-sub">Manage your ratings and profile</p>
</div>

<div class="op-header-rating">
    <div class="op-avg">
        <span class="op-avg-num" data-live="averageRating">{{ number_format($averageRating ?? 0, 1) }}</span>
        <span class="op-avg-stars" data-live-stars="averageRating">
            @php $avg = $averageRating ?? 0; @endphp
            @for ($i = 1; $i <= 5; $i++)
                <i class="bi {{ $i <= round($avg) ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= round($avg) ? 'var(--secondary)' : 'rgba(255,255,255,0.3)' }}"></i>
            @endfor
        </span>
    </div>
    <div class="op-avg-caption uppercase" data-live="ratingCaption">
        @if (($totalRatings ?? 0) > 0)
            Avg Rating · {{ $totalRatings }} {{ $totalRatings === 1 ? 'Rating' : 'Ratings' }}
        @else
            Avg Rating · No Ratings Yet
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="op-dashboard">
    @include('partials.operator.dashboard-quick-actions', ['operator' => $operator])

    <div class="op-stack">
        @include('partials.operator.qr-code-card', ['operator' => $operator])
        @include('partials.operator.rating-breakdown-card', ['ratingCounts' => $ratingCounts, 'totalRatings' => $totalRatings])
    </div>
</div>

<div id="howToUseModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal">
        <div class="tw-modal-head">
            <h5 class="text-base font-bold text-slate-900"><i class="bi bi-question-circle-fill mr-2 text-navy-600"></i>How to Use TriFair</h5>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="tw-modal-body space-y-4">
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-600/10 text-sm font-extrabold text-navy-600">1</div>
                <div>
                    <strong class="text-sm">Get QR Code</strong>
                    <p class="text-sm text-slate-500">TFRB Officer assigns you a QR code — it appears on your dashboard.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold/10 text-sm font-extrabold text-gold-dark">2</div>
                <div>
                    <strong class="text-sm">Print & Display</strong>
                    <p class="text-sm text-slate-500">Tap "Print QR" to get a large copy. Tape it inside your tricycle.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-extrabold text-emerald-600">3</div>
                <div>
                    <strong class="text-sm">Passengers Scan</strong>
                    <p class="text-sm text-slate-500">They scan the QR and rate their trip — helping you build your reputation.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-sm font-extrabold text-red-600">4</div>
                <div>
                    <strong class="text-sm">Respond</strong>
                    <p class="text-sm text-slate-500">Low ratings (1-2 stars) let you reply. Go to "My Ratings" to respond.</p>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">
            <button type="button" class="tw-btn tw-btn-navy w-full" data-tw-modal-close>Got it!</button>
        </div>
    </div>
</div>

@include('partials.dashboard-live')
@endsection
