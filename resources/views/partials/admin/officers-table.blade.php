{{--
    Reusable TFRB officer table <tbody>. Requires:
    - $officers  LengthAwarePaginator of App\Models\User (role = tfrb_officer)
    Renders compact rows carrying a data-officer payload consumed by
    initOfficerModals() in public/js/app.js.
--}}
<tbody>
    @forelse ($officers as $officer)
        @php
            $avatarColors = ['bg-navy-700', 'bg-blue-600', 'bg-purple-600', 'bg-cyan-600', 'bg-emerald-600', 'bg-amber-600', 'bg-red-600'];
            $avBg = $avatarColors[$officer->id % count($avatarColors)];

            $verified = !empty($officer->email_verified_at);
            if ($verified) {
                $statusLabel = 'Verified'; $statusClass = 'tw-badge-green'; $statusIcon = 'bi-check-circle-fill';
            } else {
                $statusLabel = 'Unverified'; $statusClass = 'tw-badge-amber'; $statusIcon = 'bi-clock-fill';
            }

            $viewData = [
                'id' => $officer->id,
                'name' => $officer->name,
                'email' => $officer->email,
                'phone' => $officer->phone,
                'joined' => $officer->created_at?->format('M d, Y') ?? '—',
                'verified' => $verified,
                'avatarBg' => $avBg,
                'statusLabel' => $statusLabel,
                'statusClass' => $statusClass,
                'statusIcon' => $statusIcon,
            ];
        @endphp
        <tr class="tw-tr-hover even:bg-slate-50/60">
            <td class="tw-td">
                <div class="flex items-center gap-2.5">
                    <div class="tw-avatar tw-avatar-sm {{ $avBg }}">{{ strtoupper(substr($officer->name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-slate-800">{{ $officer->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $officer->email }}</div>
                        <div class="mt-0.5 flex items-center gap-1 text-xs text-slate-500 md:hidden">
                            <i class="bi bi-telephone"></i>{{ $officer->phone ?? '—' }}
                        </div>
                    </div>
                </div>
            </td>
            <td class="tw-td hidden text-sm text-slate-500 md:table-cell">{{ $officer->phone ?? '—' }}</td>
            <td class="tw-td">
                <span class="tw-badge {{ $statusClass }}"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
            </td>
            <td class="tw-td text-sm text-slate-500">{{ $officer->created_at?->format('M d, Y') ?? '—' }}</td>
            <td class="tw-td text-right">
                <div class="inline-flex gap-1.5">
                    <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" title="View details" aria-label="View {{ $officer->name }}"
                            data-officer-view='@json($viewData)'>
                        <i class="bi bi-eye"></i>
                    </button>
                    <form action="{{ route('superadmin.officers.destroy', $officer) }}" method="POST" onsubmit="return confirm('Remove this officer? They will lose all system access.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600" title="Remove Officer" aria-label="Remove {{ $officer->name }}">
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
                    <div class="tw-empty-icon"><i class="bi bi-shield"></i></div>
                    <h3 class="tw-empty-title">No Officers Found</h3>
                    <p class="text-sm text-slate-500">Create an account so a TFRB Officer can start reviewing operators.</p>
                    <a href="{{ route('superadmin.officers.create') }}" class="tw-btn tw-btn-sm tw-btn-gold mt-4">
                        <i class="bi bi-shield-plus"></i>Add Officer
                    </a>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
