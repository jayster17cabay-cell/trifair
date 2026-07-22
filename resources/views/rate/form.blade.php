<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate {{ $driver->user->name }} - TriFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #1e3a5f;
            --primary-dark: #0f2b4a;
            --primary-light: #2a4a7a;
            --gold: #f5a623;
            --gold-dark: #d48b0a;
            --gold-glow: rgba(245, 166, 35, 0.25);
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(165deg, var(--primary-dark) 0%, var(--primary) 40%, var(--primary-light) 100%);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -webkit-tap-highlight-color: transparent;
        }

        .app-shell {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .driver-hero {
            padding: calc(var(--safe-top) + 2rem) 1.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }

        .driver-hero::after {
            content: '';
            position: absolute;
            top: 30%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .driver-avatar {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 32px rgba(245, 166, 35, 0.35);
            position: relative;
            z-index: 1;
            animation: avatarIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        .driver-name {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 0.35rem;
            position: relative;
            z-index: 1;
        }

        .driver-meta {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            position: relative;
            z-index: 1;
        }

        .driver-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .brand-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.12);
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .rating-panel {
            flex: 1;
            background: white;
            border-radius: 28px 28px 0 0;
            padding: 0;
            margin-top: -4px;
            position: relative;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
            padding-bottom: calc(var(--safe-bottom) + 1rem);
        }

        .panel-handle {
            width: 40px;
            height: 4px;
            background: var(--gray-200);
            border-radius: 4px;
            margin: 0.75rem auto 0;
        }

        .panel-content { padding: 1.5rem; }

        .star-prompt { text-align: center; margin-bottom: 1.5rem; }
        .star-prompt h2 { font-size: 1.2rem; font-weight: 800; color: var(--gray-800); letter-spacing: -0.02em; margin-bottom: 0.25rem; }
        .star-prompt p { font-size: 0.82rem; color: var(--gray-400); }

        .star-grid {
            display: flex;
            justify-content: center;
            gap: 0.65rem;
            padding: 0.5rem 0;
            margin-bottom: 0.5rem;
        }

        .star-cell {
            width: 60px;
            height: 60px;
            border: none;
            background: var(--gray-100);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            -webkit-tap-highlight-color: transparent;
        }

        .star-cell i { font-size: 1.5rem; color: var(--gray-300); transition: all 0.2s ease; }
        .star-cell:active { transform: scale(0.88); }

        .star-cell.selected {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            box-shadow: 0 6px 24px var(--gold-glow);
            transform: scale(1.08);
        }

        .star-cell.selected i { color: white; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15)); }

        .star-cell.selected::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 20px;
            border: 2px solid var(--gold);
            opacity: 0.3;
            animation: ringPulse 1s ease-in-out infinite;
        }

        .star-labels { display: flex; justify-content: space-between; padding: 0 0.25rem; margin-bottom: 1.5rem; }
        .star-labels span { font-size: 0.65rem; font-weight: 600; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em; }

        .feedback-row { text-align: center; min-height: 3rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .feedback-emoji { font-size: 2.25rem; animation: popIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .feedback-text { font-size: 0.95rem; font-weight: 700; color: var(--gray-700); animation: fadeSlideUp 0.3s ease both; animation-delay: 0.1s; }

        .form-divider { border: none; border-top: 1px solid var(--gray-100); margin: 1rem 0; }

        .field-group { margin-bottom: 0.85rem; }
        .field-group label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-500); margin-bottom: 0.4rem; }
        .field-group label .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        .field-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--gray-800);
            background: white;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            -webkit-appearance: none;
        }

        .field-input::placeholder { color: var(--gray-300); }
        .field-input:focus { border-color: var(--gold); box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1); }

        .location-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        .field-textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--gray-800);
            background: white;
            outline: none;
            resize: none;
            min-height: 56px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field-textarea::placeholder { color: var(--gray-300); }
        .field-textarea:focus { border-color: var(--gold); box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1); }

        .complaint-panel {
            background: var(--red-light);
            border: 1.5px solid rgba(239, 68, 68, 0.15);
            border-radius: 16px;
            padding: 1.25rem;
            margin-top: 1rem;
        }

        .complaint-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .complaint-header .warn-icon { width: 28px; height: 28px; background: var(--red); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; }
        .complaint-header span { font-size: 0.82rem; font-weight: 700; color: var(--red); }

        .complaint-note {
            display: flex;
            align-items: flex-start;
            gap: 0.4rem;
            font-size: 0.72rem;
            color: var(--gray-500);
            margin-top: 0.75rem;
            padding: 0.6rem;
            background: white;
            border-radius: 10px;
        }
        .complaint-note i { color: var(--gray-400); margin-top: 0.05rem; }

        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: 14px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        .upload-zone:active { background: var(--gray-50); border-color: var(--gold); }
        .upload-zone .upload-icon { font-size: 1.5rem; color: var(--gray-300); margin-bottom: 0.4rem; }
        .upload-zone .upload-text { font-size: 0.8rem; color: var(--gray-600); font-weight: 500; }
        .upload-zone .upload-hint { font-size: 0.65rem; color: var(--gray-400); margin-top: 0.15rem; }

        .file-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem; }
        .file-chip { display: inline-flex; align-items: center; gap: 0.3rem; background: white; color: var(--primary); padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; border: 1px solid var(--gray-200); }

        .map-toggle-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.65rem;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: 14px;
            cursor: pointer;
            color: var(--gray-500);
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .map-toggle-bar:active { background: var(--gray-100); }

        .map-wrapper {
            margin-top: 0.75rem;
            border-radius: 14px;
            overflow: hidden;
            border: 2px solid var(--gray-200);
            display: none;
        }
        .map-wrapper.open { display: block; }
        #rateMap { height: 220px; width: 100%; }
        .map-hint { text-align: center; font-size: 0.7rem; color: var(--gray-400); padding: 0.5rem; background: var(--gray-50); }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 1rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 6px 24px var(--gold-glow);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn-submit:disabled { background: var(--gray-200); box-shadow: none; color: var(--gray-400); cursor: not-allowed; }
        .btn-submit:not(:disabled):active { transform: scale(0.97); }

        .success-screen { text-align: center; padding: 2rem 0; }
        .success-icon {
            width: 88px; height: 88px; border-radius: 28px;
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem; color: white; font-size: 2.5rem;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.35);
            animation: successBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            animation-delay: 0.1s;
        }
        .success-screen h3 { font-size: 1.25rem; font-weight: 800; color: var(--gray-800); margin-bottom: 0.35rem; }
        .success-screen p { font-size: 0.88rem; color: var(--gray-500); margin-bottom: 1.5rem; }
        .success-stars { display: flex; justify-content: center; gap: 0.4rem; margin-bottom: 1rem; }
        .success-stars i { color: var(--gold); font-size: 1.5rem; animation: starIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }

        .btn-done {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.85rem 2.5rem; border: none; border-radius: 14px;
            background: var(--primary); color: white; font-size: 0.9rem;
            font-weight: 700; font-family: inherit; cursor: pointer; text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-done:active { transform: scale(0.97); }

        .rated-screen { text-align: center; padding: 2rem 0; }
        .rated-icon {
            width: 88px; height: 88px; border-radius: 28px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem; color: white; font-size: 2.5rem;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.35);
        }
        .rated-stars { display: flex; justify-content: center; gap: 0.4rem; margin: 0.75rem 0; }
        .rated-stars i { color: var(--gold); font-size: 1.25rem; }

        @keyframes avatarIn { from { opacity: 0; transform: scale(0.6) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        @keyframes popIn { from { opacity: 0; transform: scale(0.4); } to { opacity: 1; transform: scale(1); } }
        @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes ringPulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.1; } }
        @keyframes successBounce { from { opacity: 0; transform: scale(0.3); } to { opacity: 1; transform: scale(1); } }
        @keyframes starIn { from { opacity: 0; transform: scale(0) rotate(-30deg); } to { opacity: 1; transform: scale(1) rotate(0deg); } }

        @media (max-width: 380px) {
            .star-cell { width: 52px; height: 52px; border-radius: 15px; }
            .star-cell i { font-size: 1.3rem; }
            .star-grid { gap: 0.5rem; }
            .driver-hero { padding-top: calc(var(--safe-top) + 1.5rem); padding-bottom: 1.5rem; }
            .panel-content { padding: 1.25rem; }
        }
        @media (min-width: 481px) {
            .rating-panel { margin-top: 1rem; border-radius: 28px; }
        }
    </style>
