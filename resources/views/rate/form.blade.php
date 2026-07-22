<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate Driver - TriFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="margin:0; background:linear-gradient(160deg,#1a2e4a 0%,#1e3a5f 50%,#243b5e 100%); min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

<div style="max-width:480px; margin:0 auto; min-height:100vh; display:flex; flex-direction:column;">

    {{-- DRIVER HEADER --}}
    <div style="text-align:center; padding:2rem 1.5rem 1rem; color:white;">
        <div style="width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,#f5a623,#e09400); display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem; font-size:1.8rem; color:white; border:3px solid rgba(255,255,255,0.25); box-shadow:0 4px 20px rgba(245,166,35,0.35);">
            <i class="bi bi-person-fill"></i>
        </div>
        <div style="font-size:1.25rem; font-weight:800; margin-bottom:0.25rem;">{{ $driver->user->name }}</div>
        <div style="display:flex; justify-content:center; gap:1rem; font-size:0.75rem; opacity:0.7;">
            @if($driver->plate_number)<span><i class="bi bi-upc-scan me-1"></i>{{ $driver->plate_number }}</span>@endif
            @if($driver->body_number)<span><i class="bi bi-bicycle me-1"></i>{{ $driver->body_number }}</span>@endif
            @if($driver->tricycle_color)<span><i class="bi bi-palette-fill me-1"></i>{{ $driver->tricycle_color }}</span>@endif
        </div>
    </div>

    {{-- MAIN BODY --}}
    <div style="flex:1; background:white; border-radius:24px 24px 0 0; padding:1.5rem; margin-top:0.5rem; box-shadow:0 -8px 30px rgba(0,0,0,0.2);">

        {{-- ALREADY RATED --}}
        @if(isset($alreadyRated) && $alreadyRated)
            <div style="text-align:center; padding:3rem 0;">
                <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#2563eb); display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-size:2.5rem; color:white; box-shadow:0 8px 30px rgba(59,130,246,0.3);">
                    <i class="bi bi-info-lg"></i>
                </div>
                <h4 style="font-weight:800; color:#1f2937; margin-bottom:0.5rem;">Already Rated Today</h4>
                <p style="color:#6b7280; font-size:0.9rem;">
                    You gave
                    <strong style="color:#f5a623;">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $existingRating->rating)<i class="bi bi-star-fill"></i>@else<i class="bi bi-star"></i>@endif
                        @endfor
                    </strong>
                    to <strong>{{ $driver->user->name }}</strong> today.
                </p>
                <p style="font-size:0.75rem; color:#9ca3af;">You can only rate once per day.</p>
                <button type="button" onclick="window.close()" style="margin-top:0.5rem; padding:0.75rem 2rem; border:none; border-radius:16px; background:#6b7280; color:white; font-size:0.9rem; font-weight:700; cursor:pointer;">
                    <i class="bi bi-x-lg me-1"></i> Close
                </button>
            </div>

        {{-- SUCCESS (after submit) --}}
        @elseif(session('success'))
            <div style="text-align:center; padding:3rem 0;">
                <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#059669,#10b981); display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-size:2.5rem; color:white; box-shadow:0 8px 30px rgba(5,150,105,0.3);">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h4 style="font-weight:800; color:#1f2937; margin-bottom:0.5rem;">Salamat!</h4>
                <p style="color:#6b7280; font-size:0.9rem;">{{ session('success') }}</p>
                <a href="{{ url()->current() }}" style="display:inline-block; margin-top:1rem; padding:0.75rem 2rem; border:none; border-radius:16px; background:linear-gradient(135deg,#f5a623,#e09400); color:white; font-size:0.9rem; font-weight:700; text-decoration:none; box-shadow:0 4px 16px rgba(245,166,35,0.3);">
                    <i class="bi bi-arrow-repeat me-1"></i> Rate Another Trip
                </a>
            </div>

        {{-- RATING FORM --}}
        @else
            <form action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data" id="ratingForm">
                @csrf

                <div style="text-align:center; margin-bottom:0.25rem;">
                    <div style="font-size:1.1rem; font-weight:700; color:#374151;">How was your trip?</div>
                    <div style="font-size:0.8rem; color:#9ca3af; margin-bottom:1.5rem;">Tap a star to rate</div>
                </div>

                {{-- STAR RATING --}}
                <div style="display:flex; justify-content:center; gap:0.5rem; margin-bottom:0.5rem;" id="starRow">
                    <button type="button" class="star-btn" data-value="1" style="width:56px; height:56px; border:none; background:#f3f4f6; border-radius:16px; font-size:1.6rem; color:#d1d5db; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-star-fill"></i>
                    </button>
                    <button type="button" class="star-btn" data-value="2" style="width:56px; height:56px; border:none; background:#f3f4f6; border-radius:16px; font-size:1.6rem; color:#d1d5db; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-star-fill"></i>
                    </button>
                    <button type="button" class="star-btn" data-value="3" style="width:56px; height:56px; border:none; background:#f3f4f6; border-radius:16px; font-size:1.6rem; color:#d1d5db; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-star-fill"></i>
                    </button>
                    <button type="button" class="star-btn" data-value="4" style="width:56px; height:56px; border:none; background:#f3f4f6; border-radius:16px; font-size:1.6rem; color:#d1d5db; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-star-fill"></i>
                    </button>
                    <button type="button" class="star-btn" data-value="5" style="width:56px; height:56px; border:none; background:#f3f4f6; border-radius:16px; font-size:1.6rem; color:#d1d5db; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-star-fill"></i>
                    </button>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0 0.5rem; margin-bottom:1.5rem;">
                    <span style="font-size:0.65rem; color:#9ca3af; text-transform:uppercase; letter-spacing:0.04em; font-weight:600;">Poor</span>
                    <span style="font-size:0.65rem; color:#9ca3af; text-transform:uppercase; letter-spacing:0.04em; font-weight:600;">Excellent</span>
                </div>

                <div id="emojiFeedback" style="text-align:center; font-size:2rem; margin-bottom:1.25rem; min-height:2.5rem;"></div>
                <input type="hidden" name="rating" id="ratingValue" value="">

                <hr style="border:none; border-top:1px solid #f0f0f0; margin:1.25rem 0;">

                {{-- LOCATION --}}
                <div style="font-size:0.8rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.75rem;">
                    <i class="bi bi-geo-alt me-1"></i> Trip Location <span style="font-size:0.65rem; font-weight:400; color:#9ca3af; text-transform:none;">(optional)</span>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                    <div>
                        <label style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.3rem; color:#6b7280;">
                            <span style="width:8px; height:8px; border-radius:50%; background:#059669; display:inline-block;"></span> From
                        </label>
                        <input type="text" name="start_location" id="start_location" placeholder="Starting point"
                               style="width:100%; padding:0.65rem 0.75rem; border:2px solid #e5e7eb; border-radius:12px; font-size:0.85rem; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.3rem; color:#6b7280;">
                            <span style="width:8px; height:8px; border-radius:50%; background:#dc2626; display:inline-block;"></span> To
                        </label>
                        <input type="text" name="end_location" id="end_location" placeholder="Destination"
                               style="width:100%; padding:0.65rem 0.75rem; border:2px solid #e5e7eb; border-radius:12px; font-size:0.85rem; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div id="mapToggle" style="display:flex; align-items:center; justify-content:center; gap:0.4rem; padding:0.55rem; background:#f9fafb; border:1px dashed #d1d5db; border-radius:12px; color:#6b7280; font-size:0.8rem; font-weight:600; cursor:pointer; margin-bottom:0.75rem;" onclick="toggleMap()">
                    <i class="bi bi-map"></i> Use map to set location
                    <i class="bi bi-chevron-down" id="mapChevron"></i>
                </div>

                <div id="mapWrapper" style="display:none; margin-bottom:0.75rem;">
                    <div id="tripMap" style="height:200px; border-radius:12px; border:2px solid #e5e7eb;"></div>
                    <div style="text-align:center; font-size:0.7rem; color:#9ca3af; margin-top:0.4rem;">Tap the map to set start (1st tap) and destination (2nd tap)</div>
                </div>

                {{-- COMMENT --}}
                <div style="font-size:0.8rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.75rem;">
                    <i class="bi bi-chat-dots me-1"></i> Comment <span style="font-size:0.65rem; font-weight:400; color:#9ca3af; text-transform:none;">(optional)</span>
                </div>
                <textarea name="reason" rows="2" placeholder="Share your experience (optional)..."
                          style="width:100%; padding:0.75rem; border:2px solid #e5e7eb; border-radius:12px; font-size:0.85rem; resize:vertical; min-height:60px; outline:none; font-family:inherit; box-sizing:border-box;"></textarea>

                {{-- COMPLAINT DETAILS (1-2 stars only) --}}
                <div id="extraFields" style="display:none; margin-top:1.25rem;">
                    <hr style="border:none; border-top:1px solid #f0f0f0; margin:1.25rem 0;">

                    <div style="font-size:0.8rem; font-weight:700; color:#dc2626; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.75rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i> Complaint Details
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                        <div>
                            <label style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.3rem; display:block; color:#6b7280;">Your Name</label>
                            <input type="text" name="passenger_name" placeholder="Juan Dela Cruz"
                                   style="width:100%; padding:0.65rem 0.75rem; border:2px solid #e5e7eb; border-radius:12px; font-size:0.85rem; outline:none; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.3rem; display:block; color:#6b7280;">Contact No.</label>
                            <input type="text" name="passenger_contact" placeholder="09171234567"
                                   style="width:100%; padding:0.65rem 0.75rem; border:2px solid #e5e7eb; border-radius:12px; font-size:0.85rem; outline:none; box-sizing:border-box;">
                        </div>
                    </div>
                    <div style="font-size:0.65rem; color:#9ca3af; margin-bottom:0.75rem;">
                        <i class="bi bi-info-circle me-1"></i> Admin may contact you to clarify your complaint.
                    </div>

                    <div style="font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.5rem;">
                        <i class="bi bi-paperclip me-1"></i> Upload Evidence <span style="font-size:0.65rem; font-weight:400; color:#9ca3af; text-transform:none;">(optional)</span>
                    </div>
                    <div onclick="document.getElementById('proofInput').click()"
                         style="border:2px dashed #d1d5db; border-radius:12px; padding:1rem; text-align:center; cursor:pointer; background:#fafafa;">
                        <div style="font-size:1.5rem; color:#d1d5db;"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div style="font-size:0.8rem; color:#6b7280;">Tap to upload files</div>
                        <div style="font-size:0.65rem; color:#9ca3af;">JPG, PNG, MP4, PDF (max 20MB each)</div>
                    </div>
                    <input type="file" name="proofs[]" id="proofInput" multiple accept=".jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.pdf,.doc,.docx" style="display:none;">
                    <div id="fileTags" style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-top:0.5rem;"></div>
                </div>

                {{-- SUBMIT --}}
                <div style="margin-top:1.5rem; padding-bottom:env(safe-area-inset-bottom,0);">
                    <button type="submit" id="submitBtn" disabled
                            style="width:100%; padding:1rem; border:none; border-radius:16px; background:linear-gradient(135deg,#f5a623,#e09400); color:white; font-size:1rem; font-weight:800; cursor:pointer; box-shadow:0 4px 16px rgba(245,166,35,0.3);">
                        <i class="bi bi-send me-1"></i> Submit Rating
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map = null, startMarker = null, endMarker = null, mapLoaded = false;

