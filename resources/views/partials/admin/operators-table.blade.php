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
                    <h3 class="tw-empty-title">No Operators Found</h3>
                    <p class="text-sm text-slate-500">No operators match the current filters.</p>
                    @if (!request('status'))
                        <a href="{{ route($routePrefix . '.operators.create') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-4">
                            <i class="bi bi-person-plus"></i>Add Operator
                        </a>
                    @endif
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
