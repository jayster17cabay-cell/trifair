@extends(Auth::user()->isSuperadmin() ? 'layouts.superadmin' : 'layouts.tfrb-officer')

@section('title', 'Notifications')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bell mr-2 text-gold"></i>Notifications</h1>
        <p class="tw-page-sub">Stay updated with system alerts and new ratings</p>
    </div>
    @if ($notifications->count() > 0)
        <form id="markAllReadForm" action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="tw-btn tw-btn-gold">
                <i class="bi bi-check-all"></i> Mark All as Read
            </button>
        </form>
    @endif
</div>

@php
    $invalidRoute = Auth::user()->isSuperadmin() ? 'superadmin.invalid-ratings' : 'tfrb-officer.invalid-ratings';
    $invalidActive = request()->routeIs('*.invalid-ratings');
    $tabs = [
        ['key' => 'all', 'label' => 'All', 'icon' => 'bi-bell', 'count' => $counts['all']],
        ['key' => 'unread', 'label' => 'Unread', 'icon' => 'bi-envelope-dash', 'count' => $counts['unread']],
        ['key' => 'complaint', 'label' => 'Complaints', 'icon' => 'bi-exclamation-triangle', 'count' => $counts['complaint']],
        ['key' => 'new_rating', 'label' => 'New Ratings', 'icon' => 'bi-star-fill', 'count' => $counts['new_rating']],
        ['key' => 'operator_response', 'label' => 'Responses', 'icon' => 'bi-reply-fill', 'count' => $counts['operator_response']],
    ];
@endphp

<div class="mb-5 flex flex-wrap items-center gap-2">
    @foreach ($tabs as $tab)
        <a href="{{ route('notifications.index', ['type' => $tab['key']]) }}" class="{{ $type === $tab['key'] ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
            <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            <span class="tw-badge {{ $type === $tab['key'] ? 'tw-badge-gold' : 'tw-badge-gray' }} ml-0.5" data-notif-count="{{ $tab['key'] }}">{{ $tab['count'] }}</span>
        </a>
    @endforeach
    <a href="{{ route($invalidRoute) }}" class="{{ $invalidActive ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-x-circle"></i> Invalid
        <span class="tw-badge {{ $invalidActive ? 'tw-badge-gold' : 'tw-badge-gray' }} ml-0.5" data-notif-count="invalid">{{ $invalidCount }}</span>
    </a>
</div>

<div class="tw-card overflow-hidden">
    <div id="notificationList">
        @include('notifications.list', ['notifications' => $notifications, 'type' => $type])
    </div>
</div>
@endsection
