<div class="tw-card transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="tw-card-title text-sm"><i class="bi bi-flag-fill mr-1 text-gold"></i> Complaints by Type</h3>
        @if ($totalComplaints > 0)
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" onclick="showComplaintModal()">
                Show all categories <i class="bi bi-chevron-right"></i>
            </button>
        @endif
    </div>
    @if ($totalComplaints > 0)
        @php
            $complaintMax = $complaintStats->max('total');
            $complaintVisible = $complaintStats->where('total', '>', 0);
        @endphp
        <div id="complaintChartBody" class="max-h-[248px] space-y-3 overflow-y-auto px-5 py-4">
            @foreach ($complaintVisible as $c)
                <div>
                    <div class="mb-1 flex items-center justify-between gap-2 text-[11px]">
                        <span class="truncate font-semibold text-slate-600">{{ $c->complaint_type }}</span>
                        <span class="shrink-0 font-bold text-slate-900">{{ $c->total }}</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-navy-500 to-navy-700" style="width: {{ $complaintMax ? round(($c->total / $complaintMax) * 100) : 0 }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <p class="text-sm font-bold text-slate-700">No complaints recorded</p>
        </div>
    @endif
</div>

@if ($totalComplaints > 0)
<script>
    window.showAllComplaintTypes = false;
    window.__lastComplaintStats = @json($complaintStats->map(fn ($c) => ['complaint_type' => $c->complaint_type, 'total' => (int) $c->total])->values());
</script>
@endif
