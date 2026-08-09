{{--
    Grouped notification list (shared between the full page and the live-poll JSON
    response). Requires:
    - $notifications  Illuminate\Pagination\LengthAwarePaginator
    - $type           string  active filter key ('all' | 'unread' | ...)
--}}
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
