@php
    $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
    $shell = [
        'docTitle' => 'TFRB Officer',
        'roleLabel' => 'TFRB Officer',
        'roleIcon' => 'bi-shield-check',
        'home' => 'tfrb-officer.dashboard',
        'showBell' => true,
        'groups' => [
            [
                'label' => 'Main Menu',
                'links' => [
                    ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'route' => 'tfrb-officer.dashboard', 'match' => 'tfrb-officer.dashboard'],
                    ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle', 'route' => 'tfrb-officer.complaints', 'match' => 'tfrb-officer.complaints'],
                ],
            ],
            [
                'label' => 'Management',
                'links' => [
                    ['label' => 'Operators', 'icon' => 'bi-people', 'route' => 'tfrb-officer.operators', 'match' => 'tfrb-officer.operators'],
                    ['label' => 'Pending Approvals', 'icon' => 'bi-hourglass-split', 'href' => route('tfrb-officer.operators', ['status' => 'pending']), 'match' => 'tfrb-officer.operators', 'active' => request()->query('status') === 'pending', 'gold' => true],
                    ['label' => 'Ratings', 'icon' => 'bi-star-half', 'route' => 'tfrb-officer.ratings', 'match' => 'tfrb-officer.ratings*'],
                    ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'route' => 'tfrb-officer.reports', 'match' => 'tfrb-officer.reports'],
                    ['label' => 'TODA', 'icon' => 'bi-diagram-3', 'route' => 'tfrb-officer.todas', 'match' => 'tfrb-officer.todas*'],
                ],
            ],
            [
                'label' => 'Monitoring',
                'links' => [
                    ['label' => 'Activity Logs', 'icon' => 'bi-clock-history', 'route' => 'tfrb-officer.activity-logs', 'match' => 'tfrb-officer.activity-logs'],
                    ['label' => 'Alerts', 'icon' => 'bi-bell', 'route' => 'notifications.index', 'match' => 'notifications*', 'badge' => $unreadCount],
                ],
            ],
            [
                'label' => 'Account',
                'links' => [
                    ['label' => 'Settings', 'icon' => 'bi-gear', 'route' => 'tfrb-officer.settings', 'match' => 'tfrb-officer.settings'],
                    ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'logout' => true, 'inMenu' => false],
                ],
            ],
        ],
    ];
@endphp
@include('partials.admin-shell')
