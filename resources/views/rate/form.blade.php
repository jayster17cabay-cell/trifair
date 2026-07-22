<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Rate - {{ $driver->user->name }} | TriFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        /* === DRIVER HEADER === */
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
            -webkit-backdrop-filter: blur(10px);
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

        /* === MAIN CARD === */
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

        .panel-content {
            padding: 1.5rem;
        }

        /* === SCREENS === */
        .screen {
            display: none;
            animation: screenIn 0.35s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .screen.active { display: block; }

        /* === STAR RATING === */
        .star-prompt {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .star-prompt h2 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--gray-800);
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .star-prompt p {
            font-size: 0.82rem;
            color: var(--gray-400);
        }

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

        .star-cell i {
            font-size: 1.5rem;
            color: var(--gray-300);
            transition: all 0.2s ease;
        }

        .star-cell:active {
            transform: scale(0.88);
        }

        .star-cell.selected {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            box-shadow: 0 6px 24px var(--gold-glow);
            transform: scale(1.08);
        }

        .star-cell.selected i {
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
        }

        .star-cell.selected::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 20px;
            border: 2px solid var(--gold);
            opacity: 0.3;
            animation: ringPulse 1s ease-in-out infinite;
        }

        .star-labels {
            display: flex;
            justify-content: space-between;
            padding: 0 0.25rem;
            margin-bottom: 1.5rem;
        }

        .star-labels span {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .feedback-row {
            text-align: center;
            min-height: 3rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .feedback-emoji {
            font-size: 2.25rem;
            animation: popIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        .feedback-text {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--gray-700);
            animation: fadeSlideUp 0.3s ease both;
            animation-delay: 0.1s;
        }

        /* === LOCATION SECTION === */
        .optional-section {
            border-top: 1px solid var(--gray-100);
            padding-top: 1.25rem;
            margin-top: 0.5rem;
        }

        .section-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: var(--gray-50);
            border-radius: 14px;
            cursor: pointer;
            border: 1.5px solid var(--gray-200);
            transition: all 0.2s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .section-toggle:active {
            background: var(--gray-100);
        }

        .section-toggle .toggle-left {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .section-toggle .toggle-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .section-toggle .toggle-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .section-toggle .toggle-hint {
            font-size: 0.68rem;
            color: var(--gray-400);
        }

        .section-toggle .toggle-chevron {
            font-size: 0.9rem;
            color: var(--gray-400);
            transition: transform 0.25s ease;
        }

        .section-toggle.open .toggle-chevron {
            transform: rotate(180deg);
        }

        .collapsible-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .collapsible-body.open {
            max-height: 600px;
        }

        .collapsible-inner {
            padding: 1rem 0 0.5rem;
        }

        .field-group {
            margin-bottom: 0.85rem;
        }

        .field-group label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            margin-bottom: 0.4rem;
        }

        .field-group label .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

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

        .field-input::placeholder {
            color: var(--gray-300);
        }

        .field-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1);
        }

        .location-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        /* === COMMENT === */
        .comment-section {
            margin-top: 1rem;
        }

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
        .field-textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1);
        }

        /* === COMPLAINT (1-2 stars) === */
        .complaint-panel {
            background: var(--red-light);
            border: 1.5px solid rgba(239, 68, 68, 0.15);
            border-radius: 16px;
            padding: 1.25rem;
            margin-top: 1rem;
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .complaint-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .complaint-header .warn-icon {
            width: 28px;
            height: 28px;
            background: var(--red);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .complaint-header span {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--red);
        }

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

        /* === FILE UPLOAD === */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: 14px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            -webkit-tap-highlight-color: transparent;
        }

        .upload-zone:active {
            background: var(--gray-50);
            border-color: var(--gold);
        }

        .upload-zone .upload-icon {
            font-size: 1.5rem;
            color: var(--gray-300);
            margin-bottom: 0.4rem;
        }

        .upload-zone .upload-text {
            font-size: 0.8rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .upload-zone .upload-hint {
            font-size: 0.65rem;
            color: var(--gray-400);
            margin-top: 0.15rem;
        }

        .file-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.5rem;
        }

        .file-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: white;
            color: var(--primary);
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
            border: 1px solid var(--gray-200);
            animation: chipIn 0.2s ease both;
        }

        /* === SUBMIT === */
        .submit-area {
            margin-top: 1.5rem;
        }

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
            letter-spacing: -0.01em;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:disabled {
            background: var(--gray-200);
            box-shadow: none;
            color: var(--gray-400);
            cursor: not-allowed;
        }

        .btn-submit:not(:disabled):active {
            transform: scale(0.97);
            box-shadow: 0 2px 12px var(--gold-glow);
        }

        .btn-submit .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn-submit.loading .btn-text { display: none; }
        .btn-submit.loading .spinner { display: inline-block; }

        /* === SUCCESS SCREEN === */
        .success-screen {
            text-align: center;
            padding: 2rem 0;
        }

        .success-icon {
            width: 88px;
            height: 88px;
            border-radius: 28px;
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            color: white;
            font-size: 2.5rem;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.35);
            animation: successBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            animation-delay: 0.1s;
        }

        .success-screen h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: 0.35rem;
        }

        .success-screen p {
            font-size: 0.88rem;
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        .success-stars {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }

        .success-stars i {
            color: var(--gold);
            font-size: 1.5rem;
            animation: starIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        .btn-done {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 2.5rem;
            border: none;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-done:active { transform: scale(0.97); }

        /* === ALREADY RATED === */
        .rated-screen {
            text-align: center;
            padding: 2rem 0;
        }

        .rated-icon {
            width: 88px;
            height: 88px;
            border-radius: 28px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            color: white;
            font-size: 2.5rem;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.35);
        }

        .rated-stars {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
            margin: 0.75rem 0;
        }

        .rated-stars i {
            color: var(--gold);
            font-size: 1.25rem;
        }

        /* === ANIMATIONS === */
        @keyframes avatarIn {
            from { opacity: 0; transform: scale(0.6) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes screenIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.4); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); max-height: 0; }
            to { opacity: 1; transform: translateY(0); max-height: 500px; }
        }

        @keyframes ringPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.1; }
        }

        @keyframes chipIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes successBounce {
            from { opacity: 0; transform: scale(0.3); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes starIn {
            from { opacity: 0; transform: scale(0) rotate(-30deg); }
            to { opacity: 1; transform: scale(1) rotate(0deg); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* === MOBILE TWEAKS === */
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

    {{-- === DRIVER HERO === --}}
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

    {{-- === RATING PANEL === --}}
    <div class="rating-panel">
        <div class="panel-handle"></div>
        <div class="panel-content">

            {{-- ALREADY RATED --}}
            @if(isset($alreadyRated) && $alreadyRated)
                <div class="rated-screen">
                    <div class="rated-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>Already Rated Today</h3>
                    <p>Na-rate mo na si <strong>{{ $driver->user->name }}</strong> ngayong araw.</p>
                    <div class="rated-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $existingRating->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                    </div>
                    <p style="font-size: 0.75rem; color: var(--gray-400);">Isang rating lang bawat araw bawat driver.</p>
                    <button type="button" onclick="window.close()" class="btn-done" style="margin-top: 0.5rem; background: var(--gray-200); color: var(--gray-600);">
                        <i class="bi bi-x-lg"></i> Close
                    </button>
                </div>

            {{-- SUCCESS --}}
            @elseif(session('success'))
                <div class="success-screen">
                    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="success-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star-fill" style="animation-delay: {{ 0.2 + ($i * 0.08) }}s;"></i>
                        @endfor
                    </div>
                    <h3>Salamat!</h3>
                    <p>Na-submit na ang rating mo para kay <strong>{{ $driver->user->name }}</strong>.</p>
                    <a href="{{ url()->current() }}" class="btn-done">
                        <i class="bi bi-arrow-repeat"></i> Rate Again
                    </a>
                </div>

            {{-- RATING FORM --}}
            @else
                <form action="{{ route('rate.submit', $driver->qr_code) }}" method="POST" enctype="multipart/form-data" id="ratingForm">
                    @csrf

                    {{-- STEP 1: STAR RATING --}}
                    <div id="stepRating">
                        <div class="star-prompt">
                            <h2>Kumusta ang byahe?</h2>
                            <p>I-tap ang bituin para mag-rate</p>
                        </div>

                        <div class="star-grid" id="starGrid">
                            <button type="button" class="star-cell" data-value="1"><i class="bi bi-star-fill"></i></button>
                            <button type="button" class="star-cell" data-value="2"><i class="bi bi-star-fill"></i></button>
                            <button type="button" class="star-cell" data-value="3"><i class="bi bi-star-fill"></i></button>
                            <button type="button" class="star-cell" data-value="4"><i class="bi bi-star-fill"></i></button>
                            <button type="button" class="star-cell" data-value="5"><i class="bi bi-star-fill"></i></button>
                        </div>

                        <div class="star-labels">
                            <span>Pangit</span>
                            <span>Okay lang</span>
                            <span>Grabe!</span>
                        </div>

                        <input type="hidden" name="rating" id="ratingValue" value="">

                        <div class="feedback-row" id="feedbackRow"></div>

                        {{-- COMMENT --}}
                        <div class="comment-section" id="commentSection" style="display:none;">
                            <div class="section-toggle" onclick="toggleSection('commentBody', this)">
                                <div class="toggle-left">
                                    <div class="toggle-icon" style="background: var(--primary); color: white;"><i class="bi bi-chat-dots"></i></div>
                                    <div>
                                        <div class="toggle-text">Magbigay ng comment</div>
                                        <div class="toggle-hint">Optional lang</div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-down toggle-chevron"></i>
                            </div>
                            <div class="collapsible-body" id="commentBody">
                                <div class="collapsible-inner">
                                    <textarea name="reason" class="field-textarea" rows="2" placeholder="I-share ang experience mo..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- COMPLAINT FIELDS (1-2 stars) --}}
                        <div id="complaintSection" style="display:none;">
                            <div class="complaint-panel">
                                <div class="complaint-header">
                                    <div class="warn-icon"><i class="bi bi-exclamation-triangle"></i></div>
                                    <span>Complaint Details</span>
                                </div>

                                <div class="location-row" style="margin-bottom: 0.85rem;">
                                    <div class="field-group" style="margin-bottom: 0;">
                                        <label>Pangalan</label>
                                        <input type="text" name="passenger_name" class="field-input" placeholder="Juan Dela Cruz">
                                    </div>
                                    <div class="field-group" style="margin-bottom: 0;">
                                        <label>Contact No.</label>
                                        <input type="tel" name="passenger_contact" class="field-input" placeholder="09171234567" inputmode="numeric">
                                    </div>
                                </div>

                                <div class="upload-zone" onclick="document.getElementById('proofInput').click()">
                                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <div class="upload-text">Mag-upload ng evidence</div>
                                    <div class="upload-hint">Photo, video, o document (max 20MB)</div>
                                </div>
                                <input type="file" name="proofs[]" id="proofInput" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display:none;">
                                <div class="file-chips" id="fileChips"></div>

                                <div class="complaint-note">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Maaaring makipag-ugnayan ang admin para sa karagdagang impormasyon.</span>
                                </div>
                            </div>
                        </div>

                        {{-- LOCATION (collapsible, always available) --}}
                        <div class="optional-section" id="locationSection" style="display:none;">
                            <div class="section-toggle" onclick="toggleSection('locationBody', this)">
                                <div class="toggle-left">
                                    <div class="toggle-icon" style="background: var(--success); color: white;"><i class="bi bi-geo-alt"></i></div>
                                    <div>
                                        <div class="toggle-text">Saan ka galing at pupunta?</div>
                                        <div class="toggle-hint">Optional - makakatulong sa tracking</div>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-down toggle-chevron"></i>
                            </div>
                            <div class="collapsible-body" id="locationBody">
                                <div class="collapsible-inner">
                                    <div class="location-row">
                                        <div class="field-group" style="margin-bottom:0;">
                                            <label><span class="dot" style="background: var(--green);"></span> From</label>
                                            <input type="text" name="start_location" class="field-input" placeholder="Saan nagsimula?">
                                        </div>
                                        <div class="field-group" style="margin-bottom:0;">
                                            <label><span class="dot" style="background: var(--red);"></span> To</label>
                                            <input type="text" name="end_location" class="field-input" placeholder="Saan pupunta?">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="submit-area">
                        <button type="submit" id="submitBtn" class="btn-submit" disabled>
                            <span class="btn-text"><i class="bi bi-send"></i> Submit Rating</span>
                            <span class="spinner"></span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
(function() {
    var selectedRating = 0;
    var form = document.getElementById('ratingForm');
    if (!form) return;

    var feedbackData = {
        1: { emoji: '\uD83D\uDE22', text: 'Nakakalungkot. We\'ll look into this.' },
        2: { emoji: '\uD83D\uDE15', text: 'Hindi okay. Sorry about that.' },
        3: { emoji: '\uD83D\uDE10', text: 'Okay lang naman.' },
        4: { emoji: '\uD83D\uDE0A', text: 'Maganda ang byahe!' },
        5: { emoji: '\uD83E\uDD29', text: 'Sobrang galing! Salamat!' }
    };

    document.querySelectorAll('.star-cell').forEach(function(cell) {
        cell.addEventListener('click', function() {
            selectedRating = parseInt(this.getAttribute('data-value'));
            document.getElementById('ratingValue').value = selectedRating;

            document.querySelectorAll('.star-cell').forEach(function(c, i) {
                if (i < selectedRating) {
                    c.classList.add('selected');
                } else {
                    c.classList.remove('selected');
                }
            });

            var fb = feedbackData[selectedRating];
            document.getElementById('feedbackRow').innerHTML =
                '<span class="feedback-emoji">' + fb.emoji + '</span>' +
                '<span class="feedback-text">' + fb.text + '</span>';

            document.getElementById('submitBtn').disabled = false;
            document.getElementById('commentSection').style.display = 'block';
            document.getElementById('locationSection').style.display = 'block';

            var cs = document.getElementById('complaintSection');
            if (selectedRating <= 2) {
                cs.style.display = 'block';
            } else {
                cs.style.display = 'none';
            }

            // haptic feedback on mobile
            if (navigator.vibrate) navigator.vibrate(15);
        });
    });

    // submit loading
    form.addEventListener('submit', function() {
        if (!document.getElementById('submitBtn').disabled) {
            document.getElementById('submitBtn').classList.add('loading');
        }
    });

    // file upload
    var fi = document.getElementById('proofInput');
    if (fi) {
        fi.addEventListener('change', function() {
            var chips = document.getElementById('fileChips');
            chips.innerHTML = '';
            Array.from(this.files).forEach(function(f) {
                chips.innerHTML += '<span class="file-chip"><i class="bi bi-file-earmark"></i> ' + f.name + '</span>';
            });
        });
    }
})();

function toggleSection(id, toggleEl) {
    var body = document.getElementById(id);
    var isOpen = body.classList.contains('open');
    if (isOpen) {
        body.classList.remove('open');
        toggleEl.classList.remove('open');
    } else {
        body.classList.add('open');
        toggleEl.classList.add('open');
    }
}
</script>
</body>
</html>