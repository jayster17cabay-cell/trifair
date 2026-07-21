@extends(Auth::user()->isSuperadmin() ? 'layouts.superadmin' : 'layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Notifications</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Stay updated with system alerts</p>
    </div>
    <form action="{{ route('notifications.readAll') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-yellow">
            <i class="bi bi-check-all me-1"></i> Mark All as Read
        </button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse ($notifications as $notification)
            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }} {{ !$notification->is_read ? 'bg-primary bg-opacity-10' : '' }}">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 46px; height: 46px;
                                 background: {{ $notification->type === 'complaint' ? 'var(--warning-light)' : 'var(--secondary-light)' }};
                                color: {{ $notification->type === 'complaint' ? 'var(--warning)' : 'var(--secondary-dark)' }};">
                        <i class="bi bi-{{ $notification->type === 'complaint' ? 'exclamation-triangle' : 'info-circle' }} fs-5"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong style="font-size: 0.95rem;">{{ $notification->title }}</strong>
                                @if (!$notification->is_read)
                                    <span class="badge bg-primary ms-2" style="font-size: 0.6rem;">NEW</span>
                                @endif
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem; white-space: nowrap;">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-2 mt-1" style="font-size: 0.85rem; color: var(--gray-600);">{{ $notification->message }}</p>
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
                <p class="text-muted mb-0">No notifications yet.</p>
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
