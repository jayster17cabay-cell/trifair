<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rate {{ $operator->user->name }} - TriFair</title>
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
            background:
                radial-gradient(1000px 520px at 50% -12%, rgba(30,58,95,0.10) 0%, transparent 60%),
                radial-gradient(700px 420px at 100% 105%, rgba(245,166,35,0.07) 0%, transparent 60%),
                var(--gray-50);
            background-attachment: fixed;
            min-height: 100vh;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
        }
        @keyframes pageIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .page-header { animation: pageIn 0.45s ease both; }
        .header-datetime {
            text-align: center;
            padding: 0.15rem 0 0.3rem;
        }
        .header-time {
            font-size: 1.15rem; font-weight: 700; letter-spacing: 0.01em;
            color: white; line-height: 1.1;
            display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .header-time i { font-size: 0.95rem; color: var(--gold); }
        .header-date {
            font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.7);
            margin-top: 0.15rem;
            display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .header-date i { color: var(--gold); }

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
            text-align: center; font-size: 0.85rem; color: var(--gray-500);
            margin-bottom: 1.5rem;
        }
        .rating-section .operator-name {
            text-align: center; font-size: 0.95rem; font-weight: 800;
            color: var(--gray-800); margin-bottom: 1.5rem;
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
            color: var(--gray-500); text-transform: uppercase;
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
            display: none; align-items: center; justify-content: center;
            gap: 0.4rem; padding: 0.6rem; background: var(--gray-50);
            border: 1.5px dashed var(--gray-200); border-radius: 12px;
            cursor: pointer; color: var(--gray-500); font-size: 0.82rem;
            font-weight: 600; transition: all 0.2s; margin-top: 0.75rem;
        }
        .map-toggle:active { background: var(--gray-100); }

        .map-box {
            display: none; border-radius: 14px; overflow: hidden;
            border: 1.5px solid var(--gray-200); margin-top: 0.75rem;
            position: relative;
        }
        .map-box.open { display: block; }
        #rateMap { height: 70vh; min-height: 480px; max-height: 640px; width: 100%; }
        .map-hint {
            text-align: center; font-size: 0.75rem; color: var(--gray-500);
            padding: 0.5rem; background: var(--gray-50);
        }
        .route-info {
            display: flex; align-items: center; justify-content: center; gap: 1rem;
            padding: 0.6rem; background: var(--gray-50); border-top: 1px solid var(--gray-100);
            font-size: 0.75rem; color: var(--gray-600); font-weight: 600;
        }
        .route-info span { display: flex; align-items: center; gap: 0.3rem; }
        .route-deviation {
            display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: white; border-radius: 16px; padding: 1.5rem 2rem; z-index: 10000;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; max-width: 320px;
            animation: popIn 0.3s ease;
        }
        .route-deviation.show { display: block; }
        .route-deviation .dev-icon {
            width: 56px; height: 56px; border-radius: 50%; background: #fef2f2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.5rem; color: var(--danger);
        }
        .route-deviation h4 { font-size: 1rem; font-weight: 800; color: var(--gray-800); margin-bottom: 0.3rem; }
        .route-deviation p { font-size: 0.82rem; color: var(--gray-500); margin-bottom: 1rem; }
        .route-deviation .dev-btn {
            padding: 0.6rem 1.5rem; border: none; border-radius: 10px;
            background: var(--primary); color: white; font-weight: 700;
            font-size: 0.85rem; cursor: pointer; font-family: inherit;
        }
        .route-deviation .dev-dismiss {
            background: none; border: none; color: var(--gray-500); font-size: 0.78rem;
            cursor: pointer; margin-top: 0.5rem; font-family: inherit;
        }
        .route-deviation-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4); z-index: 9999;
        }
        .route-deviation-overlay.show { display: block; }
        @keyframes popIn { from { opacity: 0; transform: translate(-50%, -50%) scale(0.85); } to { opacity: 1; transform: translate(-50%, -50%) scale(1); } }

        .loc-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 10000;
            align-items: center; justify-content: center; padding: 1.5rem;
        }
        .loc-overlay.show { display: flex; }
        .loc-overlay-card {
            background: white; border-radius: 20px; padding: 2rem 1.5rem;
            text-align: center; max-width: 340px; width: 100%;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            animation: popIn2 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes popIn2 { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }
        .loc-overlay-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1e3a5f);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.6rem; color: white;
        }
        .loc-overlay-card h4 { font-size: 1.1rem; font-weight: 800; color: var(--gray-800); margin-bottom: 0.5rem; }
        .loc-overlay-card p { font-size: 0.85rem; color: var(--gray-500); line-height: 1.5; margin-bottom: 1.25rem; }
        .loc-overlay-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.75rem 2rem; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1e3a5f);
            color: white; font-size: 0.9rem; font-weight: 700;
            font-family: inherit; cursor: pointer; width: 100%;
            transition: all 0.2s;
        }
        .loc-overlay-btn:active { transform: scale(0.97); }
        .loc-overlay-skip {
            font-size: 0.78rem; color: var(--gray-500); cursor: pointer;
            margin-top: 0.75rem; text-decoration: underline;
        }

        .map-route-line { border-top: 3px dashed var(--gold); margin: 0.5rem 0; }
        .to-container { position: relative; }
        .to-container #searchResults { position: absolute; left: 0; right: 0; background: white; border: 1px solid var(--gray-200); border-radius: 8px; max-height: 150px; overflow-y: auto; z-index: 999; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 2px; }
        .to-container .search-item { padding: 0.5rem 0.75rem; font-size: 0.78rem; color: var(--gray-700); cursor: pointer; border-bottom: 1px solid var(--gray-50); }
        .to-container .search-item:last-child { border-bottom: none; }
        .to-container .search-item:hover { background: var(--gray-50); }
        .location-status {
            display: flex; align-items: center; gap: 0.35rem;
            font-size: 0.72rem; color: var(--gray-500); margin-top: 0.5rem;
            padding: 0.35rem 0.6rem; background: var(--gray-50); border-radius: 8px;
        }
        .location-status .dot-pulse {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--gold); animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }

        .loc-icon-from, .loc-icon-to {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 11px; color: white;
            border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        .loc-icon-from { background: #059669; }
        .loc-icon-to { background: #dc2626; }
        .loc-tracking-dot {
            width: 28px; height: 28px; position: relative;
        }
        .loc-tracking-dot .dot-core {
            width: 14px; height: 14px; background: #2563eb; border: 3px solid white;
            border-radius: 50%; position: absolute; top: 7px; left: 7px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.4); z-index: 2;
        }
        .loc-tracking-dot .dot-pulse-ring {
            width: 28px; height: 28px; background: rgba(37,99,235,0.2);
            border-radius: 50%; position: absolute; top: 0; left: 0;
            animation: trackPulse 2s ease-out infinite; z-index: 1;
        }
        @keyframes trackPulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        .complaint-box {
            background: var(--danger-light); border: 1.5px solid rgba(239,68,68,0.15);
            border-radius: 14px; padding: 1.25rem; margin-top: 1rem;
            display: none;
        }
        .complaint-box.show { display: block; animation: slideUp 0.3s ease; }
        .complaint-title {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; font-weight: 700; color: var(--danger); margin-bottom: 1rem;
        }
        .complaint-title .icon {
            width: 26px; height: 26px; background: var(--danger); border-radius: 8px;
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
        .upload-area .sub-text { font-size: 0.72rem; color: var(--gray-500); margin-top: 0.1rem; }

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
            color: var(--gray-500); cursor: not-allowed;
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

        @media (max-width: 480px) {
            #rateMap { height: 66vh; min-height: 460px; max-height: 600px; }
            .map-hint { font-size: 0.72rem; padding: 0.45rem; }
            .route-info { flex-wrap: wrap; row-gap: 0.15rem; }
        }
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
        <div class="header-datetime">
            <div class="header-time"><i class="bi bi-clock"></i> {{ now('Asia/Manila')->format('g:i A') }}</div>
            <div class="header-date"><i class="bi bi-calendar-event"></i> {{ now('Asia/Manila')->format('l, F j, Y') }}</div>
        </div>
    </div>
</div>

<div class="container">

    @if ($errors->any())
        <div style="background: var(--red-light); color: var(--red); border: 1px solid rgba(239,68,68,0.2); border-radius: 12px; padding: 0.9rem 1rem; font-size: 0.85rem; margin-bottom: 1rem; display: flex; gap: 0.6rem; align-items: flex-start;">
            <i class="bi bi-exclamation-triangle-fill" style="margin-top: 0.15rem;"></i>
            <div>
                <strong>Please check the following:</strong>
                <ul style="margin: 0.25rem 0 0 1.1rem; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(isset($alreadyRated) && $alreadyRated)
        <div class="rating-section">
            <div class="screen-center">
                <div class="screen-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 30px rgba(99,102,241,0.3);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h3>Already Rated Today</h3>
                <p>You already gave <strong>{{ $operator->user->name }}</strong> a rating today.</p>
                <div class="screen-stars">
                    @php
                        $er = $existingRating->rating;
                        $esOn = 'var(--gold)';
                        $esOff = 'var(--gray-200)';
                    @endphp
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $er ? 'bi-star-fill' : 'bi-star' }}" style="color: {{ $i <= $er ? $esOn : $esOff }};"></i>
                    @endfor
                </div>
                <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.5rem;">One rating per operator per day.</p>
                <button type="button" onclick="if(navigator.userAgent.includes('Chrome')){window.close()}else{window.close()}" class="btn-action" style="background: var(--gray-100); color: var(--gray-600); margin-top: 1rem;">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>

    @else
        <div class="rating-section">
            <h3>Rate Your Trip</h3>
            <p class="subtitle" style="font-weight: 800; color: var(--gray-700); margin-bottom: 0.25rem;">How's my ride?</p>
            <div class="operator-name">{{ $operator->user->name }}</div>

            <form action="{{ route('rate.submit', $operator->qr_code) }}" method="POST" enctype="multipart/form-data" id="rateForm">
                @csrf

                <input type="hidden" name="rating" id="ratingValue" value="">

                <div class="trip-route-section" id="tripRouteSection">
                    <hr class="divider">
                    <div class="field-label"><i class="bi bi-map" style="color: var(--primary);"></i> Trip Route</div>
                    <div class="location-grid">
                        <div>
                            <label class="field-label" style="font-size:0.72rem;" for="start_location"><span class="dot" style="background: var(--green);"></span> From</label>
                            <input type="text" name="start_location" id="start_location" class="field-input" placeholder="Auto-detecting..." readonly>
                        </div>
                        <div class="to-container">
                            <label class="field-label" style="font-size:0.72rem;" for="end_location"><span class="dot" style="background: var(--danger);"></span> To</label>
                            <input type="text" name="end_location" id="end_location" class="field-input" placeholder="Type destination..." autocomplete="off">
                            <div id="searchResults"></div>
                        </div>
                    </div>
                    <div class="location-status" id="locStatus">
                        <div class="dot-pulse"></div>
                        <span>Detecting your location...</span>
                    </div>
                    <div class="map-box open" id="mapBox">
                        <div id="rateMap"></div>
                        <div class="route-info" id="routeInfo" style="display:none;">
                            <span id="routeDistance"><i class="bi bi-signpost"></i> Calculating...</span>
                            <span id="routeTime"><i class="bi bi-clock"></i> --</span>
                        </div>
                        <div id="routeDbg" style="display:none;background:#fef2f2;color:#dc2626;font-size:0.72rem;padding:0.4rem 0.6rem;border-top:1px solid #fecaca;word-break:break-word;"></div>
                        <div class="map-hint" id="mapHint">Type destination above or tap map to set destination (To)</div>
                    </div>
                </div>

                <div class="star-section" id="starSection" style="display:none;">
                    <hr class="divider">
                    <div class="field-label"><i class="bi bi-star" style="color: var(--gold);"></i> How was your ride?</div>
                    <div class="stars-row" id="starGrid" role="radiogroup" aria-label="Rate your ride from 1 to 5 stars">
                        <button type="button" class="star-btn" data-value="1" role="radio" aria-label="Rate 1 star" aria-pressed="false"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-btn" data-value="2" role="radio" aria-label="Rate 2 stars" aria-pressed="false"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-btn" data-value="3" role="radio" aria-label="Rate 3 stars" aria-pressed="false"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-btn" data-value="4" role="radio" aria-label="Rate 4 stars" aria-pressed="false"><i class="bi bi-star-fill"></i></button>
                        <button type="button" class="star-btn" data-value="5" role="radio" aria-label="Rate 5 stars" aria-pressed="false"><i class="bi bi-star-fill"></i></button>
                    </div>
                    <div class="star-labels-row">
                        <span>Poor</span>
                        <span>Okay</span>
                        <span>Great</span>
                    </div>
                    <div class="feedback-msg" id="feedbackMsg"></div>
                </div>

                <div class="extra-fields" id="extraFields">
                    <div class="complaint-box" id="complaintBox">
                        <div class="complaint-title">
                            <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                            Report a Problem
                        </div>
                        <div class="field-label" style="margin-top: 0.75rem;"><i class="bi bi-list-check" style="color: var(--danger);"></i> What happened?</div>
                        <label for="complaintType" class="sr-only">Complaint type</label>
                        <select name="complaint_type" id="complaintType" class="field-input" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--gray-200); border-radius: 10px; font-size: 0.85rem; background: white;">
                            <option value="">Select complaint type...</option>
                            @foreach (\App\Models\Rating::COMPLAINT_TYPES as $complaintOption)
                                <option value="{{ $complaintOption }}">{{ $complaintOption }}</option>
                            @endforeach
                        </select>
                        <div class="others-box" id="othersBox" style="display: none;">
                            <label for="complaintDetails" class="sr-only">Describe your complaint</label>
                            <textarea name="complaint_details" id="complaintDetails" class="field-textarea" rows="2" placeholder="Please describe your complaint..." style="margin-top: 0.5rem;"></textarea>
                        </div>
                        <div class="location-grid">
                            <div>
                                <label class="field-label" for="passenger_name">Your Name</label>
                                <input type="text" name="passenger_name" id="passenger_name" class="field-input" placeholder="Juan Dela Cruz">
                            </div>
                            <div>
                                <label class="field-label" for="passenger_contact">Contact No.</label>
                                <input type="tel" name="passenger_contact" id="passenger_contact" class="field-input" placeholder="09171234567" inputmode="numeric">
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
                            <span>A TFRB Officer may contact you for additional information.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="bi bi-send-fill"></i> Submit Rating
                    </button>
                    <p style="text-align:center; font-size: 0.72rem; color: var(--gray-500); margin-top: 0.5rem;" id="submitHint">Tap a star above to rate</p>
                </div>
            </form>
        </div>
    @endif
