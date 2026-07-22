<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate {{ $driver->user->name }} - TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e3a5f;
            --primary-light: #2a4a7a;
            --gold: #f5a623;
            --gold-dark: #d48b0a;
            --green: #10b981;
            --green-dark: #059669;
            --red: #ef4444;
            --red-light: #fef2f2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            min-height: 100vh;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: calc(var(--safe-top) + 1.5rem) 1.5rem 2rem;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--primary));
        }
        .page-header .badge-trip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.9);
            margin-bottom: 1rem;
        }
        .driver-info {
            display: flex; align-items: center; gap: 1rem;
            text-align: left;
        }
        .driver-avatar {
            width: 64px; height: 64px; border-radius: 18px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem; color: var(--gold); flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .driver-details h2 {
            font-size: 1.15rem; font-weight: 800; color: white;
            margin-bottom: 0.2rem; letter-spacing: -0.02em;
        }
        .driver-meta {
            display: flex; flex-wrap: wrap; gap: 0.5rem;
            font-size: 0.75rem; color: rgba(255,255,255,0.7);
        }
        .driver-meta span {
            display: inline-flex; align-items: center; gap: 0.25rem;
            background: rgba(255,255,255,0.1); padding: 0.15rem 0.5rem;
            border-radius: 6px;
        }

        .container { max-width: 480px; margin: 0 auto; padding: 0 1rem; }

        .rating-section {
            background: white; border-radius: 20px;
            margin: -1rem 1rem 1rem; padding: 2rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative; z-index: 1;
        }
        .rating-section h3 {
            text-align: center; font-size: 1.1rem; font-weight: 800;
            color: var(--gray-800); margin-bottom: 0.25rem;
        }
        .rating-section .subtitle {
            text-align: center; font-size: 0.85rem; color: var(--gray-400);
            margin-bottom: 1.5rem;
        }

        .stars-row {
            display: flex; justify-content: center; gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .star-btn {
            width: 58px; height: 58px; border: none; border-radius: 16px;
            background: var(--gray-100); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            -webkit-tap-highlight-color: transparent;
        }
        .star-btn i { font-size: 1.5rem; color: var(--gray-300); transition: all 0.15s ease; }
        .star-btn:active { transform: scale(0.9); }
        .star-btn.selected {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            box-shadow: 0 4px 16px rgba(245,166,35,0.3);
            transform: scale(1.05);
        }
        .star-btn.selected i { color: white; }

        .star-labels-row {
            display: flex; justify-content: space-between;
            padding: 0 0.25rem; margin-bottom: 1.25rem;
        }
        .star-labels-row span {
            font-size: 0.7rem; font-weight: 600;
            color: var(--gray-400); text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .feedback-msg {
            text-align: center; padding: 0.5rem 0;
            font-size: 0.95rem; font-weight: 700; color: var(--gray-700);
            min-height: 2rem;
        }
        .feedback-msg .emoji { font-size: 1.5rem; margin-right: 0.4rem; }

        .extra-fields { display: none; }
        .extra-fields.show { display: block; animation: slideUp 0.3s ease; }

        .field-label {
            display: flex; align-items: center; gap: 0.35rem;
            font-size: 0.78rem; font-weight: 700; color: var(--gray-600);
            margin-bottom: 0.4rem;
        }
        .field-label .dot { width: 8px; height: 8px; border-radius: 50%; }

        .field-input {
            width: 100%; padding: 0.75rem 1rem;
            border: 1.5px solid var(--gray-200); border-radius: 12px;
            font-size: 0.9rem; font-family: inherit; color: var(--gray-800);
            background: white; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field-input::placeholder { color: var(--gray-300); }
        .field-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(245,166,35,0.1); }

        .field-textarea {
            width: 100%; padding: 0.75rem 1rem;
            border: 1.5px solid var(--gray-200); border-radius: 12px;
            font-size: 0.9rem; font-family: inherit; color: var(--gray-800);
            background: white; outline: none; resize: none; min-height: 60px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field-textarea::placeholder { color: var(--gray-300); }
        .field-textarea:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(245,166,35,0.1); }

        .location-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; }

        .map-toggle {
            display: flex; align-items: center; justify-content: center;
            gap: 0.4rem; padding: 0.6rem; background: var(--gray-50);
            border: 1.5px dashed var(--gray-200); border-radius: 12px;
            cursor: pointer; color: var(--gray-500); font-size: 0.82rem;
            font-weight: 600; transition: all 0.2s; margin-top: 0.75rem;
        }
        .map-toggle:active { background: var(--gray-100); }

        .map-box {
            display: none; border-radius: 12px; overflow: hidden;
            border: 1.5px solid var(--gray-200); margin-top: 0.75rem;
        }
        .map-box.open { display: block; }
        #rateMap { height: 200px; width: 100%; }
        .map-hint {
            text-align: center; font-size: 0.75rem; color: var(--gray-400);
            padding: 0.5rem; background: var(--gray-50);
        }

        .complaint-box {
            background: var(--red-light); border: 1.5px solid rgba(239,68,68,0.15);
            border-radius: 14px; padding: 1.25rem; margin-top: 1rem;
            display: none;
        }
        .complaint-box.show { display: block; animation: slideUp 0.3s ease; }
        .complaint-title {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; font-weight: 700; color: var(--red); margin-bottom: 1rem;
        }
        .complaint-title .icon {
            width: 26px; height: 26px; background: var(--red); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.75rem;
        }

        .upload-area {
            border: 2px dashed var(--gray-200); border-radius: 12px;
            padding: 1.25rem; text-align: center; cursor: pointer;
            background: white; transition: all 0.2s; margin-top: 0.75rem;
        }
        .upload-area:active { border-color: var(--gold); background: var(--gray-50); }
        .upload-area i { font-size: 1.5rem; color: var(--gray-300); margin-bottom: 0.3rem; }
        .upload-area .main-text { font-size: 0.85rem; color: var(--gray-600); font-weight: 600; }
        .upload-area .sub-text { font-size: 0.72rem; color: var(--gray-400); margin-top: 0.1rem; }

        .file-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem; }
        .file-chip {
            display: inline-flex; align-items: center; gap: 0.25rem;
            background: white; color: var(--primary); padding: 0.25rem 0.5rem;
            border-radius: 6px; font-size: 0.72rem; font-weight: 600;
            border: 1px solid var(--gray-200);
        }

        .btn-submit {
            width: 100%; padding: 0.95rem; border: none; border-radius: 14px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white; font-size: 1rem; font-weight: 800;
            font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 16px rgba(245,166,35,0.25);
            transition: all 0.2s; margin-top: 1.25rem;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-submit:disabled {
            background: var(--gray-200); box-shadow: none;
            color: var(--gray-400); cursor: not-allowed;
        }
        .btn-submit:not(:disabled):active { transform: scale(0.98); }

        .info-note {
            display: flex; align-items: flex-start; gap: 0.35rem;
            font-size: 0.75rem; color: var(--gray-500); margin-top: 0.75rem;
            padding: 0.5rem; background: white; border-radius: 8px;
        }

        .divider { border: none; border-top: 1px solid var(--gray-100); margin: 1.25rem 0; }

        /* Already Rated */
        .screen-center { text-align: center; padding: 2rem 0; }
        .screen-icon {
            width: 80px; height: 80px; border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem; font-size: 2rem; color: white;
        }
        .screen-center h3 { font-size: 1.2rem; font-weight: 800; color: var(--gray-800); margin-bottom: 0.3rem; }
        .screen-center p { font-size: 0.88rem; color: var(--gray-500); margin-bottom: 0.75rem; }
        .screen-stars { display: flex; justify-content: center; gap: 0.35rem; margin: 0.75rem 0; }
        .screen-stars i { font-size: 1.5rem; }

        .btn-action {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 2rem; border: none; border-radius: 12px;
            font-size: 0.9rem; font-weight: 700; font-family: inherit;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-action:active { transform: scale(0.97); }

        @keyframes slideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes popIn { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }

        @media (max-width: 380px) {
            .star-btn { width: 50px; height: 50px; border-radius: 14px; }
            .star-btn i { font-size: 1.3rem; }
            .stars-row { gap: 0.35rem; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container">
        <div class="badge-trip">
            <i class="bi bi-shield-check" style="color: var(--gold);"></i>
            TriFair Verified
        </div>
        <div class="driver-info">
            <div class="driver-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="driver-details">
                <h2>{{ $driver->user->name }}</h2>
                <div class="driver-meta">
                    @if($driver->body_number)
                        <span><i class="bi bi-hash"></i>{{ $driver->body_number }}</span>
                    @endif
                    @if($driver->plate_number)
                        <span><i class="bi bi-upc-scan"></i>{{ $driver->plate_number }}</span>
                    @endif
                    @if($driver->tricycle_color)
                        <span><i class="bi bi-palette-fill"></i>{{ $driver->tricycle_color }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">

    @if(isset($alreadyRated) && $alreadyRated)
        <div class="rating-section">
            <div class="screen-center">
                <div class="screen-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 30px rgba(99,102,241,0.3);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h3>Already Rated Today</h3>
                <p>You already gave <strong>{{ $driver->user->name }}</strong> a rating today.</p>
                <div class="screen-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $existingRating->rating ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= $existingRating->rating ? 'var(--gold)' : 'var(--gray-200)' }};"></i>
                    @endfor
                </div>
                <p style="font-size: 0.8rem; color: var(--gray-400); margin-top: 0.5rem;">One rating per driver per day.</p>
                <button type="button" onclick="window.close()" class="btn-action" style="background: var(--gray-100); color: var(--gray-600); margin-top: 1rem;">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>

    @elseif(session('success'))
        <div class="rating-section">
            <div class="screen-center">
                <div class="screen-icon" style="background: linear-gradient(135deg, var(--green), var(--green-dark)); box-shadow: 0 8px 30px rgba(16,185,129,0.3);">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="screen-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star-fill" style="color: var(--gold);"></i>
                    @endfor
                </div>
                <h3>Thank You!</h3>
                <p>Your rating for <strong>{{ $driver->user->name }}</strong> has been recorded.</p>
                <a href="{{ url()->current() }}" class="btn-action" style="background: var(--primary); color: white;">
                    <i class="bi bi-arrow-repeat"></i> Rate Again
                </a>
            </div>
        </div>

    @else
        <div class="rating-section">
            <h3>Rate Your Trip</h3>
            <p class="subtitle">How was your ride with {{ $driver->user->name }}?</p>

            <form action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="stars-row" id="starGrid">
                    <button type="button" class="star-btn" data-value="1"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="2"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="3"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="4"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="5"><i class="bi bi-star-fill"></i></button>
                </div>
                <div class="star-labels-row">
                    <span>Poor</span>
                    <span>Okay</span>
                    <span>Great</span>
                </div>
                <input type="hidden" name="rating" id="ratingValue" value="">
                <div class="feedback-msg" id="feedbackMsg"></div>

                <div class="extra-fields" id="extraFields">
                    <hr class="divider">

                    <div class="field-label"><i class="bi bi-chat-dots" style="color: var(--primary);"></i> Comment <span style="font-weight:400; color: var(--gray-400);">(optional)</span></div>
                    <textarea name="reason" class="field-textarea" rows="2" placeholder="Tell us about your experience..."></textarea>

                    <div style="margin-top: 1rem;">
                        <div class="field-label"><i class="bi bi-map" style="color: var(--primary);"></i> Trip Route <span style="font-weight:400; color: var(--gray-400);">(optional)</span></div>
                        <div class="location-grid">
                            <div>
                                <div class="field-label" style="font-size:0.72rem;"><span class="dot" style="background: var(--green);"></span> From</div>
                                <input type="text" name="start_location" id="start_location" class="field-input" placeholder="Starting point">
                            </div>
                            <div>
                                <div class="field-label" style="font-size:0.72rem;"><span class="dot" style="background: var(--red);"></span> To</div>
                                <input type="text" name="end_location" id="end_location" class="field-input" placeholder="Destination">
                            </div>
                        </div>
                        <div class="map-toggle" id="mapToggle">
                            <i class="bi bi-pin-map"></i> Use map to set location
                            <i class="bi bi-chevron-down" id="mapChevron" style="transition: transform 0.25s;"></i>
                        </div>
                        <div class="map-box" id="mapBox">
                            <div id="rateMap"></div>
                            <div class="map-hint">Tap to set start (1st) and destination (2nd)</div>
                        </div>
                    </div>

                    <div class="complaint-box" id="complaintBox">
                        <div class="complaint-title">
                            <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                            Report a Problem
                        </div>
                        <div class="location-grid">
                            <div>
                                <div class="field-label">Your Name</div>
                                <input type="text" name="passenger_name" class="field-input" placeholder="Juan Dela Cruz">
                            </div>
                            <div>
                                <div class="field-label">Contact No.</div>
                                <input type="tel" name="passenger_contact" class="field-input" placeholder="09171234567" inputmode="numeric">
                            </div>
                        </div>
                        <div class="upload-area" id="uploadZone">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <div class="main-text">Upload evidence</div>
                            <div class="sub-text">Photo, video, or document (max 20MB)</div>
                        </div>
                        <input type="file" name="proofs[]" id="proofInput" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display:none;">
                        <div class="file-chips" id="fileChips"></div>
                        <div class="info-note">
                            <i class="bi bi-info-circle" style="margin-top:0.05rem; color: var(--gray-400);"></i>
                            <span>Admin may contact you for additional information.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send-fill"></i> Submit Rating
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var selectedRating = 0;
var emojis = ['', '😞', '😐', '🙂', '😊', '🤩'];
var labels = ['', 'Not great', 'Below average', 'It was okay', 'Good ride!', 'Excellent ride!'];
var map = null, startMarker = null, endMarker = null, mapLoaded = false;

document.querySelectorAll('.star-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-value'));
        document.getElementById('ratingValue').value = selectedRating;

        document.querySelectorAll('.star-btn').forEach(function(b, i) {
            if (i < selectedRating) { b.classList.add('selected'); }
            else { b.classList.remove('selected'); }
        });

        document.getElementById('feedbackMsg').innerHTML =
            '<span class="emoji">' + emojis[selectedRating] + '</span> ' + labels[selectedRating];

        document.getElementById('extraFields').classList.add('show');

        var cb = document.getElementById('complaintBox');
        if (selectedRating <= 2) { cb.classList.add('show'); } else { cb.classList.remove('show'); }

        if (navigator.vibrate) navigator.vibrate(15);

        document.getElementById('extraFields').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.getElementById('mapToggle').addEventListener('click', function() {
    var box = document.getElementById('mapBox');
    var chevron = document.getElementById('mapChevron');
    if (box.classList.contains('open')) {
        box.classList.remove('open');
        chevron.style.transform = '';
    } else {
        box.classList.add('open');
        chevron.style.transform = 'rotate(180deg)';
        if (!mapLoaded) initMap();
        setTimeout(function() { if (map) map.invalidateSize(); }, 150);
    }
});

function initMap() {
    try {
        map = L.map('rateMap').setView([12.8797, 121.7740], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap', maxZoom: 18
        }).addTo(map);
        map.on('click', function(e) {
            if (!startMarker) { setMarker(e.latlng, 'start'); }
            else if (!endMarker) { setMarker(e.latlng, 'end'); }
        });
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(p) {
                var latlng = L.latLng(p.coords.latitude, p.coords.longitude);
                map.setView(latlng, 15);
                if (!startMarker) setMarker(latlng, 'start');
            }, function() {}, { timeout: 5000, enableHighAccuracy: true });
        }
        mapLoaded = true;
    } catch(e) {
        document.getElementById('rateMap').innerHTML = '<div style="text-align:center;padding:2rem;color:#9ca3af;"><i class="bi bi-map" style="font-size:1.5rem;"></i><br><small>Map unavailable</small></div>';
    }
}

function setMarker(latlng, type) {
    var color = type === 'start' ? '#059669' : '#dc2626';
    var label = type === 'start' ? 'S' : 'E';
    var icon = L.divIcon({
        html: '<div style="background:' + color + ';color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.25);font-weight:800;font-size:12px;">' + label + '</div>',
        className: '', iconSize: [28, 28], iconAnchor: [14, 14]
    });
    if (type === 'start') {
        if (startMarker) map.removeLayer(startMarker);
        startMarker = L.marker(latlng, {icon: icon}).addTo(map);
        reverseGeocode(latlng, 'start_location');
    } else {
        if (endMarker) map.removeLayer(endMarker);
        endMarker = L.marker(latlng, {icon: icon}).addTo(map);
        reverseGeocode(latlng, 'end_location');
    }
}

function reverseGeocode(latlng, inputId) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + latlng.lat + '&lon=' + latlng.lng)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.display_name) {
                document.getElementById(inputId).value = d.display_name.split(',').slice(0,3).join(',');
            }
        }).catch(function() {});
}

var uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    uploadZone.addEventListener('click', function() {
        document.getElementById('proofInput').click();
    });
}
var proofInput = document.getElementById('proofInput');
if (proofInput) {
    proofInput.addEventListener('change', function() {
        var chips = document.getElementById('fileChips');
        chips.innerHTML = '';
        Array.from(this.files).forEach(function(f) {
            chips.innerHTML += '<span class="file-chip"><i class="bi bi-file-earmark"></i> ' + f.name + '</span>';
        });
    });
}
</script>
</body>
</html>
