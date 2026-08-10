<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate {{ $operator->user->name }} - TriFair</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
    <style>
        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
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
        .rate-header { animation: ratePageIn 0.45s ease both; }
        @keyframes ratePageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .rate-stack { padding: 1rem 0 calc(var(--safe-bottom, 0px) + 1.5rem); }

        /* Alerts */
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

        /* Already rated screen */
        .screen-center { text-align: center; padding: 0.5rem 0; }
        .screen-icon {
            width: 72px; height: 72px; border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.8rem; color: #fff;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.3);
        }
        .screen-center h3 { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 0.3rem; }
        .screen-center p { font-size: 0.88rem; color: #64748b; margin-bottom: 0.75rem; }
        .screen-stars { display: flex; justify-content: center; gap: 0.35rem; margin: 0.75rem 0; }
        .screen-stars i { font-size: 1.5rem; }
        .btn-action {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 2rem; border: none; border-radius: 12px;
            font-size: 0.9rem; font-weight: 700; font-family: inherit;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
            background: #f1f5f9; color: #64748b; margin-top: 1rem;
        }
        .btn-action:active { transform: scale(0.97); }

        /* Deviation warning */
        .route-deviation {
            display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #fff; border-radius: 16px; padding: 1.5rem 2rem; z-index: 10000;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); text-align: center; max-width: 320px;
            animation: ratePopIn 0.3s ease;
        }
        .route-deviation.show { display: block; }
        .route-deviation .dev-icon {
            width: 56px; height: 56px; border-radius: 50%; background: #fef2f2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.5rem; color: #dc2626;
        }
        .route-deviation h4 { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 0.3rem; }
        .route-deviation p { font-size: 0.82rem; color: #64748b; margin-bottom: 1rem; }
        .route-deviation .dev-btn {
            padding: 0.6rem 1.5rem; border: none; border-radius: 10px;
            background: #0f2a4a; color: #fff; font-weight: 700;
            font-size: 0.85rem; cursor: pointer; font-family: inherit;
        }
        .route-deviation .dev-dismiss {
            background: none; border: none; color: #64748b; font-size: 0.78rem;
            cursor: pointer; margin-top: 0.5rem; font-family: inherit;
        }
        .route-deviation-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4); z-index: 9999;
        }
        .route-deviation-overlay.show { display: block; }
        @keyframes ratePopIn {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.85); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        /* Location overlay */
        .loc-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5); z-index: 10000;
            align-items: center; justify-content: center; padding: 1.5rem;
        }
        .loc-overlay.show { display: flex; }
        .loc-overlay-card {
            background: #fff; border-radius: 20px; padding: 2rem 1.5rem;
            text-align: center; max-width: 340px; width: 100%;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            animation: ratePopIn2 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes ratePopIn2 {
            from { opacity: 0; transform: scale(0.85); }
            to { opacity: 1; transform: scale(1); }
        }
        .loc-overlay-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #0f2a4a);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.6rem; color: #fff;
        }
        .loc-overlay-card h4 { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
        .loc-overlay-card p { font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-bottom: 1.25rem; }
        .loc-overlay-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.75rem 2rem; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #0f2a4a);
            color: #fff; font-size: 0.9rem; font-weight: 700;
            font-family: inherit; cursor: pointer; width: 100%;
            transition: all 0.2s;
        }
        .loc-overlay-btn:active { transform: scale(0.97); }
        .loc-overlay-skip {
            font-size: 0.78rem; color: #64748b; cursor: pointer;
            margin-top: 0.75rem; text-decoration: underline;
        }
    </style>
</head>
<body>

@include('partials.rate.rating-page-header')