</div>

<div class="route-deviation-overlay" id="devOverlay"></div>
<div class="route-deviation" id="devWarning">
    <div class="dev-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <h4>Off Route!</h4>
    <p>You seem to have deviated from the planned route. The route is being recalculated.</p>
    <button class="dev-btn" onclick="dismissDeviation()">Got it</button>
</div>
<div class="loc-overlay" id="locOverlay">
    <div class="loc-overlay-card">
        <div class="loc-overlay-icon"><i class="bi bi-geo-alt"></i></div>
        <h4>Location Access Needed</h4>
        <p>Please enable your device location so we can track your trip route. You can enable it in your browser or device settings.</p>
        <button class="loc-overlay-btn" onclick="retryLocation()"><i class="bi bi-crosshair me-1"></i> Try Again</button>
        <div class="loc-overlay-skip" onclick="skipLocation()">Skip for now</div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var selectedRating = 0;
var emojis = ['', '😞', '😐', '🙂', '😊', '🤩'];
var labels = ['', 'Not great', 'Below average', 'It was okay', 'Good ride!', 'Excellent ride!'];
var map = null, startMarker = null, endMarker = null, routeLine = null, arrowMarkers = [];
var solanoCenter = [16.5200, 121.1900];
var TRICYCLE_SPEED_MS = 5.56; // ~20 km/h average speed in town
var solanoSW = [16.4500, 121.1200];
var solanoNE = [16.5900, 121.2600];
var solanoBounds = L.latLngBounds(solanoSW, solanoNE);
var SERVICE_RADIUS_DEG = 0.15; // ~16 km around Solano (neighboring towns)
var serviceBounds = L.latLngBounds(
    [solanoSW[0] - SERVICE_RADIUS_DEG, solanoSW[1] - SERVICE_RADIUS_DEG],
    [solanoNE[0] + SERVICE_RADIUS_DEG, solanoNE[1] + SERVICE_RADIUS_DEG]
);
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

