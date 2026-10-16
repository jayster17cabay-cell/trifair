{{--
    Section B — members list of the president's TODA.
    Requires: $members (paginator), $memberDetailUrl (route base for detail ajax).
--}}
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <h3 class="tw-card-title text-sm !text-white"><i class="bi bi-people-fill mr-1 text-gold"></i> TODA Members</h3>
        <div class="flex items-center gap-3">
            <p class="hidden text-xs text-slate-300 sm:inline">{{ $members->total() }} {{ $members->total() === 1 ? 'member' : 'members' }}</p>
            <button type="button" class="tw-btn tw-btn-gold px-3 py-1.5 text-xs" data-tw-modal-open="presidentAddMemberModal">
                <i class="bi bi-person-plus-fill mr-1"></i> Add Member
            </button>
        </div>
    </div>

    <div id="presidentMembersTable">
        @include('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => $memberDetailUrl])
    </div>
</div>
