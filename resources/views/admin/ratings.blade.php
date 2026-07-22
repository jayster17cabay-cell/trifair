@extends('layouts.admin')

@section('title', 'All Ratings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">All Ratings & Feedback</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Review all passenger feedback and complaints</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Rating</th>
                        <th>Trip Route</th>
                        <th>Reason</th>
                        <th>Passenger</th>
                        <th>Proofs</th>
                        <th>Response</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ratings as $rating)
                        <tr class="{{ $rating->rating <= 2 && !$rating->is_reviewed ? 'table-warning' : '' }}">
                            <td style="color: var(--gray-400); font-weight: 500;">{{ $loop->iteration + ($ratings->currentPage() - 1) * $ratings->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width: 34px; height: 34px;
                                                background: {{ $rating->rating >= 4 ? 'var(--primary-50)' : ($rating->rating <= 2 ? 'var(--warning-light)' : 'var(--secondary-light)') }};
                                                color: {{ $rating->rating >= 4 ? 'var(--primary)' : ($rating->rating <= 2 ? 'var(--warning)' : 'var(--secondary-dark)') }};
                                                font-weight: 700; font-size: 0.85rem;">
                                        {{ strtoupper(substr($rating->driver->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <strong style="font-size: 0.9rem;">{{ $rating->driver->user->name ?? 'Unknown' }}</strong>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $rating->rating)
                                            <i class="bi bi-star-fill" style="color: var(--secondary); font-size: 0.85rem;"></i>
                                        @else
                                            <i class="bi bi-star" style="color: var(--gray-300); font-size: 0.85rem;"></i>
                                        @endif
                                    @endfor
                                </div>
                            </td>
                            <td style="font-size: 0.8rem; max-width: 180px;">
                                @if ($rating->start_location && $rating->end_location)
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi bi-geo-alt-fill" style="color: var(--success); font-size: 0.65rem;"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 160px;">{{ $rating->start_location }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <i class="bi bi-arrow-down" style="color: var(--gray-400); font-size: 0.6rem; margin-left: 0.15rem;"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi bi-geo-alt-fill" style="color: var(--danger); font-size: 0.65rem;"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 160px;">{{ $rating->end_location }}</span>
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">N/A</span>
                                @endif
                            </td>
                            <td style="font-size: 0.85rem; max-width: 200px;">
                                <span class="text-truncate d-inline-block" style="max-width: 200px;">{{ $rating->reason ?? 'N/A' }}</span>
                            </td>
                            <td style="font-size: 0.85rem;">
                                @if ($rating->passenger_name || $rating->passenger_contact)
                                    <div style="font-size: 0.85rem;">
                                        @if ($rating->passenger_name)
                                            <div><i class="bi bi-person me-1" style="font-size: 0.7rem; color: var(--gray-400);"></i>{{ $rating->passenger_name }}</div>
                                        @endif
                                        @if ($rating->passenger_contact)
                                            <a href="tel:{{ $rating->passenger_contact }}" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.8rem;">
                                                <i class="bi bi-telephone-fill me-1" style="font-size: 0.65rem;"></i>{{ $rating->passenger_contact }}
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">Anonymous</span>
                                @endif
                            </td>
                            <td>
                                @if ($rating->proofs->count() > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-paperclip me-1"></i>{{ $rating->proofs->count() }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.85rem;">None</span>
                                @endif
                            </td>
                            <td style="font-size: 0.8rem; max-width: 180px;">
                                @if ($rating->response)
                                    <span class="badge bg-success bg-opacity-10 text-success" title="{{ $rating->response->message }}">
                                        <i class="bi bi-chat-dots me-1"></i> Responded
                                    </span>
                                    <div class="text-truncate d-inline-block" style="max-width: 140px; font-size: 0.75rem; color: var(--gray-500); margin-top: 1px;">
                                        "{{ Str::limit($rating->response->message, 40) }}"
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">Pending</span>
                                @endif
                            </td>
                            <td style="font-size: 0.85rem; white-space: nowrap; color: var(--gray-600);">{{ $rating->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if ($rating->is_reviewed)
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-check-circle me-1"></i> Reviewed
                                    </span>
                                @elseif ($rating->rating <= 2)
                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-clock me-1"></i> Pending
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        <i class="bi bi-check me-1"></i> OK
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if (!$rating->is_reviewed && $rating->rating <= 2)
                                        <form action="{{ route('admin.ratings.review', $rating) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-warning" title="Mark as Reviewed">
                                                <i class="bi bi-check-lg"></i> Review
                                            </button>
                                        </form>
                                    @endif
                                    @if ($rating->proofs->count() > 0)
                                        @foreach ($rating->proofs as $proof)
                                            <a href="{{ route('storage.serve', $proof->file_path) }}" target="_blank"
                                               class="btn btn-sm btn-outline-primary" title="{{ $proof->original_name }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: var(--gray-300);"></i>
                                </div>
                                <p class="text-muted mb-0">No ratings yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ratings->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $ratings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
