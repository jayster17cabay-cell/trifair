<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - TriFair Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2a4a7a 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('superadmin.dashboard') }}">
                <i class="bi bi-eye me-2" style="color: #f5a623;"></i> TriFair <span style="color: #f5a623;">Superadmin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item d-flex align-items-center">
                        <a href="{{ route('notifications.index') }}" class="btn btn-link text-white position-relative me-3" style="text-decoration: none;">
                            <i class="bi bi-bell fs-5"></i>
                            @if ($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item d-flex align-items-center">
                        <div class="text-end me-3">
                            <small class="d-block" style="font-size: 0.65rem; color: rgba(255,255,255,0.5); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Welcome back</small>
                            <span style="font-weight: 700; font-size: 0.9rem; color: #f5a623;">{{ Auth::user()->name }}</span>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #f5a623;">
                            <span style="color: #1e3a5f; font-weight: 900; font-size: 0.95rem;">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        </div>
                    </li>
                    <li class="nav-item ms-2">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link text-white" style="opacity: 0.7; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
                                <i class="bi bi-box-arrow-right fs-5"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block sidebar py-3">
                <div class="position-sticky" style="top: 1rem;">
                    <div class="sidebar-heading">Main Menu</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" href="{{ route('superadmin.dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.admins*') ? 'active' : '' }}" href="{{ route('superadmin.admins') }}">
                                <i class="bi bi-shield"></i> Admins
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.complaints') ? 'active' : '' }}" href="{{ route('superadmin.complaints') }}">
                                <i class="bi bi-exclamation-triangle"></i> Complaints
                            </a>
                        </li>
                    </ul>
                    <div class="sidebar-heading mt-3">Management</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.drivers*') ? 'active' : '' }}" href="{{ route('superadmin.drivers') }}">
                                <i class="bi bi-people"></i> Drivers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.todas*') ? 'active' : '' }}" href="{{ route('superadmin.todas') }}">
                                <i class="bi bi-diagram-3"></i> TODAs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.ratings*') ? 'active' : '' }}" href="{{ route('admin.ratings') }}">
                                <i class="bi bi-star"></i> Ratings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.reports') ? 'active' : '' }}" href="{{ route('superadmin.reports') }}">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>
                        </li>
                    </ul>
                    <div class="sidebar-heading mt-3">Monitoring</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('superadmin.activity-logs') ? 'active' : '' }}" href="{{ route('superadmin.activity-logs') }}">
                                <i class="bi bi-clock-history"></i> Activity Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notifications*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell"></i> Alerts
                                @if ($unreadCount > 0)
                                    <span class="badge bg-danger ms-auto">{{ $unreadCount }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                    <div class="sidebar-heading mt-3">Account</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link w-100 text-start" style="color: rgba(255,255,255,0.7);">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-10 ms-sm-auto px-4 py-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
