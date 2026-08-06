<div class="tw-card">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="tw-card-title text-sm"><i class="bi bi-bar-chart-fill mr-1 text-gold"></i> Ratings Distribution</h3>
        <p class="text-[11px] text-slate-400">Per star level</p>
    </div>
    @php $distTotal = array_sum($ratingDistribution); @endphp
    @if ($distTotal > 0)
        <div class="relative h-52 p-3 sm:p-4">
            <canvas id="ratingChart"></canvas>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <div class="tw-empty-icon"><i class="bi bi-star"></i></div>
            <h4 class="text-sm font-bold text-slate-700">No ratings yet</h4>
            <p class="mt-1 text-sm text-slate-400">Passenger ratings will appear here.</p>
        </div>
    @endif
</div>

<script>
function initRatingChart() {
    var canvas = document.getElementById('ratingChart');
    var dist = @json($ratingDistribution);
    var ctx = canvas.getContext('2d');
    window.ratingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [1, 2, 3, 4, 5].map(function(i) { return i + ' Star'; }),
            datasets: [{
                data: [1, 2, 3, 4, 5].map(function(i) { return dist[i] || 0; }),
                backgroundColor: function (context) {
                    var area = context.chart.chartArea;
                    if (!area) return '#2e7dd1';
                    var g = ctx.createLinearGradient(0, area.bottom, 0, area.top);
                    g.addColorStop(0, '#2e7dd1');
                    g.addColorStop(1, '#0f2a4a');
                    return g;
                },
                borderRadius: 5,
                maxBarThickness: 40,
                hoverBackgroundColor: '#2563a8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                            return ' ' + context.parsed.y + ' rating' + (context.parsed.y !== 1 ? 's' : '');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(226,232,240,0.6)' },
                    ticks: { precision: 0, font: { size: 10 } }
                }
            }
        }
    });
}
(function () {
    function tryInit() {
        if (window.Chart && !window.ratingChart && document.getElementById('ratingChart')) {
            initRatingChart();
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
