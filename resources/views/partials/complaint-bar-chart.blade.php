<div class="tw-card transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-flag-fill mr-1 text-gold"></i> Complaints by Type</h3>
        @if ($totalComplaints > 0)
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" onclick="showComplaintModal()">
                Details <i class="bi bi-chevron-right"></i>
            </button>
        @endif
    </div>
    @if ($totalComplaints > 0)
        @php
            $complaintMax = $complaintStats->max('total');
            $complaintVisible = $complaintStats->where('total', '>', 0);
            $complaintHiddenCount = $complaintStats->count() - $complaintVisible->count();
        @endphp
        <div id="complaintChartBody" class="max-h-[248px] space-y-2 overflow-y-auto p-4">
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
        @if ($complaintHiddenCount > 0)
            <div class="px-4 pb-3">
                <button type="button" id="complaintTypeToggle" onclick="toggleComplaintTypes()" class="mt-2 cursor-pointer text-sm text-gold hover:underline">
                    Show all categories
                </button>
            </div>
        @endif
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <p class="text-sm text-gray-400">No complaints recorded</p>
        </div>
    @endif
</div>

@if ($totalComplaints > 0)
<script>
    window.showAllComplaintTypes = false;
    window.__lastComplaintStats = @json($complaintStats->map(fn ($c) => ['complaint_type' => $c->complaint_type, 'total' => (int) $c->total])->values());
    function toggleComplaintTypes() {
        window.showAllComplaintTypes = !window.showAllComplaintTypes;
        var btn = document.getElementById('complaintTypeToggle');
        if (btn) btn.textContent = window.showAllComplaintTypes ? 'Show less' : 'Show all categories';
        if (window.__rerenderComplaints) window.__rerenderComplaints();
    }
</script>
@endif
