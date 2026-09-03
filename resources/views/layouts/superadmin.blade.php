@php
    $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
    $shell = [
        'docTitle' => 'Superadmin',
        'roleLabel' => 'Superadmin',
        'roleIcon' => 'bi-eye',
        'home' => 'superadmin.dashboard',
        'showBell' => true,
        'groups' => [
            [
                'label' => 'Main Menu',
                'links' => [
                    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'superadmin.dashboard', 'match' => 'superadmin.dashboard'],
                    ['label' => 'TFRB Officers', 'icon' => 'bi-shield', 'route' => 'superadmin.officers', 'match' => 'superadmin.officers*'],
                    ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle', 'route' => 'superadmin.complaints', 'match' => 'superadmin.complaints*'],
                ],
            ],
            [
                'label' => 'Management',
                'links' => [
                    ['label' => 'Operators', 'icon' => 'bi-people', 'route' => 'superadmin.operators', 'match' => 'superadmin.operators'],
                    ['label' => 'TODA', 'icon' => 'bi-diagram-3', 'route' => 'superadmin.todas', 'match' => 'superadmin.todas*'],
                    ['label' => 'Presidents', 'icon' => 'bi-award', 'route' => 'superadmin.presidents', 'match' => 'superadmin.presidents*'],
                    ['label' => 'Ratings', 'icon' => 'bi-star', 'route' => 'superadmin.ratings', 'match' => 'superadmin.ratings*'],
                    ['label' => 'Reports', 'icon' => 'bi-bar-chart', 'route' => 'superadmin.reports', 'match' => 'superadmin.reports*'],
                ],
            ],
            [
                'label' => null,
                'links' => [
                    ['label' => 'Pending Approvals', 'icon' => 'bi-hourglass-split', 'href' => route('superadmin.operators', ['status' => 'pending']), 'match' => 'superadmin.operators', 'active' => request()->query('status') === 'pending', 'gold' => true],
                ],
            ],
            [
                'label' => 'Monitoring',
                'links' => [
                    ['label' => 'Activity Logs', 'icon' => 'bi-clock-history', 'route' => 'superadmin.activity-logs', 'match' => 'superadmin.activity-logs'],
                    ['label' => 'Alerts', 'icon' => 'bi-bell', 'route' => 'notifications.index', 'match' => 'notifications*', 'badge' => $unreadCount],
                ],
            ],
            [
                'label' => 'Account',
                'links' => [
                    ['label' => 'Settings', 'icon' => 'bi-gear', 'route' => 'superadmin.settings', 'match' => 'superadmin.settings'],
                    ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'logout' => true, 'inMenu' => false],
                ],
            ],
        ],
    ];
@endphp
@include('partials.admin-shell')
