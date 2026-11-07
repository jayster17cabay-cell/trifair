{{-- Shared complaints page body. Requires: $routePrefix, $complaints, $filter,
     $openCount, $rejectedCount, $solvedCount, $totalCount, $sourceCounts. --}}
@php
    $presidentMode = $presidentMode ?? false;
    $actor = $actor ?? (auth()->check() && auth()->user()->role === 'operator_president' ? 'president' : 'staff');
    $monitoringComplaints = $monitoringComplaints ?? null;
    $readOnly = false;
@endphp

@if (!$presidentMode)
{{-- Section 1: Actionable by TFRB / superadmin --}}
<div class="mb-8">
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy-600 text-white"><i class="bi bi-lightning-charge-fill"></i></span>
        <div>
            <h2 class="text-base font-bold text-slate-800">Aksyon (TFRB / Superadmin)</h2>
            <p class="text-xs text-slate-500">Complaints na kaya mong i-solve o i-reject — forwarded, against president, unassigned.</p>
        </div>
    </div>
    @include('partials.admin.complaint-list')
</div>

{{-- Section 2: Monitoring — lahat ng complaints (kasama ang nasa TODA president) --}}
<div class="mb-8">
    <div class="mb-3 flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-600 text-white"><i class="bi bi-eye-fill"></i></span>
        <div>
            <h2 class="text-base font-bold text-slate-800">Monitoring (Lahat ng Complaints)</h2>
            <p class="text-xs text-slate-500">Viewing lang — kasama ang nasa TODA president, para mamonitor ang kanilang actions. (Read-only)</p>
        </div>
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
</div>
@endif

@if ($presidentMode)
    @include('partials.admin.complaint-list')
@endif