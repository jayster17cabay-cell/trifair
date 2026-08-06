<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - TriFair TFRB Officer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}">
</head>
<body>
    <nav class="tw-topbar border-b-2 border-gold">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" type="button" class="tw-topbar-icon-btn lg:hidden" aria-label="Toggle menu">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <a class="tw-topbar-brand" href="{{ route('tfrb-officer.dashboard') }}">
                <i class="bi bi-shield-check text-xl text-gold"></i>
                <span>TriFair <span class="text-gold">TFRB Officer</span></span>
            </a>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="hidden text-right md:block">
                <div class="text-[11px] font-bold text-slate-300" data-live-clock="datetime"></div>
            </div>
            <a href="{{ route('notifications.index') }}" class="tw-topbar-icon-btn" title="Notifications">
                <i class="bi bi-bell text-lg"></i>
                @if ($unreadCount > 0)
                    <span id="unreadBellBadge" class="tw-topbar-badge" data-live="unreadCount">{{ $unreadCount }}</span>
                @endif
            </a>
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <small class="block text-[11px] font-medium uppercase tracking-wider text-slate-400">Welcome back</small>
                    <strong class="text-sm font-bold text-gold">{{ Auth::user()->name }}</strong>
                </div>
                <div class="tw-avatar tw-avatar-md bg-gold text-navy-800">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
            </div>
            <div class="relative">
                <button type="button" data-tw-dropdown="topMenu" class="tw-topbar-icon-btn" aria-label="Menu">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div id="topMenu" data-tw-dropdown-menu class="tw-dropdown right-0 top-12 w-64">
                    <div class="tw-dropdown-label">Menu</div>
                    <a href="{{ route('tfrb-officer.dashboard') }}" class="tw-dropdown-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                    <a href="{{ route('tfrb-officer.complaints') }}" class="tw-dropdown-item"><i class="bi bi-exclamation-triangle"></i> Complaints</a>
                    <a href="{{ route('tfrb-officer.operators') }}" class="tw-dropdown-item"><i class="bi bi-people"></i> Operators</a>
                    <a href="{{ route('tfrb-officer.operators', ['status' => 'pending']) }}" class="tw-dropdown-item"><i class="bi bi-hourglass-split"></i> Pending Approvals</a>
                    <a href="{{ route('tfrb-officer.ratings') }}" class="tw-dropdown-item"><i class="bi bi-star-half"></i> Ratings</a>
                    <a href="{{ route('tfrb-officer.reports') }}" class="tw-dropdown-item"><i class="bi bi-bar-chart-line"></i> Reports</a>
                    <a href="{{ route('tfrb-officer.todas') }}" class="tw-dropdown-item"><i class="bi bi-diagram-3"></i> TODA</a>
                    <a href="{{ route('tfrb-officer.activity-logs') }}" class="tw-dropdown-item"><i class="bi bi-clock-history"></i> Activity Logs</a>
                    <a href="{{ route('notifications.index') }}" class="tw-dropdown-item"><i class="bi bi-bell"></i> Alerts</a>
                    <div class="tw-dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="tw-dropdown-item tw-dropdown-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/60 lg:hidden"></div>
        <aside data-tw-sidebar class="tw-sidebar fixed left-0 top-0 z-50 -translate-x-full transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0">
            <div class="relative mb-4 border-b border-white/10 pb-4 lg:hidden">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-base font-extrabold text-white">TriFair <span class="text-gold">TFRB</span></span>
                    <button id="sidebarClose" type="button" class="tw-topbar-icon-btn" aria-label="Close menu">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="tw-sidebar-heading mb-0">Logged in as</div>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gold/30 bg-gold/10 px-3 py-1.5 text-xs font-bold text-gold">
                    <i class="bi bi-shield-check"></i> TFRB Officer
                </span>
            </div>

            <div class="tw-sidebar-heading">Main Menu</div>
            <nav class="space-y-1">
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.dashboard') ? 'active' : '' }}" href="{{ route('tfrb-officer.dashboard') }}">
                    <i class="bi bi-grid-1x2 w-5 text-center"></i> Dashboard
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.complaints') ? 'active' : '' }}" href="{{ route('tfrb-officer.complaints') }}">
                    <i class="bi bi-exclamation-triangle w-5 text-center"></i> Complaints
                </a>
            </nav>

            <div class="tw-sidebar-heading">Management</div>
            <nav class="space-y-1">
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.operators') ? 'active' : '' }}" href="{{ route('tfrb-officer.operators') }}">
                    <i class="bi bi-people w-5 text-center"></i> Operators
                </a>
                <a class="tw-sidebar-link {{ request()->query('status') === 'pending' ? 'active bg-gold/10 !text-gold' : '' }}" href="{{ route('tfrb-officer.operators', ['status' => 'pending']) }}">
                    <i class="bi bi-hourglass-split w-5 text-center"></i> Pending Approvals
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.ratings') ? 'active' : '' }}" href="{{ route('tfrb-officer.ratings') }}">
                    <i class="bi bi-star-half w-5 text-center"></i> Ratings
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.reports') ? 'active' : '' }}" href="{{ route('tfrb-officer.reports') }}">
                    <i class="bi bi-bar-chart-line w-5 text-center"></i> Reports
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.todas*') ? 'active' : '' }}" href="{{ route('tfrb-officer.todas') }}">
                    <i class="bi bi-diagram-3 w-5 text-center"></i> TODA
                </a>
            </nav>

            <div class="my-3 h-px bg-white/5"></div>

            <div class="tw-sidebar-heading">Monitoring</div>
            <nav class="space-y-1">
                <a class="tw-sidebar-link {{ request()->routeIs('tfrb-officer.activity-logs') ? 'active' : '' }}" href="{{ route('tfrb-officer.activity-logs') }}">
                    <i class="bi bi-clock-history w-5 text-center"></i> Activity Logs
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('notifications*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                    <i class="bi bi-bell w-5 text-center"></i> Alerts
                    @if ($unreadCount > 0)
                        <span id="unreadSideBadge" class="tw-sidebar-badge" data-live="unreadCount">{{ $unreadCount }}</span>
                    @endif
                </a>
            </nav>

            <div class="my-3 h-px bg-white/5"></div>

            <div class="tw-sidebar-heading">Account</div>
            <nav class="space-y-1">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="tw-sidebar-link w-full text-left hover:bg-red-500/10 hover:text-red-400">
                        <i class="bi bi-box-arrow-right w-5 text-center"></i> Logout
                    </button>
                </form>
            </nav>
        </aside>

        <main class="min-h-screen w-full min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="tw-alert tw-alert-success">
                    <i class="bi bi-check-circle-fill mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif

            @if (session('error'))
                <div class="tw-alert tw-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="tw-alert-close" data-tw-dismiss aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="tw-alert tw-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
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

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
