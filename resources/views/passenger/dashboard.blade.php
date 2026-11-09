<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — TriFair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
                <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Sign out of your TriFair account?')">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/20">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Which Google account these complaints belong to — a passenger juggling
         two or three accounts must never wonder whose tickets are on screen. --}}
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-2.5 sm:px-6">
            <span class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 py-1 pl-1.5 pr-3 text-xs font-bold text-slate-600">
                @if (Auth::user()->provider === 'google')
                    @include('partials.google-g', ['class' => 'h-4 w-4 shrink-0'])
                @else
                    <i class="bi bi-person-circle text-sm text-navy-500" aria-hidden="true"></i>
                @endif
                <span class="truncate">{{ Auth::user()->email }}</span>
            </span>
            <span class="hidden text-[0.7rem] text-slate-400 sm:inline">
                The complaints below belong to this account.
            </span>
            @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                <a href="{{ route('login.google', ['intended' => 'passenger/dashboard']) }}"
                   class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-navy-600 transition hover:border-navy-500 hover:bg-navy-50 focus:outline-none focus:ring-2 focus:ring-navy-600/20"
                   title="Choose a different Google account to see its complaints">
                    <i class="bi bi-arrow-left-right" aria-hidden="true"></i> Switch account
                </a>
            @endif
        </div>
    </div>

    <main class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8">

        @if (session('status'))
            <div class="tw-alert tw-alert-success mb-6">
                <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->has('follow_up'))
            <div class="tw-alert tw-alert-danger mb-6">
                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                <span>{{ $errors->first('follow_up') }}</span>
            </div>
        @elseif ($errors->any())
            {{-- Google sign-in failures (cancelled chooser, unverified address)
                 land here after a switch attempt — without this they would be
                 swallowed, because only the follow-up error was ever rendered. --}}
            <div class="tw-alert tw-alert-danger mb-6">
                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl">My Complaints</h1>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">Your filed complaints only. Violation reports are kept in their own tab below.</p>
            </div>
            <a href="/" class="tw-btn tw-btn-sm tw-btn-navy shrink-0">
                <i class="bi bi-qr-code-scan"></i> Scan a QR code
            </a>
        </div>

        <nav class="mb-6 grid gap-2 sm:grid-cols-2" aria-label="Passenger dashboard sections">
            <a href="#complaints" class="flex items-center justify-between rounded-xl border border-navy-200 bg-navy-50 px-4 py-3 text-sm font-bold text-navy-700 transition hover:border-navy-500 hover:shadow-sm">
                <span class="flex items-center gap-2"><i class="bi bi-chat-square-text"></i> Complaints</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-extrabold text-navy-600">{{ $totalCount }}</span>
            </a>
            <a href="#violation-reports" class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 transition hover:border-amber-400 hover:shadow-sm">
                <span class="flex items-center gap-2"><i class="bi bi-flag"></i> Violation reports</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-extrabold text-amber-700">{{ $violations->count() }}</span>
            </a>
        </nav>

        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-navy-200 bg-navy-600/5 px-4 py-3.5">
            <i class="bi bi-shield-lock-fill mt-0.5 text-navy-600" aria-hidden="true"></i>
            <div>
                <div class="text-sm font-bold text-navy-700">No one can see your identity</div>
                <p class="mt-0.5 text-xs leading-relaxed text-navy-700/80">
                    <strong>The driver CANNOT see your name, email, or contact number.</strong>
                    Your details are used only by TriFair/TFRB for status updates and investigation — they are never shown to the driver or other passengers.
                </p>
            </div>
        </div>

        <section id="complaints" class="scroll-mt-6">
            {{-- Tabs: Open / Rejected / Solved / Violation Reports --}}
            <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3" role="tablist" aria-label="Complaint sections">
                @php
                    $tabs = [
                        ['key' => 'open', 'label' => 'Open', 'count' => $openCount, 'icon' => 'bi-clock-history'],
                        ['key' => 'rejected', 'label' => 'Rejected', 'count' => $rejectedCount, 'icon' => 'bi-x-circle'],
                        ['key' => 'solved', 'label' => 'Solved', 'count' => $solvedCount, 'icon' => 'bi-patch-check'],
                        ['key' => 'violations', 'label' => 'Violation Reports', 'count' => $violations->count(), 'icon' => 'bi-flag'],
                    ];
                @endphp
                @foreach ($tabs as $t)
                    @php
                        $isActive = $tab === $t['key'];
                        $tabHref = route('passenger.dashboard', ['tab' => $t['key']]);
                    @endphp
                    <a href="{{ $tabHref }}"
                       role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}"
                       class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-bold transition {{ $isActive ? 'border-navy-600 bg-navy-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-200 hover:bg-slate-50' }}">
                        <i class="bi {{ $t['icon'] }}"></i>
                        <span>{{ $t['label'] }}</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $t['count'] }}</span>
                    </a>
                @endforeach
            </div>

            @if ($notifications->isNotEmpty())
                <section class="mb-6">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-slate-400">
                        <i class="bi bi-bell-fill mr-1"></i>Account updates
                    </h2>
                    <div class="space-y-2">
                        @foreach ($notifications as $notification)
                            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <i class="bi {{ $notification->rating && $notification->rating->is_solved ? 'bi-patch-check-fill text-navy-600' : 'bi-check-circle-fill text-emerald-500' }} mt-0.5"></i>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-800">
                                        {{ $notification->title }}
                                        @if ($notification->rating && $notification->rating->reference_number)
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

            @if ($tab === 'violations')
                <h2 class="mb-4 text-xl font-extrabold text-slate-900">
                    <i class="bi bi-flag-fill mr-1 text-amber-500"></i>My Violation Reports
                </h2>
                @if ($violations->isEmpty())
                    <div class="tw-empty py-12">
                        <div class="tw-empty-icon"><i class="bi bi-flag text-slate-300"></i></div>
                        <h3 class="tw-card-title mb-1">No violation reports yet</h3>
                        <p class="text-sm text-slate-500">When you scan the QR code of a suspended or franchise-lapsed operator and tap <strong>Report Operator</strong>, your report and its status will appear here.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($violations as $violation)
                            @include('passenger.partials.violation-card', ['violation' => $violation])
                        @endforeach
                    </div>
                @endif
            @else
                @if ($complaints->isEmpty())
                    @if ($totalCount === 0 && $tab === 'open')
                        <div class="tw-empty py-16">
                            <div class="tw-empty-icon"><i class="bi bi-inbox text-slate-300"></i></div>
                            <h3 class="tw-card-title mb-1">No complaints yet</h3>
                            <p class="text-sm text-slate-500">When you rate a ride with the QR code while signed in, your complaints and their status will appear here.</p>

                            <div class="mx-auto mt-5 max-w-md rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3.5 text-left">
                                <p class="text-xs leading-relaxed text-slate-500">
                                    <strong class="font-bold text-slate-700">Is the list empty?</strong>
                                    You may have used a different Google account when you complained. You are currently signed in as
                                    <span class="font-semibold text-navy-700">{{ Auth::user()->email }}</span>
                                    @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                                        — choose that account to see its complaints there.
                                    @endif
                                </p>
                                @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                                    <a href="{{ route('login.google', ['intended' => 'passenger/dashboard']) }}"
                                       class="group mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-extrabold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-navy-500 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-navy-600/15">
                                        @include('partials.google-g', ['class' => 'h-4 w-4 transition-transform duration-200 group-hover:scale-110'])
                                        Try a different Google account
                                        <i class="bi bi-arrow-right-circle text-navy-500" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="tw-empty py-12">
                            <div class="tw-empty-icon"><i class="bi bi-check2-circle text-slate-300"></i></div>
                            <h3 class="tw-card-title mb-1">No {{ $tab }} complaints</h3>
                            <p class="text-sm text-slate-500">There is nothing to show in this tab right now.</p>
                        </div>
                    @endif
                @else
                    <div class="space-y-4">
                        @foreach ($complaints as $rating)
                            @include('passenger.partials.complaint-card', ['statusBorder' => 'border-l-slate-200'])
                        @endforeach
                    </div>

                    @if ($complaints->hasPages())
                        <div class="mt-4">
                            {{ $complaints->links('pagination::tailwind') }}
                        </div>
                    @endif
                @endif
            @endif
        </section>

        <p class="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-400">
            TriFair — Passenger Feedback System. Your identity stays private from drivers.
        </p>
    </main>
</body>
</html>