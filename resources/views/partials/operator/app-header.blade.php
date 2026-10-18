{{--
    AppHeader component — navy mobile-app header shared across operator screens.
    Renders the brand row + NavDropdownMenu, then optional page content via
    @yield('header-body'). Requires: $nav (menu link array).
--}}
<header class="op-header">
    <div class="op-header-top">
        <a href="{{ route('operator.dashboard') }}" class="op-brand">
            <span class="op-brand-logo"><i class="bi bi-bicycle"></i></span>
            <span class="font-extrabold">TriFair <span class="text-gold">Operator</span></span>
        </a>

        @php
            // Only an account in more than one TODA needs a way back to the
            // picker; a single-TODA operator would have nothing to choose.
            $opHeaderTodas = collect();
            $opHeaderUser = auth()->user();
            if ($opHeaderUser && ($opHeaderUser->isOperator() || $opHeaderUser->isOperatorPresident())) {
                $opHeaderTodas = \App\Support\TodaContext::availableTodas($opHeaderUser);
            }
        @endphp
        @if ($opHeaderTodas->count() > 1)
            <a href="{{ route('toda.select') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-white/25 bg-white/10 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-white/20"
               title="Change TODA" aria-label="Change TODA">
                <i class="bi bi-arrow-left-right"></i>
                <span class="hidden sm:inline">Change TODA</span>
            </a>
        @endif

        @include('partials.operator.nav-dropdown-menu', ['nav' => $nav ?? []])
    </div>

    <div class="op-header-body">
        @yield('header-body')
    </div>
</header>
