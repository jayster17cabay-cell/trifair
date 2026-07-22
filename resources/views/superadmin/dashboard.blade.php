@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
<div class="welcome-card">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3>Selamat datang, {{ Auth::user()->name }}!</h3>
            <p>System overview ng TriFair. Dito mo makikita lahat ng data sa real-time.</p>
        </div>
        <div class="text-end" style="z-index: 1;">
            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.75rem; padding: 0.4rem 0.8rem;">
                <i class="bi bi-check-circle-fill me-1"></i> System Online
            </span>
        </div>
    </div>
</div>

@if (isset($unreadCount) && $unreadCount > 0)
    <div class="alert alert-warning alert-banner d-flex align-items-center justify-content-between shadow-sm" role="alert">
        <div>
            <i class="bi bi-bell-fill me-2 text-warning"></i>
            <strong>You have {{ $unreadCount }} unread notification{{ $unreadCount > 1 ? 's' : '' }}!</strong>
            <span class="ms-2" style="font-size: 0.9rem;">May bago kang alerts na kailangan i-check.</span>
        </div>
        <a href="{{ route('notifications.index') }}" class="btn btn-yellow btn-sm">
            <i class="bi bi-eye me-1"></i> View Alerts
        </a>
    </div>
@endif

<div class="section-label">Quick Actions</div>
<div class="quick-actions-grid mb-4">
    <a href="{{ route('superadmin.drivers.create') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--primary-50); color: var(--primary);"><i class="bi bi-person-plus"></i></div>
        <div><div class="qa-text">Add Driver</div><div class="qa-desc">Bagong tricycle driver</div></div>
    </a>
    <a href="{{ route('superadmin.admins') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--info-light); color: var(--info);"><i class="bi bi-shield"></i></div>
        <div><div class="qa-text">Manage Admins</div><div class="qa-desc">Admin accounts</div></div>
    </a>
    <a href="{{ route('superadmin.todas') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--secondary-50); color: var(--secondary-dark);"><i class="bi bi-diagram-3"></i></div>
        <div><div class="qa-text">Manage TODAs</div><div class="qa-desc">Tricycle organizations</div></div>
    </a>
    <a href="{{ route('superadmin.reports') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--success-50); color: var(--success);"><i class="bi bi-bar-chart"></i></div>
        <div><div class="qa-text">View Reports</div><div class="qa-desc">Performance analytics</div></div>
    </a>
    <a href="{{ route('superadmin.complaints') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--danger-50); color: var(--danger);"><i class="bi bi-exclamation-triangle"></i></div>
        <div><div class="qa-text">Complaints</div><div class="qa-desc">Review flagged trips</div></div>
    </a>
    <a href="{{ route('superadmin.activity-logs') }}" class="quick-action-card">
        <div class="qa-icon" style="background: var(--gray-100); color: var(--gray-600);"><i class="bi bi-clock-history"></i></div>
        <div><div class="qa-text">Activity Logs</div><div class="qa-desc">System history</div></div>
    </a>
</div>

<div class="section-label">System Stats</div>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-label">Total Drivers</div>
            <div class="stat-value">{{ $totalDrivers }}</div>
            <div class="stat-footer">{{ $activeDrivers }} currently active</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-yellow">
            <div class="stat-icon"><i class="bi bi-star"></i></div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-value">{{ number_format($averageRating ?? 0, 1) }}</div>
            <div class="stat-footer">from {{ $totalRatings }} total ratings</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-label">Complaints</div>
            <div class="stat-value">{{ $totalComplaints }}</div>
            <div class="stat-footer">Low ratings with proof</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
            <div class="stat-label">System Admins</div>
            <div class="stat-value">{{ $totalAdmins }}</div>
            <div class="stat-footer">Managing the system</div>
        </div>
    </div>
</div>

