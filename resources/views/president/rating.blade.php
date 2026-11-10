@extends('layouts.president')

@section('title', ($rating->reference_number ? 'Rating ' . $rating->reference_number : 'Rating Details'))

@section('content')
@php
    $r = (int) $rating->rating;
    if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
    elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
    else { $cClass = 'bg-gold-50 text-gold-700'; }

    $backFilter = request()->query('filter');
    $backUrl = in_array($backFilter, ['complaints', 'needs_response'], true)
        ? route('president.ratings', ['filter' => $backFilter])
        : route('president.ratings');

    $statusTone = [
        'Solved' => 'tw-badge tw-badge-navy',
        'Open' => 'tw-badge tw-badge-amber',
        'Rejected' => 'tw-badge tw-badge-red',
    ];
    $statusText = [
        'Solved' => 'Solved',
        'Open' => 'Under review',
        'Rejected' => 'Rejected',
    ];
    $statusIcon = [
        'Solved' => 'bi-patch-check-fill',
        'Open' => 'bi-clock-fill',
        'Rejected' => 'bi-x-circle-fill',
    ];
    $isComplaint = (bool) $rating->complaint_type;
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-navy-600 transition hover:text-navy-800 no-underline">
            <i class="bi bi-arrow-left"></i> Back to my ratings
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Rating Details</h1>
            @if ($rating->reference_number)
                <span class="rounded-md border border-navy-600/10 bg-navy-600/5 px-2 py-0.5 font-mono text-[11px] font-bold text-navy-700">{{ $rating->reference_number }}</span>
            @endif
        </div>
        <p class="mt-0.5 text-sm text-slate-500">Full feedback from the passenger and your response — scoped to {{ $toda->name }}.</p>
    </div>
    <span class="tw-badge tw-badge-blue"><i class="bi bi-geo-alt"></i>{{ $toda->name }}</span>
</div>

