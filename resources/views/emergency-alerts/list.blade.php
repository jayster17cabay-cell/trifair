@php
    $colors = [
        'general' => '#dc2626',
        'accident' => '#ea580c',
        'theft_harassment' => '#7f1d1d',
        'other' => '#475569',
    ];
@endphp

@if ($alerts->isEmpty())
    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl text-slate-300">
            <i class="bi bi-shield-check"></i>
        </div>
        <h3 class="mt-4 text-base font-bold text-slate-700">No emergency alerts</h3>
        <p class="mt-1 max-w-sm text-sm text-slate-400">
            Passenger SOS alerts will appear here the moment one is triggered on a ride page.
        </p>
    </div>
@else
    <ul class="divide-y divide-slate-100">
        @foreach ($alerts as $alert)
            <li id="alert-panel-{{ $alert->id }}" class="border-l-[3px] transition-colors"
                style="border-left-color: {{ $colors[$alert->category] ?? '#475569' }};">
                <div class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg text-white"
                                  style="background: {{ $colors[$alert->category] ?? '#475569' }};">
                                <i class="bi {{ $alert->category_icon }}"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-extrabold text-slate-800">
                                        {{ $alert->category_label }}
                                        @if ($alert->location_lat !== null && $alert->location_lng !== null)
                                            <a href="{{ $alert->map_link }}" target="_blank" rel="noopener"
                                               class="ml-1 inline-flex items-center gap-1 text-xs font-bold text-red-500 hover:underline">
                                                <i class="bi bi-geo-alt"></i> Map
                                            </a>
                                        @endif
                                    </span>
                                    <span class="{{ $alert->status_badge }}">{{ $alert->status_label }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $alert->created_at->format('M d, Y h:i A') }}
                                </p>
                            </div>
                        </div>
                        @if (in_array($alert->status, ['active', 'responding'], true))
                            <div class="flex shrink-0 flex-wrap items-center gap-1.5" data-alert-actions="{{ $alert->id }}">
                                @if ($alert->status === 'active')
                                    <form method="POST" action="{{ route($updateRoute ?? 'president.alerts.update', $alert) }}" data-alert-update>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="responding">
                                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                                            <i class="bi bi-arrow-right-circle"></i> Responding
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route($updateRoute ?? 'president.alerts.update', $alert) }}" data-alert-update>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    @if (!empty($alert->note) && $alert->status === 'active')
                                        <input type="hidden" name="resolution_note" value="{{ $alert->note }}">
                                    @endif
<button type="submit" class="tw-btn tw-btn-sm tw-btn-success">
                                            <i class="bi bi-check-circle"></i> Resolved
                                        </button>
                                </form>
                                <form method="POST" action="{{ route($updateRoute ?? 'president.alerts.update', $alert) }}" data-alert-update>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="false_alarm">
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline">
                                        <i class="bi bi-x-circle"></i> False Alarm
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <div>
                                <span class="tw-stat-label"><i class="bi bi-person mr-1 text-navy-600"></i>Passenger</span>
                                <div class="font-semibold text-slate-700">
                                    {{ $alert->passenger_name ?: 'Anonymous passenger' }}
                                </div>
                            </div>
                            @if ($alert->passenger_contact)
                                <div>
                                    <span class="tw-stat-label"><i class="bi bi-telephone mr-1 text-emerald-600"></i>Contact</span>
                                    <a href="tel:{{ $alert->passenger_contact }}" class="font-semibold text-navy-600 hover:underline">{{ $alert->passenger_contact }}</a>
                                </div>
                            @endif
                            <div>
                                <span class="tw-stat-label"><i class="bi bi-bicycle mr-1 text-slate-500"></i>Operator</span>
                                <div class="font-semibold text-slate-700">
                                    {{ $alert->operator && $alert->operator->user ? $alert->operator->user->name : 'Not on a ride yet' }}
                                </div>
                            </div>
                            <div>
                                <span class="tw-stat-label"><i class="bi bi-diagram-3 mr-1 text-violet-500"></i>TODA</span>
                                <div class="font-semibold text-slate-700">{{ $alert->toda ? $alert->toda->name : 'â€”' }}</div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            @if ($alert->note)
                                <div>
                                    <span class="tw-stat-label"><i class="bi bi-chat-left-text mr-1 text-amber-600"></i>Note</span>
                                    <p class="leading-relaxed text-slate-600">{{ $alert->note }}</p>
                                </div>
                            @endif
                            @if ($alert->status !== 'active')
                                <div>
                                    <span class="tw-stat-label"><i class="bi bi-check2-circle mr-1 text-emerald-600"></i>Resolution</span>
                                    <p class="leading-relaxed text-slate-600">
                                        {{ $alert->resolution_note ?: ($alert->status_label . ($alert->resolver ? ' by ' . $alert->resolver->name : ' (no note)')) }}
                                    </p>
                                    @if ($alert->resolved_at)
                                        <p class="mt-0.5 text-xs text-slate-400">on {{ $alert->resolved_at->format('M d, Y h:i A') }}</p>
                                    @endif
                                </div>
                            @endif
                            @if ($alert->status === 'active' || $alert->status === 'responding')
                                <div>
                                    <span class="tw-stat-label"><i class="bi bi-clock mr-1 text-blue-500"></i>Time since alert</span>
                                    <div class="font-semibold text-blue-600" data-alert-created-ms="{{ (int) floor($alert->created_at->timestamp * 1000) }}"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
        {{ $alerts->withQueryString()->links() }}
    </div>
@endif

<script>
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-alert-update]');
        if (!form) return;
        e.preventDefault();
        form.querySelector('[type="submit"]').disabled = true;
        form.querySelector('[type="submit"]').innerHTML = '<i class="bi bi-arrow-repeat"></i> Updating...';
        fetch(form.action, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: new FormData(form),
            credentials: 'same-origin'
        }).then(function (r) {
            if (r.ok) {
                var actions = form.closest('[data-alert-actions]');
                if (actions) actions.remove();
                if (window.__emergencyAlertsRefresh) window.__emergencyAlertsRefresh();
                window.location.reload();
            } else {
                window.location.reload();
            }
        }).catch(function () {
            window.location.reload();
        });
    });
</script>