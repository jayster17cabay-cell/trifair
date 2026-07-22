@extends(Auth::user()->isSuperadmin() ? 'layouts.superadmin' : 'layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Notifications</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Stay updated with system alerts and new ratings</p>
    </div>
    @if ($notifications->count() > 0)
        <form action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-yellow">
                <i class="bi bi-check-all me-1"></i> Mark All as Read
            </button>
        </form>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse ($notifications as $notification)
            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }} {{ !$notification->is_read ? 'bg-primary bg-opacity-10' : '' }}">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 46px; height: 46px;
                                 background: {{ $notification->type === 'complaint' ? 'var(--warning-light)' : ($notification->type === 'new_rating' ? 'var(--secondary-light)' : 'var(--info-light)') }};
                                color: {{ $notification->type === 'complaint' ? 'var(--warning)' : ($notification->type === 'new_rating' ? 'var(--secondary-dark)' : 'var(--info)') }};">
                        <i class="bi bi-{{ $notification->type === 'complaint' ? 'exclamation-triangle' : ($notification->type === 'new_rating' ? 'star-fill' : 'info-circle') }} fs-5"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong style="font-size: 0.95rem;">{{ $notification->title }}</strong>
                                @if (!$notification->is_read)
                                    <span class="badge bg-primary ms-2" style="font-size: 0.68rem;">NEW</span>
                                @endif
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem; white-space: nowrap;">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-2 mt-1" style="font-size: 0.85rem; color: var(--gray-600);">{{ $notification->message }}</p>
                        @if ($notification->rating && $notification->rating->driver)
                            <div style="font-size: 0.82rem; color: var(--gray-600); margin-bottom: 0.5rem;">
                                <i class="bi bi-person me-1"></i>Driver: <strong>{{ $notification->rating->driver->user->name ?? 'Unknown' }}</strong>
                                @if ($notification->rating->start_location && $notification->rating->end_location)
                                    <span class="ms-2"><i class="bi bi-geo-alt me-1" style="color: var(--success);"></i>{{ $notification->rating->start_location }} <i class="bi bi-arrow-right mx-1" style="font-size: 0.65rem;"></i> {{ $notification->rating->end_location }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="d-flex gap-2">
                            <a href="{{ route('notifications.read', $notification) }}" class="btn btn-sm btn-outline-yellow">
                                <i class="bi bi-eye me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; background: var(--gray-100);">
                    <i class="bi bi-bell-slash" style="font-size: 2.5rem; color: var(--gray-300);"></i>
                </div>
                <h5 style="color: var(--gray-600);">No notifications yet</h5>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">You'll see alerts here when passengers submit ratings.</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection