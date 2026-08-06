{{-- Live clock + real-time dashboard polling (shared across roles) --}}
<script>
(function () {
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function escHtml(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function fmt12(h) {
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return { h: h12, ampm: ampm };
    }

    function updateClock() {
        var now = new Date();
        var dtEl = document.querySelector('[data-live-clock="datetime"]');
        if (dtEl) {
            var t = fmt12(now.getHours());
            dtEl.textContent = days[now.getDay()].slice(0, 3) + ', ' + months[now.getMonth()].slice(0, 3) + ' ' + now.getDate()
                + ', ' + now.getFullYear() + ' · ' + t.h + ':' + pad(now.getMinutes()) + ' ' + t.ampm;
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    function setStat(name, value) {
        var nodes = document.querySelectorAll('[data-live="' + name + '"]');
        if (!nodes.length) return;
        var text = value;
        if (name === 'averageRating') text = Number(value).toFixed(1);
        for (var i = 0; i < nodes.length; i++) nodes[i].textContent = text;
    }

    function setList(key, html) {
        var el = document.querySelector('[data-live-list="' + key + '"]');
        if (el && html) el.innerHTML = html;
    }

    function updateAvgStars(avg) {
        var stars = document.querySelector('[data-live-stars]');
        if (!stars) return;
        var filled = Math.round(Number(avg) || 0);
        stars.innerHTML = '';
        for (var i = 1; i <= 5; i++) {
            var icon = document.createElement('i');
            icon.className = 'bi ' + (i <= filled ? 'bi-star-fill' : 'bi-star');
            icon.style.color = i <= filled ? 'var(--secondary)' : 'rgba(255,255,255,0.3)';
            stars.appendChild(icon);
        }
    }

    function updateComplaintChart(stats) {
        if (!stats) return;
        var filtered = stats.filter(function (s) { return s.total > 0; });
        var body = document.getElementById('complaintChartBody');
        if (body) {
            var max = 0;
            for (var i = 0; i < filtered.length; i++) max = Math.max(max, filtered[i].total);
            var html = '';
            for (var i = 0; i < filtered.length; i++) {
                var s = filtered[i];
                var pct = max > 0 ? Math.round((s.total / max) * 100) : 0;
                html += '<div>' +
                    '<div class="mb-1 flex items-center justify-between gap-2 text-[11px]">' +
                    '<span class="truncate font-semibold text-slate-600">' + escHtml(s.complaint_type) + '</span>' +
                    '<span class="shrink-0 font-bold text-slate-900">' + s.total + '</span></div>' +
                    '<div class="h-2.5 overflow-hidden rounded-full bg-slate-100">' +
                    '<div class="h-full rounded-full" style="width:' + pct + '%;background:linear-gradient(90deg,#2e7dd1,#0f2a4a);"></div></div>' +
                    '</div>';
            }
            body.innerHTML = html;
        }
        var modal = document.getElementById('complaintModalBody');
        if (!modal) return;
        var sum = stats.reduce(function (a, s) { return a + s.total; }, 0);
        var mhtml = '';
        for (var i = 0; i < filtered.length; i++) {
            var s = filtered[i];
            var pct = sum > 0 ? Math.round((s.total / sum) * 100) : 0;
            mhtml += '<div class="mb-3">' +
                '<div class="mb-1 flex items-center justify-between text-sm text-slate-700"><span>' + escHtml(s.complaint_type) + '</span><strong>' + s.total + '</strong></div>' +
                '<div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width:' + pct + '%;"></div></div>' +
                '</div>';
        }
        modal.innerHTML = mhtml;
    }

    function updateRatingChart(dist) {
        if (!dist) return;
        var body = document.getElementById('ratingChartBody');
        if (!body) return;
        var counts = [1, 2, 3, 4, 5].map(function (i) { return dist[i] || 0; });
        var max = 0;
        for (var i = 0; i < counts.length; i++) max = Math.max(max, counts[i]);
        var html = '';
        for (var i = 0; i < counts.length; i++) {
            var pct = max > 0 ? Math.round((counts[i] / max) * 100) : 0;
            html += '<div class="flex h-full flex-1 flex-col items-center justify-end">' +
                '<span class="mb-1 text-[11px] font-bold text-slate-700">' + counts[i] + '</span>' +
                '<div class="w-full rounded-t-md" style="height:' + Math.max(pct, 2) + '%;background:linear-gradient(180deg,#2e7dd1,#0f2a4a);"></div>' +
                '</div>';
        }
        body.innerHTML = html;
    }

    function setVisibility(id, show) {
        var el = document.getElementById(id);
        if (el) el.style.display = show ? '' : 'none';
    }

    function poll() {
        fetch(window.location.href, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('poll failed'); return r.json(); })
            .then(function (data) {
                Object.keys(data).forEach(function (key) {
                    if (key === 'complaintsHtml') setList('complaints', data.complaintsHtml);
                    else if (key === 'topHtml') setList('top', data.topHtml);
                    else if (key === 'ratingsHtml') setList('ratings', data.ratingsHtml);
                    else if (key === 'breakdownHtml') setList('breakdown', data.breakdownHtml);
                    else if (key === 'complaintStats') updateComplaintChart(data.complaintStats);
                    else if (key === 'ratingDistribution') updateRatingChart(data.ratingDistribution);
                    else if (key === 'averageRating') { setStat(key, data[key]); updateAvgStars(data.averageRating); }
                    else setStat(key, data[key]);
                });
                if (typeof data.pendingReview !== 'undefined') {
                    setVisibility('pendingReviewBanner', data.pendingReview > 0);
                    var pr = document.getElementById('pendingReviewText');
                    if (pr) pr.textContent = data.pendingReview + ' complaint' + (data.pendingReview !== 1 ? 's' : '') + ' pending review';
                }
                if (typeof data.totalRatings !== 'undefined') setVisibility('operatorNoRatingsBanner', data.totalRatings > 0);
                if (typeof data.unreadCount !== 'undefined') {
                    setVisibility('unreadBanner', data.unreadCount > 0);
                    setVisibility('unreadBellBadge', data.unreadCount > 0);
                    setVisibility('unreadSideBadge', data.unreadCount > 0);
                    var t = document.getElementById('unreadCountText');
                    if (t) t.textContent = data.unreadCount + ' unread notification' + (data.unreadCount !== 1 ? 's' : '');
                }
                var state = document.getElementById('admLiveState');
                if (state) { state.style.color = 'var(--success)'; state.textContent = 'Live'; }
            })
            .catch(function () {
                var state = document.getElementById('admLiveState');
                if (state) { state.style.color = 'var(--danger)'; state.textContent = 'Offline'; }
            });
    }

    setTimeout(poll, 1500);
    setInterval(poll, 30000);
})();
</script>
