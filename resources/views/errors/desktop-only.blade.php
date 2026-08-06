<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desktop Only - TriFair</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
</head>
<body class="flex min-h-screen min-h-dvh items-center justify-center bg-slate-50 p-8">
    <div class="w-full max-w-[360px] rounded-3xl bg-white p-10 text-center shadow-soft">
        <span class="mb-6 inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-navy-600">
            <i class="bi bi-shield-check"></i> TriFair TFRB Officer
        </span>
        <div class="mx-auto mb-6 flex h-[88px] w-[88px] items-center justify-center rounded-3xl bg-gradient-to-br from-navy-600 to-navy-500 text-4xl text-white shadow-lift">
            <i class="bi bi-pc-display-horizontal"></i>
        </div>
        <h1 class="mb-2 text-xl font-extrabold text-slate-800">Desktop Only</h1>
        <p class="mb-3 text-sm leading-relaxed text-slate-500">
            The TFRB Officer dashboard is only accessible from a <strong class="text-slate-700">computer or laptop</strong>.
        </p>
        <p class="mb-3 text-xs text-slate-400">
            Passengers and operators can use their phones to rate and view ratings.
        </p>
        <div class="mt-4 rounded-2xl bg-slate-50 p-3 text-xs text-slate-400">
            <i class="bi bi-laptop mr-1 text-navy-600"></i>
            Please open this page on a desktop browser.
        </div>
    </div>
</body>
</html>
