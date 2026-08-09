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
                    <p class="text-sm text-slate-500">No TODA found.</p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
