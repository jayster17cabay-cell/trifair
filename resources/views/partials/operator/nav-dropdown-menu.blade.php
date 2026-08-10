{{--
    NavDropdownMenu component — single right-side menu trigger with overlay.
    Items come from $nav (['label','icon','route']); active item is highlighted.
    Includes a divider + red Logout action.
--}}
<div class="relative">
    <button type="button" data-op-menu-toggle class="op-menu-btn" aria-label="Open menu" aria-expanded="false" aria-haspopup="true">
        <i class="bi bi-list text-2xl"></i>
    </button>

    <div data-op-menu class="op-menu" role="menu" aria-hidden="true">
        <div class="op-menu-label">Menu</div>
        @foreach ($nav as $link)
            @php $isActive = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}" role="menuitem" class="op-menu-item {{ $isActive ? 'active' : '' }}" {{ $isActive ? 'aria-current="page"' : '' }}>
                <i class="bi {{ $link['icon'] }}"></i> {{ $link['label'] }}
            </a>
        @endforeach

        <div class="op-menu-divider"></div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" role="menuitem" class="op-menu-item op-menu-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

    <div data-op-menu-overlay class="op-menu-overlay" aria-hidden="true"></div>
</div>
