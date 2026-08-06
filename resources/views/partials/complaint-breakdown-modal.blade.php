@if ($totalComplaints > 0)
<script>
function showComplaintModal() {
    var el = document.getElementById('complaintModal');
    if (el) { el.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function closeComplaintModal() {
    var el = document.getElementById('complaintModal');
    if (el) { el.classList.add('hidden'); document.body.style.overflow = ''; }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeComplaintModal();
});
</script>

<div id="complaintModal" class="tw-modal-backdrop hidden" onclick="if(event.target===this)closeComplaintModal()">
    <div class="tw-modal">
        <div class="tw-modal-head">
            <h4 class="text-base font-bold text-slate-900"><i class="bi bi-flag-fill mr-2 text-gold"></i> Complaints by Type</h4>
            <button type="button" class="tw-modal-close" onclick="closeComplaintModal()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="tw-modal-body" id="complaintModalBody">
            @php $complaintTotal = $complaintStats->sum('total'); @endphp
            @foreach ($complaintStats->where('total', '>', 0) as $c)
                @php $pct = $complaintTotal > 0 ? round(($c->total / $complaintTotal) * 100) : 0; @endphp
                <div class="mb-3">
                    <div class="mb-1 flex items-center justify-between text-sm text-slate-700">
                        <span>{{ $c->complaint_type }}</span>
                        <strong>{{ $c->total }}</strong>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
