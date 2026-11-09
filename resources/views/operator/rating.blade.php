@extends('layouts.operator')

@section('title', 'Rating Details')

@section('header-body')
<div class="op-header-greet">
    <h1 class="op-header-title">Rating Details</h1>
    <p class="op-header-sub">Full feedback and your response</p>
</div>
@endsection

@section('content')
@php
    $r = $rating->rating;
    if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
    elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
    else { $cClass = 'bg-gold-50 text-gold-700'; }
@endphp
<div class="mb-4">
    <a href="{{ route('operator.ratings') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-navy-600 hover:text-navy-800">
        <i class="bi bi-arrow-left"></i> Back to all ratings
    </a>
</div>

<div class="tw-card tw-card-accent-gold">
    <div class="p-5">
        <div class="flex items-start gap-3">
            <div class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg font-extrabold {{ $cClass }}">
                {{ $rating->rating }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="mb-1 flex flex-wrap items-center justify-between gap-1">
                    <div class="flex gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-base"></i>
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
            </div>
        </div>

        @if ($rating->reason)
            <p class="mt-3 text-sm text-slate-600">{{ $rating->reason }}</p>
        @endif

        @if ($rating->complaint_details)
            <p class="mt-2 text-sm text-slate-600">{{ $rating->complaint_details }}</p>
        @endif

        @if ($rating->start_location || $rating->end_location)
            <p class="mt-2 text-sm text-slate-500">{{ $rating->start_location }} → {{ $rating->end_location }}</p>
        @endif

        @if ($rating->rating <= 2 && $rating->proofs->count() > 0)
            <div class="mt-3">
                <small class="text-xs text-slate-400">Attached proof ({{ $rating->proofs->count() }} file(s)):</small>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach ($rating->proofs as $proof)
                        @if (str_starts_with($proof->file_type, 'image'))
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                     alt="{{ $proof->original_name }}"
                                     class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                            </a>
                        @else
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                               class="tw-btn tw-btn-sm tw-btn-outline">
                                <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($rating->rating <= 2)
            <div class="mt-4 border-t border-slate-100 pt-4">
                @if ($rating->response)
                    <div class="rounded-xl bg-navy-600/10 p-3">
                        <small class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">
                            <i class="bi bi-chat-dots mr-1"></i> Your Response
                        </small>
                        <p class="mb-0 mt-1 text-sm text-slate-700">{{ $rating->response->message }}</p>
                        @if ($rating->operatorProofs->count() > 0)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($rating->operatorProofs as $proof)
                                    @if (str_starts_with($proof->file_type, 'image'))
                                        <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                            <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                                 alt="{{ $proof->original_name }}"
                                                 class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                                        </a>
                                    @else
                                        <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" class="tw-btn tw-btn-sm tw-btn-outline">
                                            <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <small class="mt-1 block text-xs text-slate-400">{{ $rating->response->created_at->diffForHumans() }}</small>
                    </div>
                @else
                    <div class="rounded-xl bg-amber-50 p-3">
                        <small class="text-[0.7rem] font-bold uppercase tracking-widest text-amber-600">
                            <i class="bi bi-exclamation-triangle mr-1"></i> This rating needs your response
                        </small>
                        <p class="my-1 text-[0.8rem] text-slate-500">Explain your side to the passenger and TFRB Officer. You may attach proof (images or PDF).</p>
                        <form action="{{ route('operator.ratings.respond', $rating) }}" method="POST" enctype="multipart/form-data" data-submit-loading>
                            @csrf
                            <textarea class="tw-textarea text-sm @error('message') is-invalid @enderror"
                                      name="message" rows="3" aria-label="Your explanation" placeholder="Write your explanation here..."></textarea>
                            @error('message') <span class="tw-error-text" role="alert">{{ $message }}</span> @enderror
                            <label class="mt-2 flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-navy-500 hover:text-navy-600">
                                <i class="bi bi-paperclip"></i> Attach proof (jpg, png, pdf — max 5 files)
                                <input type="file" name="files[]" multiple accept="image/*,.pdf" class="sr-only" data-proof-input>
                            </label>
                            <div class="mt-1 text-[0.7rem] text-slate-400" data-proof-files></div>
                            @error('files') <span class="tw-error-text" role="alert">{{ $message }}</span> @enderror
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold mt-2">
                                <i class="bi bi-send"></i> Submit Response
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('form[data-submit-loading]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type="submit"]');
            if (!button) return;
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-arrow-repeat animate-spin" aria-hidden="true"></i> Sending...';
        });
    });
</script>
@endpush