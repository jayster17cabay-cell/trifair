{{--
    Franchise status + reporting-period selector for the operator app.
    Requires: $franchise (current FranchiseTerm or null), $period (string:
    all|year|month|week), $scopeRoute (route name to submit back to, e.g.
    'operator.dashboard').
--}}
@php
    $period = $period ?? 'all';
    $periodLabels = [
        'all' => 'All Time',
        'year' => 'This Year',
        'month' => 'This Month',
        'week' => 'This Week',
    ];
    $franchiseValidUntil = $franchise?->valid_until;
    $franchiseExpired = $franchise ? $franchise->isExpired() : false;
    $franchiseDaysLeft = $franchiseValidUntil && !$franchiseExpired
        ? (int) now()->startOfDay()->diffInDays($franchiseValidUntil->copy()->startOfDay(), false)
        : null;
@endphp
<div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
    <div class="min-w-0">
        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
            <i class="bi bi-patch-check mr-1"></i>Franchise
        </div>
        @if ($franchise)
            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-mono text-sm font-bold text-slate-800">{{ $franchise->franchise_no ?? 'No number' }}</span>
                @if ($franchiseValidUntil)
                    @if ($franchiseExpired)
                        <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle-fill"></i>Expired {{ $franchiseValidUntil->format('M d, Y') }}</span>
                    @else
                        <span class="tw-badge tw-badge-green"><i class="bi bi-patch-check-fill"></i>Valid until {{ $franchiseValidUntil->format('M d, Y') }}</span>
                        @if ($franchiseDaysLeft !== null)
                            <span class="text-[11px] font-semibold text-slate-500">
                                @if ($franchiseDaysLeft <= 0)
                                    expires today
                                @elseif ($franchiseDaysLeft === 1)
                                    1 day left
                                @else
                                    {{ $franchiseDaysLeft }} days left
                                @endif
                            </span>
                        @endif
                    @endif
                @else
                    <span class="tw-badge tw-badge-gray"><i class="bi bi-infinity"></i>No expiry</span>
                @endif
            </div>
        @else
            <div class="mt-1 text-sm font-bold text-slate-800">No active franchise</div>
        @endif
    </div>
    <form method="GET" action="{{ route($scopeRoute) }}" class="flex items-center gap-2">
        <label for="operatorPeriod" class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
            <i class="bi bi-calendar3 mr-1"></i>Show
        </label>
        <select name="period" id="operatorPeriod" class="tw-select py-1.5 text-xs" onchange="this.form.submit()">
            @foreach ($periodLabels as $value => $label)
                <option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>