var searchTimeout = null;
var endLatLng = null;
var tripAccepted = false;
var routeReqSeq = 0;
var reverseSeq = { start_location: 0, end_location: 0 };

document.querySelectorAll('.star-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-value'));
        document.getElementById('ratingValue').value = selectedRating;

        document.querySelectorAll('.star-btn').forEach(function(b, i) {
            if (i < selectedRating) {
                b.classList.add('selected');
                b.setAttribute('aria-pressed', 'true');
            } else {
                b.classList.remove('selected');
                b.setAttribute('aria-pressed', 'false');
            }
        });

        document.getElementById('feedbackMsg').innerHTML =
            '<span class="emoji">' + emojis[selectedRating] + '</span> ' + labels[selectedRating];

        document.getElementById('extraFields').classList.add('show');
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitHint').style.display = 'none';

        var cb = document.getElementById('complaintBox');
        if (selectedRating <= 2) { cb.classList.add('show'); } else { cb.classList.remove('show'); }

        if (navigator.vibrate) navigator.vibrate(15);

        document.getElementById('extraFields').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

var endInput = document.getElementById('end_location');
var searchResults = document.getElementById('searchResults');
var lastGeocodeCall = 0;

endInput.addEventListener('input', function() {
    var q = this.value.trim();
    if (q.length < 1) { searchResults.style.display = 'none'; return; }
    clearTimeout(searchTimeout);
    var wait = Math.max(500, lastGeocodeCall + 1200 - Date.now());
    searchTimeout = setTimeout(function() { forwardGeocode(q); }, wait);
});

