<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Select TODA - TriFair</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e3a5f;
            --gold: #F5B301;
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
        .select-card {
            background: white; border-radius: 24px;
            padding: 2.5rem 2rem; max-width: 440px; width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        }
        .select-icon {
            width: 96px; height: 96px; border-radius: 28px;
            background: linear-gradient(135deg, var(--primary), #16293f);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; font-size: 2.75rem; color: white;
            box-shadow: 0 8px 30px rgba(30,58,95,0.3);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .select-card h1 {
            font-size: 1.5rem; font-weight: 900; color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        .select-card p {
            font-size: 0.95rem; color: var(--gray-600); line-height: 1.55;
            margin-bottom: 1.5rem;
        }
        .operator-name { color: var(--primary); font-weight: 700; }
        .toda-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .toda-option {
            display: flex; align-items: center; gap: 0.9rem;
            width: 100%; padding: 1rem 1.15rem;
            border: 1.5px solid var(--gray-200); border-radius: 16px;
            background: white; text-align: left; text-decoration: none;
            transition: all 0.15s; cursor: pointer;
        }
        .toda-option:hover, .toda-option:active {
            border-color: var(--gold);
            background: #fffbeb;
            transform: translateY(-1px);
        }
        .toda-option i {
            width: 44px; height: 44px; flex: 0 0 44px;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            background: var(--primary); color: white; font-size: 1.25rem;
        }
        .toda-option .label { display: block; font-size: 1rem; font-weight: 800; color: var(--gray-800); }
        .toda-option .sub { display: block; font-size: 0.78rem; color: var(--gray-500); margin-top: 0.1rem; }
        .powered { font-size: 0.7rem; color: var(--gray-500); margin-top: 1.75rem; }
        .powered strong { color: var(--primary); font-weight: 700; }

        @keyframes popIn { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>

<div class="select-card">
    <div class="select-icon">
        <i class="bi bi-buildings-fill"></i>
    </div>

    <h1>Select the TODA</h1>
    <p>
        <span class="operator-name">{{ $operator->user->name ?? 'operator' }}</span>
        belongs to more than one TODA. Select which TODA this trip belongs to.
    </p>

    <div class="toda-list">
        @foreach($todas as $toda)
            <a class="toda-option" href="{{ route('rate.toda', ['qrCode' => $operator->qr_code, 'todaId' => $toda->id]) }}">
                <i class="bi bi-building-fill"></i>
                <span>
                    <span class="label">{{ $toda->name }}</span>
                    @if($toda->area)
                        <span class="sub">{{ $toda->area }}</span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    <div class="powered">Powered by <strong>TriFair</strong></div>
</div>

</body>
</html>
