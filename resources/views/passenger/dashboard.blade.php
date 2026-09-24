<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <header class="bg-navy-600 text-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-2 text-lg font-bold">
                <i class="bi bi-shield-check"></i> Tri<span class="text-gold">Fair</span>
                <span class="rounded bg-white/15 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-widest">Passenger</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden items-center gap-1.5 sm:flex">
                    <i class="bi bi-person-circle"></i>{{ Auth::user()->email }}
                </span>
                <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Sign out of your TriFair account?')">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/20">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl">My Complaints</h1>
                <p class="mt-1 text-sm text-slate-500">Mag-log in ka lang sa umpisa para makita at ma-track ang iyong mga complaint.</p>
            </div>
            <a href="/" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-qr-code-scan"></i> Scan a QR code
            </a>
        </div>

        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-navy-200 bg-navy-600/5 px-4 py-3.5">
            <i class="bi bi-shield-lock-fill mt-0.5 text-navy-600" aria-hidden="true"></i>
            <div>
                <div class="text-sm font-bold text-navy-700">Walang makakakita ng iyong identity</div>
                <p class="mt-0.5 text-xs leading-relaxed text-navy-700/80">
                    <strong>HINDI nakikita ng driver ang iyong pangalan, email, o contact number.</strong>
                    Ang iyong mga detalye ay para lamang sa TriFair/TFRB para sa status updates at imbestigasyon — hindi ito ipinapakita sa driver o ibang pasahero.
                </p>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-violet"><i class="bi bi-list-ul"></i></div>
                <div class="tw-stat-num">{{ $totalCount }}</div>
                <div class="tw-stat-label">Total</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-amber"><i class="bi bi-clock-history"></i></div>
                <div class="tw-stat-num">{{ $pendingCount }}</div>
                <div class="tw-stat-label">Pending</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-emerald"><i class="bi bi-check-circle"></i></div>
                <div class="tw-stat-num">{{ $reviewedCount }}</div>
                <div class="tw-stat-label">Reviewed</div>
            </div>
            <div class="tw-stat">
                <div class="tw-stat-icon tw-stat-icon-navy"><i class="bi bi-patch-check"></i></div>
                <div class="tw-stat-num">{{ $solvedCount }}</div>
                <div class="tw-stat-label">Solved</div>
            </div>
        </div>

        @if ($notifications->isNotEmpty())
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-slate-400">
                    <i class="bi bi-bell-fill mr-1"></i>Recent updates
                </h2>
                <div class="space-y-2">
                    @foreach ($notifications as $notification)
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <i class="bi {{ $notification->rating && $notification->rating->is_solved ? 'bi-patch-check-fill text-navy-600' : 'bi-check-circle-fill text-emerald-500' }} mt-0.5"></i>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-800">
                                    {{ $notification->title }}
                                    @if ($notification->rating)
                                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[0.7rem] font-semibold text-navy-600">{{ $notification->rating->reference_number }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">{{ $notification->message }}</p>
                                <div class="mt-1 text-[0.68rem] text-slate-400">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-slate-400">
            <i class="bi bi-exclamation-triangle mr-1 text-amber-500"></i>Complaint history
        </h2>

        @forelse ($complaints as $rating)
            @php
                $operator = $rating->operator;
                $operatorName = $operator->user->name ?? 'Unknown';
                $bodyNumber = $operator ? $operator->body_number : null;
            @endphp
            <div class="tw-card mb-3 overflow-hidden border-l-4 {{ $rating->rating === 1 ? 'border-l-red-500' : 'border-l-amber-400' }}">
                <div class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[0.7rem] font-semibold text-navy-600">{{ $rating->reference_number }}</span>
                        @if ($bodyNumber)
                            <span class="text-xs text-slate-400">B#{{ $bodyNumber }} — {{ $operatorName }}</span>
                        @else
                            <span class="text-xs text-slate-400">{{ $operatorName }}</span>
                        @endif
                        <span class="ml-auto inline-flex items-center gap-1 rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-[0.7rem] font-semibold text-amber-700">
                            <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($rating->is_solved)
                            <span class="tw-badge tw-badge-navy"><i class="bi bi-patch-check-fill"></i> Solved</span>
                        @elseif ($rating->is_reviewed)
                            <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i> Reviewed</span>
                        @else
                            <span class="tw-badge tw-badge-amber"><i class="bi bi-clock-fill"></i> Pending</span>
                        @endif
                        <span class="text-xs text-slate-400">{{ $rating->created_at->format('M d, Y h:i A') }}</span>
                        @if ($rating->solved_at)
                            <span class="text-xs text-slate-400">· solved {{ $rating->solved_at->diffForHumans() }}</span>
                        @endif
                    </div>

                    @if ($rating->complaint_details)
                        <p class="mt-3 text-sm italic leading-relaxed text-slate-500">"{{ $rating->complaint_details }}"</p>
                    @endif

                    @if ($rating->response)
                        <div class="mt-3 rounded-xl bg-navy-600/10 px-4 py-3">
                            <div class="mb-1 flex items-center gap-1.5">
                                <i class="bi bi-reply-fill text-navy-600"></i>
                                <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's response</span>
                            </div>
                            <p class="mb-0 text-sm text-slate-700">{{ $rating->response->message }}</p>
                        </div>
                    @elseif ($rating->is_solved || $rating->is_reviewed)
                        <p class="mt-3 text-xs text-slate-400"><i class="bi bi-hourglass mr-1"></i>No response from the operator yet.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="tw-empty py-16">
                <div class="tw-empty-icon"><i class="bi bi-inbox text-slate-300"></i></div>
                <h3 class="tw-card-title mb-1">No complaints yet</h3>
                <p class="text-sm text-slate-500">Kapag nag-rate ka ng isang sakay gamit ang QR code habang naka-log in, lalabas dito ang iyong mga complaint at ang kanilang status.</p>
            </div>
        @endforelse

        @if ($complaints->hasPages())
            <div class="mt-4">
                {{ $complaints->links('pagination::tailwind') }}
            </div>
        @endif

        <p class="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-400">
            TriFair — Passenger Feedback System. Ang iyong identity ay nananatiling pribado mula sa mga driver.
        </p>
    </main>
</body>
</html>