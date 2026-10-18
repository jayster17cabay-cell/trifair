@php
    $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
    $shell = [
        'docTitle' => 'TODA President',
        'roleLabel' => 'TODA President',
        'roleIcon' => 'bi-award',
        'home' => 'president.dashboard',
        'showBell' => true,
        'groups' => [
            [
                'label' => 'Dashboard',
                'links' => [
                    ['label' => 'Overview', 'icon' => 'bi-grid-1x2', 'route' => 'president.dashboard', 'match' => 'president.dashboard', 'active' => !request()->filled('section')],
                    ['label' => 'My Ratings', 'icon' => 'bi-star', 'href' => route('president.dashboard', ['section' => 'ratings']), 'match' => 'president.dashboard', 'active' => request()->query('section') === 'ratings'],
                    ['label' => 'Join Requests', 'icon' => 'bi-box-arrow-in-down', 'href' => route('president.dashboard', ['section' => 'join-requests']), 'match' => 'president.dashboard', 'active' => request()->query('section') === 'join-requests'],
                    ['label' => 'Members', 'icon' => 'bi-people', 'href' => route('president.dashboard', ['section' => 'members']), 'match' => 'president.dashboard', 'active' => request()->query('section') === 'members'],
                ],
            ],
            [
                'label' => 'Management',
                'links' => [
                    ['label' => 'Members', 'icon' => 'bi-people', 'route' => 'president.members', 'match' => 'president.members*'],
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
