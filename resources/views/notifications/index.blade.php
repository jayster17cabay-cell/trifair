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
@endphp

<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route('notifications.index', ['type' => 'all']) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $type === 'all' ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-bell"></i> All
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $type === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('notifications.index', ['type' => 'unread']) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $type === 'unread' ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-envelope-dash"></i> Unread
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $type === 'unread' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts['unread'] }}</span>
    </a>
    <a href="{{ route('notifications.index', ['type' => 'complaint']) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $type === 'complaint' ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-exclamation-triangle"></i> Complaints
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $type === 'complaint' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts['complaint'] }}</span>
    </a>
    <a href="{{ route('notifications.index', ['type' => 'new_rating']) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $type === 'new_rating' ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-star-fill"></i> New Ratings
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $type === 'new_rating' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts['new_rating'] }}</span>
    </a>
    <a href="{{ route('notifications.index', ['type' => 'operator_response']) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $type === 'operator_response' ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-reply-fill"></i> Responses
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $type === 'operator_response' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts['operator_response'] }}</span>
    </a>
    <a href="{{ route($invalidRoute) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-bold transition {{ $invalidActive ? 'border-transparent bg-navy-600 text-white shadow-lg shadow-navy-600/20' : 'border-slate-200 bg-white text-slate-600 hover:border-navy-600 hover:text-navy-600' }}">
        <i class="bi bi-x-circle"></i> Invalid
        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold {{ $invalidActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $invalidCount }}</span>
    </a>
</div>

<div class="tw-card overflow-hidden">
    <div class="divide-y divide-slate-100">
        @forelse ($notifications as $notification)
            @php
                $type = $notification->type;
                if ($type === 'complaint') { $nbg = 'bg-amber-100 text-amber-600'; $nicon = 'exclamation-triangle'; }
                elseif ($type === 'new_rating') { $nbg = 'bg-emerald-50 text-emerald-600'; $nicon = 'star-fill'; }
                elseif ($type === 'operator_response') { $nbg = 'bg-blue-50 text-blue-600'; $nicon = 'reply-fill'; }
                else { $nbg = 'bg-sky-100 text-sky-600'; $nicon = 'info-circle'; }
            @endphp
            <div class="flex items-start gap-3 p-4 transition-colors {{ !$notification->is_read ? 'bg-blue-50/50' : 'hover:bg-slate-50' }}">
                <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg {{ $nbg }}">
                    <i class="bi bi-{{ $nicon }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[15px] font-bold text-slate-900">{{ $notification->title }}</span>
                            @if (!$notification->is_read)
                                <span class="tw-badge tw-badge-navy ml-2">NEW</span>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $notification->message }}</p>
                    @if ($notification->rating && $notification->rating->operator)
                        <div class="mt-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-600"><i class="bi bi-person mr-1"></i>Operator: {{ $notification->rating->operator->user->name ?? 'Unknown' }}</span>
                            @if ($notification->rating->start_location && $notification->rating->end_location)
                                <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <i class="bi bi-circle-fill text-[0.5rem] text-emerald-600"></i>{{ $notification->rating->start_location }}
                                    <i class="bi bi-arrow-right text-[0.7rem] text-slate-300"></i>
                                    <i class="bi bi-circle-fill text-[0.5rem] text-red-600"></i>{{ $notification->rating->end_location }}
                                </span>
                            @endif
                        </div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('notifications.read', $notification) }}" class="tw-btn tw-btn-sm tw-btn-outline">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
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
    </div>

    @if ($notifications->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $notifications->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection
