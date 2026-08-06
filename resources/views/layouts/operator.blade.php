<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - TriFair Operator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
</head>
<body>
    <nav class="tw-topbar">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" type="button" class="tw-topbar-icon-btn lg:hidden" aria-label="Toggle menu">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <a class="tw-topbar-brand" href="{{ route('operator.dashboard') }}">
                <i class="bi bi-bicycle text-xl text-gold"></i>
                <span>TriFair <span class="text-gold">Operator</span></span>
            </a>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
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
                <div id="topMenu" data-tw-dropdown-menu class="tw-dropdown right-0 top-12 w-60">
                    <div class="tw-dropdown-label">Menu</div>
                    <a href="{{ route('operator.dashboard') }}" class="tw-dropdown-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="{{ route('operator.ratings') }}" class="tw-dropdown-item"><i class="bi bi-star"></i> My Ratings</a>
                    <div class="tw-dropdown-divider"></div>
                    <a href="{{ route('operator.settings') }}" class="tw-dropdown-item"><i class="bi bi-gear"></i> Settings</a>
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
            <div class="mb-4 flex items-center justify-between border-b border-white/10 pb-4 lg:hidden">
                <span class="text-base font-extrabold text-white">TriFair <span class="text-gold">Operator</span></span>
                <button id="sidebarClose" type="button" class="tw-topbar-icon-btn" aria-label="Close menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="tw-sidebar-heading">Main Menu</div>
            <nav class="space-y-1">
                <a class="tw-sidebar-link {{ request()->routeIs('operator.dashboard') ? 'active' : '' }}" href="{{ route('operator.dashboard') }}">
                    <i class="bi bi-speedometer2 w-5 text-center"></i> Dashboard
                </a>
                <a class="tw-sidebar-link {{ request()->routeIs('operator.ratings') ? 'active' : '' }}" href="{{ route('operator.ratings') }}">
                    <i class="bi bi-star w-5 text-center"></i> My Ratings
                </a>
            </nav>

            <div class="tw-sidebar-heading">Account</div>
            <nav class="space-y-1">
                <a class="tw-sidebar-link {{ request()->routeIs('operator.settings') ? 'active' : '' }}" href="{{ route('operator.settings') }}">
                    <i class="bi bi-gear w-5 text-center"></i> Settings
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="tw-sidebar-link w-full text-left">
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

    <script src="{{ asset('js/app.js') . '?v=' . filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
