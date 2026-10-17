<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Suspended - TriFair</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e3a5f;
            --gold: #F5B301;
            --amber: #f59e0b;
            --amber-dark: #d97706;
            --gray-50: #f9fafb;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            -webkit-font-smoothing: antialiased;
            padding: 1rem;
        }
        .suspended-card {
            background: white; border-radius: 24px;
            padding: 2.5rem 2rem; max-width: 400px; width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        }
        .suspended-icon {
            width: 96px; height: 96px; border-radius: 28px;
            background: linear-gradient(135deg, var(--amber), var(--amber-dark));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; font-size: 2.75rem; color: white;
            box-shadow: 0 8px 30px rgba(245,158,11,0.3);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .suspended-card h1 {
            font-size: 1.5rem; font-weight: 900; color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        .suspended-card p {
            font-size: 0.95rem; color: var(--gray-600); line-height: 1.55;
            margin-bottom: 0.5rem;
        }
        .operator-name { color: var(--primary); font-weight: 700; }
        .toda-tag {
            font-size: 0.78rem; font-weight: 700; color: var(--gold);
            text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.25rem;
        }
        .suspend-note {
            margin-top: 1.25rem; padding: 0.85rem 1rem;
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
            font-size: 0.85rem; color: #92400e;
        }
        .close-btn {
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            width: 100%; max-width: 300px; margin: 1.5rem auto 0;
            padding: 1rem 2rem; border: none; border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #16293f);
            color: white; font-size: 1rem; font-weight: 800;
            font-family: inherit; cursor: pointer; transition: all 0.2s;
        }
        .close-btn:active { transform: scale(0.97); }
        .powered { font-size: 0.7rem; color: var(--gray-500); margin-top: 1.5rem; }
        .powered strong { color: var(--primary); font-weight: 700; }

        @keyframes popIn { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>

<div class="suspended-card">
    <div class="suspended-icon">
        <i class="bi bi-slash-circle-fill"></i>
    </div>

    @if(!empty($suspendedToda))
        <div class="toda-tag"><i class="bi bi-building me-1"></i>{{ $suspendedToda->name }}</div>
    @endif

    <h1>Suspended</h1>
    <p>
        The account of <span class="operator-name">{{ $operator->user->name ?? 'this operator' }}</span>
        is currently suspended by the TODA. Ratings are temporarily unavailable.
    </p>

    <div class="suspend-note">
        <i class="bi bi-info-circle-fill"></i>
        @if (!empty($suspension) && $suspension->suspended_until)
            This suspension runs until <strong>{{ $suspension->suspended_until->format('M d, Y') }}</strong>.
        @else
            Please inform the TFRB Office so this account can be checked.
        @endif
    </div>

    <button type="button" class="close-btn" id="closeBtn" onclick="tryClose()">
        <i class="bi bi-x-lg"></i> Close
    </button>

    <div class="powered">Powered by <strong>TriFair</strong></div>
</div>

<script>
var closed = false;

function doClose() {
    if (closed) return;
    closed = true;
    var btn = document.getElementById('closeBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-x-lg"></i> Closing...'; }
    try { window.close(); } catch (e) {}
    try { self.close(); } catch (e) {}
    try { top.close(); } catch (e) {}
}

window.tryClose = function () {
    doClose();
};
</script>
</body>
</html>
