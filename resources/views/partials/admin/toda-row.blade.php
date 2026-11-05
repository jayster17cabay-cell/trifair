{{--
    Reusable TODA card. Requires:
    - $toda        App\Models\Toda  (must be withCount: operators_count, active_operators_count)
    - $todas       LengthAwarePaginator (kept for backwards compatibility)
    - $routePrefix string  'superadmin' | 'tfrb-officer'
    - $showManage  bool    shows the edit/delete actions
    The card itself is not clickable; "View members" opens showTodaMembers.
--}}
@php
    $todaTotal = (int) ($toda->operators_count ?? 0);
    $todaActive = (int) ($toda->active_operators_count ?? 0);
    $todaInactive = max(0, $todaTotal - $todaActive);
    $activePercent = $todaTotal > 0 ? (int) round(($todaActive / $todaTotal) * 100) : 0;

    if (!$toda->is_active) {
        $statusLabel = 'Inactive'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-x-circle-fill';
        $accentClass = 'from-slate-400 to-slate-500';
        $barClass = 'bg-slate-400';
    } elseif ($todaTotal === 0) {
        $statusLabel = 'No members'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-person-dash';
        $accentClass = 'from-cyan-500 to-sky-600';
        $barClass = 'bg-slate-300';
    } else {
        $statusLabel = 'Active'; $statusClass = 'tw-badge-green'; $statusIcon = 'bi-check-circle-fill';
        $accentClass = 'from-emerald-500 to-cyan-600';
        $barClass = 'bg-emerald-500';
    }
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-md">
    <div class="h-1 w-full bg-gradient-to-r {{ $accentClass }}"></div>

    <div class="flex flex-1 flex-col gap-4 p-4 sm:p-5">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-600 to-navy-700 text-white shadow-sm">
                <i class="bi bi-diagram-3 text-lg"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-extrabold text-slate-900" title="{{ $toda->name }}">{{ $toda->name }}</h3>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                    <i class="bi bi-geo-alt shrink-0 text-slate-400"></i>
                    <span class="truncate">{{ $toda->area ?: 'No area set' }}</span>
                </p>
            </div>
            <span class="tw-badge {{ $statusClass }} shrink-0"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
        </div>

        @if (filled($toda->description))
            <p class="line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $toda->description }}</p>
        @endif

        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
            <div class="flex items-end justify-between gap-2">
                <span class="text-xl font-extrabold leading-none tabular-nums text-slate-800">{{ $todaTotal }} total</span>
                <span class="text-sm font-bold tabular-nums text-emerald-600">{{ $todaActive }} active</span>
            </div>
            <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full {{ $barClass }} transition-all duration-300" style="width: {{ $activePercent }}%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                <span>{{ $todaInactive }} inactive</span>
                <span class="font-semibold text-slate-500">{{ $activePercent }}% active</span>
            </div>
        </div>

        <div class="mt-auto flex items-center gap-2 border-t border-slate-100 pt-3">
            <button type="button"
                    class="tw-btn tw-btn-sm tw-btn-outline flex-1 justify-center"
                    onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))"
                    title="View members of {{ $toda->name }}">
                <i class="bi bi-people"></i>View members
            </button>
            @if ($showManage)
                <a href="{{ route($routePrefix . '.todas.edit', $toda) }}"
                   class="tw-btn tw-btn-sm tw-btn-outline"
                   title="Edit {{ $toda->name }}" aria-label="Edit {{ $toda->name }}">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route($routePrefix . '.todas.destroy', $toda) }}" method="POST" onsubmit="return confirm('Delete this TODA? Drivers must be reassigned first.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete {{ $toda->name }}" aria-label="Delete {{ $toda->name }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>
</article>
