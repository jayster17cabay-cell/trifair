@extends('layouts.operator')

@section('title', 'My Ratings')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">My Ratings</h1>
    <p class="op-header-sub">All feedback received from passengers</p>
</div>
@endsection

@section('content')
@include('partials.operator.franchise-scope-bar', ['franchise' => $operator->currentTerm, 'period' => $period, 'scopeRoute' => 'operator.ratings', 'todas' => $todas ?? collect(), 'selectedToda' => $selectedToda ?? null])
<div class="op-stat-strip">
    <div class="op-stat-strip-item">
        <small>Average</small>
        <strong>{{ number_format($averageRating ?? 0, 1) }}</strong>
    </div>
    <div class="op-stat-strip-item">
        <small>Total</small>
        <strong>{{ $totalRatings }}</strong>
    </div>
</div>

@if($ratings->count() > 0)
<div class="tw-alert tw-alert-navy mb-4">
    <i class="bi bi-info-circle-fill mt-0.5"></i>
    <span class="text-sm">Low ratings (1-2 stars) allow you to respond and explain your side. Tap a rating to open its details.</span>
</div>
@endif

<div class="tw-card tw-card-accent-gold">
    <div class="divide-y divide-slate-100">
        @forelse ($ratings as $rating)
            @php
                $r = $rating->rating;
                if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
                elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
                else { $cClass = 'bg-gold-50 text-gold-700'; }
            @endphp
            <a href="{{ route('operator.ratings.show', $rating) }}"
               class="flex items-start gap-3 px-5 py-4 transition-colors hover:bg-slate-50 no-underline">
                <div class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg font-extrabold {{ $cClass }}">
                    {{ $rating->rating }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-1">
                        <div class="flex gap-0.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-base"></i>
                            @endfor
                        </div>
                        <small class="text-xs text-slate-400">{{ $rating->created_at->format('M d, Y h:i A') }}</small>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($rating->complaint_type)
                            <span class="tw-badge tw-badge-red">
                                <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                            </span>
                        @endif
                        @if ($rating->response)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                <i class="bi bi-check-circle-fill"></i> Responded
                            </span>
                        @elseif ($r <= 2)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                <i class="bi bi-exclamation-circle"></i> Needs your response
                            </span>
                        @endif
                    </div>
                    @if ($rating->reason)
                        <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ $rating->reason }}</p>
                    @elseif ($rating->complaint_details)
                        <p class="mt-1 text-sm text-slate-600 line-clamp-2">{{ $rating->complaint_details }}</p>
                    @endif
                </div>
                <i class="bi bi-chevron-right mt-2 shrink-0 text-slate-400" aria-hidden="true"></i>
            </a>
        @empty
            <div class="tw-empty py-10">
                <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                <p class="mt-2 text-sm text-slate-500">No ratings yet.</p>
                <p class="text-[0.85rem] text-slate-400">Once passengers scan your QR code and rate their trip, their feedback will appear here.</p>
                <a href="{{ route('operator.dashboard') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-3">
                    <i class="bi bi-qr-code"></i> Go to Dashboard
                </a>
            </div>
        @endforelse
    </div>

    @if ($ratings->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $ratings->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection