{{--
    Reusable TODA member list item. Requires:
    - $member      App\Models\Operator (eager-loaded with user + presidentTodas)
    - $routePrefix string 'superadmin' | 'tfrb-officer'
    - $toda        App\Models\Toda the list belongs to (identifies the president)
    Compact row: avatar, name + plate/body meta, status badge, "..." toggle that
    expands an inline Edit/Remove action strip (data-member-more + data-member-actions).
    Presidents are listed but their actions are withheld — those accounts are
    managed from the Presidents section, where edit/archive already refuse them.
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

    // Presidency lives on the membership, so only flag the row when it presides
    // over THIS TODA — a president of another TODA is a plain member here.
    $presidesHere = isset($toda) && $toda
        && collect($member->presidentTodas ?? [])->contains(
            fn ($presidentToda) => (int) $presidentToda->id === (int) $toda->id
        );

    $managedElsewhere = ($member->user?->role ?? 'operator') !== 'operator';
@endphp

<div class="border-b border-slate-100 py-3 last:border-b-0" data-member-item>
    <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <div class="truncate text-sm font-bold text-slate-800">{{ $memberName }}</div>
                @if ($presidesHere)
                    <span class="tw-badge tw-badge-amber shrink-0" data-member-role="president"><i class="bi bi-award-fill"></i>President</span>
                @endif
            </div>
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
            <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-0.5"><i class="bi bi-star-fill text-gold"></i>{{ $member->ratings_count ?? 0 }}</span>
                <span class="inline-flex items-center gap-0.5"><i class="bi bi-exclamation-triangle-fill text-red-500"></i>{{ $member->complaint_count ?? 0 }}</span>
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
            @if ($managedElsewhere)
                <span class="text-[11px] font-medium text-slate-500"><i class="bi bi-shield-lock mr-1"></i>Managed under Presidents — edit and archive are not available here.</span>
            @else
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
            @endif
        </div>
    </div>
</div>
