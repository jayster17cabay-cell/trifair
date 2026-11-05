<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report Operator - TriFair</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
    <style>
        [hidden] { display: none !important; }
        body {
            background:
                radial-gradient(1000px 520px at 50% -12%, rgba(15,42,74,0.10) 0%, transparent 60%),
                radial-gradient(700px 420px at 100% 105%, rgba(245,184,0,0.07) 0%, transparent 60%),
                #f8fafc;
            background-attachment: fixed;
            min-height: 100vh;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
        }
        .rate-stack { padding: 1rem 0 calc(env(safe-area-inset-bottom, 0px) + 1.5rem); }

        .rate-alert {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
        }
        .rate-alert ul { margin: 0.25rem 0 0 1.1rem; padding: 0; }
        .rate-alert-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid rgba(5, 150, 105, 0.28);
        }

        /* Why ratings are unavailable */
        .report-reason {
            margin-top: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 0.82rem;
            line-height: 1.5;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
            text-align: left;
        }
        .report-reason i { margin-top: 0.1rem; }

        .report-badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em;
            text-transform: uppercase; color: #b91c1c;
            background: #fee2e2; border: 1px solid #fecaca;
            padding: 0.3rem 0.7rem; border-radius: 999px;
        }

        .report-note {
            margin-top: 0.75rem;
            font-size: 0.72rem;
            color: #64748b;
            line-height: 1.45;
        }

        .file-hint { margin-top: 0.35rem; font-size: 0.72rem; color: #94a3b8; }
    </style>
</head>
<body>
<noscript>
    <div class="rate-alert" style="margin:1rem auto;max-width:640px;">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div><strong>JavaScript is disabled.</strong> You need to enable JavaScript in your browser to use the map and the report form. Tap the three dots and choose "Enable JavaScript", or open this page in another browser.</div>
    </div>
</noscript>

@include('partials.rate.rating-page-header')

<div class="rate-container">
    <div class="rate-stack">

        @if ($errors->any())
            <div class="rate-alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true" style="margin-top: 0.15rem;"></i>
                <div>
                    <strong>Please check the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="rate-alert rate-alert-success" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true" style="margin-top: 0.15rem;"></i>
                <div><strong>{{ session('status') }}</strong></div>
            </div>
        @endif

        {{-- Operator card — who this report is about --}}
        <section class="rate-card" style="text-align: center;">
            <span class="report-badge"><i class="bi bi-slash-circle" aria-hidden="true"></i> Ratings Unavailable</span>
            <h3 class="rate-heading" style="margin-top: 0.85rem;">Report Operator</h3>
            <p class="rate-subtitle" style="margin-top: 0.15rem;">This operator cannot be rated right now.</p>
            <div class="rate-driver-row">
                @include('partials.rate.driver-info-pill', ['driverName' => $operator->user->name ?? 'Driver'])
            </div>
            @if ($selectedToda)
                <p class="rate-subtitle" style="margin-top: 0.5rem;">
                    <i class="bi bi-building"></i> {{ $selectedToda->name }}
                </p>
            @endif

            <div class="report-reason">
                <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                <span>
                    @if ($reason === 'suspended')
                        This operator is <strong>suspended</strong>@if ($suspension?->suspended_until) until <strong>{{ $suspension->suspended_until->format('M d, Y') }}</strong>@endif.
                        Ratings are paused while the suspension is active.
                    @elseif ($reason === 'franchise')
                        This operator's <strong>franchise has expired</strong>@if ($term?->valid_until) on <strong>{{ $term->valid_until->format('M d, Y') }}</strong>@endif.
                        Ratings are unavailable until the franchise is renewed.
                    @else
                        This operator is <strong>inactive</strong>.
                        Ratings are unavailable until the account is reactivated.
                    @endif
                    If you observed them operating, file a report — it goes straight to the TFRB.
                </span>
            </div>
        </section>

        {{-- Map — same map the rating page uses, minus the route tracking --}}
        <section class="rate-card">
            <div class="map-shell">
                <div id="reportMap" class="route-map" role="application" aria-label="Map of your current location"></div>
            </div>
            <div class="map-info">
                <span class="map-attribution">© OpenStreetMap contributors</span>
                <span class="map-summary" id="reportMapStatus">Locating you…</span>
            </div>
        </section>

        {{-- Violation report — replaces the 1-5 star rating --}}
        <form action="{{ route('rate.report.submit', $operator->qr_code) }}" method="POST" enctype="multipart/form-data" class="rate-stack" id="reportForm">
            @csrf
            @if ($selectedToda)
                <input type="hidden" name="toda_id" value="{{ $selectedToda->id }}">
            @endif

            <section class="rate-card">
                <h3 class="rate-heading" style="text-align:left;"><i class="bi bi-flag-fill" style="color:#dc2626;"></i> Report a Violation</h3>
                <p class="rate-subtitle" style="text-align:left; margin-top:0.25rem;">Still running trips despite the suspension, or with a lapsed franchise? Report it — the TFRB will review and can penalize or bar them.</p>

                <div style="margin-top: 0.9rem;">
                    <label for="violation_type" class="rate-label"><i class="bi bi-list-check" style="color:#dc2626;"></i> Violation type</label>
                    <select name="violation_type" id="violation_type" required class="rate-field">
                        <option value="">Select violation type...</option>
                        @foreach ($violationTypes as $type)
                            <option value="{{ $type }}" {{ old('violation_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-top: 0.75rem;">
                    <label for="incident_at" class="rate-label"><i class="bi bi-calendar-event" style="color:#dc2626;"></i> Incident date &amp; time</label>
                    <input type="datetime-local" name="incident_at" id="incident_at" required class="rate-field"
                           value="{{ old('incident_at', now('Asia/Manila')->format('Y-m-d\TH:i')) }}">
                </div>

                <div style="margin-top: 0.75rem;">
                    <label for="location" class="rate-label"><i class="bi bi-geo-alt" style="color:#dc2626;"></i> Location (optional)</label>
                    <input type="text" name="location" id="location" maxlength="255" class="rate-field"
                           placeholder="e.g. Market Road, near the terminal" value="{{ old('location') }}">
                </div>

                <div style="margin-top: 0.75rem;">
                    <label for="details" class="rate-label"><i class="bi bi-pencil-square" style="color:#dc2626;"></i> What did you observe?</label>
                    <textarea name="details" id="details" rows="3" required maxlength="2000" class="rate-field"
                              placeholder="Describe what you saw — e.g. picked up passengers at the terminal despite the suspension...">{{ old('details') }}</textarea>
                </div>

                <div style="margin-top: 0.75rem;">
                    <label for="proofs" class="rate-label"><i class="bi bi-paperclip" style="color:#dc2626;"></i> Proof attachments (optional)</label>
                    <input type="file" name="proofs[]" id="proofs" multiple accept=".jpg,.jpeg,.png,.pdf,.mp4" class="rate-field">
                    <p class="file-hint">Photos, video or documents — up to 5 files, 10 MB each.</p>
                </div>

                @if ($linkedPassenger)
                    <div style="margin-top: 0.75rem;">
                        <label class="rate-label"><i class="bi bi-person-check" style="color:#4f46e5;"></i> Filed as</label>
                        <input type="email" class="rate-field" value="{{ auth()->user()->email }}" readonly>
                        <p class="file-hint"><i class="bi bi-google" style="color:#ea4335;"></i> Linked to your Google account — the TFRB can reach you through this address.</p>
                    </div>
                @else
                    <div style="margin-top: 0.75rem;">
                        <label for="reporter_name" class="rate-label"><i class="bi bi-person" style="color:#4f46e5;"></i> Your name</label>
                        <input type="text" name="reporter_name" id="reporter_name" required maxlength="100" class="rate-field"
                               placeholder="e.g. Juan Dela Cruz" value="{{ old('reporter_name') }}" autocomplete="name">
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <label for="reporter_contact" class="rate-label"><i class="bi bi-telephone" style="color:#4f46e5;"></i> Contact number or email</label>
                        <input type="text" name="reporter_contact" id="reporter_contact" required maxlength="100" class="rate-field"
                               placeholder="e.g. 0917 123 4567 or juan@gmail.com" value="{{ old('reporter_contact') }}" autocomplete="tel">
                        <p class="file-hint">Only the TFRB office sees this — used if they need to follow up on your report.</p>
                    </div>
                @endif

                <button type="submit" class="rate-submit" id="reportSubmitBtn" style="margin-top: 1rem;">
                    <i class="bi bi-send-fill" aria-hidden="true"></i> Submit Report
                </button>
                <p class="report-note"><i class="bi bi-shield-check"></i> Your report goes directly to the TFRB review queue. The operator is attached automatically. False reports may be penalized.</p>
            </section>
        </form>

    </div>
</div>

<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('vendor/leaflet-routing-machine/leaflet-routing-machine.js') }}"></script>
<script src="{{ asset('vendor/leaflet-polylinedecorator/leaflet.polylineDecorator.js') }}"></script>
<script src="{{ asset('js/rate-map.js') . '?v=' . filemtime(public_path('js/rate-map.js')) }}"></script>
<script>
(function () {
    'use strict';

    var statusEl = document.getElementById('reportMapStatus');
    var api = window.TripRouteMap && window.TripRouteMap.create('reportMap', { center: [16.52, 121.19], zoom: 14 });
    if (!api) {
        if (statusEl) { statusEl.textContent = 'Map unavailable'; }
        return;
    }

    var marker = null;
    function showAt(latlng, zoom) {
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { icon: window.TripRouteMap.pickupIcon(), zIndexOffset: 1000 }).addTo(api.getMap());
        }
        api.setView(latlng, zoom || 15);
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                showAt(L.latLng(pos.coords.latitude, pos.coords.longitude), 15);
                if (statusEl) { statusEl.textContent = 'Your current location'; }
            },
            function () {
                if (statusEl) { statusEl.textContent = 'Location off — showing Solano area'; }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    } else if (statusEl) {
        statusEl.textContent = 'Location not supported — showing Solano area';
    }

    document.getElementById('reportForm').addEventListener('submit', function () {
        var btn = document.getElementById('reportSubmitBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Sending report…'; }
    });
})();
</script>
</body>
</html>