<div class="rate-container">
    <div class="rate-stack">

        @if ($errors->any())
            <div class="rate-alert">
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

        @if(isset($alreadyRated) && $alreadyRated)
            <div class="rate-card">
                <div class="screen-center">
                    <div class="screen-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>Already Rated Today</h3>
                    <p>You already gave <strong>{{ $operator->user->name }}</strong> a rating today.</p>
                    <div class="screen-stars">
                        @php
                            $er = $existingRating->rating;
                            $esOn = '#f5b800';
                            $esOff = '#e2e8f0';
                        @endphp
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $er ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= $er ? $esOn : $esOff }};" aria-hidden="true"></i>
                        @endfor
                    </div>
                    <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem;">One rating per operator per day.</p>
                    <button type="button" onclick="window.close()" class="btn-action">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Close
                    </button>
                </div>
            </div>
        @else
            <section class="rate-card">
                <h3 class="rate-heading">Rate Your Trip</h3>
                <p class="rate-subtitle" style="margin-top: 0.15rem;">How's my ride?</p>
                <div class="rate-driver-row">
                    @include('partials.rate.driver-info-pill', ['driverName' => $operator->user->name])
                </div>
            </section>

            <form action="{{ route('rate.submit', $operator->qr_code) }}" method="POST" enctype="multipart/form-data" id="rateForm" class="rate-stack">
                @csrf

                <input type="hidden" name="rating" id="ratingValue" value="">

                @include('partials.rate.trip-route-map', [
                    'mapId' => 'rateMap',
                    'mode' => 'track',
                    'startAddress' => 'Detecting location...',
                    'endAddress' => 'Type destination or tap map',
                    'summaryText' => 'Select destination',
                ])

                <section class="rate-card" id="starSection" style="display:none;">
                    <div class="rate-stars" id="starGrid" role="radiogroup" aria-label="Rate your ride from 1 to 5 stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" class="rate-star" data-value="{{ $i }}" role="radio" aria-label="Rate {{ $i }} star{{ $i > 1 ? 's' : '' }}" aria-pressed="false"><i class="bi bi-star" aria-hidden="true"></i></button>
                        @endfor
                    </div>
                    <div class="rate-star-labels">
                        <span>Poor</span>
                        <span>Okay</span>
                        <span>Great</span>
                    </div>
                    <div class="rate-feedback" id="feedbackMsg" aria-live="polite"></div>

                    <div id="extraFields">
                        <div class="rate-complaint" id="complaintBox">
                            <div class="rate-complaint-title">
                                <span class="icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                                Report a Problem
                            </div>
                            <label for="complaintType" class="rate-label"><i class="bi bi-list-check" style="color: #dc2626;"></i> What happened?</label>
                            <select name="complaint_type" id="complaintType" class="rate-field">
                                <option value="">Select complaint type...</option>
                                @foreach (\App\Models\Rating::COMPLAINT_TYPES as $complaintOption)
                                    <option value="{{ $complaintOption }}">{{ $complaintOption }}</option>
                                @endforeach
                            </select>
                            <div id="othersBox" style="display:none;">
                                <label for="complaintDetails" class="rate-label" style="margin-top:0.6rem;"><i class="bi bi-pencil-square" style="color:#dc2626;"></i> Describe your complaint</label>
                                <textarea name="complaint_details" id="complaintDetails" class="rate-field" rows="3" placeholder="Please describe your complaint..."></textarea>
                            </div>
                            <div class="rate-field-grid">
                                <div>
                                    <label for="passenger_name" class="rate-label">Your Name</label>
                                    <input type="text" name="passenger_name" id="passenger_name" class="rate-field" placeholder="Juan Dela Cruz">
                                </div>
                                <div>
                                    <label for="passenger_contact" class="rate-label">Contact No.</label>
                                    <input type="tel" name="passenger_contact" id="passenger_contact" class="rate-field" placeholder="09171234567" inputmode="numeric">
                                </div>
                            </div>
                            <div class="rate-upload" id="uploadZone">
                                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                <div class="main-text">Upload evidence</div>
                                <div class="sub-text">Photo, video, or document (max 20MB)</div>
                            </div>
                            <input type="file" name="proofs[]" id="proofInput" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display:none;">
                            <div class="rate-file-chips" id="fileChips"></div>
                            <div class="rate-note">
                                <i class="bi bi-info-circle" aria-hidden="true" style="margin-top:0.05rem;"></i>
                                <span>A TFRB Officer may contact you for additional information.</span>
                            </div>
                        </div>

                        <button type="submit" class="rate-submit" id="submitBtn" disabled>
                            <i class="bi bi-send-fill" aria-hidden="true"></i> Submit Rating
                        </button>
                        <p class="rate-hint" id="submitHint">Tap a star above to rate</p>
                    </div>
                </section>
            </form>
        @endif

    </div>
</div>

