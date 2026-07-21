<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>How's My Driving? - TriFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { background: linear-gradient(145deg, #0f2b4a 0%, #1e3a5f 40%, #2a4a7a 70%, #1e3a5f 100%); }
        .rating-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .rating-page::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(245, 166, 35, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }
        .rating-page::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3a5f, #f5a623, #ffffff, #f5a623, #1e3a5f);
        }
        .rating-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            position: relative;
        }
        .rating-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3a5f, #f5a623, #1e3a5f);
            z-index: 1;
        }
        .driver-profile-section {
            background: linear-gradient(180deg, #1e3a5f 0%, #2a4a7a 100%);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .driver-profile-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, transparent, white);
        }
        .driver-avatar-lg {
            width: 90px;
            height: 90px;
            background: linear-gradient(145deg, #f5a623 0%, #e09412 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.25rem;
            box-shadow: 0 8px 30px rgba(245, 166, 35, 0.4);
            margin: 0 auto 1rem;
            border: 4px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 2;
        }
        .driver-name-display {
            color: white;
            font-weight: 900;
            font-size: 1.4rem;
            letter-spacing: -0.03em;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }
        .how-driving-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            color: #f5a623;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.02em;
            margin-top: 0.75rem;
            position: relative;
            z-index: 2;
        }
        .how-driving-badge i {
            font-size: 1rem;
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        .driver-info-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: -1rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 3;
        }
        .driver-info-card {
            background: white;
            border-radius: 12px;
            padding: 0.85rem 0.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid var(--gray-100);
        }
        .driver-info-card .info-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.4rem;
            font-size: 0.85rem;
        }
        .driver-info-card .info-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            font-weight: 600;
            margin-bottom: 0.15rem;
        }
        .driver-info-card .info-value {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--gray-800);
        }
        .rating-card .card-body {
            padding: 1.5rem 1.5rem 2rem;
        }
        #tripMap {
            height: 240px;
            border-radius: 12px;
            border: 2px solid var(--gray-200);
            z-index: 0;
        }
        .location-input-group {
            position: relative;
        }
        .location-input-group .location-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            font-size: 1rem;
        }
        .location-input-group .form-control {
            padding-left: 40px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .location-input-group .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.12);
        }
        .start-icon { color: #059669; }
        .end-icon { color: #dc2626; }
        .trip-route-visual {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .trip-route-visual .route-line {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 24px;
            flex-shrink: 0;
            padding-top: 4px;
        }
        .trip-route-visual .route-line .dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 3px solid;
        }
        .trip-route-visual .route-line .dot.start { background: #059669; border-color: #a7f3d0; }
        .trip-route-visual .route-line .dot.end { background: #dc2626; border-color: #fecaca; }
        .trip-route-visual .route-line .connector {
            width: 3px;
            flex-grow: 1;
            min-height: 32px;
            background: linear-gradient(to bottom, #059669, #dc2626);
            border-radius: 2px;
        }
        .star-rating {
            font-size: 2.5rem;
            cursor: pointer;
            color: var(--gray-300);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .star-rating:hover,
        .star-rating.active {
            color: #f5a623;
            transform: scale(1.15);
        }
        .star-rating:hover ~ .star-rating {
            color: var(--gray-300);
            transform: scale(1);
        }
        .proof-section { display: none; }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 1.5rem;
        }
        .step-indicator .step {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--gray-200);
            transition: all 0.3s ease;
        }
        .step-indicator .step.active { background: var(--secondary); width: 28px; border-radius: 5px; }
        .step-indicator .step.done { background: var(--primary); }
        .trip-summary-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #fef2f2 100%);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: 1px solid var(--gray-200);
        }
        @media (max-width: 576px) {
            .rating-page { padding: 1rem; }
            .driver-avatar-lg { width: 70px; height: 70px; font-size: 1.75rem; }
            .driver-info-cards { grid-template-columns: repeat(3, 1fr); gap: 0.5rem; padding: 0 1rem; }
            #tripMap { height: 180px; }
        }
    </style>
</head>
<body>
    <div class="rating-page">
        <div class="rating-card">
            <div class="driver-profile-section">
                <div class="driver-avatar-lg">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="driver-name-display">{{ $driver->user->name }}</div>
                <div class="how-driving-badge">
                    <i class="bi bi-star-fill"></i>
                    How's My Driving?
                </div>
            </div>
            <div class="driver-info-cards mb-3">
                <div class="driver-info-card">
                    <div class="info-icon" style="background: var(--primary-50); color: var(--primary);">
                        <i class="bi bi-upc-scan"></i>
                    </div>
                    <div class="info-label">Plate No.</div>
                    <div class="info-value">{{ $driver->plate_number ?: 'N/A' }}</div>
                </div>
                <div class="driver-info-card">
                    <div class="info-icon" style="background: var(--secondary-50); color: var(--secondary);">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <div class="info-label">Body No.</div>
                    <div class="info-value">{{ $driver->body_number ?: 'N/A' }}</div>
                </div>
                <div class="driver-info-card">
                    <div class="info-icon" style="background: var(--success-50); color: var(--success);">
                        <i class="bi bi-palette-fill"></i>
                    </div>
                    <div class="info-label">Color</div>
                    <div class="info-value">{{ $driver->tricycle_color ?: 'N/A' }}</div>
                </div>
            </div>

            <div class="card-body" style="padding: 1.25rem 1.5rem 2rem;">
                @if (session('success'))
                    <div class="alert alert-success text-center py-4">
                        <i class="bi bi-check-circle-fill" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                        <h5 class="mb-2" style="color: #065f46;">Thank You!</h5>
                        <p class="mb-0" style="font-size: 0.9rem;">{{ session('success') }}</p>
                    </div>
                    <a href="{{ url()->current() }}" class="btn btn-yellow w-100 mt-2">
                        <i class="bi bi-arrow-repeat me-1"></i> Rate Another Trip
                    </a>
                @elseif (isset($alreadyRated) && $alreadyRated)
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-check-circle-fill" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                        <h5 class="mb-2" style="color: #1e40af;">Already Rated Today</h5>
                        <p class="mb-0" style="font-size: 0.9rem; color: var(--gray-600);">
                            You already gave
                            <strong>
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $existingRating->rating)
                                        <i class="bi bi-star-fill" style="color: #f5a623;"></i>
                                    @else
                                        <i class="bi bi-star" style="color: var(--gray-300);"></i>
                                    @endif
                                @endfor
                            </strong>
                            to <strong>{{ $driver->user->name }}</strong> today.
                        </p>
                        <p style="font-size: 0.8rem; color: var(--gray-500); margin-bottom: 0;">
                            You can only rate this driver once per day.
                        </p>
                    </div>
                    <button type="button" class="btn btn-yellow w-100 mt-2" onclick="window.close();">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                @else
                    <form action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data" id="ratingForm">
                        @csrf

                        <!-- Step Progress -->
                        <div class="step-indicator">
                            <span class="step active" id="step1-indicator"></span>
                            <span class="step" id="step2-indicator"></span>
                        </div>

                        <!-- Step 1: Trip Details -->
                        <div id="step1">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: var(--primary); color: white; font-size: 0.75rem; font-weight: 800;">1</div>
                                <h6 class="mb-0" style="font-weight: 700; color: var(--gray-700);">Where did you ride?</h6>
                            </div>

                            <div class="trip-route-visual mb-3">
                                <div class="route-line">
                                    <div class="dot start"></div>
                                    <div class="connector"></div>
                                    <div class="dot end"></div>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="location-input-group mb-3">
                                        <i class="bi bi-geo-alt-fill location-icon start-icon"></i>
                                        <input type="text" class="form-control @error('start_location') is-invalid @enderror"
                                               id="start_location" name="start_location"
                                               value="{{ old('start_location') }}"
                                               placeholder="e.g. SM City, Main Street"
                                               onchange="geocodeLocation(this.value, 'start')">
                                        @error('start_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <small class="text-muted" style="font-size: 0.7rem;">Starting point of your trip</small>
                                    </div>
                                    <div class="location-input-group">
                                        <i class="bi bi-geo-alt-fill location-icon end-icon"></i>
                                        <input type="text" class="form-control @error('end_location') is-invalid @enderror"
                                               id="end_location" name="end_location"
                                               value="{{ old('end_location') }}"
                                               placeholder="e.g. Public Market, Plaza"
                                               onchange="geocodeLocation(this.value, 'end')">
                                        @error('end_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <small class="text-muted" style="font-size: 0.7rem;">Destination of your trip</small>
                                    </div>
                                </div>
                            </div>

                            <div id="tripMap" class="mb-3"></div>

                            <div class="trip-summary-card d-none" id="tripSummary">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill" style="color: #059669;"></i>
                                    <span style="font-size: 0.85rem; font-weight: 600;">Trip route confirmed</span>
                                </div>
                                <div id="routeInfo" style="font-size: 0.75rem; color: var(--gray-500); margin-top: 2px;"></div>
                            </div>

                            <button type="button" class="btn btn-primary w-100" id="toStep2">
                                <i class="bi bi-arrow-right me-1"></i> Continue to Rating
                            </button>
                        </div>

                        <!-- Step 2: Rating -->
                        <div id="step2" style="display:none;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: var(--secondary); color: white; font-size: 0.75rem; font-weight: 800;">2</div>
                                <h6 class="mb-0" style="font-weight: 700; color: var(--gray-700);">How was your trip?</h6>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-center d-block" style="font-size: 0.95rem; color: var(--primary);">Rate your experience</label>
                                <div class="d-flex justify-content-center gap-1" id="starContainer">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="star-rating bi bi-star" data-value="{{ $i }}"></span>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" id="ratingValue" value="">
                                @error('rating')
                                    <div class="text-danger text-center mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" id="reasonSection" style="display:none;">
                                <label for="reason" class="form-label">What went wrong? (optional)</label>
                                <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3" placeholder="Tell us about your experience..."></textarea>
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3" id="contactSection" style="display:none;">
                                <label for="passenger_name" class="form-label" style="font-weight: 700;">
                                    <i class="bi bi-person-fill me-1" style="color: var(--primary);"></i> Your Name <span style="font-size: 0.7rem; color: var(--gray-400); font-weight: 400;">(optional)</span>
                                </label>
                                <input type="text" class="form-control @error('passenger_name') is-invalid @enderror"
                                       id="passenger_name" name="passenger_name"
                                       value="{{ old('passenger_name') }}"
                                       placeholder="e.g. Juan Dela Cruz"
                                       style="border-color: var(--gray-200); margin-bottom: 0.75rem;">
                                @error('passenger_name') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                <label for="passenger_contact" class="form-label" style="font-weight: 700;">
                                    <i class="bi bi-telephone-fill me-1" style="color: var(--primary);"></i> Contact Number <span style="font-size: 0.7rem; color: var(--gray-400); font-weight: 400;">(optional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text" style="background: var(--gray-100); border-color: var(--gray-200);">
                                        <i class="bi bi-phone" style="color: var(--gray-500);"></i>
                                    </span>
                                    <input type="text" class="form-control @error('passenger_contact') is-invalid @enderror"
                                           id="passenger_contact" name="passenger_contact"
                                           value="{{ old('passenger_contact') }}"
                                           placeholder="e.g. 09171234567"
                                           style="border-color: var(--gray-200);">
                                </div>
                                @error('passenger_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-info-circle me-1"></i> Admin or Superadmin may contact you to clarify your complaint.
                                </small>
                            </div>

                            <div class="mb-4 proof-section" id="proofSection">
                                <label for="proofs" class="form-label" style="color: var(--danger); font-weight: 700;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Upload Evidence (Required for low ratings)
                                </label>
                                <div class="proof-upload-area">
                                    <input type="file" class="form-control border-0 p-0 @error('proofs') is-invalid @enderror @error('proofs.*') is-invalid @enderror"
                                           id="proofs" name="proofs[]" multiple accept=".jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.pdf,.doc,.docx" style="background: transparent;">
                                    <small class="text-muted d-block mt-2">Accepted: JPG, PNG, GIF, MP4, AVI, PDF, DOC (Max 20MB per file)</small>
                                </div>
                                <div id="fileList" class="mt-2 d-flex flex-wrap gap-1"></div>
                                @error('proofs') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @error('proofs.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary w-50" id="backToStep1">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="submit" class="btn btn-yellow w-50" id="submitBtn" disabled>
                                    <i class="bi bi-send me-2"></i> Submit Rating
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map centered on Philippines
        const map = L.map('tripMap').setView([12.8797, 121.7740], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(map);

        let startMarker = null;
        let endMarker = null;
        let routeLine = null;
        let isStartSet = false;
        let isEndSet = false;
        let liveDot = null;
        let firstFix = true;

        // Auto-detect current location as starting point + live GPS tracking
        if (navigator.geolocation) {
            // First fix: set the start marker (boarding point)
            navigator.geolocation.getCurrentPosition(function(pos) {
                const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                setMarker(latlng, 'start');
                map.setView(latlng, 15);
                firstFix = false;
                checkStep1Complete();
            }, function(error) {
                console.log('GPS not available:', error.message);
                document.getElementById('start_location').placeholder = 'Type your starting location';
                checkStep1Complete();
            }, { enableHighAccuracy: true, timeout: 10000 });

            // Live tracking: blue dot follows the passenger
            navigator.geolocation.watchPosition(function(pos) {
                const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                const accuracy = pos.coords.accuracy;
                if (liveDot) {
                    liveDot.setLatLng(latlng);
                } else {
                    liveDot = L.circleMarker(latlng, {
                        radius: 8,
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.6,
                        weight: 3,
                    }).addTo(map);
                    liveDot.bindTooltip('You are here', { direction: 'top' });
                }
            }, function() {}, { enableHighAccuracy: true, timeout: 5000, maximumAge: 3000 });
        }

        // Click on map to set markers
        map.on('click', function(e) {
            if (!isStartSet) {
                setMarker(e.latlng, 'start');
            } else if (!isEndSet) {
                setMarker(e.latlng, 'end');
            } else {
                // Both set - reset and start over
                resetMarkers();
                setMarker(e.latlng, 'start');
            }
        });

        function setMarker(latlng, type) {
            const icon = L.divIcon({
                html: type === 'start'
                    ? '<div style="background:#059669;color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #a7f3d0;font-size:14px;font-weight:bold;">S</div>'
                    : '<div style="background:#dc2626;color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fecaca;font-size:14px;font-weight:bold;">E</div>',
                className: '',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            const inputId = type === 'start' ? 'start_location' : 'end_location';

            if (type === 'start') {
                if (startMarker) map.removeLayer(startMarker);
                startMarker = L.marker(latlng, { icon, draggable: true }).addTo(map);
                isStartSet = true;
                startMarker.on('dragend', function() {
                    reverseGeocode(this.getLatLng(), inputId);
                    updateRoute();
                });
            } else {
                if (endMarker) map.removeLayer(endMarker);
                endMarker = L.marker(latlng, { icon, draggable: true }).addTo(map);
                isEndSet = true;
                endMarker.on('dragend', function() {
                    reverseGeocode(this.getLatLng(), inputId);
                    updateRoute();
                });
            }
            reverseGeocode(latlng, inputId);
            updateRoute();
            checkStep1Complete();
            map.fitBounds([startMarker.getLatLng(), endMarker ? endMarker.getLatLng() : startMarker.getLatLng()].filter(Boolean), { padding: [40, 40] });
        }

        function reverseGeocode(latlng, inputId) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&addressdetails=1`;
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById(inputId).value = data.display_name;
                        checkStep1Complete();
                    }
                })
                .catch(() => {
                    document.getElementById(inputId).value = latlng.lat.toFixed(6) + ', ' + latlng.lng.toFixed(6);
                    checkStep1Complete();
                });
        }

        function updateRoute() {
            if (routeLine) map.removeLayer(routeLine);
            if (startMarker && endMarker) {
                const start = startMarker.getLatLng();
                const end = endMarker.getLatLng();
                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?geometries=geojson&overview=full`;
                fetch(osrmUrl)
                    .then(r => r.json())
                    .then(data => {
                        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                            const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                            routeLine = L.polyline(coords, { color: '#1e3a5f', weight: 5, opacity: 0.8 }).addTo(map);
                            document.getElementById('tripSummary').classList.remove('d-none');
                            const dist = (data.routes[0].distance / 1000).toFixed(1);
                            const dur = Math.round(data.routes[0].duration / 60);
                            document.getElementById('routeInfo').innerHTML = `<i class="bi bi-signpost-2"></i> ${dist} km &middot; <i class="bi bi-clock"></i> ${dur} min`;
                        }
                    })
                    .catch(() => {
                        routeLine = L.polyline([start, end], { color: '#1e3a5f', weight: 4, opacity: 0.7, dashArray: '10, 8' }).addTo(map);
                        document.getElementById('tripSummary').classList.remove('d-none');
                    });
            }
        }

        function resetMarkers() {
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker) map.removeLayer(endMarker);
            if (routeLine) map.removeLayer(routeLine);
            startMarker = null;
            endMarker = null;
            routeLine = null;
            isStartSet = false;
            isEndSet = false;
            checkStep1Complete();
        }

        function checkStep1Complete() {
            const btn = document.getElementById('toStep2');
            btn.disabled = false;
        }

        // Geocode using Nominatim
        function geocodeLocation(query, type) {
            if (!query || query.length < 3) return;
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`;
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const latlng = L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon));
                        setMarker(latlng, type);
                    }
                })
                .catch(() => {});
        }

        // Step navigation
        document.getElementById('toStep2').addEventListener('click', function() {
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            document.getElementById('step1-indicator').classList.remove('active');
            document.getElementById('step1-indicator').classList.add('done');
            document.getElementById('step2-indicator').classList.add('active');
            setTimeout(() => map.invalidateSize(), 300);
        });

        document.getElementById('backToStep1').addEventListener('click', function() {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
            document.getElementById('step2-indicator').classList.remove('active');
            document.getElementById('step1-indicator').classList.add('active');
            setTimeout(() => map.invalidateSize(), 300);
        });

        // Check inputs on keyup
        document.getElementById('start_location').addEventListener('keyup', checkStep1Complete);
        document.getElementById('end_location').addEventListener('keyup', checkStep1Complete);

        // Star rating
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('ratingValue');
        const submitBtn = document.getElementById('submitBtn');
        const reasonSection = document.getElementById('reasonSection');
        const proofSection = document.getElementById('proofSection');
        const contactSection = document.getElementById('contactSection');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                ratingInput.value = value;
                submitBtn.disabled = false;

                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill');
                        s.classList.add('active');
                    } else {
                        s.classList.remove('bi-star-fill');
                        s.classList.remove('active');
                        s.classList.add('bi-star');
                    }
                });

                if (value <= 2) {
                    reasonSection.style.display = 'block';
                    proofSection.style.display = 'block';
                    contactSection.style.display = 'block';
                } else {
                    reasonSection.style.display = 'none';
                    proofSection.style.display = 'none';
                    contactSection.style.display = 'none';
                }
            });
        });

        // File list
        document.getElementById('proofs').addEventListener('change', function() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            Array.from(this.files).forEach(file => {
                fileList.innerHTML += `<span class="badge bg-primary me-1">${file.name}</span> `;
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
