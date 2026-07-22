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
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(160deg, #1a2e4a 0%, #1e3a5f 50%, #243b5e 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .rate-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .driver-header {
            text-align: center;
            padding: 2rem 1.5rem 1rem;
            color: white;
        }
        .driver-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5a623, #e09400);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.8rem;
            color: white;
            border: 3px solid rgba(255,255,255,0.25);
            box-shadow: 0 4px 20px rgba(245,166,35,0.35);
        }
        .driver-name {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        .driver-meta {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .rate-body {
            flex: 1;
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 1.5rem;
            margin-top: 0.5rem;
            box-shadow: 0 -8px 30px rgba(0,0,0,0.2);
        }

        .question-text {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-700, #374151);
            margin-bottom: 0.25rem;
        }
        .question-sub {
            text-align: center;
            font-size: 0.8rem;
            color: var(--gray-400, #9ca3af);
            margin-bottom: 1.5rem;
        }

        .star-row {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        .star-btn {
            width: 56px;
            height: 56px;
            border: none;
            background: #f3f4f6;
            border-radius: 16px;
            font-size: 1.6rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .star-btn:active { transform: scale(0.9); }
        .star-btn.selected {
            background: linear-gradient(135deg, #f5a623, #e09400);
            color: white;
            box-shadow: 0 4px 16px rgba(245,166,35,0.4);
            transform: scale(1.05);
        }
        .star-labels {
            display: flex;
            justify-content: space-between;
            padding: 0 0.5rem;
            margin-bottom: 1.75rem;
        }
        .star-labels span {
            font-size: 0.65rem;
            color: var(--gray-400, #9ca3af);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .emoji-feedback {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            min-height: 2.5rem;
            transition: all 0.3s ease;
        }

        .section-divider {
            border: none;
            border-top: 1px solid #f0f0f0;
            margin: 1.25rem 0;
        }

        .section-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gray-500, #6b7280);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .location-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .location-field {
            position: relative;
        }
        .location-field label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .location-field label .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-start { background: #059669; }
        .dot-end { background: #dc2626; }
        .location-field input {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.85rem;
            transition: border-color 0.2s;
            outline: none;
        }
        .location-field input:focus {
            border-color: #f5a623;
        }
        .location-field input::placeholder {
            color: #c4c9d4;
        }

        .map-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.55rem;
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            color: var(--gray-500, #6b7280);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.75rem;
        }
        .map-toggle:hover { background: #f3f4f6; border-color: #f5a623; color: #f5a623; }

        .map-wrapper {
            display: none;
            margin-bottom: 0.75rem;
        }
        .map-wrapper.show { display: block; }
        #tripMap {
            height: 200px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        .map-hint {
            text-align: center;
            font-size: 0.7rem;
            color: var(--gray-400, #9ca3af);
            margin-top: 0.4rem;
        }

        .comment-box textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.85rem;
            resize: vertical;
            min-height: 60px;
            outline: none;
            font-family: inherit;
        }
        .comment-box textarea:focus { border-color: #f5a623; }
        .comment-box textarea::placeholder { color: #c4c9d4; }

        .extra-fields {
            display: none;
            animation: slideDown 0.3s ease;
        }
        .extra-fields.show { display: block; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .contact-field label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.3rem;
            display: block;
            color: var(--gray-500, #6b7280);
        }
        .contact-field input {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.85rem;
            outline: none;
        }
        .contact-field input:focus { border-color: #f5a623; }
        .contact-field input::placeholder { color: #c4c9d4; }

        .proof-upload {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
        }
        .proof-upload:hover { border-color: #f5a623; background: #fffbeb; }
        .proof-upload input { display: none; }
        .proof-upload .upload-icon { font-size: 1.5rem; color: #d1d5db; }
        .proof-upload .upload-text { font-size: 0.8rem; color: var(--gray-500, #6b7280); margin-top: 0.25rem; }
        .proof-upload .upload-hint { font-size: 0.65rem; color: var(--gray-400, #9ca3af); margin-top: 0.15rem; }
        .file-tags { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem; }
        .file-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #eff6ff;
            color: var(--primary, #1e3a5f);
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .file-tag .remove-file { cursor: pointer; opacity: 0.5; }

        .submit-area {
            margin-top: 1.5rem;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        .btn-submit {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #f5a623, #e09400);
            color: white;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(245,166,35,0.3);
        }
        .btn-submit:disabled {
            background: #d1d5db;
            box-shadow: none;
            cursor: not-allowed;
        }
        .btn-submit:not(:disabled):active { transform: scale(0.97); }

        .success-view {
            display: none;
            text-align: center;
            padding: 3rem 1.5rem;
        }
        .success-view.show { display: block; }
        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #10b981);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 8px 30px rgba(5,150,105,0.3);
        }
        .already-view {
            display: none;
            text-align: center;
            padding: 3rem 1.5rem;
        }
        .already-view.show { display: block; }

        @media (max-width: 380px) {
            .location-grid, .contact-grid { grid-template-columns: 1fr; }
            .star-btn { width: 48px; height: 48px; font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <div class="rate-container">
        <div class="driver-header">
            <div class="driver-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="driver-name">{{ $driver->user->name }}</div>
            <div class="driver-meta">
                @if($driver->plate_number)<span><i class="bi bi-upc-scan me-1"></i>{{ $driver->plate_number }}</span>@endif
                @if($driver->body_number)<span><i class="bi bi-bicycle me-1"></i>{{ $driver->body_number }}</span>@endif
                @if($driver->tricycle_color)<span><i class="bi bi-palette-fill me-1"></i>{{ $driver->tricycle_color }}</span>@endif
            </div>
        </div>

        <div class="rate-body">
            {{-- SUCCESS VIEW --}}
            <div class="success-view" id="successView">
                <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                <h4 style="font-weight: 800; color: var(--gray-800, #1f2937); margin-bottom: 0.5rem;">Salamat!</h4>
                <p style="color: var(--gray-500, #6b7280); font-size: 0.9rem;">Your feedback helps us improve our service.</p>
                <button type="button" class="btn-submit" onclick="location.reload()" style="margin-top: 1rem;">
                    <i class="bi bi-arrow-repeat me-1"></i> Rate Another Trip
                </button>
            </div>

            {{-- ALREADY RATED VIEW --}}
            <div class="already-view" id="alreadyView">
                <div class="success-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <i class="bi bi-info-lg"></i>
                </div>
                <h4 style="font-weight: 800; color: var(--gray-800, #1f2937); margin-bottom: 0.5rem;">Already Rated Today</h4>
                <p style="color: var(--gray-500, #6b7280); font-size: 0.9rem;">
                    You already gave
                    <strong style="color: #f5a623;">{{ $existingRating->rating }} star{{ $existingRating->rating > 1 ? 's' : '' }}</strong>
                    to <strong>{{ $driver->user->name }}</strong> today.
                </p>
                <p style="font-size: 0.75rem; color: var(--gray-400, #9ca3af);">You can only rate once per day.</p>
                <button type="button" class="btn-submit" onclick="window.close()" style="margin-top: 0.5rem; background: #6b7280;">
                    <i class="bi bi-x-lg me-1"></i> Close
                </button>
            </div>

            {{-- RATING FORM --}}
            <form id="ratingForm" action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data" style="{{ ($alreadyRated ?? false) ? 'display:none;' : '' }}">
                @csrf

                <div class="question-text">How was your trip?</div>
                <div class="question-sub">Tap a star to rate</div>

                <div class="star-row" id="starRow">
                    <button type="button" class="star-btn" data-value="1"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="2"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="3"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="4"><i class="bi bi-star-fill"></i></button>
                    <button type="button" class="star-btn" data-value="5"><i class="bi bi-star-fill"></i></button>
                </div>
                <div class="star-labels">
                    <span>Poor</span>
                    <span>Excellent</span>
                </div>

                <div class="emoji-feedback" id="emojiFeedback"></div>

                <input type="hidden" name="rating" id="ratingValue" value="">

                <hr class="section-divider">

                <div class="section-title">
                    <i class="bi bi-geo-alt"></i> Trip Location
                    <span style="font-size: 0.65rem; font-weight: 400; color: var(--gray-400, #9ca3af); text-transform: none;">(optional)</span>
                </div>

                <div class="location-grid">
                    <div class="location-field">
                        <label><span class="dot dot-start"></span> From</label>
                        <input type="text" name="start_location" id="start_location" placeholder="Starting point">
                    </div>
                    <div class="location-field">
                        <label><span class="dot dot-end"></span> To</label>
                        <input type="text" name="end_location" id="end_location" placeholder="Destination">
                    </div>
                </div>

                <div class="map-toggle" id="mapToggle" onclick="toggleMap()">
                    <i class="bi bi-map"></i> Use map to set location
                    <i class="bi bi-chevron-down" id="mapChevron"></i>
                </div>

                <div class="map-wrapper" id="mapWrapper">
                    <div id="tripMap"></div>
                    <div class="map-hint">Tap the map to set start (1st tap) and destination (2nd tap)</div>
                </div>

                <div class="section-title">
                    <i class="bi bi-chat-dots"></i> Comment
                    <span style="font-size: 0.65rem; font-weight: 400; color: var(--gray-400, #9ca3af); text-transform: none;">(optional)</span>
                </div>
                <div class="comment-box">
                    <textarea name="reason" id="reason" placeholder="Share your experience (optional)..." rows="2"></textarea>
                </div>

                {{-- EXTRA FIELDS: only for low ratings (1-2 stars) --}}
                <div class="extra-fields" id="extraFields">
                    <hr class="section-divider">

                    <div class="section-title" style="color: var(--danger, #dc2626);">
                        <i class="bi bi-exclamation-triangle"></i> Complaint Details
                    </div>

                    <div class="contact-grid" style="margin-bottom: 0.75rem;">
                        <div class="contact-field">
                            <label>Your Name</label>
                            <input type="text" name="passenger_name" placeholder="Juan Dela Cruz">
                        </div>
                        <div class="contact-field">
                            <label>Contact No.</label>
                            <input type="text" name="passenger_contact" placeholder="09171234567">
                        </div>
                    </div>
                    <div style="font-size: 0.65rem; color: var(--gray-400, #9ca3af); margin-bottom: 0.75rem;">
                        <i class="bi bi-info-circle me-1"></i> Admin may contact you to clarify your complaint.
                    </div>

                    <div class="section-title" style="font-size: 0.75rem;">
                        <i class="bi bi-paperclip"></i> Upload Evidence
                        <span style="font-size: 0.65rem; font-weight: 400; color: var(--gray-400, #9ca3af); text-transform: none;">(optional)</span>
                    </div>
                    <div class="proof-upload" id="proofUpload" onclick="document.getElementById('proofInput').click()">
                        <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="upload-text">Tap to upload files</div>
                        <div class="upload-hint">JPG, PNG, MP4, PDF (max 20MB each)</div>
                        <input type="file" name="proofs[]" id="proofInput" multiple accept=".jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.pdf,.doc,.docx">
                    </div>
                    <div class="file-tags" id="fileTags"></div>
                </div>

                <div class="submit-area">
                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="bi bi-send me-1"></i> Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($alreadyRated ?? false)
    <script>document.getElementById('alreadyView').classList.add('show');</script>
    @endif

    <script>
    (function() {
        var selectedRating = 0;
        var emojis = ['', '😞', '😕', '😐', '😊', '🤩'];
        var labels = ['', 'Poor', 'Below Average', 'Okay', 'Good', 'Excellent'];

        // Star rating
        var starBtns = document.querySelectorAll('.star-btn');
        var ratingInput = document.getElementById('ratingValue');
        var submitBtn = document.getElementById('submitBtn');
        var emojiEl = document.getElementById('emojiFeedback');
        var extraFields = document.getElementById('extraFields');

        starBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.value);
                ratingInput.value = selectedRating;
                submitBtn.disabled = false;

                starBtns.forEach(function(b, i) {
                    if (i < selectedRating) {
                        b.classList.add('selected');
                    } else {
                        b.classList.remove('selected');
                    }
                });

                emojiEl.innerHTML = '<span style="animation: popIn 0.3s ease;">' + emojis[selectedRating] + '</span> <span style="font-size: 0.85rem; font-weight: 700; color: var(--gray-600, #4b5563);">' + labels[selectedRating] + '</span>';

                if (selectedRating <= 2) {
                    extraFields.classList.add('show');
                } else {
                    extraFields.classList.remove('show');
                }
            });
        });

        // Map
        var mapLoaded = false;
        var map = null;
        var startMarker = null;
        var endMarker = null;

        window.toggleMap = function() {
            var wrapper = document.getElementById('mapWrapper');
            var chevron = document.getElementById('mapChevron');
            wrapper.classList.toggle('show');
            chevron.style.transform = wrapper.classList.contains('show') ? 'rotate(180deg)' : '';

            if (wrapper.classList.contains('show') && !mapLoaded) {
                loadMap();
            }
            if (map) {
                setTimeout(function() { map.invalidateSize(); }, 100);
            }
        };

        function loadMap() {
            try {
                map = L.map('tripMap').setView([12.8797, 121.7740], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 18
                }).addTo(map);

                map.on('click', function(e) {
                    if (!startMarker) {
                        setMapMarker(e.latlng, 'start');
                    } else if (!endMarker) {
                        setMapMarker(e.latlng, 'end');
                    }
                });

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        map.setView([pos.coords.latitude, pos.coords.longitude], 15);
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
                className: '',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
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
                .then(function(data) {
                    if (data.display_name) {
                        var short = data.display_name.split(',').slice(0, 3).join(',');
                        document.getElementById(inputId).value = short;
                    }
                })
                .catch(function() {});
        }

        // File upload
        document.getElementById('proofInput').addEventListener('change', function() {
            var tags = document.getElementById('fileTags');
            tags.innerHTML = '';
            Array.from(this.files).forEach(function(f, i) {
                tags.innerHTML += '<span class="file-tag"><i class="bi bi-file-earmark"></i> ' + f.name + '</span>';
            });
        });
    })();
    </script>
    <style>
        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            70% { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>