<div class="route-deviation-overlay" id="devOverlay"></div>
<div class="route-deviation" id="devWarning">
    <div class="dev-icon"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></div>
    <h4>Off Route!</h4>
    <p>You seem to have deviated from the planned route. The route is being recalculated.</p>
    <button class="dev-btn" onclick="dismissDeviation()">Got it</button>
</div>
<div class="loc-overlay" id="locOverlay">
    <div class="loc-overlay-card">
        <div class="loc-overlay-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></div>
        <h4>Location Access Needed</h4>
        <p>Please enable your device location so we can track your trip route. You can enable it in your browser or device settings.</p>
        <button class="loc-overlay-btn" onclick="retryLocation()"><i class="bi bi-crosshair" aria-hidden="true"></i> Try Again</button>
        <div class="loc-overlay-skip" onclick="skipLocation()">Skip for now</div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="https://unpkg.com/leaflet-polylinedecorator@1.6.0/dist/leaflet.polylineDecorator.js"></script>
<script src="{{ asset('js/rate-map.js') }}"></script>
<script>
(function () {
    'use strict';

    var selectedRating = 0;
    var emojis = ['', '😞', '😐', '🙂', '😊', '🤩'];
    var labels = ['', 'Not great', 'Below average', 'It was okay', 'Good ride!', 'Excellent ride!'];

    var map = null;
    var mapApi = null;
    var startMarker = null;
    var endMarker = null;
    var endLatLng = null;
    var tripAccepted = false;
    var routeCoords = [];
    var approxLine = null;

    var deviationDismissed = false;
    var deviationCooldown = false;
    var lastRerouteLatLng = null;
    var lastRerouteTime = 0;
    var rerouteThreshold = 30;
    var REROUTE_MIN_MS = 15000;

    var trackingWatchId = null;
    var lastFromAccuracy = Infinity;
    var lastFromGeocodeTime = 0;
    var mapLastInteracted = 0;
    var locationCancelled = false;

    var SOLANO_CENTER = window.TripRouteMap.SOLANO_CENTER;
    var serviceBounds = window.TripRouteMap.serviceBounds;

    var solanoPolygon = [
        [16.552472, 121.121654],
        [16.543768, 121.129026],
        [16.536739, 121.129461],
        [16.531032, 121.132792],
        [16.524894, 121.149681],
        [16.522823, 121.152811],
        [16.520146, 121.150737],
        [16.508909, 121.176208],
        [16.508053, 121.181800],
        [16.504280, 121.190442],
        [16.488542, 121.195675],
        [16.495760, 121.201254],
        [16.501945, 121.208924],
        [16.504151, 121.218838],
        [16.507540, 121.223125],
        [16.522704, 121.233863],
        [16.539266, 121.251053],
        [16.542216, 121.254779],
        [16.546993, 121.247680],
        [16.547453, 121.241192],
        [16.549846, 121.236793],
        [16.547856, 121.234772],
        [16.548533, 121.223974],
        [16.555185, 121.221456],
        [16.572587, 121.221670],
        [16.584263, 121.214689],
        [16.578469, 121.198574],
        [16.575769, 121.194501],
        [16.572420, 121.193566],
        [16.572751, 121.191424],
        [16.570236, 121.190585],
        [16.575077, 121.175274],
        [16.571506, 121.172424],
        [16.574153, 121.172616],
        [16.574807, 121.171308],
        [16.573239, 121.170237],
        [16.573585, 121.167889],
        [16.571416, 121.167780],
        [16.571580, 121.163451],
        [16.569220, 121.160299],
        [16.570759, 121.153814],
        [16.568406, 121.152179],
        [16.569147, 121.149812],
        [16.567544, 121.149389],
        [16.567524, 121.145220],
        [16.570332, 121.144245],
        [16.567497, 121.142170],
        [16.569379, 121.140489],
        [16.567169, 121.137273],
        [16.568301, 121.127347],
        [16.567321, 121.125514],
        [16.569330, 121.122047],
        [16.592330, 121.121929],
        [16.594535, 121.111555],
        [16.594452, 121.108061],
        [16.585512, 121.094233],
        [16.562177, 121.094763],
        [16.557363, 121.113903],
        [16.552472, 121.121654]
    ];
    var SOLANO_BUFFER_METERS = 250;

    function inSolano(latlng) {
        var pt = latlng.lat != null ? latlng : L.latLng(latlng[0], latlng[1]);
        var lat = pt.lat, lng = pt.lng;
        var inside = false;
        for (var i = 0, j = solanoPolygon.length - 1; i < solanoPolygon.length; j = i++) {
            var latI = solanoPolygon[i][0], lngI = solanoPolygon[i][1];
            var latJ = solanoPolygon[j][0], lngJ = solanoPolygon[j][1];
            if ((lngI > lng) !== (lngJ > lng) && lat < (lngJ - lngI) * (lng - lngI) / (lngJ - lngI) + latI) {
                inside = !inside;
            }
        }
        if (inside) return true;
        var a = { lat: solanoPolygon[0][0], lng: solanoPolygon[0][1] };
        for (var k = 1; k < solanoPolygon.length; k++) {
            var b = { lat: solanoPolygon[k][0], lng: solanoPolygon[k][1] };
            if (distToSegment(pt, a, b) <= SOLANO_BUFFER_METERS) return true;
            a = b;
        }
        return false;
    }

    function distToSegment(p, a, b) {
        var toRad = Math.PI / 180;
        var cosLat = Math.cos(p.lat * toRad);
        function toXY(latlng) {
            return { x: latlng.lng * toRad * cosLat, y: latlng.lat * toRad };
        }
        var A = toXY(a), B = toXY(b), P = toXY(p);
        var dx = B.x - A.x, dy = B.y - A.y;
        var len2 = dx * dx + dy * dy;
        var t = len2 === 0 ? 0 : Math.max(0, Math.min(1, ((P.x - A.x) * dx + (P.y - A.y) * dy) / len2));
        var ex = P.x - (A.x + t * dx), ey = P.y - (A.y + t * dy);
        return 6371000 * Math.sqrt(ex * ex + ey * ey);
    }

    /* ---- Map init ---- */

    function initMap() {
        try {
            mapApi = window.TripRouteMap.create('rateMap', {
                onRouteSelected: onRouteSelected,
                onRouteError: onRouteError
            });
            if (!mapApi) throw new Error('map init failed');
            map = mapApi.getMap();

            map.on('click', function (e) {
                if (!serviceBounds.contains(e.latlng)) return;
                applyDestination(e.latlng);
                reverseGeocode(e.latlng, 'rateMapEnd');
            });

            detectLocation();
        } catch (e) {
            document.getElementById('rateMap').innerHTML = '<div style="text-align:center;padding:2rem;color:#94a3b8;"><i class="bi bi-map" style="font-size:1.5rem;"></i><br><small>Map unavailable</small></div>';
        }
    }

    /* ---- Location detection + tracking ---- */

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
            setFallbackLocation();
            return;
        }
        updateLocStatus('Detecting your location...');
        getPositionWithRetry(3, { timeout: 15000, enableHighAccuracy: true, maximumAge: 10000 })
            .then(function (p) {
                if (locationCancelled) { return; }
                var latlng = L.latLng(p.coords.latitude, p.coords.longitude);
                document.getElementById('locOverlay').classList.remove('show');
                if (serviceBounds.contains(latlng)) {
                    setStartMarker(latlng);
                    var acc = Math.round(p.coords.accuracy);
                    var inSolanoNow = inSolano(latlng);
                    mapApi.setView(inSolanoNow ? latlng : L.latLng(SOLANO_CENTER), inSolanoNow ? 16 : 14);
                    updateLocStatus(inSolanoNow
                        ? 'Location detected! Accuracy: ~' + acc + 'm. Tracking movement.'
                        : 'Location detected outside Solano (nearby town). Tracking movement.', 'ok');
                    reverseGeocode(latlng, 'rateMapStart');
                    startTracking();
                } else {
                    setFallbackLocation();
                }
            })
            .catch(function (err) {
                if (locationCancelled) { return; }
                if (err && err.code === 1) {
                    updateLocStatus('Location access denied. Enable location in your browser and try again.', 'error');
                    document.getElementById('locOverlay').classList.add('show');
                } else {
                    setFallbackLocation();
                }
            });
    }

    function retryLocation() {
        locationCancelled = false;
        document.getElementById('locOverlay').classList.remove('show');
        updateLocStatus('Detecting your location...');
        detectLocation();
    }

    function skipLocation() {
        locationCancelled = true;
        document.getElementById('locOverlay').classList.remove('show');
        setFallbackLocation();
    }

    function setFallbackLocation() {
        var fallback = L.latLng(SOLANO_CENTER);
        setStartMarker(fallback);
        mapApi.setView(fallback, 14);
        updateLocStatus('Using Solano center — type destination or tap map.', 'ok');
        reverseGeocode(fallback, 'rateMapStart');
        startTracking();
    }

    function startTracking() {
        if (!navigator.geolocation) return;
        trackingWatchId = navigator.geolocation.watchPosition(function (p) {
            var latlng = L.latLng(p.coords.latitude, p.coords.longitude);
            if (!serviceBounds.contains(latlng)) return;
            var acc = p.coords.accuracy || 999;
            var now = Date.now();

            if (startMarker) {
                startMarker.setLatLng(latlng);
                followMarker(latlng);
            }

            if (lastFromAccuracy === Infinity || now - lastFromGeocodeTime > 2500 || acc < lastFromAccuracy) {
                if (lastFromAccuracy === Infinity || acc < lastFromAccuracy) lastFromAccuracy = acc;
                lastFromGeocodeTime = now;
                reverseGeocode(latlng, 'rateMapStart');
            }
            updateLocStatus('Tracking active. Accuracy: ~' + Math.round(acc) + 'm', 'ok');

            if (endMarker && endLatLng) {
                checkDeviation(latlng);
                maybeReroute(latlng);
            }
        }, function () {}, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
    }

    function followMarker(latlng) {
        if (!map || !latlng) return;
        if (!map.__followBound) {
            map.__followBound = true;
            map.on('dragstart', markMapInteraction);
            map.on('zoomstart', markMapInteraction);
            map.on('touchstart', markMapInteraction);
        }
        if (Date.now() - mapLastInteracted < 8000) return;
        var size = map.getSize();
        var inner = L.latLngBounds(
            map.containerPointToLatLng(L.point(size.x * 0.25, size.y * 0.3)),
            map.containerPointToLatLng(L.point(size.x * 0.75, size.y * 0.7))
        );
        if (!inner.contains(latlng)) {
            map.panTo(latlng, { animate: true, duration: 0.4 });
        }
    }

    function markMapInteraction() { mapLastInteracted = Date.now(); }

    /* ---- Markers ---- */

    function setStartMarker(latlng) {
        var icon = window.TripRouteMap.pickupIcon();
        if (startMarker) {
            startMarker.setLatLng(latlng);
            return;
        }
        startMarker = L.marker(latlng, { icon: icon, zIndexOffset: 1000 }).addTo(map);
    }

    function setEndMarker(latlng) {
        if (!startMarker || !map) return;
        var icon = window.TripRouteMap.dropoffIcon();
        if (endMarker) {
            endMarker.setLatLng(latlng);
            drawRoute();
            return;
        }
        endMarker = L.marker(latlng, { icon: icon }).addTo(map);
        drawRoute();
    }

    function applyDestination(latlng) {
        endLatLng = latlng;
        lastRerouteLatLng = null;
        lastRerouteTime = 0;
        deviationCooldown = false;
        deviationDismissed = false;
        var ov = document.getElementById('devOverlay');
        var dw = document.getElementById('devWarning');
        if (ov) ov.classList.remove('show');
        if (dw) dw.classList.remove('show');
        setEndMarker(latlng);
    }

    /* ---- Route drawing ---- */

    function onRouteSelected(route) {
        if (!route || !route.coordinates || route.coordinates.length < 2) return;
        routeCoords = route.coordinates.slice();
        var distMeters = route.summary ? route.summary.totalDistance : 0;
        var durSeconds = route.summary ? route.summary.totalTime : 0;
        updateSummary(fmtDistance(distMeters) + ' · ' + fmtDuration(durSeconds));
        hideNote();
        revealStars();
    }

    function onRouteError() {
        if (!startMarker || !endMarker) return;
        drawApproximateRoute(startMarker.getLatLng(), endMarker.getLatLng());
    }

    function drawRoute(fitView, skipTripCheck) {
        if (!startMarker || !endMarker) { clearRouteLayers(); return; }
        var start = startMarker.getLatLng();
        var end = endMarker.getLatLng();

        if (!skipTripCheck) {
            var validationError = tripValidationError(start, end);
            if (validationError) {
                tripAccepted = false;
                clearRouteLayers();
                blockRating();
                showNote(validationError, true);
                return;
            }
        }

        tripAccepted = true;
        clearRouteLayers();
        mapApi.route(start, end, fitView !== false);
    }

    function drawApproximateRoute(start, end) {
        if (!mapApi) return;
        clearRouteLayers();
        routeCoords = [start, end];
        approxLine = L.polyline([start, end], {
            color: '#0f2a4a', weight: 4, opacity: 0.5, dashArray: '4 6', interactive: false
        }).addTo(map);
        mapApi.fitBounds([start, end]);

        var distMeters = start.distanceTo(end);
        updateSummary(fmtDistance(distMeters) + ' · — <span style="opacity:0.75;">(straight line)</span>');
        showNote('Showing approximate route', false);
        revealStars();
    }

    function clearRouteLayers() {
        routeCoords = [];
        if (approxLine && map) { map.removeLayer(approxLine); approxLine = null; }
        if (mapApi) mapApi.clearOverlays();
    }

    function tripValidationError(from, to) {
        if (!from || !to) return null;
        if (!serviceBounds.contains(from) || !serviceBounds.contains(to)) {
            return 'Too far from Solano. Only trips to/from nearby towns (~15 km) are accepted.';
        }
        if (!inSolano(from) && !inSolano(to)) {
            return 'Both From and To are outside Solano. At least one must be inside Solano.';
        }
        return null;
    }

    function checkDeviation(currentLatLng) {
        if (routeCoords.length < 2 || deviationDismissed || deviationCooldown) return;
        var minDist = Infinity;
        for (var i = 0; i < routeCoords.length - 1; i++) {
            var d = distToSegment(currentLatLng, routeCoords[i], routeCoords[i + 1]);
            if (d < minDist) minDist = d;
        }
        if (minDist > 100) {
            showDeviationWarning();
        }
    }

    function showDeviationWarning() {
        deviationCooldown = true;
        document.getElementById('devOverlay').classList.add('show');
        document.getElementById('devWarning').classList.add('show');
        if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        setTimeout(function () { deviationCooldown = false; }, 30000);
    }

    function maybeReroute(latlng) {
        var now = Date.now();
        var movedEnough = !lastRerouteLatLng || latlng.distanceTo(lastRerouteLatLng) > rerouteThreshold;
        var stale = now - lastRerouteTime > REROUTE_MIN_MS;
        if (tripAccepted && movedEnough && stale) {
            drawRoute(false, true);
            lastRerouteLatLng = latlng;
            lastRerouteTime = now;
        }
    }

    /* ---- Rating reveal / block ---- */

    function blockRating() {
        selectedRating = 0;
        document.getElementById('ratingValue').value = '';
        var sec = document.getElementById('starSection');
        if (sec) sec.style.display = 'none';
        document.querySelectorAll('.rate-star').forEach(function (b) {
            b.classList.remove('selected');
            b.setAttribute('aria-pressed', 'false');
            var icon = b.querySelector('i');
            icon.classList.remove('bi-star-fill');
            icon.classList.add('bi-star');
        });
        var fb = document.getElementById('feedbackMsg');
        if (fb) fb.innerHTML = '';
        var ef = document.getElementById('extraFields');
        if (ef) ef.classList.remove('show');
        var cb = document.getElementById('complaintBox');
        if (cb) cb.classList.remove('show');
        var ob = document.getElementById('othersBox');
        if (ob) ob.style.display = 'none';
        var sb = document.getElementById('submitBtn');
        if (sb) sb.disabled = true;
        var sh = document.getElementById('submitHint');
        if (sh) sh.style.display = '';
    }

    function revealStars() {
        var sec = document.getElementById('starSection');
        if (!sec || sec.style.display === 'block') return;
        sec.style.display = 'block';
        setTimeout(function () {
            sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 250);
    }

    /* ---- Status / summary / notes ---- */

    function updateLocStatus(msg, state) {
        var banner = document.getElementById('rateMapTracking');
        var el = document.getElementById('rateMapLocStatus');
        if (!banner || !el) return;
        banner.hidden = false;
        banner.className = 'tracking-banner' + (state === 'error' ? ' error' : state === 'warn' ? ' warn' : '');
        if (state === 'ok') {
            el.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>' + escHtml(msg) + '</span>';
        } else if (state === 'error') {
            el.innerHTML = '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>' + escHtml(msg) + '</span>';
        } else {
            el.innerHTML = '<span class="dot-pulse" aria-hidden="true"></span><span>' + escHtml(msg) + '</span>';
        }
    }

    function updateSummary(text) {
        var el = document.getElementById('rateMapSummary');
        if (el) el.innerHTML = text;
    }

    function showNote(msg, isError) {
        var note = document.getElementById('rateMapNote');
        if (!note) return;
        if (!msg) {
            note.hidden = true;
            note.className = 'map-note';
            note.textContent = '';
            return;
        }
        note.hidden = false;
        note.className = 'map-note' + (isError ? ' error' : '');
        note.innerHTML = (isError
            ? '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> '
            : '<i class="bi bi-info-circle" aria-hidden="true"></i> ') + escHtml(msg);
    }

    function hideNote() {
        var note = document.getElementById('rateMapNote');
        if (note) {
            note.hidden = true;
            note.className = 'map-note';
            note.textContent = '';
        }
    }

    function fmtDistance(m) {
        m = Number(m) || 0;
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
    }

    function fmtDuration(s) {
        s = Math.max(1, Math.round(Number(s) || 0));
        return s >= 3600
            ? Math.floor(s / 3600) + 'h ' + Math.floor((s % 3600) / 60) + 'm'
            : s >= 60
                ? Math.round(s / 60) + ' min'
                : s + ' sec';
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    /* ---- Destination search (Nominatim) ---- */

    var endInput = document.getElementById('rateMapEnd');
    var searchResults = document.getElementById('rateMapSearchResults');
    var searchTimeout = null;
    var lastGeocodeCall = 0;

    endInput.addEventListener('input', function () {
        var q = this.value.trim();
        if (q.length < 1) { searchResults.innerHTML = ''; return; }
        clearTimeout(searchTimeout);
        var wait = Math.max(500, lastGeocodeCall + 1200 - Date.now());
        searchTimeout = setTimeout(function () { forwardGeocode(q); }, wait);
    });

    endInput.addEventListener('focus', function () {
        if (searchResults.children.length > 0) searchResults.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.route-body')) searchResults.innerHTML = '';
    });

    function forwardGeocode(query) {
        lastGeocodeCall = Date.now();
        var vb = '121.0,16.7,121.4,16.3';
        var base = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&viewbox=' + vb + '&limit=10&addressdetails=1';

        function render(results) {
            searchResults.innerHTML = '';
            if (!results || results.length === 0) return;

            var inside = [], outside = [];
            results.forEach(function (item) {
                var latlng = L.latLng(parseFloat(item.lat), parseFloat(item.lon));
                (serviceBounds.contains(latlng) ? inside : outside).push({ item: item, latlng: latlng });
            });

            var list = inside.length > 0 ? inside : outside.slice(0, 5);

            list.forEach(function (e) {
                var item = e.item, latlng = e.latlng;

                var div = document.createElement('div');
                div.className = 'search-item';
                div.textContent = trimAddress(item.display_name);
                div.setAttribute('data-lat', item.lat);
                div.setAttribute('data-lon', item.lon);
                div.addEventListener('click', function () {
                    endInput.value = this.textContent;
                    searchResults.innerHTML = '';
                    var ll = L.latLng(parseFloat(this.getAttribute('data-lat')), parseFloat(this.getAttribute('data-lon')));
                    if (!serviceBounds.contains(ll)) {
                        updateLocStatus('Destination is too far from Solano. Only nearby towns (~15 km) are accepted.', 'warn');
                        return;
                    }
                    applyDestination(ll);
                });
                searchResults.appendChild(div);
            });
        }

        fetch(base + '&q=' + encodeURIComponent(query))
            .then(function (r) { return r.json(); })
            .then(function (results) {
                if (!results || results.length === 0) {
                    return fetch(base + '&q=' + encodeURIComponent(query + ', Nueva Vizcaya'))
                        .then(function (r) { return r.json(); })
                        .then(render);
                }
                var hasInside = results.some(function (item) {
                    return serviceBounds.contains(L.latLng(parseFloat(item.lat), parseFloat(item.lon)));
                });
                if (!hasInside) {
                    return fetch(base + '&q=' + encodeURIComponent(query + ', Nueva Vizcaya'))
                        .then(function (r) { return r.json(); })
                        .then(function (fb) { render(results.concat(fb)); });
                }
                render(results);
            }).catch(function () { searchResults.innerHTML = ''; });
    }

    function reverseGeocode(latlng, inputId) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + latlng.lat + '&lon=' + latlng.lng + '&zoom=18&addressdetails=1')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.display_name) {
                    document.getElementById(inputId).value = trimAddress(d.display_name);
                }
            }).catch(function () {});
    }

    function trimAddress(addr) {
        var skip = ['philippines', 'cagayan valley', 'region ii', 'luzon', 'valle de cagayan', 'isabela', 'northern luzon'];
        var parts = addr.split(',').map(function (s) { return s.trim(); }).filter(function (s) {
            if (!s) return false;
            if (/^\d{4}$/.test(s)) return false;
            if (skip.indexOf(s.toLowerCase()) !== -1) return false;
            return true;
        });
        return parts.slice(0, 5).join(', ');
    }

    /* ---- Stars ---- */

    document.querySelectorAll('.rate-star').forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectedRating = parseInt(this.getAttribute('data-value'), 10);
            document.getElementById('ratingValue').value = selectedRating;

            document.querySelectorAll('.rate-star').forEach(function (b, i) {
                var on = i < selectedRating;
                b.classList.toggle('selected', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                var icon = b.querySelector('i');
                icon.classList.toggle('bi-star-fill', on);
                icon.classList.toggle('bi-star', !on);
            });

            document.getElementById('feedbackMsg').innerHTML =
                '<span class="emoji">' + emojis[selectedRating] + '</span> ' + labels[selectedRating];

            document.getElementById('submitBtn').disabled = false;
            document.getElementById('submitHint').style.display = 'none';

            var cb = document.getElementById('complaintBox');
            if (selectedRating <= 2) { cb.classList.add('show'); } else { cb.classList.remove('show'); }

            if (navigator.vibrate) navigator.vibrate(15);

            document.getElementById('starSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    /* ---- Complaint "Others" ---- */

    var complaintType = document.getElementById('complaintType');
    if (complaintType) {
        complaintType.addEventListener('change', function () {
            document.getElementById('othersBox').style.display = (this.value === 'Others') ? 'block' : 'none';
        });
    }

    /* ---- File upload ---- */

    var uploadZone = document.getElementById('uploadZone');
    if (uploadZone) {
        uploadZone.addEventListener('click', function () {
            document.getElementById('proofInput').click();
        });
    }
    var proofInput = document.getElementById('proofInput');
    if (proofInput) {
        proofInput.addEventListener('change', function () {
            var chips = document.getElementById('fileChips');
            var files = Array.from(this.files);
            var MAX_FILES = 3;
            var MAX_SIZE = 20 * 1024 * 1024;
            var oversized = files.filter(function (f) { return f.size > MAX_SIZE; });

            chips.innerHTML = '';
            if (files.length > MAX_FILES) {
                chips.innerHTML = '<span class="rate-file-chip" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Up to 3 files only — ' + files.length + ' selected</span>';
                this.value = '';
                return;
            }
            if (oversized.length > 0) {
                chips.innerHTML = '<span class="rate-file-chip" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> "' + escHtml(oversized[0].name) + '" is over 20MB</span>';
                this.value = '';
                return;
            }
            files.forEach(function (f) {
                chips.innerHTML += '<span class="rate-file-chip"><i class="bi bi-file-earmark" aria-hidden="true"></i> ' + escHtml(f.name) + '</span>';
            });
        });
    }

    /* ---- Global dismiss ---- */

    window.dismissDeviation = function () {
        document.getElementById('devOverlay').classList.remove('show');
        document.getElementById('devWarning').classList.remove('show');
        deviationDismissed = false;
    };
    window.retryLocation = retryLocation;
    window.skipLocation = skipLocation;

    setTimeout(function () { initMap(); }, 300);
})();
</script>
</body>
</html>
