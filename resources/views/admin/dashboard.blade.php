@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@if (isset($unreadCount) && $unreadCount > 0)
    <div class="alert alert-warning alert-banner d-flex align-items-center justify-content-between" role="alert" style="margin-bottom: 1.25rem;">
        <div>
            <i class="bi bi-bell-fill me-2"></i>
            <strong>{{ $unreadCount }} unread notification{{ $unreadCount > 1 ? 's' : '' }}</strong>
        </div>
        <a href="{{ route('notifications.index') }}" class="btn btn-yellow btn-sm">
            <i class="bi bi-eye me-1"></i> View
        </a>
    </div>
@endif

<div class="dash-stats">
    <div class="dash-stat-card" style="--accent: var(--primary); --accent-bg: var(--primary-50);">
        <div class="dash-stat-icon"><i class="bi bi-people-fill"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ $totalDrivers }}</div>
            <div class="dash-stat-text">Total Drivers</div>
            <div class="dash-stat-sub">{{ $activeDrivers }} active</div>
        </div>
    </div>
    <div class="dash-stat-card" style="--accent: var(--secondary-dark); --accent-bg: var(--secondary-50);">
        <div class="dash-stat-icon"><i class="bi bi-star-fill"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ number_format($averageRating ?? 0, 1) }}</div>
            <div class="dash-stat-text">Average Rating</div>
            <div class="dash-stat-sub">{{ $totalRatings }} total</div>
        </div>
    </div>
    <div class="dash-stat-card" style="--accent: var(--danger); --accent-bg: var(--danger-50);">
        <div class="dash-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ $totalComplaints }}</div>
            <div class="dash-stat-text">Complaints</div>
            <div class="dash-stat-sub">{{ $pendingReview }} pending</div>
        </div>
    </div>
    <div class="dash-stat-card" style="--accent: var(--success); --accent-bg: var(--success-50);">
        <div class="dash-stat-icon"><i class="bi bi-graph-up"></i></div>
        <div class="dash-stat-body">
            <div class="dash-stat-num">{{ $totalDrivers > 0 ? round(($activeDrivers / $totalDrivers) * 100) : 0 }}%</div>
            <div class="dash-stat-text">Active Rate</div>
            <div class="dash-stat-sub">Driver performance</div>
        </div>
    </div>
</div>

<div class="dash-section-label">Quick Actions</div>
<div class="dash-quick-actions">
    <a href="{{ route('admin.drivers.create') }}" class="dash-qa-item">
        <i class="bi bi-person-plus"></i>
        <span>Add Driver</span>
    </a>
    <a href="{{ route('admin.drivers') }}" class="dash-qa-item">
        <i class="bi bi-people"></i>
        <span>Drivers</span>
    </a>
    <a href="{{ route('admin.ratings') }}" class="dash-qa-item">
        <i class="bi bi-star"></i>
        <span>Ratings</span>
    </a>
    <a href="{{ route('admin.reports') }}" class="dash-qa-item">
        <i class="bi bi-bar-chart"></i>
        <span>Reports</span>
    </a>
    <a href="{{ route('admin.todas') }}" class="dash-qa-item">
        <i class="bi bi-diagram-3"></i>
        <span>TODAs</span>
    </a>
    <a href="{{ route('admin.activity-logs') }}" class="dash-qa-item">
        <i class="bi bi-clock-history"></i>
        <span>Logs</span>
    </a>
</div>

