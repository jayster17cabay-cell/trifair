@extends('layouts.operator')

@section('title', 'Dashboard')

@section('content')
<div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-navy-700 via-navy-600 to-navy-500 p-6 text-white shadow-soft sm:p-8">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[radial-gradient(circle,rgba(245,166,35,0.18)_0%,transparent_70%)]"></div>
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Welcome back, <span class="text-gold">{{ explode(' ', Auth::user()->name)[0] }}</span>
            </h2>
            <p class="mt-1 text-sm text-slate-300">Manage your ratings and QR code here</p>
        </div>
        <div class="flex items-center gap-3 sm:gap-5">
            <div class="text-center">
                <div class="text-3xl font-black text-gold" data-live="averageRating">{{ number_format($averageRating ?? 0, 1) }}</div>
                <div class="mt-1 text-sm" data-live-stars="averageRating">
                    @php $avg = $averageRating ?? 0; @endphp
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill {{ $i <= round($avg) ? 'text-gold' : 'text-white/30' }}"></i>
                    @endfor
                </div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Avg Rating</div>
            </div>
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="text-center">
                <div class="text-3xl font-black" data-live="totalRatings">{{ $totalRatings }}</div>
                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider text-slate-300">Total Ratings</div>
            </div>
        </div>
    </div>
</div>

@if ($totalRatings === 0)
<div id="operatorNoRatingsBanner" class="mb-6 flex items-center gap-3 rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-blue-50 p-4">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500 text-white">
        <i class="bi bi-info-lg"></i>
    </div>
    <div class="text-sky-900">
        <strong>Welcome to TriFair!</strong>
        <div class="text-sm text-sky-800">Print your QR code and display it inside your tricycle so passengers can rate their trip.</div>
    </div>
</div>
@endif

<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    <a href="{{ route('operator.ratings') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold/10 text-lg text-gold-dark transition group-hover:scale-105">
            <i class="bi bi-star-fill"></i>
        </div>
        <span class="text-sm font-semibold text-slate-700">My Ratings</span>
    </a>
    @if ($operator && $operator->qr_code)
    <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" target="_blank" rel="noopener" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600 transition group-hover:scale-105">
            <i class="bi bi-printer-fill"></i>
        </div>
        <span class="text-sm font-semibold text-slate-700">Print QR</span>
    </a>
    @endif
    <a href="{{ route('operator.settings') }}" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-navy-600/10 text-lg text-navy-600 transition group-hover:scale-105">
            <i class="bi bi-gear-fill"></i>
        </div>
        <span class="text-sm font-semibold text-slate-700">Settings</span>
    </a>
    <a href="#" data-tw-modal-open="howToUseModal" class="tw-card group flex flex-col items-center gap-2.5 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-soft">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-lg text-sky-600 transition group-hover:scale-105">
            <i class="bi bi-question-circle-fill"></i>
        </div>
        <span class="text-sm font-semibold text-slate-700">How to Use</span>
    </a>
</div>

<div class="grid gap-5 lg:grid-cols-2">
    <div class="tw-card">
        <div class="tw-card-pad flex items-center justify-between border-b border-slate-100">
            <h3 class="tw-card-title"><i class="bi bi-qr-code mr-1 text-navy-600"></i> Your QR Code</h3>
            @if ($operator && $operator->qr_code)
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" target="_blank" rel="noopener" class="tw-btn tw-btn-sm tw-btn-outline">Print <i class="bi bi-box-arrow-up-right"></i></a>
            @endif
        </div>
        <div class="tw-card-pad text-center">
            @if ($operator && $operator->qr_code)
                <div class="inline-block rounded-2xl border-2 border-slate-100 bg-white p-3 shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" alt="QR Code" class="h-40 w-40">
                </div>
                <div class="mt-3 text-sm font-semibold text-slate-700">
                    {{ Auth::user()->name }}
                    @if ($operator->body_number)
                        <span class="text-slate-500"> ({{ $operator->body_number }})</span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" target="_blank" rel="noopener" class="tw-btn tw-btn-sm tw-btn-navy">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" download="trifair-qr-{{ $operator->qr_code }}.png" class="tw-btn tw-btn-sm tw-btn-outline">
                        <i class="bi bi-download"></i> Save
                    </a>
                </div>
            @else
                <div class="py-4">
                    <i class="bi bi-qr-code text-4xl text-slate-300"></i>
                    <p class="mt-3 text-sm text-slate-500">No QR code assigned yet.</p>
                    <p class="text-xs text-slate-600">Contact your TFRB Officer to get one.</p>
                </div>
            @endif
        </div>
        @if ($operator && $operator->qr_code)
        <div class="rounded-b-2xl border-t border-slate-100 bg-sky-50 px-5 py-3 text-xs font-medium text-sky-600">
            <i class="bi bi-printer mr-1"></i> Print and display inside your tricycle
        </div>
        @endif
    </div>

    <div class="tw-card">
        <div class="tw-card-pad border-b border-slate-100">
            <h3 class="tw-card-title"><i class="bi bi-bar-chart mr-1 text-navy-600"></i> Rating Breakdown</h3>
        </div>
        <div class="tw-card-pad" data-live-list="breakdown">
            @include('partials.dashboard-rating-breakdown')
        </div>
    </div>
</div>

<div id="howToUseModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal">
        <div class="tw-modal-head">
            <h5 class="text-base font-bold text-slate-900"><i class="bi bi-question-circle-fill mr-2 text-navy-600"></i>How to Use TriFair</h5>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="tw-modal-body space-y-4">
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-600/10 text-sm font-extrabold text-navy-600">1</div>
                <div>
                    <strong class="text-sm">Get QR Code</strong>
                    <p class="text-sm text-slate-500">TFRB Officer assigns you a QR code — it appears on your dashboard.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold/10 text-sm font-extrabold text-gold-dark">2</div>
                <div>
                    <strong class="text-sm">Print & Display</strong>
                    <p class="text-sm text-slate-500">Tap "Print QR" to get a large copy. Tape it inside your tricycle.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-extrabold text-emerald-600">3</div>
                <div>
                    <strong class="text-sm">Passengers Scan</strong>
                    <p class="text-sm text-slate-500">They scan the QR and rate their trip — helping you build your reputation.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-sm font-extrabold text-red-600">4</div>
                <div>
                    <strong class="text-sm">Respond</strong>
                    <p class="text-sm text-slate-500">Low ratings (1-2 stars) let you reply. Go to "My Ratings" to respond.</p>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">
            <button type="button" class="tw-btn tw-btn-navy w-full" data-tw-modal-close>Got it!</button>
        </div>
    </div>
</div>

@include('partials.dashboard-live')
@endsection
