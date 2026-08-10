{{--
    AppHeader component — navy mobile-app header shared across operator screens.
    Renders the brand row + NavDropdownMenu, then optional page content via
    @yield('header-body'). Requires: $nav (menu link array).
--}}
<header class="op-header">
    <div class="op-header-top">
        <a href="{{ route('operator.dashboard') }}" class="op-brand">
            <span class="op-brand-logo"><i class="bi bi-bicycle"></i></span>
            <span>TriFair <span class="text-gold">Operator</span></span>
        </a>

        @include('partials.operator.nav-dropdown-menu', ['nav' => $nav ?? []])
    </div>

    <div class="op-header-body">
        @yield('header-body')
    </div>
</header>
