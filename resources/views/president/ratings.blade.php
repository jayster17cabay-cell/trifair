@extends('layouts.president')

@section('title', 'My Ratings & Complaints')

@section('content')
@php
    $counts = $stats['counts'];
    $tabs = [
        'all' => ['label' => 'All Ratings', 'icon' => 'bi-star', 'count' => $counts['all']],
        'complaints' => ['label' => 'Complaints', 'icon' => 'bi-flag', 'count' => $counts['complaints']],
        'needs_response' => ['label' => 'Needs Response', 'icon' => 'bi-exclamation-circle', 'count' => $counts['needs_response']],
    ];

    // Review state of a complaint, phrased for the person it was filed against.
    $statusTone = [
        'Solved' => 'tw-badge tw-badge-navy',
        'Accepted' => 'tw-badge tw-badge-green',
        'Rejected' => 'tw-badge tw-badge-red',
        'Pending' => 'tw-badge tw-badge-amber',
    ];
    $statusText = [
        'Solved' => 'Solved',
        'Accepted' => 'Accepted',
        'Rejected' => 'Rejected',
        'Pending' => 'Under review',
    ];
@endphp

{{-- Page header — who this page is for and which TODA the numbers come from --}}
<div class="tw-card mb-4 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <div class="min-w-0">
            <div class="text-[11px] font-bold uppercase tracking-widest text-gold">TODA President</div>
            <h1 class="text-lg font-extrabold leading-tight text-white">My Ratings &amp; Complaints</h1>
            <p class="mt-0.5 text-xs text-white/70">Everything passengers said about your own driving — ratings, complaints and your responses.</p>
        </div>
        <span class="tw-badge !bg-white/10 !text-white"><i class="bi bi-geo-alt mr-1"></i>{{ $toda->name }}</span>
    </div>
</div>

{{-- Headline numbers — each card jumps to the matching filter --}}
<div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
    @include('partials.dashboard-kpi', ['kpi' => [
        'href' => route('president.ratings'),
        'icon' => 'bi-star-fill',
        'value' => $stats['average'] !== null ? number_format($stats['average'], 1) : '—',
        'label' => 'Avg Rating',
    ]])
    @include('partials.dashboard-kpi', ['kpi' => [
        'href' => route('president.ratings'),
        'icon' => 'bi-star',
        'value' => $stats['total'],
        'label' => 'Total Ratings',
    ]])
    @include('partials.dashboard-kpi', ['kpi' => [
        'href' => route('president.ratings', ['filter' => 'complaints']),
        'icon' => 'bi-flag',
        'value' => $counts['complaints'],
        'label' => 'Complaints',
    ]])
    @include('partials.dashboard-kpi', ['kpi' => [
        'href' => route('president.ratings', ['filter' => 'needs_response']),
        'icon' => 'bi-exclamation-triangle',
        'value' => $counts['needs_response'],
        'label' => 'Needs Response',
    ]])
</div>

{{-- Filter tabs --}}
<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach ($tabs as $key => $tab)
        @php $activeTab = $filter === $key; @endphp
        <a href="{{ $key === 'all' ? route('president.ratings') : route('president.ratings', ['filter' => $key]) }}"
           @if ($activeTab) aria-current="page" @endif
           class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $activeTab ? 'border-navy-700 bg-navy-700 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-500 hover:text-navy-700' }}">
            <i class="bi {{ $tab['icon'] }}"></i>
            {{ $tab['label'] }}
            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold {{ $activeTab ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $tab['count'] }}</span>
        </a>
    @endforeach
</div>

@if ($counts['needs_response'] > 0)
    <div class="tw-alert tw-alert-navy mb-4">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <span class="text-sm">Low ratings (1–2 stars) let you respond and explain your side to the passenger and the TFRB.</span>
    </div>
@endif

<div class="tw-card tw-card-accent-gold">
    <div class="divide-y divide-slate-100">
        @forelse ($ratings as $rating)
            @php
                $r = $rating->rating;
                if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
                elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
                else { $cClass = 'bg-gold-50 text-gold-700'; }
            @endphp
            <div class="p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg font-extrabold {{ $cClass }}">
                        {{ $rating->rating }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <div class="flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $rating->rating ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-base"></i>
                                    @endfor
                                </div>
                                @if ($rating->complaint_type)
                                    <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}</span>
                                @endif
                                @if ($rating->complaint_type && isset($statusText[$rating->status_label]))
                                    <span class="{{ $statusTone[$rating->status_label] }}"><i class="bi bi-clipboard-check"></i>{{ $statusText[$rating->status_label] }}</span>
                                @endif
                            </div>
                            <small class="text-xs text-slate-400">{{ $rating->created_at->format('M d, Y h:i A') }}</small>
                        </div>

                        @if ($rating->complaint_type && $rating->complaint_details)
                            <p class="mb-2 rounded-lg border border-red-100 bg-red-50/60 p-2.5 text-sm leading-relaxed text-slate-700">"{{ $rating->complaint_details }}"</p>
                        @endif

                        @if ($rating->reason && $rating->reason !== $rating->complaint_details)
                            <p class="mb-2 text-sm text-slate-600">{{ $rating->reason }}</p>
                        @endif

                        @if ($rating->start_location || $rating->end_location)
                            <p class="mb-2 text-xs text-slate-400">
                                <i class="bi bi-geo-alt mr-1"></i>{{ $rating->start_location ?: '—' }} <i class="bi bi-arrow-right mx-0.5"></i> {{ $rating->end_location ?: '—' }}
                            </p>
                        @endif

                        @if ($rating->proofs->count() > 0)
                            <div class="mb-2">
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

                        @if ($rating->response)
                            <div class="mt-3 rounded-xl bg-navy-600/10 p-3">
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
                        @elseif ($rating->rating <= 2)
                            <div class="mt-3 rounded-xl bg-amber-50 p-3">
                                <small class="text-[0.7rem] font-bold uppercase tracking-widest text-amber-600">
                                    <i class="bi bi-exclamation-triangle mr-1"></i> This rating needs your response
                                </small>
                                <p class="my-1 text-[0.8rem] text-slate-500">Explain your side to the passenger and TFRB Officer. You may attach proof (images or PDF).</p>
                                <form action="{{ route('president.ratings.respond', $rating) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <textarea class="tw-textarea text-sm @error('message') is-invalid @enderror"
                                              name="message" rows="2" aria-label="Your explanation" placeholder="Write your explanation here..."></textarea>
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
                </div>
            </div>
        @empty
            <div class="tw-empty py-10">
                <div class="tw-empty-icon"><i class="bi {{ $filter === 'complaints' ? 'bi-flag' : ($filter === 'needs_response' ? 'bi-check2-all' : 'bi-star') }}"></i></div>
                @if ($filter === 'complaints')
                    <p class="mt-2 text-sm font-semibold text-slate-600">No complaints yet.</p>
                    <p class="text-[0.85rem] text-slate-400">No passenger filed a complaint against you in {{ $toda->name }}.</p>
                @elseif ($filter === 'needs_response')
                    <p class="mt-2 text-sm font-semibold text-slate-600">All caught up.</p>
                    <p class="text-[0.85rem] text-slate-400">Every low rating already carries your response.</p>
                @else
                    <p class="mt-2 text-sm font-semibold text-slate-600">No ratings yet.</p>
                    <p class="text-[0.85rem] text-slate-400">Once passengers scan your QR code and rate your trip, their feedback will appear here.</p>
                    <a href="{{ route('president.dashboard') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-3">
                        <i class="bi bi-grid-1x2"></i> Go to Overview
                    </a>
                @endif
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
