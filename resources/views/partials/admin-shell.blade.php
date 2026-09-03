{{--
    Shared admin shell. Rendered by the role layouts (layouts/superadmin, layouts/tfrb-officer,
    layouts/operator). Expects a $shell array:
    - docTitle    string   page/brand suffix shown in <title>
    - roleLabel   string   human role label (e.g. 'Superadmin')
    - roleIcon    string   bootstrap icon for the brand
    - home        string   route name of the role dashboard
    - showBell    bool     whether the unread-notification bell is shown
    - groups      array    sidebar nav groups:
                           ['label' => string|null, 'links' => [
                               ['label', 'icon', 'match' (route pattern), 'route' (name),
                                'href' (optional explicit url), 'badge' (int|null),
                                'active' (bool|null override), 'gold' (bool)]
                           ]]
    The content section is yielded from the extending view.
--}}
@php
    $shell['roleLabel'] = $shell['roleLabel'] ?? 'Dashboard';
    $shell['roleIcon'] = $shell['roleIcon'] ?? 'bi-tools';
    $shell['home'] = $shell['home'] ?? '#';
    $shell['showBell'] = $shell['showBell'] ?? true;
    $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $shell['docTitle'] }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
    @stack('head')
</head>
<body>
    <nav class="tw-topbar">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" type="button" class="inline-flex items-center justify-center rounded-md bg-slate-100 p-2 text-slate-800 transition-colors hover:bg-slate-200 lg:hidden" aria-label="Toggle menu">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <a class="tw-topbar-brand" href="{{ route($shell['home']) }}">
                <i class="bi {{ $shell['roleIcon'] }} text-xl text-gold"></i>
                <span>TriFair <span class="text-gold">{{ $shell['roleLabel'] }}</span></span>
            </a>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="hidden text-right md:block">
                <div class="text-[11px] font-bold text-white/60" data-live-clock="datetime"></div>
            </div>
            @if ($shell['showBell'])
                <a href="{{ route('notifications.index') }}" class="tw-topbar-icon-btn" title="Notifications" aria-label="Notifications">
                    <i class="bi bi-bell text-lg"></i>
                    @if ($unreadCount > 0)
                        <span id="unreadBellBadge" class="tw-topbar-badge" data-live="unreadCount">{{ $unreadCount }}</span>
                    @endif
                </a>
            @endif
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <small class="block text-[11px] font-medium uppercase tracking-wider text-white/60">Welcome back</small>
                    <strong class="text-sm font-bold text-gold">{{ Auth::user()->name }}</strong>
                </div>
                <div class="tw-avatar tw-avatar-md bg-gold text-navy-800">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>
        <aside data-tw-sidebar class="tw-sidebar fixed left-0 top-0 z-50 -translate-x-full transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0">
            <div class="tw-sidebar-brand">
                <div class="tw-sidebar-brand-logo"><i class="bi {{ $shell['roleIcon'] }}"></i></div>
                <div class="min-w-0">
                    <div class="text-base font-extrabold leading-tight text-white">TriFair</div>
                    <div class="text-[11px] font-bold uppercase tracking-widest text-gold">{{ $shell['roleLabel'] }}</div>
                </div>
                <button id="sidebarClose" type="button" class="tw-topbar-icon-btn ml-auto lg:hidden" aria-label="Close menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            @foreach ($shell['groups'] as $group)
                @if (!empty($group['label']))
                    <div class="tw-sidebar-heading">{{ $group['label'] }}</div>
                @elseif (!$loop->first)
                    <div class="tw-sidebar-sep"></div>
                @endif
                <nav class="space-y-1">
                    @foreach ($group['links'] as $link)
                        @if (!empty($link['logout']))
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="tw-sidebar-link tw-sidebar-link-danger w-full text-left">
                                    <i class="bi bi-box-arrow-right w-5 text-center"></i> Logout
                                </button>
                            </form>
                            @continue
                        @endif
                        @php
                            $isActive = array_key_exists('active', $link)
                                ? (bool) $link['active']
                                : request()->routeIs($link['match'] ?? $link['route']);
                            $href = $link['href'] ?? route($link['route']);
                        @endphp
                        <a class="tw-sidebar-link {{ $isActive ? 'active' : '' }} {{ !empty($link['gold']) && !$isActive ? 'border-l-2 border-gold' : '' }}" href="{{ $href }}" aria-current="{{ $isActive ? 'page' : 'false' }}">
                            <i class="bi {{ $link['icon'] }} w-5 text-center {{ !empty($link['gold']) ? 'text-gold' : '' }}"></i>
                            {{ $link['label'] }}
                            @if (isset($link['badge']) && $link['badge'] > 0)
                                <span class="tw-sidebar-badge" data-live="unreadCount" data-badge="{{ $link['badge'] }}">{{ $link['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </aside>

        <main class="min-h-screen w-full min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="tw-alert tw-alert-success">
                    <i class="bi bi-check-circle-fill tw-alert-icon"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif

            @if (session('error'))
                <div class="tw-alert tw-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill tw-alert-icon"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="tw-alert tw-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill tw-alert-icon"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script src="{{ asset('js/app.js') . '?v=' . filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