endInput.addEventListener('focus', function() {
    if (searchResults.children.length > 0) searchResults.style.display = 'block';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.to-container')) searchResults.style.display = 'none';
});

function forwardGeocode(query) {
    lastGeocodeCall = Date.now();
    var vb = (solanoSW[1] - SERVICE_RADIUS_DEG) + ',' + (solanoNE[0] + SERVICE_RADIUS_DEG) + ',' + (solanoNE[1] + SERVICE_RADIUS_DEG) + ',' + (solanoSW[0] - SERVICE_RADIUS_DEG);
    var base = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&viewbox=' + vb + '&limit=10&addressdetails=1';

    function render(results) {
        searchResults.innerHTML = '';
        if (!results || results.length === 0) { searchResults.style.display = 'none'; return; }

        var inside = [], outside = [];
        results.forEach(function(item) {
            var latlng = L.latLng(parseFloat(item.lat), parseFloat(item.lon));
            (serviceBounds.contains(latlng) ? inside : outside).push({item: item, latlng: latlng});
        });

        var list = inside.length > 0 ? inside : outside.slice(0, 5);

        list.forEach(function(e) {
            var item = e.item, latlng = e.latlng;

            var div = document.createElement('div');
            div.className = 'search-item';
            div.textContent = trimAddress(item.display_name);
            div.setAttribute('data-lat', item.lat);
            div.setAttribute('data-lon', item.lon);
            div.addEventListener('click', function() {
                endInput.value = this.textContent;
                searchResults.style.display = 'none';
                var ll = L.latLng(parseFloat(this.getAttribute('data-lat')), parseFloat(this.getAttribute('data-lon')));
                if (!serviceBounds.contains(ll)) {
                    updateLocStatus('Destination is too far from Solano. Only nearby towns (~15 km) are accepted.', false);
                    return;
                }
                applyDestination(ll);
            });
            searchResults.appendChild(div);
        });
        searchResults.style.display = searchResults.children.length > 0 ? 'block' : 'none';
    }

    fetch(base + '&q=' + encodeURIComponent(query))
        .then(function(r) { return r.json(); })
        .then(function(results) {
            if (!results || results.length === 0) {
                return fetch(base + '&q=' + encodeURIComponent(query + ', Nueva Vizcaya'))
                    .then(function(r) { return r.json(); })
                    .then(render);
            }
            var hasInside = results.some(function(item) {
                return serviceBounds.contains(L.latLng(parseFloat(item.lat), parseFloat(item.lon)));
            });
            if (!hasInside) {
                return fetch(base + '&q=' + encodeURIComponent(query + ', Nueva Vizcaya'))
                    .then(function(r) { return r.json(); })
                    .then(function(fb) { render(results.concat(fb)); });
            }
            render(results);
        }).catch(function() { searchResults.style.display = 'none'; });
}

