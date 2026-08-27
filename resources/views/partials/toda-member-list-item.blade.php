{{--
    Reusable TODA member list item. Requires:
    - $member      App\Models\Operator (eager-loaded with user)
    - $routePrefix string 'superadmin' | 'tfrb-officer'
    Compact row: avatar, name + plate/body meta, status badge, "..." toggle that
    expands an inline Edit/Remove action strip (data-member-more + data-member-actions).
--}}
@php
    if ($member->isArchived()) {
        $statusLabel = 'Archived'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-archive-fill';
    } elseif ($member->status === 'active') {
        $statusLabel = 'Active'; $statusClass = 'tw-badge-green'; $statusIcon = 'bi-check-circle-fill';
    } elseif ($member->status === 'pending') {
        $statusLabel = 'Pending'; $statusClass = 'tw-badge-amber'; $statusIcon = 'bi-hourglass-split';
    } elseif ($member->status === 'rejected') {
        $statusLabel = 'Rejected'; $statusClass = 'tw-badge-red'; $statusIcon = 'bi-x-circle-fill';
    } else {
        $statusLabel = 'Inactive'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-pause-circle-fill';
    }

    $memberName = $member->user->name ?? 'Unknown';
@endphp

<div class="border-b border-slate-100 py-3 last:border-b-0" data-member-item>
    <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-bold text-slate-800">{{ $memberName }}</div>
            <div class="truncate text-xs text-slate-500">
                @if ($member->plate_number && $member->body_number)
                    Plate {{ $member->plate_number }} · Body #{{ $member->body_number }}
                @elseif ($member->plate_number)
                    Plate {{ $member->plate_number }}
                @elseif ($member->body_number)
                    Body #{{ $member->body_number }}
                @else
                    <span class="text-slate-400">No vehicle details</span>
                @endif
            </div>
        </div>
        <span class="tw-badge {{ $statusClass }} shrink-0"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
        <button type="button" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                data-member-more title="More options" aria-label="More options for {{ $memberName }}" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
    </div>
    <div class="hidden tw-expand-panel" data-member-actions>
        <div class="mt-2 ml-11 flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
            <a href="{{ route($routePrefix . '.operators.edit', $member) }}" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-pencil"></i>Edit
            </a>
            <form action="{{ route($routePrefix . '.operators.archive', $member) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline" onclick="return confirm(@js('Remove ' . $memberName . ' from this TODA? They will be hidden from active lists but keep their rating history.'))">
                    <i class="bi bi-archive"></i>Remove
                </button>
            </form>
        </div>
    </div>
</div>
