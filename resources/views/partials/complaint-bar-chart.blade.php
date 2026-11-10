<div class="tw-card transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
        <h3 class="tw-card-title flex items-center gap-2 text-sm">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-50 text-gold text-xs"><i class="bi bi-flag-fill"></i></span>
            Complaints by Type
        </h3>
        @if ($totalComplaints > 0)
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" onclick="showComplaintModal()">
                Show all <i class="bi bi-arrow-right"></i>
            </button>
        @endif
    </div>
    @if ($totalComplaints > 0)
        @php
            $complaintMax = $complaintStats->max('total');
            // Keep the in-card chart compact: only the top 5 categories are
            // shown here; the full breakdown (all categories) lives in the modal.
            $complaintVisible = $complaintStats->where('total', '>', 0)->take(5);
        @endphp
        <div id="complaintChartBody" class="max-h-[120px] space-y-1.5 overflow-y-auto px-4 py-2.5">
            @foreach ($complaintVisible as $c)
                <div>
                    <div class="mb-0.5 flex items-center justify-between gap-2 text-[10px]">
                        <span class="truncate font-semibold text-slate-600">{{ $c->complaint_type }}</span>
                        <span class="shrink-0 font-bold text-slate-900">{{ $c->total }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-navy-500 to-navy-700" style="width: {{ $complaintMax ? round(($c->total / $complaintMax) * 100) : 0 }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <p class="mt-1 text-xs font-bold text-slate-700">No complaints recorded</p>
        </div>
    @endif
</div>

@if ($totalComplaints > 0)
<script>
    window.showAllComplaintTypes = false;
    window.__lastComplaintStats = @json($complaintStats->map(fn ($c) => ['complaint_type' => $c->complaint_type, 'total' => (int) $c->total])->values());
</script>
@endif
