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
        <div class="relative h-52 p-3 sm:p-4">
            <canvas id="complaintChart"></canvas>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-flag"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No complaints yet</h4>
            <p class="mt-1 text-sm text-slate-400">Reported issues will appear here.</p>
        </div>
    @endif
</div>

<script>
function initComplaintChart() {
    var canvas = document.getElementById('complaintChart');
    var stats = @json($complaintStats);
    stats = stats.filter(function (s) { return s.total > 0; });
    var ctx = canvas.getContext('2d');
    window.complaintChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: stats.map(function(s) { return s.complaint_type; }),
            datasets: [{
                data: stats.map(function(s) { return s.total; }),
                backgroundColor: function (context) {
                    var area = context.chart.chartArea;
                    if (!area) return '#2e7dd1';
                    var g = ctx.createLinearGradient(area.left, 0, area.right, 0);
                    g.addColorStop(0, '#2e7dd1');
                    g.addColorStop(1, '#0f2a4a');
                    return g;
                },
                borderRadius: 5,
                maxBarThickness: 15,
                hoverBackgroundColor: '#2563a8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            animation: { duration: 400 },
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
                    beginAtZero: true,
                    grid: { color: 'rgba(226,232,240,0.6)' },
                    ticks: { precision: 0, font: { size: 10 } }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: false,
                        font: { size: 10, weight: 600 },
                        callback: function (value) {
                            var label = this.getLabelForValue(value);
                            return label.length > 22 ? label.slice(0, 22) + '\u2026' : label;
                        }
                    }
                }
            }
        }
    });
}
(function () {
    function tryInit() {
        if (window.Chart && !window.complaintChart && document.getElementById('complaintChart')) {
            initComplaintChart();
        } else if (!window.Chart) {
            setTimeout(tryInit, 200);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }
})();
</script>
