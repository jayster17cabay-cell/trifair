{{--
    Franchise status + current-term / all-time switch for the operator app.
    Requires: $franchise (current FranchiseTerm or null), $allTime (bool),
    $scopeRoute (route name to link back to, e.g. 'operator.dashboard').
--}}
<div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
    <div class="min-w-0">
        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
            <i class="bi bi-patch-check mr-1"></i>Franchise
        </div>
        @if ($franchise)
            <div class="mt-0.5 truncate text-sm font-bold text-slate-800">
                <span class="font-mono">{{ $franchise->franchise_no ?? 'No number' }}</span>
                @if ($franchise->valid_until)
                    <span class="font-normal text-slate-500">· valid until {{ $franchise->valid_until->format('M d, Y') }}</span>
                @else
                    <span class="font-normal text-slate-500">· no expiry</span>
                @endif
            </div>
        @else
            <div class="mt-0.5 text-sm text-slate-500">No franchise yet</div>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route($scopeRoute) }}" class="{{ $allTime ? 'tw-chip' : 'tw-chip tw-chip-active' }}">
            <i class="bi bi-clock-history"></i> Current Term
        </a>
        <a href="{{ route($scopeRoute, ['all_time' => 1]) }}" class="{{ $allTime ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi bi-infinity"></i> All Time Record
        </a>
    </div>
</div>
