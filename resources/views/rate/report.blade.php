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
    <link rel="stylesheet" href="{{ asset('vendor/leaflet-routing-machine/leaflet-routing-machine.css') }}">
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

        /* Google sign-in gate */
        .google-gate {
            margin-top: 0.75rem;
            text-align: center;
            padding: 1.25rem 1rem;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            background: #f8fafc;
        }
        .google-gate h4 { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 0.35rem; }
        .google-gate p { font-size: 0.82rem; color: #64748b; line-height: 1.55; margin-bottom: 0.9rem; }
        .google-gate .rate-connect-btn { max-width: 320px; margin: 0 auto; }
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

        {{-- The trip map with live From → To — the same one passengers use to rate --}}
        <form action="{{ route('rate.report.submit', $operator->qr_code) }}" method="POST" enctype="multipart/form-data" class="rate-stack" id="reportForm">
            @csrf
            @if ($selectedToda)
                <input type="hidden" name="toda_id" value="{{ $selectedToda->id }}">
            @endif

            @include('partials.rate.trip-route-map', [
                'mapId' => 'reportMap',
                'mode' => 'track',
                'startAddress' => 'Detecting location...',
                'endAddress' => 'Search destination...',
                'summaryText' => 'Select destination',
            ])

            {{-- Violation report — replaces the 1-5 star rating --}}
            <section class="rate-card">
                <h3 class="rate-heading" style="text-align:left;"><i class="bi bi-flag-fill" style="color:#dc2626;"></i> Report a Violation</h3>
                <p class="rate-subtitle" style="text-align:left; margin-top:0.25rem;">Still running trips despite the suspension, or with a lapsed franchise? Report it — the TFRB will review and can penalize or bar them.</p>

                @if ($linkedPassenger)
                    @php $linkedEmail = auth()->user()->email; @endphp
                    {{-- Signed-in passenger — the report is tied to their account --}}
                    <div class="rate-connect" style="margin-top: 0.75rem;">
                        <div class="rate-connect-done">
                            <i class="bi bi-check-circle-fill" style="color:#059669;" aria-hidden="true"></i>
                            <span>You are connected as <strong>{{ $linkedEmail }}</strong>. You can track this report in your <a href="{{ route('passenger.dashboard') }}" style="color:#0A2A5E;text-decoration:underline;font-weight:700;">Passenger Dashboard</a>.</span>
                        </div>
                        <a href="{{ route('login.google', ['intended' => request()->path()]) }}"
                           class="rate-connect-btn"
                           id="googleSwitchBtn"
                           title="Choose a different Google account">
                            @include('partials.google-g', ['class' => 'h-4 w-4 shrink-0'])
                            Switch Google account
                            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div style="margin-top: 0.9rem;">
                        <label for="violation_type" class="rate-label"><i class="bi bi-list-check" style="color:#dc2626;"></i> Violation type</label>
                        <select name="violation_type" id="violation_type" required class="rate-field">
                            <option value="">Select violation type...</option>
                            @foreach ($violationTypes as $type)
                                <option value="{{ $type }}" {{ old('violation_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Only shown when "Others" is chosen — say what kind of violation it is --}}
                    <div id="othersBox" style="margin-top: 0.75rem; display: {{ old('other_reason') ? 'block' : 'none' }};">
                        <label for="other_reason" class="rate-label"><i class="bi bi-pencil-square" style="color:#dc2626;"></i> What kind of violation?</label>
                        <input type="text" name="other_reason" id="other_reason" maxlength="100" class="rate-field"
                               placeholder="e.g. Smoking while driving, No plate number, Rude behavior"
                               value="{{ old('other_reason') }}">
                    </div>

                    <div style="margin-top: 0.75rem;">
                        <label for="incident_at" class="rate-label"><i class="bi bi-calendar-event" style="color:#dc2626;"></i> Incident date &amp; time</label>
                        <input type="datetime-local" name="incident_at" id="incident_at" required class="rate-field"
                               value="{{ old('incident_at', now('Asia/Manila')->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div style="margin-top: 0.75rem;">
                        <label for="details" class="rate-label"><i class="bi bi-pencil-square" style="color:#dc2626;"></i> What did you observe?</label>
                        <textarea name="details" id="details" rows="3" required maxlength="2000" class="rate-field"
                                  placeholder="Describe what you saw — e.g. picked up passengers at the terminal despite the suspension...">{{ old('details') }}</textarea>
                    </div>

                    <div style="margin-top: 0.75rem;">
                        <label for="proofs" class="rate-label"><i class="bi bi-paperclip" style="color:#dc2626;"></i> Proof attachments (optional)</label>
                        <input type="file" name="proofs[]" id="proofs" multiple accept=".jpg,.jpeg,.png,.pdf,.mp4" class="rate-field">
                        <div id="reportFileSummary" class="file-hint" aria-live="polite"></div>
                        <p class="file-hint">Photos, video or documents — up to 5 files, 10 MB each.</p>
                    </div>

                    <button type="submit" class="rate-submit" id="reportSubmitBtn" style="margin-top: 1rem;">
                        <i class="bi bi-send-fill" aria-hidden="true"></i> Submit Report
                    </button>
                    <p class="report-note"><i class="bi bi-shield-lock-fill"></i> No one can see your identity. The driver CANNOT see your name, email, or contact — your account is used only by TriFair/TFRB to track the report.</p>
                @else
                    {{-- Guests must continue with Google — the report is tied to their account --}}
                    <div class="google-gate">
                        <h4>Sign in to file your report</h4>
                        <p>Your report goes straight to the TFRB review queue, and you will be able to monitor its status in your <strong>Passenger Dashboard</strong> — exactly like your complaints.</p>
                        @if (config('services.google.client_id') && config('services.google.client_secret'))
                            <a href="{{ route('login.google', ['intended' => request()->path()]) }}" class="rate-connect-btn" id="googleConnectBtn">
                                @include('partials.google-g', ['class' => 'h-4 w-4 shrink-0'])
                                Continue with Google
                                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                            </a>
                        @else
                            <p style="margin-bottom: 0; color: #b91c1c; font-weight: 600;"><i class="bi bi-exclamation-triangle-fill"></i> Sign-in is temporarily unavailable. Please try again later.</p>
                        @endif
                        <p class="rate-connect-note" style="margin-top:0.75rem; justify-content:center;">
                            <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                            <span><strong>No one can see your identity.</strong> The driver CANNOT see your name, email, or contact number — your details are used only by TriFair/TFRB for status updates and investigation.</span>
                        </p>
                    </div>
                @endif
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

    /* ---- Map init (same From → To trip map passengers use when rating) ---- */

    var SOLANO_CENTER = (window.TripRouteMap && window.TripRouteMap.SOLANO_CENTER) || [16.5241, 121.2192];
    var serviceBounds = (window.TripRouteMap && window.TripRouteMap.serviceBounds) || null;

    var map = null;
    var mapApi = null;
    var startMarker = null;
    var endMarker = null;
    var endLatLng = null;
    var lastFromGeocodeLatLng = null;
    var lastFromGeocodeTime = 0;

    function mapNotAvailable() {
        var el = document.getElementById('reportMap');
        if (el) { el.style.display = 'none'; }
        var note = document.getElementById('reportMapNote');
        if (note) { note.hidden = false; note.textContent = 'Map unavailable — you can still file your report.'; }
    }

    function updateLocStatus(message) {
        var banner = document.getElementById('reportMapTracking');
        var el = document.getElementById('reportMapLocStatus');
        if (banner) { banner.hidden = false; }
        if (el) { el.textContent = message; }
    }

    function updateSummary(text) {
        var el = document.getElementById('reportMapSummary');
        if (el) { el.textContent = text; }
    }

    function hideNote() {
        var note = document.getElementById('reportMapNote');
        if (note) { note.hidden = true; }
    }

    function showNote(message) {
        var note = document.getElementById('reportMapNote');
        if (note) { note.hidden = false; note.textContent = message; }
    }

    function fitBothMarkers() {
        if (!map || !startMarker) return;
        var pts = [startMarker.getLatLng()];
        if (endMarker) { pts.push(endMarker.getLatLng()); }
        try {
            var b = L.latLngBounds(pts);
            if (!b.isValid()) return;
            if (b.getNorth() === b.getSouth() && b.getEast() === b.getWest()) {
                map.setView(b.getNorthWest(), 16, { animate: true, duration: 0.5 });
                return;
            }
            if (serviceBounds) {
                var clamped = b.intersect(serviceBounds);
                if (clamped && clamped.isValid()) { b = clamped; }
            }
            map.fitBounds(b.pad(0.4), { maxZoom: 16, animate: true, duration: 0.5 });
        } catch (e) { /* ignore mid-transition */ }
    }

    function setStartMarker(latlng) {
        var icon = window.TripRouteMap ? window.TripRouteMap.pickupIcon() : undefined;
        if (startMarker) {
            startMarker.setLatLng(latlng);
            return;
        }
        startMarker = L.marker(latlng, { icon: icon, zIndexOffset: 1000 })
            .addTo(map)
            .bindTooltip('From', { permanent: false, direction: 'top', offset: [0, -16], className: 'marker-label-from' });
    }

    function setEndMarker(latlng) {
        if (!startMarker || !map) return;
        var icon = window.TripRouteMap ? window.TripRouteMap.dropoffIcon() : undefined;
        endLatLng = latlng;
        if (endMarker) {
            endMarker.setLatLng(latlng);
            drawRoute();
            return;
        }
        endMarker = L.marker(latlng, { icon: icon })
            .addTo(map)
            .bindTooltip('To', { permanent: false, direction: 'top', offset: [0, -38], className: 'marker-label-to' });
        drawRoute();
    }

    function applyDestination(latlng) {
        setEndMarker(latlng);
        setTimeout(fitBothMarkers, 150);
    }

    function drawRoute() {
        if (!mapApi || !startMarker || !endMarker) return;
        mapApi.route(startMarker.getLatLng(), endMarker.getLatLng(), true);
    }

    function drawApproximateRoute(start, end) {
        if (mapApi && typeof mapApi.clearOverlays === 'function') { mapApi.clearOverlays(); }
        L.polyline([start, end], {
            color: '#0f2a4a', weight: 4, opacity: 0.5, dashArray: '4 6', interactive: false
        }).addTo(map);
        try {
            map.fitBounds(L.latLngBounds([start, end]).pad(0.4), { maxZoom: 16 });
        } catch (e) { /* ignore */ }
        showNote('Showing approximate route');
    }

    function fmtDistance(m) {
        m = Number(m) || 0;
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
    }

    function fmtDuration(s) {
        s = Math.max(1, Math.round(Number(s) || 0));
        return s >= 3600
            ? Math.floor(s / 3600) + 'h ' + Math.floor((s % 3600) / 60) + 'm'
            : (s >= 60 ? Math.round(s / 60) + ' min' : s + ' sec');
    }

    function onRouteSelected(route) {
        if (!route || !route.coordinates || route.coordinates.length < 2) return;
        var distMeters = route.summary ? route.summary.totalDistance : 0;
        var durSeconds = route.summary ? route.summary.totalTime : 0;
        updateSummary(fmtDistance(distMeters) + ' · ' + fmtDuration(durSeconds));
        hideNote();
    }

    function onRouteError() {
        if (!startMarker || !endMarker) return;
        drawApproximateRoute(startMarker.getLatLng(), endMarker.getLatLng());
    }

    function initMap() {
        try {
            mapApi = window.TripRouteMap.create('reportMap', {
                onRouteSelected: onRouteSelected,
                onRouteError: onRouteError
            });
            if (!mapApi) throw new Error('map init failed');
            map = mapApi.getMap();

            map.invalidateSize();
            setTimeout(function () { map.invalidateSize(); }, 300);

            map.on('click', function (e) {
                if (serviceBounds && !serviceBounds.contains(e.latlng)) return;
                applyDestination(e.latlng);
                reverseGeocode(e.latlng, 'reportMapEnd');
            });

            var fallback = L.latLng(SOLANO_CENTER);
            setStartMarker(fallback);
            mapApi.setView(fallback, 15);
            reverseGeocode(fallback, 'reportMapStart');
            detectLocation();
        } catch (e) {
            mapNotAvailable();
        }
    }

    function getPositionWithRetry(attempts, options) {
        return new Promise(function (resolve, reject) {
            var tried = 0;
            function attempt() {
                navigator.geolocation.getCurrentPosition(function (p) {
                    resolve(p);
                }, function (err) {
                    tried++;
                    if (tried < attempts) {
                        setTimeout(attempt, 1500);
                    } else {
                        reject(err);
                    }
                }, options);
            }
            attempt();
        });
    }

    function detectLocation() {
        if (!navigator.geolocation) {
            updateLocStatus('Location is not supported — tap the map to set destination.');
            return;
        }
        updateLocStatus('Detecting your location...');
        getPositionWithRetry(2, { timeout: 6000, enableHighAccuracy: false, maximumAge: 15000 })
            .then(function (p) {
                var latlng = L.latLng(p.coords.latitude, p.coords.longitude);
                if (serviceBounds && !serviceBounds.contains(latlng)) {
                    updateLocStatus('Location detected outside Solano — tap the map to set destination.');
                    return;
                }
                var acc = Math.round(p.coords.accuracy);
                setStartMarker(latlng);
                mapApi.setView(latlng, 15);
                updateLocStatus('Location detected! Accuracy: ~' + acc + 'm. Search your destination above.');
                reverseGeocode(latlng, 'reportMapStart');
            })
            .catch(function (err) {
                if (err && err.code === 1) {
                    updateLocStatus('Location access denied — enable location or tap the map to continue.');
                } else {
                    updateLocStatus('Couldn\'t detect location — search destination above.');
                }
            });
    }

    /* ---- Destination search (Nominatim via the app proxy) ---- */

    var endInput = document.getElementById('reportMapEnd');
    var searchResults = document.getElementById('reportMapSearchResults');
    var searchTimeout = null;

    if (endInput && searchResults) {
        endInput.addEventListener('input', function () {
            var q = this.value.trim();
            if (q.length < 1) { searchResults.innerHTML = ''; return; }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () { forwardGeocode(q); }, 200);
        });

        endInput.addEventListener('focus', function () {
            if (searchResults.children.length > 0) { searchResults.style.display = 'block'; }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.route-body')) { searchResults.innerHTML = ''; }
        });
    }

    function forwardGeocode(query) {
        // Pad the service box by ~0.003° (~300 m) for search results only —
        // Nominatim often returns coordinates a little past the edge.
        var searchArea = serviceBounds ? serviceBounds.pad(0.003) : null;

        function fetchResults(q) {
            return fetch('/geocode/search?q=' + encodeURIComponent(q)).then(function (r) { return r.json(); });
        }

        function render(list) {
            searchResults.innerHTML = '';
            var inside = [];
            (list || []).forEach(function (item) {
                var latlng = L.latLng(parseFloat(item.lat), parseFloat(item.lon));
                if (isNaN(latlng.lat) || isNaN(latlng.lng)) return;
                if (!searchArea || searchArea.contains(latlng)) inside.push({ item: item, latlng: latlng });
            });
            if (inside.length === 0) {
                searchResults.innerHTML = '<div class="search-item search-empty">No matching place found near Solano.</div>';
                return;
            }
            inside.slice(0, 5).forEach(function (e) {
                var item = e.item, latlng = e.latlng;
                var div = document.createElement('div');
                div.className = 'search-item';
                div.textContent = trimAddress(item.display_name);
                div.setAttribute('data-lat', item.lat);
                div.setAttribute('data-lon', item.lon);
                div.addEventListener('click', function () {
                    endInput.value = this.textContent;
                    searchResults.innerHTML = '';
                    applyDestination(latlng);
                });
                searchResults.appendChild(div);
            });
        }

        function hasInside(list) {
            if (!list || list.length === 0) return false;
            return list.some(function (item) {
                var ll = L.latLng(parseFloat(item.lat), parseFloat(item.lon));
                return !isNaN(ll.lat) && !isNaN(ll.lng) && (!searchArea || searchArea.contains(ll));
            });
        }

        fetchResults(query).then(function (results) {
            if (hasInside(results)) {
                render(results);
                return;
            }
            return fetchResults(query + ', Nueva Vizcaya').then(render);
        }).catch(function () { searchResults.innerHTML = ''; });
    }

    function reverseGeocode(latlng, inputId) {
        var timer = setTimeout(function () { fallbackName(inputId); }, 6000);
        fetch('/geocode/reverse?lat=' + latlng.lat + '&lng=' + latlng.lng)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                clearTimeout(timer);
                if (d.display_name) {
                    document.getElementById(inputId).value = trimAddress(d.display_name);
                } else {
                    fallbackName(inputId);
                }
            }).catch(function () {
                clearTimeout(timer);
                fallbackName(inputId);
            });
    }

    function fallbackName(inputId) {
        var el = document.getElementById(inputId);
        if (!el) return;
        if (el.value && el.value !== 'Detecting location...') return;
        el.value = inputId === 'reportMapStart' ? 'Current location, Solano' : 'Selected point, Solano';
    }

    function trimAddress(addr) {
        var skip = ['philippines', 'cagayan valley', 'region ii', 'luzon', 'valle de cagayan', 'isabela', 'northern luzon'];
        var parts = addr.split(',').map(function (s) { return s.trim(); }).filter(function (s) {
            if (!s) return false;
            if (/^\d{4}$/.test(s)) return false;
            return skip.indexOf(s.toLowerCase()) === -1;
        });
        return parts.slice(0, 5).join(', ');
    }

    /* ---- "Others" violation type → require the reason ---- */

    var typeSelect = document.getElementById('violation_type');
    var othersBox = document.getElementById('othersBox');
    var otherReason = document.getElementById('other_reason');
    if (typeSelect && othersBox && otherReason) {
        var syncOthers = function () {
            var isOthers = typeSelect.value === 'Others';
            othersBox.style.display = isOthers ? 'block' : 'none';
            otherReason.required = isOthers;
            if (!isOthers) { otherReason.value = ''; }
        };
        typeSelect.addEventListener('change', syncOthers);
        syncOthers();
    }

    /* ---- Submit feedback ---- */

    var reportFiles = document.getElementById('proofs');
    var reportFileSummary = document.getElementById('reportFileSummary');
    if (reportFiles && reportFileSummary) {
        reportFiles.addEventListener('change', function () {
            var files = Array.prototype.slice.call(reportFiles.files || []);
            if (!files.length) { reportFileSummary.textContent = ''; return; }
            reportFileSummary.textContent = files.length + ' file' + (files.length === 1 ? '' : 's') + ' selected: ' + files.map(function (file) { return file.name; }).join(', ');
        });
    }

    var form = document.getElementById('reportForm');
    if (form) {
        form.addEventListener('submit', function () {
            var btn = document.getElementById('reportSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Sending report…';
            }
        });
    }

    initMap();
})();
</script>
</body>
</html>
