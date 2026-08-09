<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification type presentation config
    |--------------------------------------------------------------------------
    |
    | Maps each notification type to its presentation (icon, left-border color,
    | icon-circle tint and unread dot color). Adjust these classes in one place
    | to restyle every list that reuses the NotificationItem component.
    |
    */

    'types' => [
        'operator_response' => [
            'label' => 'Operator Responded',
            'icon' => 'reply-fill',
            'icon_bg' => 'bg-blue-50 text-blue-600',
            'border' => 'border-l-blue-500',
            'dot' => 'bg-blue-500',
        ],
        'complaint' => [
            'label' => 'New Complaint Report',
            'icon' => 'exclamation-triangle',
            'icon_bg' => 'bg-amber-100 text-amber-600',
            'border' => 'border-l-amber-400',
            'dot' => 'bg-amber-400',
        ],
        'new_rating' => [
            'label' => 'New Rating Received',
            'icon' => 'star-fill',
            'icon_bg' => 'bg-emerald-50 text-emerald-600',
            'border' => 'border-l-emerald-500',
            'dot' => 'bg-emerald-500',
        ],
        'invalid' => [
            'label' => 'Invalid Rating',
            'icon' => 'x-circle',
            'icon_bg' => 'bg-red-50 text-red-600',
            'border' => 'border-l-red-500',
            'dot' => 'bg-red-500',
        ],
    ],

    'default' => [
        'label' => 'Notification',
        'icon' => 'info-circle',
        'icon_bg' => 'bg-sky-100 text-sky-600',
        'border' => 'border-l-sky-400',
        'dot' => 'bg-sky-400',
    ],

];
