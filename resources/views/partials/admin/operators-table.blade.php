<tbody>
    @forelse ($operators as $operator)
        @include('partials.admin.operator-row', [
            'operator' => $operator,
            'routePrefix' => $routePrefix,
        ])
    @empty
        <tr>
            <td colspan="5" class="px-4 py-10 text-center">
                <div class="tw-empty">
                    <div class="tw-empty-icon"><i class="bi bi-people"></i></div>
                    <p class="text-sm text-slate-500">No operators found.</p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
