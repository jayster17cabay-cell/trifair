@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Driver Performance Reports</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Analytics and performance overview of all drivers</p>
    </div>
</div>

<div class="card card-accent-yellow">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>#</th>
                        <th>Driver Name</th>
                        <th>Plate #</th>
                        <th>License</th>
                        <th>Status</th>
                        <th>Average Rating</th>
                        <th class="text-center">Total Ratings</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        <tr style="cursor: pointer;" onclick="toggleTrips({{ $driver->id }})">
                            <td class="text-center">
                                <i class="bi bi-chevron-right" id="icon-{{ $driver->id }}" style="transition: transform 0.2s; color: var(--gray-400);"></i>
                            </td>
                            <td style="color: var(--gray-400); font-weight: 500;">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: var(--secondary-light); color: var(--secondary-dark); font-weight: 800; font-size: 0.85rem; flex-shrink: 0;">
                                        {{ strtoupper(substr($driver->user->name, 0, 1)) }}
                                    </div>
                                    <strong style="font-size: 0.9rem;">{{ $driver->user->name }}</strong>
                                </div>
                            </td>
                            <td style="font-size: 0.85rem; font-weight: 600;">{{ $driver->plate_number ?? 'N/A' }}</td>
                            <td style="font-size: 0.85rem;">{{ $driver->license_number ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $driver->status === 'active' ? 'primary' : 'warning' }} bg-opacity-10 text-{{ $driver->status === 'active' ? 'primary' : 'warning' }}">
                                    <i class="bi bi-{{ $driver->status === 'active' ? 'check-circle' : 'x-circle' }} me-1"></i>
                                    {{ ucfirst($driver->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex gap-1">
                                        @php $avg = $driver->valid_ratings_avg_rating ?? 0; @endphp
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= round($avg))
                                                <i class="bi bi-star-fill" style="color: var(--secondary); font-size: 0.8rem;"></i>
                                            @else
                                                <i class="bi bi-star" style="color: var(--gray-300); font-size: 0.8rem;"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="badge" style="background: var(--secondary-light); color: var(--secondary-dark); font-weight: 700;">
                                        {{ number_format($avg, 1) }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background: var(--primary-50); color: var(--primary); font-weight: 700;">
                                    <i class="bi bi-star me-1"></i> {{ $driver->valid_ratings_count }}
                                </span>
                            </td>
                        </tr>
                        <tr id="trips-{{ $driver->id }}" style="display:none;">
                            <td colspan="8" style="padding: 0; background: var(--gray-50);">
                                <div class="p-3">
                                    @if ($driver->validRatings && $driver->validRatings->count() > 0)
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-500); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                            <i class="bi bi-clock-history me-1"></i> Recent Trips
                                        </div>
                                        @foreach ($driver->validRatings as $rating)
                                            <div class="d-flex align-items-start gap-2 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color: var(--gray-200) !important;">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width: 30px; height: 30px; background: {{ $rating->rating >= 4 ? 'var(--primary-50)' : ($rating->rating <= 2 ? 'var(--warning-light)' : 'var(--secondary-light)') }}; color: {{ $rating->rating >= 4 ? 'var(--primary)' : ($rating->rating <= 2 ? 'var(--warning)' : 'var(--secondary-dark)') }}; font-weight: 700; font-size: 0.75rem;">
                                                    {{ $rating->rating }}
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    @if ($rating->start_location && $rating->end_location)
                                                        <div class="trip-route-visual" style="display: flex; align-items: flex-start; gap: 6px;">
                                                            <div style="display: flex; flex-direction: column; align-items: center; width: 14px; flex-shrink: 0; padding-top: 2px;">
                                                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success); flex-shrink: 0;"></div>
                                                                <div style="width: 2px; flex-grow: 1; min-height: 20px; background: linear-gradient(to bottom, var(--success), var(--danger)); border-radius: 1px;"></div>
                                                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--danger); flex-shrink: 0;"></div>
                                                            </div>
                                                            <div style="font-size: 0.75rem; min-width: 0;">
                                                                <div style="color: var(--success); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                                                    <i class="bi bi-geo-alt-fill me-1" style="font-size: 0.6rem;"></i> {{ $rating->start_location }}
                                                                </div>
                                                                <div style="color: var(--danger); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                                                    <i class="bi bi-geo-alt-fill me-1" style="font-size: 0.6rem;"></i> {{ $rating->end_location }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span style="font-size: 0.75rem; color: var(--gray-400);">No route data</span>
                                                    @endif
                                                    <div style="font-size: 0.65rem; color: var(--gray-400); margin-top: 1px;">
                                                        <i class="bi bi-clock me-1"></i> {{ $rating->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-3">
                                            <i class="bi bi-inbox" style="font-size: 1.5rem; color: var(--gray-300);"></i>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.8rem;">No trips recorded yet.</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: var(--gray-300);"></i>
                                </div>
                                <p class="text-muted mb-0">No drivers found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleTrips(id) {
        const row = document.getElementById('trips-' + id);
        const icon = document.getElementById('icon-' + id);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            icon.style.transform = 'rotate(90deg)';
        } else {
            row.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endsection
