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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') . '?v=' . filemtime(public_path('css/tailwind.css')) }}">
    @stack('head')
</head>
<body class="op-app-body">
    @include('partials.operator.app-header', ['nav' => $opNav])

    <main class="op-app-main">
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

    @stack('scripts')
    <script src="{{ asset('js/app.js') . '?v=' . filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