function toggleMap() {
    var w = document.getElementById('mapWrapper');
    var c = document.getElementById('mapChevron');
    if (w.style.display === 'none') {
        w.style.display = 'block';
        c.style.transform = 'rotate(180deg)';
        if (!mapLoaded) loadMap();
        if (map) setTimeout(function() { map.invalidateSize(); }, 100);
    } else {
        w.style.display = 'none';
        c.style.transform = '';
    }
}

function loadMap() {
    try {
        map = L.map('tripMap').setView([12.8797, 121.7740], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 18
        }).addTo(map);
        map.on('click', function(e) {
            if (!startMarker) {
                setMapMarker(e.latlng, 'start');
            } else if (!endMarker) {
                setMapMarker(e.latlng, 'end');
            }
        });
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(p) {
                map.setView([p.coords.latitude, p.coords.longitude], 15);
            }, function() {}, { timeout: 5000 });
        }
        mapLoaded = true;
    } catch(e) {
        document.getElementById('tripMap').innerHTML = '<div style="text-align:center;padding:2rem;color:#9ca3af;"><i class="bi bi-map" style="font-size:1.5rem;"></i><br><small>Map unavailable</small></div>';
    }
}

function setMapMarker(latlng, type) {
    var color = type === 'start' ? '#059669' : '#dc2626';
    var label = type === 'start' ? 'S' : 'E';
    var icon = L.divIcon({
        html: '<div style="background:' + color + ';color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);font-weight:800;font-size:13px;">' + label + '</div>',
        className: '', iconSize: [30, 30], iconAnchor: [15, 15]
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

// Star rating
var emojis = ['', '\uD83D\uDE1E', '\uD83D\uDE15', '\uD83D\uDE10', '\uD83D\uDE0A', '\uD83E\uDD29'];
var labels = ['', 'Poor', 'Below Average', 'Okay', 'Good', 'Excellent'];
var selectedRating = 0;

document.querySelectorAll('.star-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-value'));
        document.getElementById('ratingValue').value = selectedRating;
        document.getElementById('submitBtn').disabled = false;

        document.querySelectorAll('.star-btn').forEach(function(b, i) {
            if (i < selectedRating) {
                b.style.background = 'linear-gradient(135deg,#f5a623,#e09400)';
                b.style.color = 'white';
                b.style.boxShadow = '0 4px 16px rgba(245,166,35,0.4)';
                b.style.transform = 'scale(1.05)';
            } else {
                b.style.background = '#f3f4f6';
                b.style.color = '#d1d5db';
                b.style.boxShadow = 'none';
                b.style.transform = 'scale(1)';
            }
        });

        document.getElementById('emojiFeedback').innerHTML =
            '<span style="display:inline-block; animation:popIn 0.3s ease;">' + emojis[selectedRating] + '</span> ' +
            '<span style="font-size:0.85rem; font-weight:700; color:#4b5563;">' + labels[selectedRating] + '</span>';

        var ef = document.getElementById('extraFields');
        if (selectedRating <= 2) { ef.style.display = 'block'; } else { ef.style.display = 'none'; }
    });
});

// File upload
var fi = document.getElementById('proofInput');
if (fi) {
    fi.addEventListener('change', function() {
        var tags = document.getElementById('fileTags');
        tags.innerHTML = '';
        Array.from(this.files).forEach(function(f) {
            tags.innerHTML += '<span style="display:inline-flex;align-items:center;gap:0.3rem;background:#eff6ff;color:#1e3a5f;padding:0.25rem 0.5rem;border-radius:8px;font-size:0.7rem;font-weight:600;"><i class="bi bi-file-earmark"></i> ' + f.name + '</span>';
        });
    });
}
</script>
<style>
@keyframes popIn { 0% { transform:scale(0.5); opacity:0; } 70% { transform:scale(1.15); } 100% { transform:scale(1); opacity:1; } }
.star-btn { transition: all 0.15s ease; }
.star-btn:active { transform: scale(0.9) !important; }
input:focus, textarea:focus { border-color: #f5a623 !important; }
@media (max-width: 380px) {
    .star-btn { width: 48px !important; height: 48px !important; font-size: 1.3rem !important; }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>