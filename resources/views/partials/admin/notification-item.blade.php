{{--
    Reusable notification item. Requires:
    - $notification  App\Models\Notification (eager-loaded with rating.operator.user)

    Collapsed by default: one row showing type icon, title, one-line message and a
    right-aligned relative timestamp. Clicking the row expands inline details
    (operator, contact, route) and marks the notification as read (dot disappears).
    Type colors (left border, icon circle, unread dot) come from config('notifications').
--}}
@php
    $cfg = config('notifications.types.' . $notification->type) ?? config('notifications.default');
    $rating = $notification->rating;
    $operator = $rating ? $rating->operator : null;
    $operatorName = $operator && $operator->user ? $operator->user->name : 'Unknown';
    $contact = $rating && $rating->passenger_contact
        ? $rating->passenger_contact
        : ($operator && $operator->user ? $operator->user->phone : null);
@endphp

<div class="border-b border-l-[3px] border-slate-100 transition-colors {{ $cfg['border'] }} {{ $notification->is_read ? '' : 'bg-blue-50/40' }}"
     data-notification-card
     data-notification-id="{{ $notification->id }}">
    <div class="flex cursor-pointer select-none items-center gap-3 px-4 py-3 transition-colors sm:px-5 {{ $notification->is_read ? 'hover:bg-slate-50/70' : '' }}"
         data-notification-toggle
         role="button"
         tabindex="0"
         aria-expanded="false">
        <span class="h-2 w-2 shrink-0 rounded-full {{ $cfg['dot'] }} {{ $notification->is_read ? 'invisible' : '' }}"
              data-notification-dot
              title="Unread"></span>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg {{ $cfg['icon_bg'] }}">
            <i class="bi bi-{{ $cfg['icon'] }}"></i>
        </div>
        <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-bold text-slate-800">{{ $notification->title }}</div>
            <p class="truncate text-xs text-slate-500">{{ $notification->message }}</p>
        </div>
        <span class="shrink-0 text-xs font-medium text-slate-400">{{ $notification->short_time }}</span>
        <i class="bi bi-chevron-down shrink-0 text-slate-400 transition-transform duration-200" data-notification-chevron></i>
    </div>

    <div class="tw-expand-panel hidden border-t border-slate-100" data-notification-details>
        <div class="grid gap-x-8 gap-y-4 p-4 sm:p-5 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-person mr-1 text-navy-600"></i>Operator</div>
                    <div class="text-sm font-semibold text-slate-700">{{ $operatorName }}</div>
                </div>
                @if ($contact)
                    <div>
                        <div class="tw-stat-label mb-1"><i class="bi bi-telephone mr-1 text-emerald-600"></i>Contact</div>
                        <a href="tel:{{ $contact }}" class="text-sm font-semibold text-navy-600 hover:underline">{{ $contact }}</a>
                    </div>
                @endif
                @if ($notification->message)
                    <div>
                        <div class="tw-stat-label mb-1"><i class="bi bi-card-text mr-1 text-slate-500"></i>Details</div>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $notification->message }}</p>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-signpost-2 mr-1 text-blue-500"></i>Route</div>
                    @if ($rating && ($rating->start_location || $rating->end_location))
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($rating->start_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-emerald-600"></i> {{ $rating->start_location }}
                                </span>
                            @endif
                            @if ($rating->start_location && $rating->end_location)
                                <i class="bi bi-arrow-right text-[0.7rem] text-slate-300"></i>
                            @endif
                            @if ($rating->end_location)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    <i class="bi bi-circle-fill text-[0.55rem] text-red-600"></i> {{ $rating->end_location }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-slate-400">No route data</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
            <span class="text-xs text-slate-400">{{ $notification->created_at->format('M d, Y \a\t h:i A') }}</span>
            <a href="{{ route('notifications.read', $notification) }}" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-arrow-right"></i> Open report
            </a>
        </div>
    </div>
</div>
