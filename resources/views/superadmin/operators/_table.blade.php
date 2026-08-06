<tbody>
    @forelse ($operators as $operator)
        <tr class="tw-tr-hover">
            <td class="tw-td text-slate-500">{{ $loop->iteration + ($operators->currentPage() - 1) * $operators->perPage() }}</td>
            <td class="tw-td">
                <div class="flex items-center gap-2.5">
                    <div class="tw-avatar tw-avatar-sm bg-amber-50 text-amber-700">{{ strtoupper(substr($operator->user->name, 0, 1)) }}</div>
                    <strong class="text-sm">{{ $operator->user->name }}</strong>
                </div>
            </td>
            <td class="tw-td">
                @if ($operator->toda)
                    <span class="tw-badge tw-badge-navy"><i class="bi bi-diagram-3"></i>{{ $operator->toda->name }}</span>
                @else
                    <span class="text-xs text-slate-400">Unassigned</span>
                @endif
            </td>
            <td class="tw-td text-sm text-slate-500">{{ $operator->user->email }}</td>
            <td class="tw-td text-sm font-semibold">{{ $operator->plate_number ?? 'N/A' }}</td>
            <td class="tw-td text-sm">{{ $operator->body_number ?? 'N/A' }}</td>
            <td class="tw-td text-sm">{{ $operator->contact_number ?? 'N/A' }}</td>
            <td class="tw-td">
                @if ($operator->status === 'active')
                    <span class="tw-badge tw-badge-navy"><i class="bi bi-check-circle"></i>Active</span>
                @elseif ($operator->status === 'pending')
                    <span class="tw-badge tw-badge-amber"><i class="bi bi-hourglass-split"></i>Pending</span>
                @elseif ($operator->status === 'rejected')
                    <span class="tw-badge tw-badge-red"><i class="bi bi-x-circle"></i>Rejected</span>
                @else
                    <span class="tw-badge tw-badge-gray"><i class="bi bi-pause-circle"></i>Inactive</span>
                @endif
            </td>
            <td class="tw-td text-center">
                <a href="{{ route('superadmin.operators.qrcode', $operator) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="View QR Code">
                    <i class="bi bi-qr-code"></i>
                </a>
            </td>
            <td class="tw-td text-right">
                <div class="inline-flex gap-1.5">
                    @if (request('status') === 'pending')
                        <form action="{{ route('superadmin.operators.approve', $operator) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-success" title="Approve" onclick="return confirm(@js('Approve ' . $operator->user->name . '?'))">
                                <i class="bi bi-check-lg"></i>Approve
                            </button>
                        </form>
                        <form action="{{ route('superadmin.operators.reject', $operator) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600" title="Reject" onclick="return confirm(@js('Reject and delete ' . $operator->user->name . '?'))">
                                <i class="bi bi-x-lg"></i>Reject
                            </button>
                        </form>
                    @else
                        <a href="{{ route('superadmin.operators.edit', $operator) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('superadmin.operators.destroy', $operator) }}" method="POST" onsubmit="return confirm('Delete this operator? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="px-4 py-10 text-center">
                <div class="tw-empty">
                    <div class="tw-empty-icon"><i class="bi bi-inbox"></i></div>
                    <p class="text-sm text-slate-500">No operators found.</p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
