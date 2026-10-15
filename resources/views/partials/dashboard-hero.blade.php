{{--
    Shared dashboard hero panel. Params:
    - $eyebrow    string  role label shown above the heading
    - $subtitle   string  one-line context under the greeting
    - $actionHref / $actionLabel / $actionIcon (optional)  primary action button
    Reads $unreadCount (guarded) to show an inline notification chip.
--}}
@php $unreadCount = isset($unreadCount) ? (int) $unreadCount : 0; @endphp
<div class="relative mb-4 overflow-hidden rounded-2xl text-white shadow-soft" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 55%, #11366f 100%);">
    <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full" style="background: radial-gradient(circle, rgba(245,184,0,0.22) 0%, transparent 70%);"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-16 h-64 w-64 rounded-full" style="background: radial-gradient(circle, rgba(46,125,209,0.2) 0%, transparent 70%);"></div>
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3.5 sm:px-7 sm:py-4">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gold">{{ $eyebrow }}</p>
            <h2 class="mt-0.5 text-lg font-extrabold tracking-tight sm:text-xl">
                Welcome, <span class="text-gold">{{ Auth::user()->name }}</span>
            </h2>
            <p class="mt-0.5 text-sm text-slate-300">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($unreadCount > 0)
                <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gold/40 bg-gold/15 px-3 py-1.5 text-xs font-bold text-gold transition hover:bg-gold/25">
                    <i class="bi bi-bell-fill"></i> {{ $unreadCount }} unread
                </a>
            @endif
            @if (!empty($actionLabel))
                <a href="{{ $actionHref }}" class="inline-flex items-center gap-2 rounded-lg bg-gold px-3 py-1.5 text-xs font-bold text-navy-800 shadow-goldlift transition hover:bg-gold-dark">
                    <i class="bi {{ $actionIcon ?? 'bi-arrow-right' }}"></i> {{ $actionLabel }}
                </a>
            @endif
        </div>
    </div>
</div>
