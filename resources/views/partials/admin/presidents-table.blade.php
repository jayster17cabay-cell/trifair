{{--
    TODA President table <tbody>. Requires:
    - $presidents  LengthAwarePaginator of App\Models\User (role = operator_president) with 'toda'.
--}}
<tbody>
    @forelse ($presidents as $president)
        @php
            $avatarColors = ['bg-navy-700', 'bg-purple-600', 'bg-cyan-600', 'bg-emerald-600', 'bg-amber-600', 'bg-red-600'];
            $avBg = $avatarColors[$president->id % count($avatarColors)];

            $verified = !empty($president->email_verified_at);
            $toda = $president->toda;
            $hasToda = $toda ? true : false;
        @endphp
        <tr class="tw-tr-hover even:bg-slate-50/60">
            <td class="tw-td">
                <div class="flex items-center gap-2.5">
                    <div class="tw-avatar tw-avatar-sm {{ $avBg }}">{{ strtoupper(substr($president->name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-slate-800">{{ $president->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $president->email }}</div>
                    </div>
                </div>
            </td>
            <td class="tw-td">
                @if ($hasToda)
                    <span class="tw-badge tw-badge-navy"><i class="bi bi-diagram-3 mr-1"></i>{{ $toda->name }}</span>
                @else
                    <span class="tw-badge tw-badge-amber"><i class="bi bi-exclamation-triangle mr-1"></i>No TODA</span>
                @endif
            </td>
            <td class="tw-td">
                @if ($president->is_active)
                    <span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill mr-1"></i>{{ $verified ? 'Active' : 'Active' }}</span>
                @else
                    <span class="tw-badge tw-badge-gray">Disabled</span>
                @endif
            </td>
            <td class="tw-td hidden text-sm text-slate-500 md:table-cell">{{ $president->created_at?->format('M d, Y') ?? '—' }}</td>
            <td class="tw-td text-right">
                <div class="inline-flex gap-1.5">
                    @if ($hasToda)
                        <a href="{{ route('superadmin.todas.members', $toda->id) }}" class="tw-btn tw-btn-sm tw-btn-outline" title="View members" aria-label="View {{ $toda->name }} members">
                            <i class="bi bi-people"></i>
                        </a>
                    @endif
                    <form action="{{ route('superadmin.presidents.destroy', $president) }}" method="POST" onsubmit="return confirm('Remove this TODA President? They will lose all system access.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline-danger" title="Remove President" aria-label="Remove {{ $president->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-4 py-10 text-center">
                <div class="tw-empty">
                    <div class="tw-empty-icon"><i class="bi bi-award"></i></div>
                    <h3 class="tw-empty-title">No Presidents Found</h3>
                    <p class="text-sm text-slate-500">Assign a president to a TODA so they can oversee its members.</p>
                    <a href="{{ route('superadmin.presidents.create') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-4">
                        <i class="bi bi-award"></i>Add President
                    </a>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
