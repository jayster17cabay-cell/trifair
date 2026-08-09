@extends(Auth::user()->isSuperadmin() ? 'layouts.superadmin' : 'layouts.tfrb-officer')

@section('title', 'Notifications')

@section('content')
<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-bell mr-2 text-gold"></i>Notifications</h1>
        <p class="tw-page-sub">Stay updated with system alerts and new ratings</p>
    </div>
    @if ($notifications->count() > 0)
        <form action="{{ route('notifications.readAll') }}" method="POST">
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
            <span class="tw-badge {{ $type === $tab['key'] ? 'tw-badge-gold' : 'tw-badge-gray' }} ml-0.5">{{ $tab['count'] }}</span>
        </a>
    @endforeach
    <a href="{{ route($invalidRoute) }}" class="{{ $invalidActive ? 'tw-chip tw-chip-active' : 'tw-chip' }}">
        <i class="bi bi-x-circle"></i> Invalid
        <span class="tw-badge {{ $invalidActive ? 'tw-badge-gold' : 'tw-badge-gray' }} ml-0.5">{{ $invalidCount }}</span>
    </a>
</div>

<div class="tw-card overflow-hidden">
    @php $lastGroup = null; @endphp
    @forelse ($notifications as $notification)
        @if ($loop->first || $notification->date_group !== $lastGroup)
            @php $lastGroup = $notification->date_group; @endphp
            <div class="flex items-center gap-2 px-4 pt-4 pb-1 text-[0.7rem] font-bold uppercase tracking-widest text-slate-400 sm:px-5 {{ $loop->first ? '' : 'mt-4' }}">
                <i class="bi bi-calendar3 text-gold"></i>{{ $lastGroup }}
            </div>
        @endif
        @include('partials.admin.notification-item', ['notification' => $notification])
    @empty
        @php
            $emptyTitle = 'No notifications yet';
            $emptyMsg = "You'll see alerts here when passengers submit ratings.";
            if ($type === 'unread') { $emptyTitle = "You're all caught up!"; $emptyMsg = 'No unread notifications.'; }
            elseif ($type === 'complaint') { $emptyTitle = 'No complaint alerts'; $emptyMsg = 'No passenger complaints have been reported.'; }
            elseif ($type === 'new_rating') { $emptyTitle = 'No new ratings'; $emptyMsg = 'No new passenger ratings have been submitted.'; }
            elseif ($type === 'operator_response') { $emptyTitle = 'No operator responses'; $emptyMsg = 'No operator responses to review.'; }
        @endphp
        <div class="p-10 text-center">
            <div class="tw-empty-icon"><i class="bi bi-bell-slash"></i></div>
            <h3 class="text-base font-bold text-slate-700">{{ $emptyTitle }}</h3>
            <p class="mt-1 text-sm text-slate-400">{{ $emptyMsg }}</p>
        </div>
    @endforelse

    @if ($notifications->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $notifications->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection
