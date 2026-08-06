@extends('layouts.operator')

@section('title', 'My Ratings')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title">My Ratings</h1>
        <p class="tw-page-sub">All feedback received from passengers</p>
    </div>
    <div class="flex gap-2">
        <div class="rounded-xl bg-gold/10 px-3 py-2 text-center">
            <small class="block text-[0.7rem] font-bold uppercase tracking-widest text-gold-dark">Average</small>
            <span class="text-xl font-black text-navy-600">{{ number_format($averageRating ?? 0, 1) }}</span>
        </div>
        <div class="rounded-xl bg-navy-600/10 px-3 py-2 text-center">
            <small class="block text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Total</small>
            <span class="text-xl font-black text-navy-600">{{ $totalRatings }}</span>
        </div>
    </div>
</div>

@if($ratings->count() > 0)
<div class="tw-alert tw-alert-navy mb-4">
    <i class="bi bi-info-circle-fill mt-0.5"></i>
    <span class="text-sm text-slate-600">Low ratings (1-2 stars) allow you to respond and explain your side.</span>
</div>
@endif

<div class="tw-card tw-card-accent-gold">
    <div class="divide-y divide-slate-100">
        @forelse ($ratings as $rating)
            <div class="p-5">
                <div class="flex items-start gap-3">
                    @php
                        $r = $rating->rating;
                        if ($r >= 4) { $cbg = 'var(--primary-50)'; $cfg = 'var(--primary)'; }
                        elseif ($r <= 2) { $cbg = 'var(--warning)'; $cfg = '#fff'; }
                        else { $cbg = 'var(--secondary-50)'; $cfg = 'var(--secondary-dark)'; }
                    @endphp
                    <div class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg font-extrabold" style="background: {{ $cbg }}; color: {{ $cfg }};">
                        {{ $rating->rating }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-1">
                            <div class="flex gap-0.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $rating->rating)
                                        <i class="bi bi-star-fill text-base" style="color: var(--secondary);"></i>
                                    @else
                                        <i class="bi bi-star text-base" style="color: var(--gray-300);"></i>
                                    @endif
                                @endfor
                            </div>
                            <small class="text-xs text-slate-400">{{ $rating->created_at->format('M d, Y h:i A') }}</small>
                        </div>

                        @if ($rating->complaint_type)
                            <div class="mb-2">
                                <span class="tw-badge tw-badge-red">
                                    <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                                </span>
                            </div>
                        @endif

                        @if ($rating->reason)
                            <p class="mb-2 text-sm text-slate-600">{{ $rating->reason }}</p>
                        @endif

                        @if ($rating->proofs->count() > 0)
                            <div class="mb-2">
                                <small class="text-xs text-slate-400">Attached proof ({{ $rating->proofs->count() }} file(s)):</small>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach ($rating->proofs as $proof)
                                        @if (str_starts_with($proof->file_type, 'image'))
                                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener">
                                                <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                                     alt="{{ $proof->original_name }}"
                                                     class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                            </a>
                                        @else
                                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener"
                                               class="tw-btn tw-btn-sm tw-btn-outline">
                                                <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($rating->rating <= 2)
                            <div class="mt-3 border-t border-slate-100 pt-3">
                                @if ($rating->response)
                                    <div class="rounded-xl bg-navy-600/10 p-3">
                                        <small class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">
                                            <i class="bi bi-chat-dots mr-1"></i> Your Response
                                        </small>
                                        <p class="mb-0 mt-1 text-sm text-slate-700">{{ $rating->response->message }}</p>
                                        <small class="text-xs text-slate-400">{{ $rating->response->created_at->diffForHumans() }}</small>
                                    </div>
                                @else
                                    <div class="rounded-xl bg-amber-50 p-3">
                                        <small class="text-[0.7rem] font-bold uppercase tracking-widest text-amber-600">
                                            <i class="bi bi-exclamation-triangle mr-1"></i> This rating needs your response
                                        </small>
                                        <p class="my-1 text-[0.8rem] text-slate-500">Explain your side to the passenger and TFRB Officer.</p>
                                        <form action="{{ route('operator.ratings.respond', $rating) }}" method="POST">
                                            @csrf
                                            <textarea class="tw-textarea text-sm @error('message') is-invalid @enderror"
                                                      name="message" rows="2" placeholder="Write your explanation here..."></textarea>
                                            @error('message') <span class="tw-error-text">{{ $message }}</span> @enderror
                                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy mt-2">
                                                <i class="bi bi-send"></i> Submit Response
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="tw-empty py-10">
                <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                <p class="mt-2 text-sm text-slate-500">No ratings yet.</p>
                <p class="text-[0.85rem] text-slate-400">Once passengers scan your QR code and rate their trip, their feedback will appear here.</p>
                <a href="{{ route('operator.dashboard') }}" class="tw-btn tw-btn-sm tw-btn-navy mt-3">
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
