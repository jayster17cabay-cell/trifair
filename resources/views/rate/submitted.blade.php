<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Thank You - TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e3a5f;
            --gold: #f5a623;
            --green: #10b981;
            --green-dark: #059669;
            --gray-50: #f9fafb;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            -webkit-font-smoothing: antialiased;
        }
        .success-card {
            background: white; border-radius: 24px;
            padding: 3rem 2rem; max-width: 380px; width: 90%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        }
        .success-icon {
            width: 96px; height: 96px; border-radius: 28px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; font-size: 2.5rem; color: white;
            box-shadow: 0 8px 30px rgba(16,185,129,0.3);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .success-card h1 {
            font-size: 1.5rem; font-weight: 900; color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        .success-card p {
            font-size: 0.95rem; color: var(--gray-600); line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        .operator-name { color: var(--primary); font-weight: 700; }
        .stars { display: flex; justify-content: center; gap: 0.35rem; margin: 1.25rem 0; }
        .stars i { font-size: 1.75rem; animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) backwards; }
        .stars i.bi-star-fill { color: var(--gold); }
        .stars i.bi-star { color: var(--gray-200); }
        .stars i:nth-child(1) { animation-delay: 0.1s; }
        .stars i:nth-child(2) { animation-delay: 0.2s; }
        .stars i:nth-child(3) { animation-delay: 0.3s; }
        .stars i:nth-child(4) { animation-delay: 0.4s; }
        .stars i:nth-child(5) { animation-delay: 0.5s; }
        .auto-close-note {
            font-size: 0.8rem; color: var(--gray-500); margin-top: 1.25rem;
            padding: 0.5rem 0.75rem; background: var(--gray-50); border-radius: 10px;
        }
        .close-btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 2.5rem; border: none; border-radius: 14px;
            background: linear-gradient(135deg, var(--gold), #d48b0a);
            color: white; font-size: 0.95rem; font-weight: 800;
            font-family: inherit; cursor: pointer; margin-top: 1.5rem;
            transition: all 0.2s;
        }
        .close-btn:active { transform: scale(0.97); }
        .powered { font-size: 0.7rem; color: var(--gray-500); margin-top: 1.5rem; }
        .powered strong { color: var(--primary); font-weight: 700; }

        @keyframes popIn { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>

<div class="success-card">
    <div class="success-icon" style="{{ session('alreadyRated') ? 'background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 8px 30px rgba(99,102,241,0.3);' : '' }}">
        <i class="bi {{ session('alreadyRated') ? 'bi-clock-history' : 'bi-check-lg' }}"></i>
    </div>
    @if($operator->toda)
        <div style="font-size: 0.78rem; font-weight: 700; color: var(--gold); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.25rem;">
            <i class="bi bi-building me-1"></i>{{ $operator->toda->name }}
        </div>
    @endif
    @if(session('alreadyRated'))
        <h1>Already Rated</h1>
        <p>You already rated <span class="operator-name">{{ $operator->user->name }}</span> today.</p>
        <p style="font-size: 0.82rem; color: var(--gray-400);">One rating per operator per day.</p>
    @else
        <h1>Thank You!</h1>
        <p>Your rating for <span class="operator-name">{{ $operator->user->name }}</span> has been recorded successfully.</p>
    @endif

    @unless(session('alreadyRated'))
    <div class="stars">
        @php
            $ratingValue = session('rating_value', 5);
        @endphp
        @for($i = 1; $i <= 5; $i++)
            <i class="bi {{ $i <= $ratingValue ? 'bi-star-fill' : 'bi-star' }}"></i>
        @endfor
    </div>
    @endunless

    <div class="auto-close-note" id="closeNote">
        <i class="bi bi-clock"></i> Page will close in <span id="countdown">3</span>s...
    </div>

    <button class="close-btn" id="closeBtn" onclick="tryClose()">
        <i class="bi bi-x-lg"></i> Close
    </button>

    <div class="powered">Powered by <strong>TriFair</strong></div>
</div>

<script>
var seconds = 3;
var timer = setInterval(function() {
    seconds--;
    document.getElementById('countdown').textContent = seconds;
    if (seconds <= 0) {
        clearInterval(timer);
        tryClose();
    }
}, 1000);

function tryClose() {
    clearInterval(timer);
    try { window.close(); } catch(e) {}
    document.getElementById('closeNote').innerHTML = '<i class="bi bi-check-circle"></i> Thank you! You can close this tab manually.';
    document.getElementById('closeBtn').style.display = 'none';
}
</script>
</body>
</html>
