{{-- Shared TODA card grid. Requires: $todas, $routePrefix, $showManage --}}

@forelse ($todas as $toda)
    @include('partials.admin.toda-row', [
        'toda' => $toda,
        'todas' => $todas,
        'routePrefix' => $routePrefix,
        'showManage' => $showManage,
    ])
@empty
    <div class="col-span-full">
        <div class="tw-empty">
            <div class="tw-empty-icon"><i class="bi bi-diagram-3"></i></div>
            <h3 class="tw-empty-title">No TODA Found</h3>
            <p class="text-sm text-slate-500">No TODA associations match the current filters.</p>
            @if ($showManage && !request('search'))
                <a href="{{ route($routePrefix . '.todas.create') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-4">
                    <i class="bi bi-plus-lg"></i>Add TODA
                </a>
            @endif
        </div>
    </div>
@endforelse
