{{--
    Reusable operator table row. Requires:
    - $operator     App\Models\Operator  (must be eager-loaded with user, toda)
    - $routePrefix  string               'superadmin' | 'tfrb-officer'
    Renders a compact <tr> (Operator/TODA/Contact/Status/Actions) with a
    data-operator payload consumed by initOperatorModals() in public/js/app.js.
--}}
@php
    if ($operator->isArchived()) {
        $statusLabel = 'Archived'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-archive-fill';
    } elseif ($operator->status === 'active') {
        $statusLabel = 'Active'; $statusClass = 'tw-badge-green'; $statusIcon = 'bi-check-circle-fill';
    } elseif ($operator->status === 'pending') {
        $statusLabel = 'Pending'; $statusClass = 'tw-badge-amber'; $statusIcon = 'bi-hourglass-split';
    } elseif ($operator->status === 'rejected') {
        $statusLabel = 'Rejected'; $statusClass = 'tw-badge-red'; $statusIcon = 'bi-x-circle-fill';
    } else {
        $statusLabel = 'Inactive'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-pause-circle-fill';
    }

    $operatorName = $operator->user->name ?? 'Unknown';
    $todaName = $operator->toda ? $operator->toda->name : null;

    $viewData = [
        'id' => $operator->id,
        'name' => $operatorName,
        'email' => $operator->user->email ?? '',
        'toda' => $todaName,
        'contact' => $operator->contact_number,
        'plate' => $operator->plate_number,
        'body' => $operator->body_number,
        'license' => $operator->license_number,
        'color' => $operator->motorcycle_model,
        'address' => $operator->address,
        'statusLabel' => $statusLabel,
        'statusClass' => $statusClass,
        'statusIcon' => $statusIcon,
        'editUrl' => route($routePrefix . '.operators.edit', $operator),
        'qrUrl' => route($routePrefix . '.operators.qrcode', $operator),
    ];
@endphp

<tr class="tw-tr-hover even:bg-slate-50/60">
    <td class="tw-td">
        <div class="flex items-center gap-2.5">
            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-slate-800">{{ $operatorName }}</div>
                <div class="truncate text-xs text-slate-500">{{ $operator->user->email ?? '—' }}</div>
                <div class="mt-0.5 flex items-center gap-1 text-xs text-slate-500 md:hidden">
                    <i class="bi bi-telephone"></i>{{ $operator->contact_number ?? '—' }}
                </div>
            </div>
        </div>
    </td>
    <td class="tw-td">
        @if ($todaName)
            <span class="tw-badge tw-badge-navy"><i class="bi bi-diagram-3"></i>{{ $todaName }}</span>
        @else
            <span class="tw-badge tw-badge-gray">Unassigned</span>
        @endif
    </td>
    <td class="tw-td hidden text-sm text-slate-500 md:table-cell">{{ $operator->contact_number ?? '—' }}</td>
    <td class="tw-td">
        <span class="tw-badge {{ $statusClass }}"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
    </td>
    <td class="tw-td text-right">
        <div class="inline-flex gap-1.5">
            <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" title="View details" aria-label="View {{ $operatorName }}"
                    data-operator-view='@json($viewData)'>
                <i class="bi bi-eye"></i>
            </button>
            @if (request('status') === 'pending')
                <form action="{{ route($routePrefix . '.operators.approve', $operator) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold" title="Approve" onclick="return confirm(@js('Approve ' . $operatorName . '?'))">
                        <i class="bi bi-check-lg"></i>Approve
                    </button>
                </form>
                <form action="{{ route($routePrefix . '.operators.reject', $operator) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Reject" onclick="return confirm(@js('Reject and delete ' . $operatorName . '?'))">
                        <i class="bi bi-x-lg"></i>Reject
                    </button>
                </form>
            @elseif (request('status') === 'archived')
                <form action="{{ route($routePrefix . '.operators.restore', $operator) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold" title="Restore" onclick="return confirm(@js('Restore ' . $operatorName . '?'))">
                        <i class="bi bi-arrow-counterclockwise"></i>Restore
                    </button>
                </form>
            @else
                <a href="{{ route($routePrefix . '.operators.edit', $operator) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="Edit" aria-label="Edit {{ $operatorName }}">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route($routePrefix . '.operators.archive', $operator) }}" method="POST" onsubmit="return confirm('Archive this operator? They will be hidden from active lists but keep their rating history.')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline" title="Archive" aria-label="Archive {{ $operatorName }}">
                        <i class="bi bi-archive"></i>
                    </button>
                </form>
                <form action="{{ route($routePrefix . '.operators.destroy', $operator) }}" method="POST" onsubmit="return confirm('Delete this operator? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Delete" aria-label="Delete {{ $operatorName }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    </td>
</tr>