function initMap() {
    try {
        map = L.map('rateMap', {
            center: solanoCenter,
            zoom: 14,
            minZoom: 11,
            maxZoom: 18,
            maxBounds: serviceBounds,
            maxBoundsViscosity: 1.0,
            zoomControl: true
        });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        map.on('click', function(e) {
            if (!serviceBounds.contains(e.latlng)) return;
            applyDestination(e.latlng);
            reverseGeocode(e.latlng, 'end_location');
        });

        detectLocation();
    } catch(e) {
        document.getElementById('rateMap').innerHTML = '<div style="text-align:center;padding:2rem;color:#9ca3af;"><i class="bi bi-map" style="font-size:1.5rem;"></i><br><small>Map unavailable</small></div>';
    }
}

function getPositionWithRetry(attempts, options) {
    return new Promise(function(resolve, reject) {
        var tried = 0;
        function attempt() {
            navigator.geolocation.getCurrentPosition(function(p) {
                resolve(p);
            }, function(err) {
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
    updateLocStatus('Detecting your location...', false);
    getPositionWithRetry(3, { timeout: 15000, enableHighAccuracy: true, maximumAge: 10000 })
        .then(function(p) {
            var latlng = L.latLng(p.coords.latitude, p.coords.longitude);
            document.getElementById('locOverlay').classList.remove('show');
            if (serviceBounds.contains(latlng)) {
                setStartMarker(latlng);
                var acc = Math.round(p.coords.accuracy);
                var inSolanoNow = inSolano(latlng);
                map.setView(inSolanoNow ? latlng : solanoCenter, inSolanoNow ? 16 : 14);
                updateLocStatus(inSolanoNow
                    ? 'Location detected! Accuracy: ~' + acc + 'm. Tracking movement.'
                    : 'Location detected outside Solano (nearby town). Tracking movement.', true);
                reverseGeocode(latlng, 'start_location');
                startTracking();
            } else {
                setFallbackLocation();
            }
        })
        .catch(function(err) {
            if (err && err.code === 1) {
                updateLocStatus('Location access denied. Enable location in your browser and try again.', false);
                document.getElementById('locOverlay').classList.add('show');
            } else {
                setFallbackLocation();
            }
        });
}

function retryLocation() {
    document.getElementById('locOverlay').classList.remove('show');
    updateLocStatus('Detecting your location...', false);
    detectLocation();
}

function skipLocation() {
    document.getElementById('locOverlay').classList.remove('show');
    setFallbackLocation();
}

var trackingWatchId = null;
var routeCoords = [];
var deviationDismissed = false;
var deviationCooldown = false;
var lastRerouteLatLng = null;
var lastRerouteTime = 0;
var rerouteThreshold = 30;
var REROUTE_MIN_MS = 15000;
var OFFROUTE_METERS = 50;

var lastFromAccuracy = Infinity;
var lastFromGeocodeTime = 0;

var mapLastInteracted = 0;
function markMapInteraction() { mapLastInteracted = Date.now(); }

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

function startTracking() {
    if (!navigator.geolocation) return;
    trackingWatchId = navigator.geolocation.watchPosition(function(p) {
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
            reverseGeocode(latlng, 'start_location');
        }
        updateLocStatus('Tracking active. Accuracy: ~' + Math.round(acc) + 'm', true);

        if (endMarker && endLatLng) {
            checkDeviation(latlng);
            maybeReroute(latlng);
        }
    }, function() {}, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
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

function distanceFromRoute(latlng) {
    if (routeCoords.length < 2) return Infinity;
    var minDist = Infinity;
    for (var i = 0; i < routeCoords.length - 1; i++) {
        var d = distToSegment(latlng, routeCoords[i], routeCoords[i + 1]);
        if (d < minDist) minDist = d;
    }
    return minDist;
}

function showDeviationWarning() {
    deviationCooldown = true;
    document.getElementById('devOverlay').classList.add('show');
    document.getElementById('devWarning').classList.add('show');
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    setTimeout(function() { deviationCooldown = false; }, 30000);
}

function dismissDeviation() {
    document.getElementById('devOverlay').classList.remove('show');
    document.getElementById('devWarning').classList.remove('show');
    deviationDismissed = false;
}

function setFallbackLocation() {
    var fallback = L.latLng(solanoCenter);
    setStartMarker(fallback);
    map.setView(fallback, 14);
    updateLocStatus('Using Solano center — type destination or tap map.', true);
    reverseGeocode(fallback, 'start_location');
    startTracking();
}

function setStartMarker(latlng) {
    var icon = L.divIcon({
        html: '<div class="loc-tracking-dot"><div class="dot-pulse-ring"></div><div class="dot-core"></div></div>',
        className: '', iconSize: [28, 28], iconAnchor: [14, 14]
    });
    if (startMarker) map.removeLayer(startMarker);
    startMarker = L.marker(latlng, {icon: icon, zIndexOffset: 1000}).addTo(map);
}

function applyDestination(latlng) {
    reverseSeq.end_location++;
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

function setEndMarker(latlng) {
    if (!startMarker) return;
    var icon = L.divIcon({
        html: '<div class="loc-icon-to">B</div>',
        className: '', iconSize: [28, 28], iconAnchor: [14, 14]
    });
    if (endMarker) map.removeLayer(endMarker);
    endMarker = L.marker(latlng, {icon: icon}).addTo(map);
    drawRoute();
}

var ROUTE_TIMEOUT_MS = 12000;

function fetchWithTimeout(url, opts, ms) {
    var ctrl = ('AbortController' in window) ? new AbortController() : null;
    var timer = setTimeout(function() { if (ctrl) ctrl.abort(); }, ms);
    var o = opts || {};
    if (ctrl) o.signal = ctrl.signal;
    return fetch(url, o).then(function(r) {
        if (!r.ok) throw new Error('http ' + r.status);
        return r.json();
    }).then(function(data) {
        clearTimeout(timer);
        return data;
    }).catch(function(err) {
        clearTimeout(timer);
        throw err;
    });
}

function fetchRoute(start, end) {
    var qs = 'slat=' + start.lat.toFixed(6) + '&slng=' + start.lng.toFixed(6)
        + '&elat=' + end.lat.toFixed(6) + '&elng=' + end.lng.toFixed(6);
    return fetchWithTimeout('/route?' + qs, { credentials: 'same-origin' }, ROUTE_TIMEOUT_MS)
        .then(function(data) {
            if (!data || !data.coords || data.coords.length < 2) throw new Error('server: no route');
            return data;
        })
        .catch(function() {
            var coords = start.lng + ',' + start.lat + ';' + end.lng + ',' + end.lat;
            var directUrls = [
                'https://routing.openstreetmap.de/routed-car/route/v1/driving/' + coords + '?overview=full&geometries=geojson&steps=false',
                'https://router.project-osrm.org/route/v1/driving/' + coords + '?overview=full&geometries=geojson&steps=false'
            ];
            return fetchWithTimeout(directUrls[0], {}, 6000)
                .catch(function() { return fetchWithTimeout(directUrls[1], {}, 6000); })
                .then(function(data) {
                    if (!data || data.code !== 'Ok' || !data.routes || !data.routes.length) throw new Error('all providers failed');
                    var route = data.routes[0];
                    return {
                        coords: route.geometry.coordinates.map(function(c) { return [c[1], c[0]]; }),
                        distanceMeters: route.distance,
                        durationSeconds: route.duration
                    };
                });
        });
}

function polylineLength(coords) {
    var total = 0;
    for (var i = 0; i < coords.length - 1; i++) {
        total += L.latLng(coords[i]).distanceTo(L.latLng(coords[i + 1]));
    }
    return total;
}

function pointAtLength(coords, targetDist) {
    var acc = 0;
    for (var i = 0; i < coords.length - 1; i++) {
        var a = L.latLng(coords[i]);
        var b = L.latLng(coords[i + 1]);
        var seg = a.distanceTo(b);
        if (acc + seg >= targetDist && seg > 0) {
            var t = (targetDist - acc) / seg;
            return L.latLng(a.lat + (b.lat - a.lat) * t, a.lng + (b.lng - a.lng) * t);
        }
        acc += seg;
    }
    return L.latLng(coords[coords.length - 1]);
}

function fitRouteToMap(bounds) {
    if (solanoBounds.contains(bounds.getSouthWest()) && solanoBounds.contains(bounds.getNorthEast())) {
        map.fitBounds(bounds.pad(0.3));
        return;
    }

    // Keep the map centered on Solano and zoom out just enough for the
    // whole trip (including the destination) to be visible.
    var center = L.latLng(solanoCenter[0], solanoCenter[1]);
    var corner = bounds.getSouthWest().distanceTo(center) >= bounds.getNorthEast().distanceTo(center)
        ? bounds.getSouthWest()
        : bounds.getNorthEast();
    var distMeters = corner.distanceTo(center);
    var metersPerPx = 156543.03 * Math.cos(center.lat * Math.PI / 180) / Math.pow(2, map.getZoom());
    var halfW = map.getSize().x / 2;
    var halfH = map.getSize().y / 2;
    var fitRatio = Math.max(distMeters / (metersPerPx * halfW), distMeters / (metersPerPx * halfH));
    var zoom = map.getZoom() - Math.log2(Math.max(1, fitRatio));
    map.setView(center, Math.max(11, Math.min(14, Math.round(zoom))));
}

function renderRoute(data, fitView) {
    routeCoords = data.coords.map(function(c) { return L.latLng(c[0], c[1]); });

    routeLine = L.polyline(data.coords, {
        color: '#2563eb', weight: 5, opacity: 0.85
    }).addTo(map);

    var totalLen = polylineLength(data.coords);
    var arrowDists = [0.25, 0.5, 0.75];
    arrowDists.forEach(function(t) {
        var pt = pointAtLength(data.coords, totalLen * t);
        var prev = pointAtLength(data.coords, Math.max(0, totalLen * t - 10));
        var angle = Math.atan2(pt.lng - prev.lng, pt.lat - prev.lat) * (180 / Math.PI);
        var arrowIcon = L.divIcon({
            html: '<svg width="18" height="18" viewBox="0 0 24 24" style="transform:rotate(' + angle + 'deg);filter:drop-shadow(0 1px 2px rgba(0,0,0,0.3));"><path d="M12 2 L4 20 L12 15 L20 20 Z" fill="#2563eb" stroke="white" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            className: '', iconSize: [18, 18], iconAnchor: [9, 9]
        });
        arrowMarkers.push(L.marker(pt, {icon: arrowIcon, interactive: false}).addTo(map));
    });

    var distMeters = data.distanceMeters;
    var distText = distMeters >= 1000
        ? (distMeters / 1000).toFixed(1) + ' km'
        : Math.round(distMeters) + ' m';
    var durSeconds = Math.max(1, Math.round(distMeters / TRICYCLE_SPEED_MS));
    var durText = durSeconds >= 3600
        ? Math.floor(durSeconds / 3600) + 'h ' + Math.floor((durSeconds % 3600) / 60) + 'm'
        : durSeconds >= 60
            ? Math.round(durSeconds / 60) + ' min'
            : Math.round(durSeconds) + ' sec';

    var routeInfo = document.getElementById('routeInfo');
    routeInfo.style.display = 'flex';
    document.getElementById('routeDistance').innerHTML = '<i class="bi bi-signpost"></i> ' + distText;
    document.getElementById('routeTime').innerHTML = '<i class="bi bi-clock"></i> ' + durText;

    if (fitView) fitRouteToMap(routeLine.getBounds());

    revealStars();
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
            var hint = document.getElementById('mapHint');
            if (hint) {
                hint.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:0.25rem;"></i> ' + escHtml(validationError);
                hint.style.background = 'var(--red-light)';
                hint.style.color = 'var(--red)';
                hint.style.fontWeight = '600';
            }
            return;
        }
    }

    tripAccepted = true;
    clearRouteLayers();

    var seq = ++routeReqSeq;

    fetchRoute(start, end).then(function(data) {
        if (seq !== routeReqSeq) return;
        renderRoute(data, fitView !== false);
    }).catch(function(err) {
        if (seq !== routeReqSeq) return;
        drawApproximateRoute(start, end);
        showRouteError(err && err.message ? err.message : 'unknown');
    });

    resetMapHint();
    document.getElementById('mapHint').innerHTML = '<i class="bi bi-signpost-2"></i> ' + escHtml(document.getElementById('start_location').value) + ' \u2192 ' + escHtml(endInput.value || 'Destination');
}

function clearRouteLayers() {
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
    arrowMarkers.forEach(function(m) { map.removeLayer(m); });
    arrowMarkers = [];
    routeCoords = [];
}

function resetMapHint() {
    var hint = document.getElementById('mapHint');
    if (hint) {
        hint.style.background = '';
        hint.style.color = '';
        hint.style.fontWeight = '';
    }
}

function blockRating() {
    selectedRating = 0;
    document.getElementById('ratingValue').value = '';
    var sec = document.getElementById('starSection');
    if (sec) sec.style.display = 'none';
    document.querySelectorAll('.star-btn').forEach(function(b) { b.classList.remove('selected'); });
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
    var ri = document.getElementById('routeInfo');
    if (ri) ri.style.display = 'none';
}

function revealStars() {
    var sec = document.getElementById('starSection');
    if (!sec || sec.style.display === 'block') return;
    sec.style.display = 'block';
    setTimeout(function() {
        sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 250);
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

function drawApproximateRoute(start, end) {
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([start, end], {
        color: '#2563eb', weight: 4, opacity: 0.5, dashArray: '4 6'
    }).addTo(map);

    var distMeters = start.distanceTo(end);
    var distText = distMeters >= 1000
        ? (distMeters / 1000).toFixed(1) + ' km'
        : Math.round(distMeters) + ' m';

    var routeInfo = document.getElementById('routeInfo');
    routeInfo.style.display = 'flex';
    document.getElementById('routeDistance').innerHTML = '<i class="bi bi-signpost"></i> ' + distText + ' <span style="font-size:0.7rem;opacity:0.7;">(straight line)</span>';
    document.getElementById('routeTime').innerHTML = '<i class="bi bi-clock"></i> \u2014';
    resetMapHint();
    document.getElementById('mapHint').innerHTML = '<i class="bi bi-signpost-2"></i> Map route unavailable \u2014 showing straight-line estimate';

    fitRouteToMap(routeLine.getBounds());

    revealStars();
}

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

function showRouteError(msg) {
    var hint = document.getElementById('mapHint');
    if (hint) {
        hint.innerHTML = '<i class="bi bi-exclamation-triangle" style="color:var(--danger);"></i> Route unavailable (' + escHtml(msg) + ') — straight line shown';
    }
    var d = document.getElementById('routeDbg');
    if (d) { d.style.display = 'block'; d.textContent = 'Route error: ' + msg; }
}

function reverseGeocode(latlng, inputId) {
    var seq = ++reverseSeq[inputId];
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + latlng.lat + '&lon=' + latlng.lng + '&zoom=18&addressdetails=1')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (seq !== reverseSeq[inputId]) return;
            if (d.display_name) {
                document.getElementById(inputId).value = trimAddress(d.display_name);
            }
        }).catch(function() {});
}

function trimAddress(addr) {
    var skip = ['philippines', 'cagayan valley', 'region ii', 'luzon', 'valle de cagayan', 'isabela', 'northern luzon'];
    var parts = addr.split(',').map(function(s) { return s.trim(); }).filter(function(s) {
        if (!s) return false;
        if (/^\d{4}$/.test(s)) return false;
        if (skip.indexOf(s.toLowerCase()) !== -1) return false;
        return true;
    });
    return parts.slice(0, 5).join(', ');
}

function updateLocStatus(msg, ok) {
    var el = document.getElementById('locStatus');
    el.innerHTML = (ok ? '<i class="bi bi-check-circle-fill" style="color:var(--green);"></i>' : '<div class="dot-pulse"></div>') + ' <span>' + msg + '</span>';
}

document.getElementById('mapBox').classList.add('open');
setTimeout(function() { initMap(); }, 300);

var complaintType = document.getElementById('complaintType');
if (complaintType) {
    complaintType.addEventListener('change', function() {
        document.getElementById('othersBox').style.display = (this.value === 'Others') ? 'block' : 'none';
    });
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
        var files = Array.from(this.files);
        var MAX_FILES = 3;
        var MAX_SIZE = 20 * 1024 * 1024;
        var oversized = files.filter(function(f) { return f.size > MAX_SIZE; });

        chips.innerHTML = '';
        if (files.length > MAX_FILES) {
            chips.innerHTML = '<span class="file-chip" style="background: var(--red-light); color: var(--red);"><i class="bi bi-exclamation-triangle"></i> Up to 3 files only — ' + files.length + ' selected</span>';
            this.value = '';
            return;
        }
        if (oversized.length > 0) {
            chips.innerHTML = '<span class="file-chip" style="background: var(--red-light); color: var(--red);"><i class="bi bi-exclamation-triangle"></i> "' + escHtml(oversized[0].name) + '" is over 20MB</span>';
            this.value = '';
            return;
        }
        files.forEach(function(f) {
            chips.innerHTML += '<span class="file-chip"><i class="bi bi-file-earmark"></i> ' + escHtml(f.name) + '</span>';
        });
    });
}
</script>
</body>
</html>
