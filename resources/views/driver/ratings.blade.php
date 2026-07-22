@extends('layouts.driver')

@section('title', 'My Ratings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">My Ratings</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">All feedback received from passengers</p>
    </div>
    <div class="d-flex gap-2">
        <div class="text-center px-3 py-2 rounded" style="background: var(--secondary-light);">
            <small class="d-block" style="color: var(--secondary-dark); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">AVERAGE</small>
            <span style="color: var(--primary); font-weight: 900; font-size: 1.2rem;">{{ number_format($averageRating ?? 0, 1) }}</span>
        </div>
        <div class="text-center px-3 py-2 rounded" style="background: var(--primary-50);">
            <small class="d-block" style="color: var(--primary); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">TOTAL</small>
            <span style="color: var(--primary); font-weight: 900; font-size: 1.2rem;">{{ $totalRatings }}</span>
        </div>
    </div>
</div>

<div class="card card-accent-yellow">
    <div class="card-body p-0">
        @forelse ($ratings as $rating)
            <div class="p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex align-items-start gap-3">
                    @php
                        $r = $rating->rating;
                        if ($r >= 4) { $cbg = 'var(--primary-50)'; $cfg = 'var(--primary)'; }
                        elseif ($r <= 2) { $cbg = 'var(--warning-light)'; $cfg = 'var(--warning)'; }
                        else { $cbg = 'var(--secondary-light)'; $cfg = 'var(--secondary-dark)'; }
                    @endphp
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background: {{ $cbg }}; color: {{ $cfg }}; font-weight: 800; font-size: 1.1rem;">
                        {{ $rating->rating }}
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $rating->rating)
                                        <i class="bi bi-star-fill" style="color: var(--secondary); font-size: 1rem;"></i>
                                    @else
                                        <i class="bi bi-star" style="color: var(--gray-300); font-size: 1rem;"></i>
                                    @endif
                                @endfor
                            </div>
                            <small class="text-muted" style="font-size: 0.8rem;">{{ $rating->created_at->format('M d, Y h:i A') }}</small>
                        </div>

                        @if ($rating->start_location && $rating->end_location)
                            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background: var(--gray-50);">
                                <div class="d-flex flex-column align-items-center" style="width: 16px;">
                                    <i class="bi bi-circle-fill" style="color: var(--success); font-size: 0.5rem;"></i>
                                    <div style="width: 2px; height: 16px; background: var(--gray-300);"></div>
                                    <i class="bi bi-geo-alt-fill" style="color: var(--danger); font-size: 0.6rem;"></i>
                                </div>
                                <div style="font-size: 0.8rem;">
                                    <div style="color: var(--gray-600);">{{ $rating->start_location }}</div>
                                    <div style="color: var(--gray-500); font-size: 0.7rem;">to</div>
                                    <div style="color: var(--gray-600);">{{ $rating->end_location }}</div>
                                </div>
                            </div>
                        @endif
                        @if ($rating->reason)
                            <p class="mb-2" style="font-size: 0.9rem; color: var(--gray-600);">{{ $rating->reason }}</p>
                        @endif

                        @if ($rating->proofs->count() > 0)
                            <div>
                                <small class="text-muted" style="font-size: 0.75rem;">Attached proof ({{ $rating->proofs->count() }} file(s)):</small>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @foreach ($rating->proofs as $proof)
                                        @if (str_starts_with($proof->file_type, 'image'))
                                            <a href="{{ route('storage.serve', $proof->file_path) }}" target="_blank">
                                                <img src="{{ route('storage.serve', $proof->file_path) }}"
                                                     alt="{{ $proof->original_name }}"
                                                     style="height: 64px; width: 64px; object-fit: cover;"
                                                     class="rounded border">
                                            </a>
                                        @else
                                            <a href="{{ route('storage.serve', $proof->file_path) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark me-1"></i> {{ $proof->original_name }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($rating->rating <= 2)
                            <div class="mt-3 pt-3 border-top">
                                @if ($rating->response)
                                    <div class="p-3 rounded" style="background: var(--primary-50);">
                                        <small style="font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.03em; font-size: 0.7rem;">
                                            <i class="bi bi-chat-dots me-1"></i> Your Response
                                        </small>
                                        <p class="mb-0 mt-1" style="font-size: 0.85rem; color: var(--gray-700);">{{ $rating->response->message }}</p>
                                        <small class="text-muted" style="font-size: 0.65rem;">{{ $rating->response->created_at->diffForHumans() }}</small>
                                    </div>
                                @else
                                    <form action="{{ route('driver.ratings.respond', $rating) }}" method="POST">
                                        @csrf
                                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600;">
                                            <i class="bi bi-chat-dots me-1"></i> Respond to this complaint
                                        </label>
                                        <textarea class="form-control form-control-sm @error('message') is-invalid @enderror"
                                                  name="message" rows="2" placeholder="Explain your side..." style="font-size: 0.85rem;"></textarea>
                                        @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <button type="submit" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-send me-1"></i> Submit Response
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background: var(--gray-100);">
                    <i class="bi bi-inbox" style="font-size: 2rem; color: var(--gray-300);"></i>
                </div>
                <p class="text-muted mt-2 mb-0">No ratings yet.</p>
            </div>
        @endforelse

        @if ($ratings->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $ratings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