</head>
<body>
<div class="app-shell">

    <div class="driver-hero">
        <div class="brand-tag">
            <i class="bi bi-shield-check" style="color: var(--gold);"></i>
            TriFair Verified Trip
        </div>
        <div class="driver-avatar">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="driver-name">{{ $driver->user->name }}</div>
        <div class="driver-meta">
            @if($driver->plate_number)
                <span><i class="bi bi-upc-scan"></i> {{ $driver->plate_number }}</span>
            @endif
            @if($driver->body_number)
                <span><i class="bi bi-bicycle"></i> {{ $driver->body_number }}</span>
            @endif
            @if($driver->tricycle_color)
                <span><i class="bi bi-palette-fill"></i> {{ $driver->tricycle_color }}</span>
            @endif
        </div>
    </div>

    <div class="rating-panel">
        <div class="panel-handle"></div>
        <div class="panel-content">

            @if(isset($alreadyRated) && $alreadyRated)
                <div class="rated-screen">
                    <div class="rated-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>Already Rated Today</h3>
                    <p>You already rated <strong>{{ $driver->user->name }}</strong> today.</p>
                    <div class="rated-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $existingRating->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                    </div>
                    <p style="font-size: 0.75rem; color: var(--gray-400);">You can only rate once per day per driver.</p>
                    <button type="button" onclick="window.close()" class="btn-done" style="margin-top: 0.5rem; background: var(--gray-200); color: var(--gray-600);">
                        <i class="bi bi-x-lg"></i> Close
                    </button>
                </div>

            @elseif(session('success'))
                <div class="success-screen">
                    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="success-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star-fill" style="animation-delay: {{ 0.2 + ($i * 0.08) }}s;"></i>
                        @endfor
                    </div>
                    <h3>Thank You!</h3>
                    <p>Your rating for <strong>{{ $driver->user->name }}</strong> has been submitted.</p>
                    <a href="{{ url()->current() }}" class="btn-done">
                        <i class="bi bi-arrow-repeat"></i> Rate Again
                    </a>
                </div>

            @else
                <form action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="star-prompt">
                        <h2>How was your trip?</h2>
                        <p>Tap a star to rate your ride</p>
                    </div>

                    <div class="star-grid" id="starGrid">
                        <button type="button" class="star-cell" data-value="1"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-cell" data-value="2"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-cell" data-value="3"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-cell" data-value="4"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-cell" data-value="5"><i class="bi bi-star-fill"></i></button>
                    </div>

                    <div class="star-labels">
                        <span>Poor</span>
                        <span>Okay</span>
                        <span>Excellent</span>
                    </div>

                    <input type="hidden" name="rating" id="ratingValue" value="">

                    <div class="feedback-row" id="feedbackRow"></div>

                    {{-- COMMENT --}}
                    <div id="commentSection" style="display:none;">
                        <hr class="form-divider">
                        <div class="field-group">
                            <label><i class="bi bi-chat-dots" style="color: var(--primary);"></i> Comment <span style="font-weight:400; text-transform:none; letter-spacing:0;">(optional)</span></label>
                        </div>
                        <textarea name="reason" class="field-textarea" rows="2" placeholder="Share your experience..."></textarea>
                    </div>

                    {{-- MAP --}}
                    <div id="mapSection" style="display:none;">
                        <hr class="form-divider">
                        <div class="map-toggle-bar" id="mapToggle" onclick="toggleMap()">
                            <i class="bi bi-map"></i> Set trip location on map
                            <i class="bi bi-chevron-down" id="mapChevron" style="transition: transform 0.25s ease;"></i>
                        </div>
                        <div class="map-wrapper" id="mapWrapper">
                            <div id="rateMap"></div>
                            <div class="map-hint">Tap the map to set start (1st tap) and destination (2nd tap)</div>
                        </div>
                        <div class="location-row" style="margin-top: 0.75rem;">
                            <div class="field-group" style="margin-bottom:0;">
                                <label><span class="dot" style="background: var(--green);"></span> From</label>
                                <input type="text" name="start_location" id="start_location" class="field-input" placeholder="Starting point">
                            </div>
                            <div class="field-group" style="margin-bottom:0;">
                                <label><span class="dot" style="background: var(--red);"></span> To</label>
                                <input type="text" name="end_location" id="end_location" class="field-input" placeholder="Destination">
                            </div>
                        </div>
                    </div>

                    {{-- COMPLAINT (1-2 stars) --}}
                    <div id="complaintSection" style="display:none;">
                        <div class="complaint-panel">
                            <div class="complaint-header">
                                <div class="warn-icon"><i class="bi bi-exclamation-triangle"></i></div>
                                <span>Complaint Details</span>
                            </div>
                            <div class="location-row" style="margin-bottom: 0.85rem;">
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label>Your Name</label>
                                    <input type="text" name="passenger_name" class="field-input" placeholder="Juan Dela Cruz">
                                </div>
                                <div class="field-group" style="margin-bottom: 0;">
                                    <label>Contact No.</label>
                                    <input type="tel" name="passenger_contact" class="field-input" placeholder="09171234567" inputmode="numeric">
                                </div>
                            </div>
                            <div class="upload-zone" id="uploadZone">
                                <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                <div class="upload-text">Upload evidence</div>
                                <div class="upload-hint">Photo, video, or document (max 20MB each)</div>
                            </div>
                            <input type="file" name="proofs[]" id="proofInput" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display:none;">
                            <div class="file-chips" id="fileChips"></div>
                            <div class="complaint-note">
                                <i class="bi bi-info-circle"></i>
                                <span>Admin may contact you for additional information about your complaint.</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn-submit" disabled>
                        <i class="bi bi-send"></i> <span id="submitText">Submit Rating</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var selectedRating = 0;
