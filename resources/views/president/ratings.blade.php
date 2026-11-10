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
@endphp

{{-- Page header — who this page is for and which TODA the numbers come from --}}
<div class="tw-card mb-4 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <div class="min-w-0">
            <div class="text-[11px] font-bold uppercase tracking-widest text-gold">TODA President</div>
            <h1 class="text-lg font-extrabold leading-tight text-white">My Own Ratings &amp; Complaints</h1>
            <p class="mt-0.5 text-xs text-white/70">Everything passengers said about <strong>your own driving</strong> — ratings, complaints and your responses. Members' ratings are viewed per member in the Members section.</p>
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
        <span class="text-sm">Low ratings (1–2 stars) let you respond and explain your side. Tap a rating to open its full details.</span>
    </div>
@endif

<div class="tw-card tw-card-accent-gold overflow-hidden">
    <div class="divide-y divide-slate-100">
        @forelse ($ratings as $rating)
            @php
                $r = (int) $rating->rating;
                if ($r >= 4) { $cClass = 'bg-blue-50 text-navy-700'; }
                elseif ($r <= 2) { $cClass = 'bg-amber-600 text-white'; }
                else { $cClass = 'bg-gold-50 text-gold-700'; }

                $isComplaint = (bool) $rating->complaint_type;
                $preview = $rating->complaint_details ?: $rating->reason;
            @endphp
            <a href="{{ route('president.ratings.show', array_filter([$rating, 'filter' => $filter !== 'all' ? $filter : null])) }}"
               class="group flex items-start gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:bg-slate-50 sm:px-5 no-underline"
               aria-label="Open rating{{ $rating->reference_number ? ' ' . $rating->reference_number : '' }} details">
                <div class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-full text-base font-extrabold {{ $cClass }}">
                    {{ $rating->rating }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="flex gap-0.5" aria-label="{{ $r }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $r ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }} text-[0.95rem]"></i>
                                @endfor
                            </span>
                            @if ($isComplaint)
                                <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}</span>
                                @if (isset($statusText[$rating->status_label]))
                                    <span class="{{ $statusTone[$rating->status_label] }}">
                                        <i class="bi {{ $statusIcon[$rating->status_label] }}"></i>{{ $statusText[$rating->status_label] }}
                                    </span>
                                @endif
                            @elseif ($rating->response)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                    <i class="bi bi-check-circle-fill"></i> Responded
                                </span>
                            @elseif ($r <= 2)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                    <i class="bi bi-exclamation-circle"></i> Needs your response
                                </span>
                            @endif
                        </div>
                        <small class="whitespace-nowrap text-xs text-slate-400">{{ $rating->created_at->format('M d, Y h:i A') }}</small>
                    </div>

                    @if ($preview)
                        <p class="mt-1 text-sm leading-snug text-slate-600 line-clamp-2">{{ $preview }}</p>
                    @endif

                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                        @if ($rating->reference_number)
                            <span class="font-mono text-[10px] font-bold text-navy-600/80">{{ $rating->reference_number }}</span>
                        @endif
                        @if ($rating->start_location || $rating->end_location)
                            <span class="inline-flex min-w-0 items-center gap-1">
                                <i class="bi bi-geo-alt"></i>
                                <span class="truncate">{{ $rating->start_location ?: '—' }} <i class="bi bi-arrow-right mx-0.5"></i> {{ $rating->end_location ?: '—' }}</span>
                            </span>
                        @endif
                        @if ($rating->proofs->count() > 0)
                            <span class="inline-flex items-center gap-1"><i class="bi bi-paperclip"></i> {{ $rating->proofs->count() }} file(s)</span>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-col items-center gap-1.5 pt-0.5">
                    <i class="bi bi-chevron-right text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-navy-500" aria-hidden="true"></i>
                </div>
            </a>
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