@if ($totalTodas > 0)
<div class="section-label">Drivers by TODA</div>
<div class="card card-accent-blue mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-diagram-3 me-2" style="color: var(--primary);"></i>TODA Overview</span>
        <a href="{{ route('superadmin.todas') }}" class="btn btn-sm btn-outline-primary">Manage TODAs</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach ($todaStats as $toda)
                <div class="col-md-4 col-lg-3">
                    <div class="p-3 rounded-3" style="background: var(--primary-50); border: 1px solid var(--primary-light);">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: var(--primary); color: white; font-size: 0.7rem; font-weight: 800; flex-shrink: 0;">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <strong style="font-size: 0.85rem; color: var(--primary);">{{ $toda->name }}</strong>
                        </div>
                        @if ($toda->area)
                            <small class="text-muted d-block mb-2" style="font-size: 0.7rem;"><i class="bi bi-geo-alt me-1"></i>{{ $toda->area }}</small>
                        @endif
                        <div class="d-flex gap-3">
                            <div>
                                <div style="font-size: 1.3rem; font-weight: 800; color: var(--primary);">{{ $toda->drivers_count }}</div>
                                <small class="text-muted" style="font-size: 0.65rem;">Drivers</small>
                            </div>
                            <div>
                                <div style="font-size: 1.3rem; font-weight: 800; color: var(--success);">{{ $toda->active_drivers_count }}</div>
                                <small class="text-muted" style="font-size: 0.65rem;">Active</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="section-label">Recent Complaints</div>
        <div class="card card-accent-blue">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle me-2" style="color: var(--warning);"></i>Flagged Ratings
            </div>
            <div class="card-body p-0">
                @forelse ($recentComplaints as $rating)
                    <div class="list-item-premium p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 34px; height: 34px; background: var(--warning-light); color: var(--warning); font-weight: 800; font-size: 0.85rem;">
                                {{ $rating->rating }}
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <strong style="font-size: 0.85rem;">{{ $rating->driver->user->name ?? 'Unknown' }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.65rem;">{{ $rating->created_at->diffForHumans() }}</small>
                                @if ($rating->start_location && $rating->end_location)
                                    <div style="font-size: 0.6rem; color: var(--gray-500); margin-top: 1px;">
                                        <i class="bi bi-geo-alt" style="color: var(--success);"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 80px; vertical-align: bottom;">{{ $rating->start_location }}</span>
                                        <i class="bi bi-arrow-right mx-1" style="font-size: 0.5rem;"></i>
                                        <i class="bi bi-geo-alt" style="color: var(--danger);"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 80px; vertical-align: bottom;">{{ $rating->end_location }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--primary-50);">
                            <i class="bi bi-check-circle" style="font-size: 2rem; color: var(--primary);"></i>
                        </div>
                        <p class="text-muted mb-0" style="font-size: 0.85rem;">Walang complaints. All good!</p>
                    </div>
                @endforelse
                @if ($totalComplaints > 5)
                    <a href="{{ route('superadmin.complaints') }}" class="d-block text-center py-3 text-decoration-none" style="color: var(--secondary-dark); font-size: 0.85rem; font-weight: 600; background: var(--secondary-50);">
                        View all complaints <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="section-label">Top Rated Drivers</div>
        <div class="card card-accent-yellow">
            <div class="card-header">
                <i class="bi bi-trophy me-2" style="color: var(--secondary);"></i>Top Performers
            </div>
            <div class="card-body p-0">
                @forelse ($topDrivers as $driver)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                             style="width: 38px; height: 38px; background: {{ $loop->index < 3 ? 'var(--secondary-light)' : 'var(--gray-100)' }}; color: {{ $loop->index < 3 ? 'var(--secondary-dark)' : 'var(--gray-500)' }}; font-weight: 900; font-size: 0.95rem;">
                            {{ $loop->iteration }}
                        </div>
                        <div class="flex-grow-1">
                            <div>
                                <strong style="font-size: 0.88rem;">{{ $driver->user->name }}</strong>
                                @if ($driver->toda)
                                    <small class="d-block" style="font-size: 0.65rem; color: var(--gray-500);">{{ $driver->toda->name }}</small>
                                @endif
                            </div>
                            <div class="d-flex gap-1 mt-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star-fill" style="color: {{ $i <= round($driver->valid_ratings_avg_rating ?? 0) ? 'var(--secondary)' : 'var(--gray-200)' }}; font-size: 0.6rem;"></i>
                                @endfor
                            </div>
                        </div>
                        <div class="text-end">
                            <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary);">{{ number_format($driver->valid_ratings_avg_rating ?? 0, 1) }}</div>
                            <small class="text-muted" style="font-size: 0.65rem;">{{ $driver->valid_ratings_count }} ratings</small>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No ratings data yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="section-label">Recent Activity</div>
        <div class="card card-accent-yellow">
            <div class="card-header">
                <i class="bi bi-clock-history me-2" style="color: var(--secondary);"></i>Latest Ratings
            </div>
            <div class="card-body p-0">
                @forelse ($recentRatings as $rating)
                    <div class="d-flex align-items-start gap-2 px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 34px; height: 34px;
                                     background: {{ $rating->rating >= 4 ? 'var(--primary-50)' : ($rating->rating <= 2 ? 'var(--warning-light)' : 'var(--secondary-light)') }};
                                    color: {{ $rating->rating >= 4 ? 'var(--primary)' : ($rating->rating <= 2 ? 'var(--warning)' : 'var(--secondary-dark)') }};
                                    font-weight: 700; font-size: 0.85rem;">
                            {{ $rating->rating }}
                        </div>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <strong style="font-size: 0.85rem;">{{ $rating->driver->user->name ?? 'Unknown' }}</strong>
                            <div class="d-flex gap-1 mt-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star-fill" style="color: {{ $i <= $rating->rating ? 'var(--secondary)' : 'var(--gray-200)' }}; font-size: 0.55rem;"></i>
                                @endfor
                            </div>
                            @if ($rating->start_location && $rating->end_location)
                                <div style="font-size: 0.6rem; color: var(--gray-500); margin-top: 1px;">
                                    <i class="bi bi-geo-alt" style="color: var(--success);"></i>
                                    <span class="text-truncate d-inline-block" style="max-width: 80px; vertical-align: bottom;">{{ $rating->start_location }}</span>
                                    <i class="bi bi-arrow-right mx-1" style="font-size: 0.5rem;"></i>
                                    <i class="bi bi-geo-alt" style="color: var(--danger);"></i>
                                    <span class="text-truncate d-inline-block" style="max-width: 80px; vertical-align: bottom;">{{ $rating->end_location }}</span>
                                </div>
                            @endif
                        </div>
                        <small class="text-muted flex-shrink-0" style="font-size: 0.65rem;">{{ $rating->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No activity yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection