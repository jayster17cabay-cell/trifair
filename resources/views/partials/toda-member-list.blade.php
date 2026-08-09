{{-- AJAX-rendered TODA member list. Requires: $members (Collection of Operator with user), $routePrefix --}}

@if ($members->isEmpty())
    <div class="py-8 text-center text-slate-400">
        <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
            <i class="bi bi-people"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">No members yet</p>
        <p class="mt-0.5 text-xs text-slate-400">Use "Add Member" to register the first operator.</p>
    </div>
@else
    @foreach ($members as $member)
        @include('partials.toda-member-list-item', ['member' => $member, 'routePrefix' => $routePrefix])
    @endforeach
@endif
