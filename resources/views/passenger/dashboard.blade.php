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

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">

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

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl">My Complaints</h1>
                <p class="mt-1 text-sm text-slate-500">Your filed complaints, grouped by status — open tickets stay on top.</p>
            </div>
            <a href="/" class="tw-btn tw-btn-sm tw-btn-navy shrink-0">
                <i class="bi bi-qr-code-scan"></i> Scan a QR code
            </a>
        </div>

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

        {{-- Status overview — each card doubles as a filter for the list below. --}}
        @php
            $statusTabs = [
                ['key' => null, 'label' => 'Total', 'count' => $totalCount, 'icon' => 'bi-list-ul', 'color' => 'violet'],
                ['key' => 'pending', 'label' => 'Pending', 'count' => $pendingCount, 'icon' => 'bi-clock-history', 'color' => 'amber'],
                ['key' => 'accepted', 'label' => 'Accepted', 'count' => $acceptedCount, 'icon' => 'bi-check-circle', 'color' => 'emerald'],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => $rejectedCount, 'icon' => 'bi-x-circle', 'color' => 'red'],
                ['key' => 'solved', 'label' => 'Solved', 'count' => $solvedCount, 'icon' => 'bi-patch-check', 'color' => 'navy'],
            ];
        @endphp
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($statusTabs as $tab)
                @php
                    $isActive = $tab['key'] === null ? $activeStatus === null : $activeStatus === $tab['key'];
                    $tabHref = $tab['key'] === null
                        ? route('passenger.dashboard')
                        : route('passenger.dashboard', ['status' => $tab['key']]);
                @endphp
                <a href="{{ $tabHref }}"
                   @if ($isActive) aria-current="page" @endif
                   class="tw-stat block no-underline transition hover:-translate-y-0.5 hover:border-navy-500 hover:shadow-md {{ $isActive ? 'border-navy-600 bg-navy-50 ring-2 ring-navy-600' : '' }}">
                    <div class="tw-stat-icon tw-stat-icon-{{ $tab['color'] }}"><i class="bi {{ $tab['icon'] }}"></i></div>
                    <div class="tw-stat-num">{{ $tab['count'] }}</div>
                    <div class="tw-stat-label">{{ $tab['label'] }}</div>
                </a>
            @endforeach
        </div>

        @if ($activeStatus !== null)
            @php $activeStatusLabel = ucfirst($activeStatus); @endphp
            <div class="mb-6 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-navy-100 bg-navy-50 px-4 py-2.5 text-sm text-navy-700">
                <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                <span>
                    Showing only <strong>{{ $activeStatusLabel }}</strong> complaints
                    ({{ $complaints->total() }} {{ $complaints->total() === 1 ? 'ticket' : 'tickets' }}).
                </span>
                <a href="{{ route('passenger.dashboard') }}" class="font-bold text-navy-600 underline hover:text-navy-800">Show all</a>
            </div>
        @endif

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

        {{-- Complaints grouped into one section per status so the list never
             mixes open tickets with closed ones. --}}
        @php
            $pageItems = collect($complaints->getCollection());
            $sections = [
                'pending' => ['label' => 'Pending', 'desc' => 'Filed and waiting for TFRB review', 'count' => $pendingCount, 'dot' => 'bg-amber-400', 'border' => 'border-l-amber-400'],
                'accepted' => ['label' => 'Accepted', 'desc' => 'Confirmed by TFRB — investigation in progress', 'count' => $acceptedCount, 'dot' => 'bg-emerald-500', 'border' => 'border-l-emerald-500'],
                'rejected' => ['label' => 'Rejected', 'desc' => 'Closed — the complaint was not upheld', 'count' => $rejectedCount, 'dot' => 'bg-red-400', 'border' => 'border-l-red-500'],
                'solved' => ['label' => 'Solved', 'desc' => 'The case has been resolved by TFRB', 'count' => $solvedCount, 'dot' => 'bg-navy-600', 'border' => 'border-l-navy-600'],
            ];
            $buckets = ['pending' => collect(), 'accepted' => collect(), 'rejected' => collect(), 'solved' => collect()];
            if ($activeStatus !== null) {
                $buckets[$activeStatus] = $pageItems;
            } else {
                foreach ($pageItems as $rating) {
                    if ($rating->is_solved) {
                        $buckets['solved']->push($rating);
                    } elseif ($rating->is_accepted === true) {
                        $buckets['accepted']->push($rating);
                    } elseif ($rating->is_accepted === false) {
                        $buckets['rejected']->push($rating);
                    } else {
                        $buckets['pending']->push($rating);
                    }
                }
            }
        @endphp

        @if ($complaints->isEmpty() && $activeStatus === null)
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
        @elseif ($complaints->isEmpty())
            <div class="tw-empty py-12">
                <div class="tw-empty-icon"><i class="bi bi-check2-circle text-slate-300"></i></div>
                <h3 class="tw-card-title mb-1">No {{ strtolower($activeStatusLabel) }} complaints</h3>
                <p class="text-sm text-slate-500">There is nothing to show in this status right now.</p>
                <a href="{{ route('passenger.dashboard') }}" class="tw-btn tw-btn-sm tw-btn-outline mt-4">
                    <i class="bi bi-list-ul"></i> Show all complaints
                </a>
            </div>
        @else
            @foreach ($sections as $key => $section)
                @if (($activeStatus === null || $activeStatus === $key) && $buckets[$key]->isNotEmpty())
                    <section class="mb-9 last:mb-0">
                        <div class="mb-3.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 border-b border-slate-200 pb-2.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $section['dot'] }}" aria-hidden="true"></span>
                            <h3 class="text-sm font-extrabold uppercase tracking-widest text-slate-700">{{ $section['label'] }}</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.68rem] font-bold text-slate-500">{{ $section['count'] }}</span>
                            <span class="ml-auto hidden text-xs text-slate-400 sm:inline">{{ $section['desc'] }}</span>
                        </div>
                        @foreach ($buckets[$key] as $rating)
                            @include('passenger.partials.complaint-card', ['statusBorder' => $section['border']])
                        @endforeach
                    </section>
                @endif
            @endforeach

            @if ($complaints->hasPages())
                <div class="mt-2">
                    {{ $complaints->links('pagination::tailwind') }}
                </div>
            @endif
        @endif

        <p class="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-400">
            TriFair — Passenger Feedback System. Your identity stays private from drivers.
        </p>
    </main>
</body>
</html>
