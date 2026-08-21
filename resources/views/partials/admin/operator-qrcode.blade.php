{{-- Shared operator QR code page body. Requires: $routePrefix, $operator, $url --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-qr-code mr-2 text-gold"></i>QR Code</h1>
        <p class="tw-page-sub">{{ $operator->user->name }}</p>
    </div>
    <a href="{{ route($routePrefix . '.operators') }}" class="tw-btn tw-btn-outline">
        <i class="bi bi-arrow-left"></i>Back
    </a>
</div>

<div class="mx-auto max-w-lg">
    <div class="tw-card overflow-hidden">
        <div class="bg-gradient-to-br from-navy-600 to-navy-500 px-6 py-6 text-center text-white">
            <div class="mx-auto mb-2 flex h-14 w-14 items-center justify-center rounded-2xl bg-gold/15 text-2xl text-gold">
                <i class="bi bi-person"></i>
            </div>
            <h3 class="text-lg font-bold">{{ $operator->user->name }}</h3>
            <div class="mt-0.5 text-sm text-slate-300">
                {{ $operator->license_number ?? 'No License' }} &middot; {{ $operator->plate_number ?? '' }}
            </div>
        </div>
        <div class="px-6 py-8 text-center">
            <div class="mb-4 inline-block rounded-2xl border-2 border-slate-100 bg-white p-5">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($url) }}"
                     alt="QR Code for {{ $operator->user->name }}"
                     style="width: 200px; height: 200px; display: block;">
            </div>
            <p class="mb-4 text-sm text-slate-500">Passengers scan this QR code to rate the operator</p>
            <div class="mb-5 rounded-xl bg-slate-50 px-4 py-3">
                <div class="mb-0.5 text-[0.68rem] font-semibold uppercase tracking-wider text-slate-500">Rating URL</div>
                <a href="{{ $url }}" class="break-all text-xs font-semibold text-emerald-600">{{ $url }}</a>
            </div>
            <button type="button" class="tw-btn tw-btn-gold px-5" onclick="window.print()">
                <i class="bi bi-printer"></i>Print QR Code
            </button>
        </div>
        <div class="border-t border-slate-100 bg-slate-50 px-6 py-3 text-center text-sm text-slate-500">
            <i class="bi bi-info-circle mr-1"></i>Display this QR code on the operator's motorcycle
        </div>
    </div>
</div>
