@extends('layouts.driver')

@section('title', 'Dashboard')

@section('content')
<div class="dash-driver-hero">
    <div class="dash-driver-hero-content">
        <div class="dash-driver-greeting">
            <h2>Welcome, {{ Auth::user()->name }}!</h2>
            <p>Share your QR code so passengers can rate their trip.</p>
        </div>
        <div class="dash-driver-score">
            <div class="dash-driver-score-num">{{ number_format($averageRating ?? 0, 1) }}</div>
            <div class="dash-driver-score-stars">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star-fill" style="color: {{ $i <= round($averageRating ?? 0) ? 'var(--secondary)' : 'rgba(255,255,255,0.3)' }};"></i>
                @endfor
            </div>
            <div class="dash-driver-score-label">avg rating</div>
        </div>
    </div>
</div>

<div class="dash-stats dash-stats-3">
    <div class="dash-stat-card" style="--accent: var(--secondary-dark); --accent-bg: var(--secondary-50);">
        <div class="dash-stat-icon"><i class="bi bi-star-fill"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ number_format($averageRating ?? 0, 1) }}</div>
            <div class="dash-stat-text">Average Rating</div>
        </div>
    </div>
    <div class="dash-stat-card" style="--accent: var(--primary); --accent-bg: var(--primary-50);">
        <div class="dash-stat-icon"><i class="bi bi-chat-square-text-fill"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ $totalRatings }}</div>
            <div class="dash-stat-text">Total Ratings</div>
        </div>
    </div>
    <div class="dash-stat-card" style="--accent: var(--success); --accent-bg: var(--success-50);">
        <div class="dash-stat-icon"><i class="bi bi-qr-code"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num" style="font-size: 1.1rem; font-weight: 700;">Active</div>
            <div class="dash-stat-text">QR Status</div>
        </div>
    </div>
</div>

<div class="dash-two-col">
    <div class="dash-col" style="flex: 0 0 38%;">
        <div class="dash-section-label">Your QR Code</div>
        <div class="dash-card" style="text-align: center;">
            @if($driver && $driver->qr_code)
                <div style="padding: 1.5rem;">
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gray-400); margin-bottom: 0.75rem;">Scan to Rate</div>
                    <div style="background: white; display: inline-block; padding: 0.75rem; border-radius: 14px; border: 2px solid var(--gray-100);">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('rate.driver', $driver->qr_code)) }}" alt="QR Code" style="width: 180px; height: 180px;">
                    </div>
                    <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-700); font-weight: 600;">
                        {{ Auth::user()->name }}
                        @if($driver->body_number)
                            <span style="color: var(--gray-400);"> ({{ $driver->body_number }})</span>
                        @endif
                    </div>
                </div>
                <div style="padding: 0.6rem 1rem; background: var(--info-light); font-size: 0.8rem; color: var(--info); font-weight: 500; border-top: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                    <i class="bi bi-printer"></i> Print and display inside your tricycle
                </div>
            @else
                <div style="padding: 2rem;">
                    <i class="bi bi-qr-code" style="font-size: 2.5rem; color: var(--gray-300);"></i>
                    <p style="font-size: 0.88rem; color: var(--gray-500); margin-top: 0.75rem;">No QR code assigned yet.</p>
                    <p style="font-size: 0.78rem; color: var(--gray-400);">Contact your admin to get one.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="dash-col">
        <div class="dash-section-label">Rating Distribution</div>
        <div class="dash-card" style="padding: 1.25rem;">
            @foreach (range(5, 1) as $star)
                @php
                    $count = $ratingCounts[$star] ?? 0;
                    $percent = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                @endphp
                <div class="rating-bar">
                    <span class="label">{{ $star }} <i class="bi bi-star-fill" style="font-size: 0.7rem; color: var(--secondary);"></i></span>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;"></div>
                    </div>
                    <span class="count">{{ $count }}</span>
                </div>
            @endforeach
            @if ($totalRatings === 0)
                <div class="dash-empty">
                    <i class="bi bi-inbox"></i>
                    <span>No ratings yet. Share your QR!</span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="dash-section-label">Recent Feedback</div>
<div class="dash-card mb-4">
    @forelse ($recentRatings as $rating)
        <div class="dash-list-item">
            <div class="dash-list-badge" style="background: var(--secondary-50); color: var(--secondary-dark);">
                {{ $rating->rating }}
            </div>
            <div class="dash-list-body">
                <div class="dash-list-stars">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill" style="color: {{ $i <= $rating->rating ? 'var(--secondary)' : 'var(--gray-200)' }}; font-size: 0.7rem;"></i>
                    @endfor
                </div>
                @if ($rating->reason)
                    <div class="dash-list-sub" style="font-style: italic;">"{{ $rating->reason }}"</div>
                @endif
                @if ($rating->start_location && $rating->end_location)
                    <div class="dash-list-route">
                        <i class="bi bi-geo-alt" style="color: var(--success);"></i> {{ $rating->start_location }}
                        <i class="bi bi-arrow-right mx-1"></i>
                        <i class="bi bi-geo-alt" style="color: var(--danger);"></i> {{ $rating->end_location }}
                    </div>
                @endif
            </div>
            <div class="dash-list-time">{{ $rating->created_at->diffForHumans() }}</div>
        </div>
    @empty
        <div class="dash-empty">
            <i class="bi bi-inbox"></i>
            <span>No feedback yet.</span>
        </div>
    @endforelse
</div>
@endsection
