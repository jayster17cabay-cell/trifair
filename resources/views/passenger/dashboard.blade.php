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
                <p class="mt-1 text-sm text-slate-500">Track every complaint you filed — open tickets stay on top, resolved ones at the bottom.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="/" class="tw-btn tw-btn-sm tw-btn-navy">
                    <i class="bi bi-qr-code-scan"></i> Scan a QR code
                </a>
                <button type="button" onclick="tryClose()" class="tw-btn tw-btn-sm tw-btn-outline">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
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

        <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-slate-400">
            <i class="bi {{ $activeStatus !== null ? 'bi-funnel' : 'bi-exclamation-triangle' }} mr-1 text-amber-500"></i>
            {{ $activeStatus !== null ? $activeStatusLabel . ' complaints' : 'Complaint history' }}
            <span class="ml-1 font-semibold text-slate-300">({{ $complaints->total() }})</span>
        </h2>

        @forelse ($complaints as $rating)
            @php
                $operator = $rating->operator;
                $operatorName = $operator->user->name ?? 'Unknown';
                $bodyNumber = $operator ? $operator->body_number : null;

                // Same precedence as the model's status_label: solved wins,
                // then accepted, rejected, and finally pending.
                if ($rating->is_solved) {
                    $statusKey = 'solved';
                } elseif ($rating->is_accepted === true) {
                    $statusKey = 'accepted';
                } elseif ($rating->is_accepted === false) {
                    $statusKey = 'rejected';
                } else {
                    $statusKey = 'pending';
                }
                $statusMap = [
                    'pending' => ['label' => 'Pending', 'icon' => 'bi-clock-fill', 'badge' => 'tw-badge tw-badge-amber', 'border' => 'border-l-amber-400'],
                    'accepted' => ['label' => 'Accepted', 'icon' => 'bi-check-circle-fill', 'badge' => 'tw-badge tw-badge-green', 'border' => 'border-l-emerald-500'],
                    'rejected' => ['label' => 'Rejected', 'icon' => 'bi-x-circle-fill', 'badge' => 'tw-badge tw-badge-red', 'border' => 'border-l-red-500'],
                    'solved' => ['label' => 'Solved', 'icon' => 'bi-patch-check-fill', 'badge' => 'tw-badge tw-badge-navy', 'border' => 'border-l-navy-600'],
                ];
                $status = $statusMap[$statusKey];
                $followUpOpen = $errors->has('message') && old('follow_up_rating_id') == $rating->id;
            @endphp
            <div class="tw-card mb-3 overflow-hidden border-l-4 {{ $status['border'] }}">
                <div class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5">
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[0.7rem] font-semibold text-navy-600">{{ $rating->reference_number }}</span>
                        @if ($bodyNumber)
                            <span class="text-xs text-slate-400">B#{{ $bodyNumber }} — {{ $operatorName }}</span>
                        @else
                            <span class="text-xs text-slate-400">{{ $operatorName }}</span>
                        @endif
                        <span class="ml-auto {{ $status['badge'] }}"><i class="bi {{ $status['icon'] }}"></i> {{ $status['label'] }}</span>
                    </div>

                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-400">
                        <span><i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>Filed {{ $rating->created_at->format('M d, Y h:i A') }}</span>
                        @if ($rating->solved_at)
                            <span><i class="bi bi-check2-all mr-1" aria-hidden="true"></i>Resolved {{ $rating->solved_at->diffForHumans() }}</span>
                        @endif
                        <span class="ml-auto inline-flex items-center gap-1 rounded-md border border-amber-100 bg-amber-50 px-2 py-0.5 text-[0.7rem] font-semibold text-amber-700">
                            <i class="bi bi-exclamation-triangle"></i>{{ $rating->complaint_type }}
                        </span>
                    </div>

                    @if ($rating->complaint_details)
                        <p class="mt-3 text-sm italic leading-relaxed text-slate-500">"{{ $rating->complaint_details }}"</p>
                    @endif

                    @if ($rating->proofs->count() > 0)
                        <div class="mt-3">
                            <div class="mb-1 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400">Attached evidence ({{ $rating->proofs->count() }} file(s))</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rating->proofs as $proof)
                                    @if (str_starts_with($proof->file_type, 'image'))
                                        <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                            <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                                 alt="{{ $proof->original_name }}"
                                                 class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                        </a>
                                    @else
                                        <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                           class="tw-btn tw-btn-sm tw-btn-outline">
                                            <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($rating->response)
                        <div class="mt-3 rounded-xl bg-navy-600/10 px-4 py-3">
                            <div class="mb-1 flex items-center gap-1.5">
                                <i class="bi bi-reply-fill text-navy-600"></i>
                                <span class="text-[0.7rem] font-bold uppercase tracking-widest text-navy-600">Operator's response</span>
                            </div>
                            <p class="mb-0 text-sm text-slate-700">{{ $rating->response->message }}</p>
                        </div>
                    @elseif ($rating->is_accepted === true)
                        <p class="mt-3 text-xs text-slate-400"><i class="bi bi-hourglass mr-1"></i>No response from the operator yet.</p>
                    @endif

                    @if ($rating->is_accepted === false)
                        <div class="mt-3 rounded-xl border border-red-100 bg-red-50 px-4 py-3">
                            <div class="mb-1 flex items-center gap-1.5">
                                <i class="bi bi-x-circle-fill text-red-500"></i>
                                <span class="text-[0.7rem] font-bold uppercase tracking-widest text-red-600">Reason for rejection</span>
                            </div>
                            @if ($rating->resolution_note)
                                <p class="mb-0 text-sm text-slate-700">{{ $rating->resolution_note }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-red-100 pt-2 text-[0.7rem] text-slate-500">
                                @if ($rating->reviewed_by_name)
                                    <span><i class="bi bi-person-badge mr-1"></i>Reviewed by {{ $rating->reviewed_by_name }}</span>
                                @endif
                                @if ($rating->resolution_at)
                                    <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </div>
                        </div>
                    @elseif ($rating->is_solved)
                        <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                            <div class="mb-1 flex items-center gap-1.5">
                                <i class="bi bi-patch-check-fill text-emerald-600"></i>
                                <span class="text-[0.7rem] font-bold uppercase tracking-widest text-emerald-700">Resolution details</span>
                            </div>
                            @if ($rating->resolution_note)
                                <p class="mb-0 text-sm text-slate-700">{{ $rating->resolution_note }}</p>
                            @endif
                            @if ($rating->resolutionProofs->count() > 0)
                                <div class="mt-2.5 border-t border-emerald-100 pt-2">
                                    <div class="mb-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400">Resolution proof ({{ $rating->resolutionProofs->count() }} file(s))</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($rating->resolutionProofs as $proof)
                                            @if (str_starts_with((string) $proof->file_type, 'image'))
                                                <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}">
                                                    <img src="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                                         alt="{{ $proof->original_name }}"
                                                         class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                                </a>
                                            @else
                                                <a href="{{ URL::signedRoute('storage.serve', ['path' => $proof->file_path]) }}"
                                                   class="tw-btn tw-btn-sm tw-btn-outline">
                                                    <i class="bi bi-file-earmark"></i> {{ $proof->original_name }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-emerald-100 pt-2 text-[0.7rem] text-slate-500">
                                @if ($rating->reviewed_by_name)
                                    <span><i class="bi bi-person-badge mr-1"></i>Reviewed by {{ $rating->reviewed_by_name }}</span>
                                @endif
                                @if ($rating->resolution_at)
                                    <span><i class="bi bi-clock-history mr-1"></i>{{ $rating->resolution_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($rating->passengerFollowUps->isNotEmpty())
                        <div class="mt-3 rounded-xl border border-violet-100 bg-violet-50/60 px-4 py-3">
                            <div class="mb-1.5 flex items-center gap-1.5">
                                <i class="bi bi-chat-left-text-fill text-violet-600"></i>
                                <span class="text-[0.7rem] font-bold uppercase tracking-widest text-violet-700">
                                    Your follow-ups ({{ $rating->passengerFollowUps->count() }})
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach ($rating->passengerFollowUps as $followUp)
                                    <div class="border-t border-violet-100 pt-2 first:border-t-0 first:pt-0">
                                        <p class="text-sm leading-relaxed text-slate-700">{{ $followUp->message }}</p>
                                        <div class="mt-0.5 text-[0.68rem] text-slate-400">
                                            {{ $followUp->created_at->format('M d, Y h:i A') }}
                                            <span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 font-semibold text-violet-700">Sent</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($rating->is_solved)
                        <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                            <i class="bi bi-check2-all text-emerald-500"></i>
                            This complaint has been resolved — you can no longer follow up.
                        </p>
                    @else
                        {{-- One collapsed follow-up action per card — the form only
                             appears after the passenger asks for it. --}}
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <button type="button"
                                    onclick="toggleFollowUp({{ $rating->id }})"
                                    aria-expanded="{{ $followUpOpen ? 'true' : 'false' }}"
                                    aria-controls="followup-panel-{{ $rating->id }}"
                                    class="tw-btn tw-btn-sm tw-btn-outline">
                                <i class="bi bi-chat-left-text text-violet-500"></i> Follow up this complaint
                            </button>
                            <div id="followup-panel-{{ $rating->id }}" class="{{ $followUpOpen ? '' : 'hidden' }} mt-3">
                                <form action="{{ route('passenger.complaints.followUp', $rating) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="follow_up_rating_id" value="{{ $rating->id }}">
                                    <label for="followup-{{ $rating->id }}" class="tw-label mb-1">
                                        <i class="bi bi-send-fill mr-1 text-violet-500"></i>Follow up this complaint
                                    </label>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                                        <textarea id="followup-{{ $rating->id }}" name="message" rows="2" maxlength="1000"
                                                  class="tw-textarea flex-1"
                                                  placeholder="Add details or request a status update...">{{ old('follow_up_rating_id') == $rating->id ? old('message') : '' }}</textarea>
                                        <div class="flex shrink-0 gap-2">
                                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy">
                                                <i class="bi bi-send"></i> Send
                                            </button>
                                            <button type="button" onclick="toggleFollowUp({{ $rating->id }})" class="tw-btn tw-btn-sm tw-btn-ghost">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                    @if ($errors->has('message') && old('follow_up_rating_id') == $rating->id)
                                        <span class="tw-error-text" role="alert">{{ $errors->first('message') }}</span>
                                    @endif
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
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
        @endforelse

        @if ($complaints->hasPages())
            <div class="mt-4">
                {{ $complaints->links('pagination::tailwind') }}
            </div>
        @endif

        <p class="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-400">
            TriFair — Passenger Feedback System. Your identity stays private from drivers.
        </p>
    </main>

    <script>
        var closed = false;

        function tryClose() {
            if (closed) return;
            closed = true;
            // Browsers only let JS close scripts-opened tabs (the QR-code scan
            // flow). If the tab does not close, just take the user to the homepage.
            try { window.close(); } catch (e) {}
            try { self.close(); } catch (e) {}
            try { top.close(); } catch (e) {}
            window.setTimeout(function () {
                if (document.hidden) return;
                window.location.replace('/');
            }, 350);
        }

        function toggleFollowUp(id) {
            var panel = document.getElementById('followup-panel-' + id);
            if (!panel) return;
            var nowOpen = panel.classList.toggle('hidden') === false;
            var button = panel.previousElementSibling;
            if (button && button.hasAttribute('aria-expanded')) {
                button.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
            }
            if (nowOpen) {
                var textarea = panel.querySelector('textarea');
                if (textarea) textarea.focus();
            }
        }
    </script>
</body>
</html>
