@php
    $opNav = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'operator.dashboard'],
        ['label' => 'My Ratings', 'icon' => 'bi-star', 'route' => 'operator.ratings'],
        ['label' => 'My Profile', 'icon' => 'bi-person-badge', 'route' => 'operator.profile'],
        ['label' => 'Settings', 'icon' => 'bi-gear', 'route' => 'operator.settings'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — TriFair Operator</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
    @stack('head')
</head>
<body class="op-app-body">
    @include('partials.operator.app-header', ['nav' => $opNav])

    <main class="op-app-main">
        @include('partials.ui.alert', ['type' => 'success', 'message' => session('success')])
        @include('partials.ui.alert', ['type' => 'danger', 'message' => session('error')])
        @include('partials.ui.alert', ['type' => 'amber', 'message' => session('warning')])
        @include('partials.ui.alert', [
            'type' => 'danger',
            'messages' => $errors->all(),
            'heading' => 'Please fix the following errors:',
        ])

        @yield('content')
    </main>

    @stack('scripts')
    <script src="{{ asset('js/app.js') . '?v=' . filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
