<div class="tw-card">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-flag-fill mr-1 text-gold"></i> Complaints by Type</h3>
        @if ($totalComplaints > 0)
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" onclick="showComplaintModal()">
                Details <i class="bi bi-chevron-right"></i>
            </button>
        @endif
    </div>
    @if ($totalComplaints > 0)
        @php $complaintMax = $complaintStats->max('total'); @endphp
        <div id="complaintChartBody" class="max-h-[248px] space-y-2 overflow-y-auto p-4">
            @foreach ($complaintStats->where('total', '>', 0) as $c)
                <div>
                    <div class="mb-1 flex items-center justify-between gap-2 text-[11px]">
                        <span class="truncate font-semibold text-slate-600">{{ $c->complaint_type }}</span>
                        <span class="shrink-0 font-bold text-slate-900">{{ $c->total }}</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full" style="width: {{ $complaintMax ? round(($c->total / $complaintMax) * 100) : 0 }}%; background: linear-gradient(90deg, #2e7dd1, #0f2a4a);"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No complaints yet</h4>
            <p class="mt-1 text-sm text-slate-400">Reported issues will appear here.</p>
        </div>
    @endif
</div>