<div class="tw-card tw-card-accent-gold overflow-hidden">
    {{-- Summary strip: score, stars, review state and timestamp --}}
    <div class="flex flex-wrap items-start gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">
        <div class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full text-xl font-extrabold {{ $cClass }}">
            {{ $rating->rating }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="flex gap-0.5" aria-label="{{ $r }} out of 5 stars">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-base"></i>
                    @endfor
                </span>
                @if ($isComplaint)
                    <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}</span>
                    @if (isset($statusText[$rating->status_label]))
                        <span class="{{ $statusTone[$rating->status_label] }}">
                            <i class="bi {{ $statusIcon[$rating->status_label] }}"></i>{{ $statusText[$rating->status_label] }}
                        </span>
                    @endif
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-400">
                <i class="bi bi-calendar3 mr-1"></i>{{ $rating->created_at->format('M d, Y \a\t h:i A') }}
            </p>
        </div>
    </div>

    <div class="space-y-5 p-4 sm:p-5">
        {{-- Complaint details / feedback text --}}
        @if ($rating->complaint_details || $rating->reason)
            <section>
                <div class="tw-stat-label mb-1.5">
                    <i class="bi {{ $isComplaint ? 'bi-chat-left-quote' : 'bi-chat-quote' }} mr-1 text-amber-500"></i>
                    {{ $isComplaint ? 'Complaint details' : 'Passenger feedback' }}
                </div>
                @if ($rating->complaint_details)
                    <p class="rounded-lg border border-red-100 bg-red-50/60 px-3.5 py-3 text-sm leading-relaxed text-slate-700">"{{ $rating->complaint_details }}"</p>
                @endif
                @if ($rating->reason && $rating->reason !== $rating->complaint_details)
                    <p class="mt-2 rounded-lg border border-slate-100 bg-slate-50/70 px-3.5 py-3 text-sm leading-relaxed text-slate-600">{{ $rating->reason }}</p>
                @endif
            </section>
        @endif

        {{-- Trip route --}}
        @if ($rating->start_location || $rating->end_location)
            <section>
                <div class="tw-stat-label mb-1.5"><i class="bi bi-signpost-2 mr-1 text-blue-500"></i>Trip route</div>
                <div class="flex flex-col gap-1.5 rounded-lg border border-blue-100 bg-blue-50/50 p-2.5 sm:flex-row sm:items-center">
                    <span class="inline-flex min-w-0 items-center gap-1.5 break-words rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600">
                        <i class="bi bi-circle-fill text-[0.55rem] text-emerald-600"></i> {{ $rating->start_location ?: '—' }}
                    </span>
                    <i class="bi bi-arrow-right text-[0.7rem] text-slate-300"></i>
                    <span class="inline-flex min-w-0 items-center gap-1.5 break-words rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600">
                        <i class="bi bi-circle-fill text-[0.55rem] text-red-600"></i> {{ $rating->end_location ?: '—' }}
                    </span>
                </div>
            </section>
        @endif

        {{-- Passenger evidence --}}
        @if ($rating->proofs->count() > 0)
            <section>
                <div class="tw-stat-label mb-1.5"><i class="bi bi-paperclip mr-1 text-amber-500"></i>Attached proof ({{ $rating->proofs->count() }} file(s))</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($rating->proofs as $proof)
                        @if (str_starts_with($proof->file_type, 'image'))
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener">
                                <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                     alt="{{ $proof->original_name }}"
                                     class="h-20 w-20 rounded-lg border border-slate-200 object-cover transition hover:opacity-90">
                            </a>
                        @else
                            <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener"
                               class="tw-btn tw-btn-sm tw-btn-outline">
                                <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Operator (president's) response --}}
        @if ($rating->response)
            <section class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <i class="bi bi-reply-fill text-navy-600"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Your Response</span>
                    <span class="ml-auto text-xs text-slate-400">{{ $rating->response->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm leading-relaxed text-slate-700">{{ $rating->response->message }}</p>
                @if ($rating->operatorProofs->count() > 0)
                    <div class="mt-2.5 border-t border-navy-600/10 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-navy-600"><i class="bi bi-paperclip mr-1"></i>Attached Proof</small>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            @foreach ($rating->operatorProofs as $proof)
                                @if (str_starts_with($proof->file_type, 'image'))
                                    <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener">
                                        <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                             alt="{{ $proof->original_name }}"
                                             class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                    </a>
                                @else
                                    <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener" class="tw-btn tw-btn-sm tw-btn-outline">
                                        <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @elseif ($r <= 2)
            <section class="rounded-xl border border-amber-100 bg-amber-50/80 p-3.5">
                <div class="flex items-center gap-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-amber-600">
                    <i class="bi bi-exclamation-triangle"></i> This rating needs your response
                </div>
                <p class="mt-1 text-[0.8rem] leading-snug text-slate-500">Explain your side to the passenger and TFRB Officer. You may attach proof (images or PDF).</p>
                <form action="{{ route('president.ratings.respond', $rating) }}" method="POST" enctype="multipart/form-data" data-submit-loading class="mt-2">
                    @csrf
                    <textarea class="tw-textarea text-sm @error('message') is-invalid @enderror"
                              name="message" rows="3" aria-label="Your explanation" placeholder="Write your explanation here..."></textarea>
                    @error('message') <span class="tw-error-text" role="alert">{{ $message }}</span> @enderror
                    <label class="mt-2 flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white/60 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-navy-500 hover:text-navy-600">
                        <i class="bi bi-paperclip"></i> Attach proof (jpg, png, pdf — max 5 files)
                        <input type="file" name="files[]" multiple accept="image/*,.pdf" class="sr-only" data-proof-input>
                    </label>
                    <div class="mt-1 text-[0.7rem] text-slate-400" data-proof-files></div>
                    @error('files') <span class="tw-error-text" role="alert">{{ $message }}</span> @enderror
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold mt-2.5">
                        <i class="bi bi-send"></i> Submit Response
                    </button>
                </form>
            </section>
        @endif

        {{-- Resolution details when the complaint has been solved or rejected. --}}
        @if ($isComplaint && in_array($rating->status_label, ['Solved', 'Rejected'], true))
            @php $isSolved = $rating->status_label === 'Solved'; @endphp
            <section class="rounded-xl border {{ $isSolved ? 'border-emerald-100 bg-emerald-50/60' : 'border-red-100 bg-red-50/60' }} p-3.5">
                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                    <i class="bi {{ $isSolved ? 'bi-patch-check-fill text-emerald-600' : 'bi-x-circle-fill text-red-500' }}"></i>
                    <span class="text-[0.7rem] font-bold uppercase tracking-widest {{ $isSolved ? 'text-emerald-700' : 'text-red-600' }}">
                        {{ $isSolved ? 'Resolution Details' : 'Reason for Rejection' }}
                    </span>
                </div>
                @if ($rating->resolution_note)
                    <p class="text-sm leading-relaxed text-slate-700">{{ $rating->resolution_note }}</p>
                @endif

                @if ($rating->hasEscalationChecklist())
                    <div class="mt-2.5 border-t border-slate-200 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-500"><i class="bi bi-list-check mr-1"></i>Panel Follow-up Checklist</small>
                        <ul class="mt-1.5 grid gap-1 sm:grid-cols-2">
                            @foreach ($rating->panelChecklistItems() as $checklistItem)
                                <li class="flex items-start gap-2 text-xs leading-relaxed {{ $checklistItem['checked'] ? 'text-slate-700' : 'text-slate-400' }}">
                                    <i class="bi {{ $checklistItem['checked'] ? 'bi-check2-circle-fill text-emerald-600' : 'bi-circle text-slate-300' }} mt-0.5 shrink-0"></i>
                                    <span>{{ $checklistItem['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($rating->resolutionProofs->count() > 0)
                    <div class="mt-2.5 border-t border-slate-200 pt-2">
                        <small class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-500"><i class="bi bi-paperclip mr-1"></i>Resolution Proof</small>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            @foreach ($rating->resolutionProofs as $proof)
                                @if (str_starts_with($proof->file_type, 'image'))
                                    <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}" target="_blank" rel="noopener">
                                        <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                             alt="{{ $proof->original_name }}"
                                             class="h-16 w-16 rounded-lg border border-emerald-200 object-cover">
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

                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-200 pt-2 text-[0.7rem] text-slate-500">
                    @if ($rating->reviewed_by_display)
                        <span><i class="bi bi-person-badge mr-1"></i>{{ $isSolved ? 'Solved by' : 'Reviewed by' }} {{ $rating->reviewed_by_display }}</span>
                    @endif
                    @if ($rating->resolution_at)
                        <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
            </section>
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
