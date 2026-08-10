{{-- Shared TODA table <tbody>. Requires: $todas, $routePrefix, $showManage --}}

<tbody>
    @forelse ($todas as $toda)
        @include('partials.admin.toda-row', [
            'toda' => $toda,
            'todas' => $todas,
            'routePrefix' => $routePrefix,
            'showManage' => $showManage,
        ])
    @empty
        <tr>
            <td colspan="{{ $showManage ? 6 : 5 }}" class="px-4 py-10 text-center">
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
            </td>
        </tr>
    @endforelse
</tbody>
