{{--
    Section B — members list of the president's TODA.
    Requires: $members (paginator), $memberDetailUrl (route base for detail ajax).
--}}
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #0a1d33 0%, #0f2a4a 100%);">
        <h3 class="tw-card-title text-sm !text-white"><i class="bi bi-people-fill mr-1 text-gold"></i> TODA Members</h3>
        <p class="hidden text-xs text-slate-300 sm:inline">{{ $members->total() }} {{ $members->total() === 1 ? 'member' : 'members' }}</p>
    </div>

    <div id="presidentMembersTable">
        @include('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => $memberDetailUrl])
    </div>
</div>
