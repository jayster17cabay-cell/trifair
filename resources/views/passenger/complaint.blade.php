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

        <section class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <i class="bi bi-megaphone-fill mt-0.5 text-amber-600"></i>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-extrabold text-amber-900">Need immediate TFRB review?</h2>
                        <p class="mt-1 text-xs leading-relaxed text-amber-800">You may appeal at any time — even while the TODA/operator has not solved the complaint yet. Use this for serious incidents or if you are not satisfied with the action taken.</p>
                        <form action="{{ route('passenger.complaints.followUp', $rating) }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="appeal" value="1">
                            <input type="hidden" name="follow_up_rating_id" value="{{ $rating->id }}">
                            <textarea name="message" rows="3" maxlength="1000" required class="tw-textarea w-full bg-white" placeholder="Explain why you are appealing this decision..."></textarea>
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy mt-2"><i class="bi bi-send-fill"></i> Send appeal</button>
                        </form>
                    </div>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
