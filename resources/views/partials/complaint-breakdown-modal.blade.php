@if ($totalComplaints > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var stats = @json($complaintStats);
    var canvas = document.getElementById('complaintDonut');
    if (canvas && window.Chart) {
        var palette = ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#ec4899'];
        window.complaintChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: stats.map(function(s) { return s.complaint_type; }),
                datasets: [{
                    data: stats.map(function(s) { return s.total; }),
                    backgroundColor: palette,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + context.parsed + ' complaint' + (context.parsed !== 1 ? 's' : '');
                            }
                        }
                    }
                }
            }
        });
    }
});

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
            <h4 class="text-base font-bold text-slate-900"><i class="bi bi-flag-fill mr-2 text-red-600"></i> Complaints by Type</h4>
            <button type="button" class="tw-modal-close" onclick="closeComplaintModal()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="tw-modal-body" id="complaintModalBody">
            @php $complaintTotal = $complaintStats->sum('total'); @endphp
            @foreach ($complaintStats as $c)
                @php $pct = $complaintTotal > 0 ? round(($c->total / $complaintTotal) * 100) : 0; @endphp
                <div class="mb-3">
                    <div class="mb-1 flex items-center justify-between text-sm text-slate-700">
                        <span>{{ $c->complaint_type }}</span>
                        <strong>{{ $c->total }}</strong>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-navy-600" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
