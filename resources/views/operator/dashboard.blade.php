@extends('layouts.operator')

@section('title', 'Dashboard')

@section('header-body')
<div class="op-header-greet">
    @php $operatorFirstName = Auth::user()->nameParts()['first'] ?: Auth::user()->name; @endphp
    <h1 class="op-header-title">Welcome, {{ $operatorFirstName }}</h1>
    <p class="op-header-sub">Manage your ratings and profile</p>
</div>

<div class="op-header-rating">
    <div class="op-avg">
        <span class="op-avg-num" data-live="averageRating" aria-live="polite">{{ number_format($averageRating ?? 0, 1) }}</span>
        <span class="op-avg-stars" data-live-stars="averageRating">
            @php $avg = $averageRating ?? 0; @endphp
            @for ($i = 1; $i <= 5; $i++)
                <i class="bi {{ $i <= round($avg) ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= round($avg) ? 'var(--secondary)' : 'rgba(255,255,255,0.3)' }}"></i>
            @endfor
        </span>
    </div>
    <div class="op-avg-caption uppercase" data-live="ratingCaption" aria-live="polite">
        @if (($totalRatings ?? 0) > 0)
            Avg Rating · {{ $totalRatings }} {{ $totalRatings === 1 ? 'Rating' : 'Ratings' }}
        @else
            Avg Rating · No Ratings Yet
        @endif
    </div>
</div>
@endsection

@section('content')
@if ($operator->isFranchiseExpiringSoon())
<div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <i class="bi bi-exclamation-triangle-fill mr-1"></i>
    <strong>Expiring Soon:</strong> Your franchise expires in {{ $operator->currentTerm?->daysUntilExpiry() }} days. Please contact the TFRB Office to renew it before it lapses.
</div>
@endif
@include('partials.operator.franchise-scope-bar', ['franchise' => $operator->currentTerm, 'period' => $period, 'scopeRoute' => 'operator.dashboard', 'todas' => $todas ?? collect(), 'selectedToda' => $selectedToda ?? null])
@if (($todas ?? collect())->count() > 1)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-navy-100 bg-navy-50/60 px-4 py-3">
        <div class="flex items-center gap-2 text-sm font-bold text-navy-700">
            <i class="bi bi-clipboard-data"></i> Overall (All TODAs)
        </div>
        <div class="flex items-center gap-3">
            <span class="text-2xl font-extrabold text-navy-700">{{ $overallAvg !== null ? number_format($overallAvg, 1) : '—' }}</span>
            <span class="text-xs font-semibold text-slate-500">{{ $overallTotal }} {{ $overallTotal === 1 ? 'rating' : 'ratings' }}</span>
        </div>
    </div>
@endif

@if (($needsResponseCount ?? 0) > 0)
    <div class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        <span>
            <strong>{{ $needsResponseCount }}</strong>
            {{ $needsResponseCount === 1 ? 'low rating needs' : 'low ratings need' }} your response.
        </span>
        <a href="{{ route('operator.ratings') }}" class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-700">
            Respond <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@endif

<div class="op-stat-strip">
    <a href="{{ route('operator.ratings') }}" class="op-stat-strip-item block no-underline transition hover:-translate-y-0.5 hover:shadow-md" title="Low ratings still waiting for your response">
        <small>Needs Response</small>
        <strong>{{ $needsResponseCount ?? 0 }}</strong>
    </a>
    <a href="{{ route('operator.ratings') }}" class="op-stat-strip-item block no-underline transition hover:-translate-y-0.5 hover:shadow-md" title="Complaints still open against you">
        <small>Open Complaints</small>
        <strong>{{ $openComplaintsCount ?? 0 }}</strong>
    </a>
</div>

<div class="op-dashboard">
    @include('partials.operator.dashboard-quick-actions', ['operator' => $operator])

    <div class="op-stack">
        @include('partials.operator.qr-code-card', ['operator' => $operator])
        @include('partials.operator.rating-breakdown-card', ['ratingCounts' => $ratingCounts, 'totalRatings' => $totalRatings])
    </div>
</div>

<div id="howToUseModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="howToUseModalTitle">
        <div class="tw-modal-head">
            <h5 id="howToUseModalTitle" class="text-base font-bold text-slate-900"><i class="bi bi-question-circle-fill mr-2 text-navy-600"></i>How to Use TriFair</h5>
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
                    <p class="text-sm text-slate-500">Tap "Print QR" to get a large copy. Tape it inside your motorcycle.</p>
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
            <button type="button" class="tw-btn tw-btn-gold w-full" data-tw-modal-close>Got it!</button>
        </div>
    </div>
</div>

@include('partials.dashboard-live')
@endsection
