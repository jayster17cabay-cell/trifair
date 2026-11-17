<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $rating->reference_number }} — Complaint — TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <header class="bg-navy-600 text-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-2 text-lg font-bold">Tri<span class="text-gold">Fair</span><span class="rounded bg-white/15 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-widest">Passenger</span></div>
            <a href="{{ route('passenger.dashboard') }}" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20"><i class="bi bi-grid-3x3-gap mr-1"></i> All complaints</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
        @if (session('status'))
            <div class="tw-alert tw-alert-success mb-5"><i class="bi bi-check-circle-fill tw-alert-icon"></i><span>{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="tw-alert tw-alert-danger mb-5"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
        @endif

        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Single complaint view</p>
            <h1 class="mt-1 text-xl font-extrabold text-slate-900 sm:text-2xl">Complaint {{ $rating->reference_number }}</h1>
            <p class="mt-1 text-sm text-slate-500">Only this complaint is shown here. Use Back to all complaints to return to your list.</p>
        </div>

        @include('passenger.partials.complaint-card', ['rating' => $rating, 'standalone' => true, 'detailsOpen' => true])

        @if ($rating->is_solved || $rating->rejected_at)
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <i class="bi bi-shield-check mt-0.5 text-slate-500"></i>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-extrabold text-slate-800">Final decision</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            This complaint has been resolved. The decision is final.
                        </p>
                    </div>
                </div>
            </section>
        @endif
    </main>
</body>
</html>
