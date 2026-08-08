{{--
    Shared TODA activity grid. Params:
    - $todaStats   collection of TODAs with operators_count, active_operators_count, avg_rating, area
    - $membersUrl  base URL for the TODA members modal (role-scoped)
    Also includes the members modal partial once.
--}}
@if (($todaStats ?? collect())->isNotEmpty())
<div class="mb-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($todaStats as $toda)
        @php
            $todaTotal = $toda->operators_count ?? 0;
            $todaActive = $toda->active_operators_count ?? 0;
            $todaPct = $todaTotal > 0 ? round(($todaActive / $todaTotal) * 100) : 0;
        @endphp
        <button type="button" onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))" class="tw-card flex cursor-pointer items-center gap-2.5 p-3 text-left transition hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-soft">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-bold text-slate-800">{{ $toda->name }}</div>
                @if ($toda->area)
                    <div class="truncate text-[11px] text-slate-500"><i class="bi bi-geo-alt"></i> {{ $toda->area }}</div>
                @endif
                <div class="tw-progress mt-1.5 h-1.5">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $todaPct }}%;"></div>
                </div>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
                @if ($toda->avg_rating !== null)
                    <span class="tw-badge tw-badge-blue"><i class="bi bi-star-fill text-[10px]"></i>{{ number_format($toda->avg_rating, 1) }}</span>
                @else
                    <span class="tw-badge tw-badge-gray">No ratings</span>
                @endif
                <span class="text-xs font-bold text-slate-600">{{ $todaActive }}<span class="font-medium text-slate-400">/{{ $todaTotal }} active</span></span>
            </div>
        </button>
    @endforeach
</div>
@include('partials.toda-members-modal', ['membersUrl' => $membersUrl])
@endif