var emojis = ['', '\uD83D\uDE1E', '\uD83D\uDE15', '\uD83D\uDE10', '\uD83D\uDE0A', '\uD83E\uDD29'];
var labels = ['', 'Not great...', 'Below average', 'It was okay', 'Good ride!', 'Excellent ride!'];
var map = null, startMarker = null, endMarker = null, mapLoaded = false;

document.querySelectorAll('.star-cell').forEach(function(cell) {
    cell.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-value'));
        document.getElementById('ratingValue').value = selectedRating;

        document.querySelectorAll('.star-cell').forEach(function(c, i) {
            if (i < selectedRating) { c.classList.add('selected'); }
            else { c.classList.remove('selected'); }
        });

        document.getElementById('feedbackRow').innerHTML =
            '<span class="feedback-emoji">' + emojis[selectedRating] + '</span>' +
            '<span class="feedback-text">' + labels[selectedRating] + '</span>';

        document.getElementById('submitBtn').disabled = false;
        document.getElementById('commentSection').style.display = 'block';
        document.getElementById('mapSection').style.display = 'block';

        var cs = document.getElementById('complaintSection');
        cs.style.display = (selectedRating <= 2) ? 'block' : 'none';

        if (navigator.vibrate) navigator.vibrate(15);
    });
});

document.getElementById('mapToggle').addEventListener('click', function() {
    var w = document.getElementById('mapWrapper');
    var c = document.getElementById('mapChevron');
    if (w.classList.contains('open')) {
        w.classList.remove('open');
        c.style.transform = '';
    } else {
        w.classList.add('open');
        c.style.transform = 'rotate(180deg)';
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
                map.setView([p.coords.latitude, p.coords.longitude], 15);
            }, function() {}, { timeout: 5000 });
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