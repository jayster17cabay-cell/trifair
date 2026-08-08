@php
    $shell = [
        'docTitle' => 'Operator',
        'roleLabel' => 'Operator',
        'roleIcon' => 'bi-bicycle',
        'home' => 'operator.dashboard',
        'showBell' => false,
        'groups' => [
            [
                'label' => 'Main Menu',
                'links' => [
                    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'operator.dashboard', 'match' => 'operator.dashboard'],
                    ['label' => 'My Ratings', 'icon' => 'bi-star', 'route' => 'operator.ratings', 'match' => 'operator.ratings'],
                ],
            ],
            [
                'label' => 'Account',
                'links' => [
                    ['label' => 'Settings', 'icon' => 'bi-gear', 'route' => 'operator.settings', 'match' => 'operator.settings'],
                    ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'logout' => true, 'inMenu' => false],
                ],
            ],
        ],
    ];
@endphp
@include('partials.admin-shell')
