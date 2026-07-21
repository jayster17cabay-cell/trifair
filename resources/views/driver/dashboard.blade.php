@extends('layouts.driver')

@section('title', 'Dashboard')

@section('content')
<div class="driver-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Driver Dashboard</h2>
            <p>Welcome back, {{ Auth::user()->name }}! Here's your performance overview.</p>
        </div>
        <div class="text-end">
            <div style="font-size: 2.5rem; font-weight: 900; line-height: 1; color: #f5a623;">{{ number_format($averageRating ?? 0, 1) }}</div>
            <small style="opacity: 0.7;">average rating</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="stat-card stat-yellow">
            <div class="stat-icon">
                <i class="bi bi-star"></i>
            </div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-value">{{ number_format($averageRating ?? 0, 1) }}</div>
                    <div class="stat-footer">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= round($averageRating ?? 0))
                                <i class="bi bi-star-fill"></i>
                            @else
                                <i class="bi bi-star" style="opacity: 0.4;"></i>
                            @endif
                        @endfor
                    </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="bi bi-chat-square-text"></i>
            </div>
            <div class="stat-label">Total Ratings</div>
            <div class="stat-value">{{ $totalRatings }}</div>
            <div class="stat-footer">All-time feedback received</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="bi bi-qr-code"></i>
            </div>
            <div class="stat-label">QR Code Status</div>
            <div class="stat-value" style="font-size: 1.5rem;">
                <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.8rem; padding: 0.4rem 0.8rem; color: white;">
                    <i class="bi bi-check-circle-fill me-1"></i> Active
                </span>
            </div>
            <div class="stat-footer">Your QR code is ready for passengers</div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card card-accent-yellow">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-bar-chart me-2" style="color: var(--secondary);"></i>
                Rating Distribution
            </div>
            <div class="card-body">
                @foreach (range(5, 1) as $star)
                    @php
                        $count = $ratingCounts[$star] ?? 0;
                        $percent = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                    @endphp
                    <div class="rating-bar">
                        <span class="label">{{ $star }} <i class="bi bi-star-fill" style="font-size: 0.6rem; color: #f5a623;"></i></span>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;"></div>
                        </div>
                        <span class="count">{{ $count }}</span>
                    </div>
                @endforeach
                @if ($totalRatings === 0)
                    <p class="text-center text-muted my-3" style="font-size: 0.9rem;">No ratings yet to display distribution.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card card-accent-yellow">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-clock-history me-2" style="color: var(--secondary);"></i>
                Recent Ratings
            </div>
            <div class="card-body">
                @forelse ($recentRatings as $rating)
                    <div class="d-flex align-items-start gap-3 pb-3 mb-3" style="border-bottom: 1px solid var(--gray-100);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: var(--secondary-light); color: var(--secondary-dark); font-weight: 800;">
                            {{ $rating->rating }}
                        </div>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $rating->rating)
                                            <i class="bi bi-star-fill" style="color: #f5a623; font-size: 0.85rem;"></i>
                                        @else
                                            <i class="bi bi-star text-muted" style="font-size: 0.85rem;"></i>
                                        @endif
                                    @endfor
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem; white-space: nowrap;">{{ $rating->created_at->diffForHumans() }}</small>
                            </div>
                            @if ($rating->reason)
                                <p class="mb-0 mt-1" style="font-size: 0.85rem; color: var(--gray-600);">{{ $rating->reason }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem; color: var(--gray-300);"></i>
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">No ratings yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
