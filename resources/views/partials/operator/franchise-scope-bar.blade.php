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
@endphp
<div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
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
            <div class="mt-0.5 truncate text-sm font-bold text-slate-800">No active franchise</div>
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
