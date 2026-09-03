@php
    $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
    $shell = [
        'docTitle' => 'TODA President',
        'roleLabel' => 'TODA President',
        'roleIcon' => 'bi-award',
        'home' => 'president.dashboard',
        'showBell' => false,
        'groups' => [
            [
                'label' => 'Main Menu',
                'links' => [
                    ['label' => 'TODA Overview', 'icon' => 'bi-speedometer2', 'route' => 'president.dashboard', 'match' => 'president.dashboard'],
                    ['label' => 'Members', 'icon' => 'bi-people', 'route' => 'president.members', 'match' => 'president.members'],
                ],
            ],
            [
                'label' => 'Account',
                'links' => [
                    ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'logout' => true, 'inMenu' => false],
                ],
            ],
        ],
    ];
@endphp
@include('partials.admin-shell')
