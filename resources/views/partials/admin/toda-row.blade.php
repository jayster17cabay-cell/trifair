{{--
    Reusable TODA table row. Requires:
    - $toda        App\Models\Toda  (must be withCount: operators_count, active_operators_count)
    - $todas       LengthAwarePaginator (for row numbering)
    - $routePrefix string  'superadmin' | 'tfrb-officer'
    - $showManage  bool    shows the edit/delete actions
    The whole row opens the members modal (showTodaMembers); action buttons
    stop propagation so they don't open the modal too.
--}}
@php
    $todaTotal = (int) ($toda->operators_count ?? 0);
    $todaActive = (int) ($toda->active_operators_count ?? 0);

    if (!$toda->is_active) {
        $statusLabel = 'Inactive'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-x-circle-fill';
    } elseif ($todaTotal === 0) {
        $statusLabel = 'No members'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-person-dash';
    } else {
        $statusLabel = 'Active'; $statusClass = 'tw-badge-green'; $statusIcon = 'bi-check-circle-fill';
    }
@endphp

<tr class="tw-tr-hover cursor-pointer even:bg-slate-50/60"
    onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))">
    <td class="tw-td text-slate-500">{{ $loop->iteration + ($todas->currentPage() - 1) * $todas->perPage() }}</td>
    <td class="tw-td">
        <div class="flex items-center gap-2.5">
            <div class="tw-avatar tw-avatar-sm bg-cyan-600"><i class="bi bi-diagram-3"></i></div>
            <span class="text-sm font-bold text-slate-800">{{ $toda->name }}</span>
        </div>
    </td>
    <td class="tw-td text-sm text-slate-500">{{ $toda->area ?? '—' }}</td>
    <td class="tw-td">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="tw-badge tw-badge-navy"><i class="bi bi-people"></i>{{ $todaTotal }} total</span>
            @if ($todaActive > 0)
                <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle"></i>{{ $todaActive }} active</span>
            @endif
        </div>
    </td>
    <td class="tw-td">
        <span class="tw-badge {{ $statusClass }}"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
    </td>
    @if ($showManage)
        <td class="tw-td text-right">
            <div class="inline-flex gap-1.5" onclick="event.stopPropagation()">
                <a href="{{ route($routePrefix . '.todas.edit', $toda) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Edit" aria-label="Edit {{ $toda->name }}">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route($routePrefix . '.todas.destroy', $toda) }}" method="POST" onsubmit="return confirm('Delete this TODA? Drivers must be reassigned first.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete" aria-label="Delete {{ $toda->name }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    @endif
</tr>
