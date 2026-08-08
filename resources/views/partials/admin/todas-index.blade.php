{{-- Shared TODA list page body. Requires: $routePrefix, $showManage, $todas --}}

<div class="tw-page-head">
    <div>
        <h1 class="tw-page-title"><i class="bi bi-diagram-3 mr-2 text-cyan-600"></i>TODA</h1>
        <p class="tw-page-sub">View all Tricycle Operators and Drivers Associations</p>
    </div>
    @if ($showManage)
        <a href="{{ route($routePrefix . '.todas.create') }}" class="tw-btn tw-btn-gold">
            <i class="bi bi-plus-circle"></i>Add TODA
        </a>
    @endif
</div>

<div class="tw-table-wrap">
    <div class="overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="tw-th">#</th>
                    <th class="tw-th">TODA Name</th>
                    <th class="tw-th">Area</th>
                    <th class="tw-th text-center">Drivers</th>
                    <th class="tw-th text-center">Active</th>
                    <th class="tw-th">Status</th>
                    @if ($showManage)
                        <th class="tw-th text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($todas as $toda)
                    <tr class="tw-tr-hover">
                        <td class="tw-td text-slate-500">{{ $loop->iteration + ($todas->currentPage() - 1) * $todas->perPage() }}</td>
                        <td class="tw-td">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-cyan-600 text-white"><i class="bi bi-diagram-3"></i></div>
                                <span class="cursor-pointer text-sm font-bold text-navy-600 underline underline-offset-2 hover:text-navy-700"
                                      onclick="showTodaMembers({{ $toda->id }}, @js($toda->name))">{{ $toda->name }}</span>
                            </div>
                        </td>
                        <td class="tw-td text-sm text-slate-500">{{ $toda->area ?? '—' }}</td>
                        <td class="tw-td text-center">
                            <span class="tw-badge tw-badge-navy"><i class="bi bi-people"></i>{{ $toda->operators_count }}</span>
                        </td>
                        <td class="tw-td text-center">
                            <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle"></i>{{ $toda->active_operators_count }}</span>
                        </td>
                        <td class="tw-td">
                            @if ($toda->is_active)
                                <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i>Active</span>
                            @else
                                <span class="tw-badge tw-badge-gray"><i class="bi bi-x-circle-fill"></i>Inactive</span>
                            @endif
                        </td>
                        @if ($showManage)
                            <td class="tw-td text-right">
                                <div class="inline-flex gap-1.5">
                                    <a href="{{ route($routePrefix . '.todas.edit', $toda) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route($routePrefix . '.todas.destroy', $toda) }}" method="POST" onsubmit="return confirm('Delete this TODA? Drivers must be reassigned first.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showManage ? 7 : 6 }}" class="px-4 py-10 text-center">
                            <div class="tw-empty">
                                <div class="tw-empty-icon"><i class="bi bi-diagram-3"></i></div>
                                <p class="text-sm text-slate-500">No TODA found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($todas->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $todas->links('pagination::tailwind') }}
        </div>
    @endif
</div>

@include('partials.toda-members-modal', ['membersUrl' => url('/' . $routePrefix . '/toda')])