@if ($totalTodas > 0)
<div class="dash-section-label">TODA Overview</div>
<div class="dash-toda-grid mb-4">
    @foreach ($todaStats as $toda)
        <div class="dash-toda-card">
            <div class="dash-toda-head">
                <div class="dash-toda-icon"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <strong>{{ $toda->name }}</strong>
                    @if ($toda->area)
                        <small><i class="bi bi-geo-alt"></i> {{ $toda->area }}</small>
                    @endif
                </div>
            </div>
            <div class="dash-toda-nums">
                <div><span class="num">{{ $toda->drivers_count }}</span><span class="lbl">Drivers</span></div>
                <div><span class="num" style="color: var(--success);">{{ $toda->active_drivers_count }}</span><span class="lbl">Active</span></div>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="dash-two-col">
    <div class="dash-col">
        <div class="dash-section-label">Recent Complaints</div>
        <div class="dash-card">
            @forelse ($recentComplaints as $rating)
                <div class="dash-list-item">
                    <div class="dash-list-badge" style="background: var(--warning-light); color: var(--warning);">
                        {{ $rating->rating }}
                    </div>
                    <div class="dash-list-body">
                        <div class="dash-list-name">{{ $rating->driver->user->name ?? 'Unknown' }}</div>
                        @if ($rating->start_location && $rating->end_location)
                            <div class="dash-list-route">
                                <i class="bi bi-geo-alt" style="color: var(--success);"></i> {{ $rating->start_location }}
                                <i class="bi bi-arrow-right mx-1"></i>
                                <i class="bi bi-geo-alt" style="color: var(--danger);"></i> {{ $rating->end_location }}
                            </div>
                        @endif
                        @if ($rating->reason)
                            <div class="dash-list-sub">"{{ \Illuminate\Support\Str::limit($rating->reason, 60) }}"</div>
                        @endif
                    </div>
                    <div class="dash-list-time">{{ $rating->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <div class="dash-empty">
                    <i class="bi bi-check-circle"></i>
                    <span>No complaints. All good!</span>
                </div>
            @endforelse
        </div>
    </div>

    <div class="dash-col">
        <div class="dash-section-label">Top Rated Drivers</div>
        <div class="dash-card">
            @forelse ($topDrivers as $driver)
                @php
                    $avg = $driver->valid_ratings_avg_rating ?? 0;
                    $starColor = 'var(--secondary)';
                    $starEmpty = 'var(--gray-200)';
                @endphp
                <div class="dash-list-item">
                    <div class="dash-list-rank {{ $loop->index < 3 ? 'rank-top' : '' }}">
                        {{ $loop->iteration }}
                    </div>
                    <div class="dash-list-body">
                        <div class="dash-list-name">{{ $driver->user->name }}</div>
                        @if ($driver->toda)
                            <div class="dash-list-sub"><i class="bi bi-diagram-3"></i> {{ $driver->toda->name }}</div>
                        @endif
                    </div>
                    <div class="dash-list-right">
                        <div class="dash-list-score">{{ number_format($avg, 1) }}</div>
                        <div class="dash-list-stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star-fill" style="color: {{ $i <= round($avg) ? $starColor : $starEmpty }}; font-size: 0.65rem;"></i>
                            @endfor
                        </div>
                    </div>
                </div>
            @empty
                <div class="dash-empty">
                    <i class="bi bi-inbox"></i>
                    <span>No ratings data yet.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="dash-section-label">Recent Ratings</div>
<div class="dash-card mb-4">
    @forelse ($recentRatings as $rating)
        @php
            $r = $rating->rating;
            if ($r >= 4) { $bg = 'var(--primary-50)'; $fg = 'var(--primary)'; }
            elseif ($r <= 2) { $bg = 'var(--danger-50)'; $fg = 'var(--danger)'; }
            else { $bg = 'var(--secondary-50)'; $fg = 'var(--secondary-dark)'; }
            $starOn = 'var(--secondary)';
            $starOff = 'var(--gray-200)';
        @endphp
        <div class="dash-list-item">
            <div class="dash-list-badge" style="background: {{ $bg }}; color: {{ $fg }};">
                {{ $r }}
            </div>
            <div class="dash-list-body">
                <div class="dash-list-name">{{ $rating->driver->user->name ?? 'Unknown' }}</div>
                <div class="dash-list-stars" style="margin-top: 2px;">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill" style="color: {{ $i <= $r ? $starOn : $starOff }}; font-size: 0.65rem;"></i>
                    @endfor
                    @if ($rating->reason)
                        <span style="font-size: 0.78rem; color: var(--gray-500); margin-left: 0.4rem; font-style: italic;">"{{ \Illuminate\Support\Str::limit($rating->reason, 50) }}"</span>
                    @endif
                </div>
            </div>
            <div class="dash-list-time">{{ $rating->created_at->diffForHumans() }}</div>
        </div>
    @empty
        <div class="dash-empty">
            <i class="bi bi-inbox"></i>
            <span>No ratings yet.</span>
        </div>
    @endforelse
</div>
@endsection