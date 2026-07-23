<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desktop Only - TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f9fafb;
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem;
        }
        .card {
            background: white; border-radius: 24px;
            padding: 3rem 2rem; max-width: 360px; width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        }
        .icon-wrap {
            width: 88px; height: 88px; border-radius: 24px;
            background: linear-gradient(135deg, #1e3a5f, #2a4a7a);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; font-size: 2rem; color: white;
            box-shadow: 0 8px 30px rgba(30,58,95,0.25);
        }
        .desktop-icon { font-size: 1.5rem; }
        .phone-icon { font-size: 1.25rem; margin-top: 0.5rem; color: #ef4444; }
        h1 { font-size: 1.2rem; font-weight: 800; color: #1f2937; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #6b7280; line-height: 1.6; margin-bottom: 0.75rem; }
        .hint {
            font-size: 0.8rem; color: #9ca3af;
            padding: 0.75rem; background: #f9fafb; border-radius: 12px;
            margin-top: 1rem;
        }
        .hint i { color: #1e3a5f; margin-right: 0.25rem; }
        .badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            background: #e0e7ff; color: #1e3a5f;
            padding: 0.3rem 0.8rem; border-radius: 20px;
            font-size: 0.72rem; font-weight: 700;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge"><i class="bi bi-shield-check"></i> TriFair Admin</div>
        <div class="icon-wrap">
            <i class="bi bi-pc-display-horizontal desktop-icon"></i>
        </div>
        <h1>Desktop Only</h1>
        <p>The admin dashboard is only accessible from a <strong>computer or laptop</strong>.</p>
        <p style="font-size: 0.82rem;">Passengers and drivers can use their phones to rate and view ratings.</p>
        <div class="hint">
            <i class="bi bi-laptop"></i>
            Please open this page on a desktop browser.
        </div>
    </div>
</body>
</html>
