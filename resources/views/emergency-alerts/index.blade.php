@extends(Auth::user()->isSuperadmin() ? 'layouts.superadmin' : (Auth::user()->isOperatorPresident() ? 'layouts.president' : 'layouts.tfrb-officer'))

@section('title', 'Emergency Alerts')

@section('content')
@php
    $statusChip = [
        'active' => ['label' => 'Active', 'icon' => 'bi-pulse', 'count' => $counts['active']],
        'responding' => ['label' => 'Responding', 'icon' => 'bi-arrow-right-circle', 'count' => $counts['responding']],
        'resolved' => ['label' => 'Resolved', 'icon' => 'bi-check-circle', 'count' => $counts['resolved']],
        'false_alarm' => ['label' => 'False Alarms', 'icon' => 'bi-dash-circle', 'count' => $counts['false_alarm']],
        'total' => ['label' => 'Total', 'icon' => 'bi-collection', 'count' => $counts['total']],
    ];
    $categoryTabs = [null => 'bi-grid'];
    foreach (\App\Models\EmergencyAlert::CATEGORIES as $c) {
        $categoryTabs[$c] = \App\Models\EmergencyAlert::CATEGORY_ICONS[$c];
    }
    $guidance = [
        'accident' => 'Consider dispatching medical assistance.',
        'theft_harassment' => 'Consider contacting barangay/police for immediate response.',
    ];
@endphp

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-sos mr-2 text-gold"></i>Emergency Alerts</h1>
        <p class="tw-page-sub">Passenger SOS alerts within your scope. Active alerts refresh automatically.</p>
    </div>
</div>

<div class="mb-5 grid grid-cols-2 gap-2.5 sm:grid-cols-5" role="list" aria-label="Alert summary">
    @foreach ($statusChip as $key => $chip)
        <a href="{{ request()->fullUrlWithQuery(['status' => $key === 'total' ? '' : $key, 'category' => $category ?? '']) }}"
           class="tw-card p-3.5 transition hover:-translate-y-px hover:shadow-md {{ $status === $key ? 'ring-2 ring-amber-400' : '' }}">
            <div class="flex items-center gap-2">
                <span class="{{ $key === 'active' ? 'tw-badge tw-badge-red' : ($key === 'responding' ? 'tw-badge tw-badge-amber' : 'tw-badge tw-badge-gray') }}">
                    <i class="bi {{ $chip['icon'] }}"></i> {{ $chip['label'] }}
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-slate-800" data-alert-count="{{ $key }}">{{ $chip['count'] }}</div>
        </a>
    @endforeach
</div>

<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach ($categoryTabs as $key => $icon)
        <a href="{{ request()->fullUrlWithQuery(['category' => $key ?? '', 'status' => $status ?? '']) }}"
           class="{{ $category === $key ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi {{ $icon }}"></i>
            {{ $key ? \App\Models\EmergencyAlert::CATEGORY_LABELS[$key] : 'All Categories' }}
        </a>
    @endforeach
</div>

@if ($category && isset($guidance[$category]))
    <div class="mb-4 rounded-xl border-l-4 border-amber-400 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
        <i class="bi bi-lightbulb mr-1"></i> {{ $guidance[$category] }}
    </div>
@endif

@if ($category && !isset($guidance[$category]) && !$status)
    <div class="mb-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <i class="bi bi-info-circle mr-1"></i> {{ \App\Models\EmergencyAlert::CATEGORY_LABELS[$category] }} alerts — tap an alert row for details.
    </div>
@endif

<div class="tw-card overflow-hidden">
    <div id="alertList">
        @include('emergency-alerts.list', ['alerts' => $alerts, 'highlightId' => $highlightId])
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var pollUrl = @json($pollUrl);
        var listEl = document.getElementById('alertList');
        if (!listEl || !pollUrl) return;

        function elapsedLabel(ms) {
            var s = Math.max(0, Math.floor((Date.now() - ms) / 1000));
            if (s < 60) return 'just now';
            var m = Math.floor(s / 60);
            if (m < 60) return m + 'm ago';
            var h = Math.floor(m / 60);
            if (h < 24) return h + 'h ' + (m % 60) + 'm ago';
            var d = Math.floor(h / 24);
            return d + 'd ago';
        }

        function bindTimers() {
            document.querySelectorAll('[data-alert-created-ms]').forEach(function (el) {
                el.textContent = elapsedLabel(parseInt(el.getAttribute('data-alert-created-ms'), 10));
            });
        }

        function highlight() {
            var id = @json($highlightId ? (int) $highlightId : null);
            if (!id) return;
            var panel = document.getElementById('alert-panel-' + id);
            if (!panel) return;
            panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            panel.classList.add('ring-2', 'ring-amber-400');
            setTimeout(function () { panel.classList.remove('ring-2', 'ring-amber-400'); }, 4000);
        }

        function refresh() {
            fetch(pollUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (!data || !data.html) return;
                listEl.innerHTML = data.html;
                bindTimers();
                if (data.counts) {
                    ['active', 'responding', 'resolved', 'false_alarm', 'total'].forEach(function (k) {
                        var el = document.querySelector('[data-alert-count="' + k + '"]');
                        if (el) el.textContent = data.counts[k];
                    });
                }
            }).catch(function () {});
        }

        bindTimers();
        highlight();
        setInterval(refresh, 15000);
        if (document.visibilityState !== 'hidden') refresh();
    })();
</script>
@endpush