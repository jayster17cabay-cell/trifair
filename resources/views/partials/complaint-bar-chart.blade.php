<div class="tw-card">
    <div class="tw-card-pad flex items-center justify-between border-b border-slate-100">
        <h3 class="tw-card-title"><i class="bi bi-flag-fill mr-1 text-red-600"></i> Complaints by Type</h3>
        @if ($totalComplaints > 0)
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" onclick="showComplaintModal()">
                Details <i class="bi bi-chevron-right"></i>
            </button>
        @endif
    </div>
    @if ($totalComplaints > 0)
        <div class="relative h-72 p-4 sm:p-5">
            <canvas id="complaintChart"></canvas>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No complaints yet</h4>
            <p class="mt-1 text-sm text-slate-400">Reported issues will appear here.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var stats = @json($complaintStats);
    var canvas = document.getElementById('complaintChart');
    if (canvas && window.Chart) {
        var palette = ['#dc2626', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#ec4899'];
        window.complaintChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: stats.map(function(s) { return s.complaint_type; }),
                datasets: [{
                    data: stats.map(function(s) { return s.total; }),
                    backgroundColor: palette,
                    borderRadius: 8,
                    maxBarThickness: 26
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.parsed.x + ' complaint' + (context.parsed.x !== 1 ? 's' : '');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(226,232,240,0.6)' },
                        ticks: { precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { weight: 600 } }
                    }
                }
            }
        });
    }
});
</script>
