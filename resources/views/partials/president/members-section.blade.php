{{--
    Section B — members list of the president's TODA (with search + status filter).
    Requires: $members (paginator), $memberDetailUrl (route base for detail ajax).
--}}
<div class="tw-card overflow-hidden transition-shadow duration-200 hover:shadow-md">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" style="background-image: linear-gradient(135deg, #0a1d33 0%, #0f2a4a 100%);">
        <h3 class="tw-card-title text-sm !text-white"><i class="bi bi-people-fill mr-1 text-gold"></i> TODA Members</h3>
        <form id="presidentMemberFilter" class="flex flex-col gap-2 sm:flex-row sm:items-center" method="GET" action="{{ route('president.dashboard') }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, body #, plate…"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold sm:w-56">
            <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                <option value="">All statuses</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="tw-btn tw-btn-gold px-4 py-1.5 text-sm"><i class="bi bi-search mr-1"></i> Filter</button>
        </form>
    </div>

    <div id="presidentMembersTable">
        @include('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => $memberDetailUrl])
    </div>
</div>
