{{-- Shared complaints page body. Requires: $routePrefix, $complaints, $filter,
     $openCount, $rejectedCount, $solvedCount, $totalCount, $sourceCounts. --}}
@php
    $presidentMode = $presidentMode ?? false;
    $actor = $actor ?? (auth()->check() && auth()->user()->role === 'operator_president' ? 'president' : 'staff');
    $monitoringComplaints = $monitoringComplaints ?? null;
    $monitoringSourceCounts = $monitoringSourceCounts ?? [];
    $monitoringTotalCount = $monitoringTotalCount ?? $totalCount;
    $view = request('view', 'action');
    if (!in_array($view, ['action', 'monitor'], true)) {
        $view = 'action';
    }
@endphp

@if (!$presidentMode)
{{-- Top-level tabs: Aksyon vs Monitoring --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route($routePrefix . '.complaints', array_merge(request()->except('view', 'page', 'mpage'), ['view' => 'action'])) }}"
       class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-bold transition {{ $view === 'action' ? 'border-navy-600 bg-navy-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-200 hover:bg-slate-50' }}">
        <i class="bi bi-lightning-charge-fill"></i> Aksyon (TFRB / Superadmin)
    </a>
    <a href="{{ route($routePrefix . '.complaints', array_merge(request()->except('view', 'page', 'mpage'), ['view' => 'monitor'])) }}"
       class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-bold transition {{ $view === 'monitor' ? 'border-slate-600 bg-slate-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
        <i class="bi bi-eye-fill"></i> Monitoring (Lahat ng Complaints)
    </a>
</div>
@endif

@if ($view === 'action' || $presidentMode)
    @if (!$presidentMode)
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy-600 text-white"><i class="bi bi-lightning-charge-fill"></i></span>
        <div>
            <h2 class="text-base font-bold text-slate-800">Aksyon (TFRB / Superadmin)</h2>
            <p class="text-xs text-slate-500">Complaints na kaya mong i-solve o i-reject — forwarded, against president, unassigned.</p>
        </div>
    </div>
    @endif
    @include('partials.admin.complaint-list')
@else
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-600 text-white"><i class="bi bi-eye-fill"></i></span>
        <div>
            <h2 class="text-base font-bold text-slate-800">Monitoring (Lahat ng Complaints)</h2>
            <p class="text-xs text-slate-500">Viewing lang — kasama ang nasa TODA president, para mamonitor ang kanilang actions. (Read-only)</p>
        </div>
    </div>
    <form method="GET" action="{{ route($routePrefix . '.complaints') }}" class="mb-5 flex flex-wrap items-end gap-2 rounded-xl border border-slate-100 bg-slate-50/70 p-3">
        <input type="hidden" name="view" value="monitor">
        <div class="min-w-0 flex-1 sm:max-w-md">
            <label for="monitoring_complaint_search" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Search all complaint references</label>
            <div class="tw-input-group rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-navy-500 focus-within:ring-2 focus-within:ring-navy-600/10">
                <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                <input type="text" id="monitoring_complaint_search" name="search" value="{{ request('search') }}"
                       placeholder="Type TFR-2026-0396" class="tw-input w-full" aria-label="Search all complaints by reference number">
            </div>
        </div>
        <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy"><i class="bi bi-search"></i> Search</button>
        @if (request('search'))
            <a href="{{ route($routePrefix . '.complaints', ['view' => 'monitor']) }}" class="tw-btn tw-btn-sm tw-btn-ghost"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
    </form>
    {{-- KPI strip --}}
    <div class="mb-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $kpiItems = [
                ['key' => 'all', 'label' => 'Total', 'icon' => 'bi-collection', 'chip' => 'bg-slate-100 text-slate-600', 'num' => $monitoringTotalCount],
                ['key' => 'open', 'label' => 'Nasa President', 'icon' => 'bi-hourglass-split', 'chip' => 'bg-amber-50 text-amber-600', 'num' => $monitoringOpenCount ?? 0],
                ['key' => 'solved', 'label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'chip' => 'bg-violet-50 text-violet-600', 'num' => $monitoringSolvedCount ?? 0],
                ['key' => 'rejected', 'label' => 'Rejected', 'icon' => 'bi-x-circle', 'chip' => 'bg-red-50 text-red-600', 'num' => $monitoringRejectedCount ?? 0],
            ];
        @endphp
        @foreach ($kpiItems as $kpi)
            <a href="{{ route($routePrefix . '.complaints', array_merge(request()->except('view', 'page', 'mpage'), ['view' => 'monitor'])) }}"
               class="flex items-center gap-2.5 rounded-lg bg-slate-50 px-3 py-2.5 transition hover:bg-slate-100 hover:ring-1 hover:ring-slate-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kpi['chip'] }}"><i class="bi {{ $kpi['icon'] }}"></i></span>
                <div class="min-w-0">
                    <div class="text-base font-extrabold leading-none text-slate-800">{{ $kpi['num'] }}</div>
                    <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $kpi['label'] }}</div>
                </div>
            </a>
        @endforeach
    </div>
    @if ($monitoringComplaints && $monitoringComplaints->count() > 0)
        @foreach ($monitoringComplaints as $rating)
            @include('partials.admin.complaint-card', ['rating' => $rating, 'routePrefix' => $routePrefix, 'actor' => $actor, 'readOnly' => true])
        @endforeach
        @if ($monitoringComplaints->hasPages())
            <div class="mt-4">{{ $monitoringComplaints->links('pagination::tailwind') }}</div>
        @endif
    @else
        <div class="tw-empty py-12">
            <div class="tw-empty-icon"><i class="bi bi-check-circle text-emerald-500"></i></div>
            <h3 class="tw-card-title mb-1">Walang complaints</h3>
            <p class="text-sm text-slate-500">Lahat ng complaints ay nasa actionable section o wala pang nai-file.</p>
        </div>
    @endif
@endif
