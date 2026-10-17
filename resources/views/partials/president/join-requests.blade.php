{{--
    Join Requests — self-service requests from operators wanting to join THIS
    president's TODA. Approving forwards the membership to the superadmin/TFRB
    for final approval; rejecting closes it.
    Requires: $joinRequests (OperatorToda collection, all status=pending).
--}}
<div class="tw-card mb-4 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <h3 class="tw-card-title text-sm !text-white">
            <i class="bi bi-join mr-1 text-gold"></i> Join Requests
            <span class="tw-badge tw-badge-amber ml-2">{{ $joinRequests->count() }}</span>
        </h3>
        <p class="hidden text-xs text-slate-300 sm:inline">Operators asking to join your TODA</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Operator</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Body #</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Plate #</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Requested</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Decision</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($joinRequests as $request)
                    @php $op = $request->operator; @endphp
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="tw-avatar tw-avatar-sm bg-navy-700 text-white">{{ strtoupper(substr($op->user->name ?? '?', 0, 1)) }}</div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-800">{{ $op->user->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-400">{{ $op->user->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $op->body_number ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $op->plate_number ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $request->created_at?->format('M d, Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('president.memberships.approve', $request) }}" onsubmit="return confirm('Approve this join request? It will then need final approval by superadmin/TFRB.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                                        <i class="bi bi-check2"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('president.memberships.reject', $request) }}" class="flex items-center gap-1" onsubmit="return confirm('Reject this join request?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="note" maxlength="500" placeholder="Reason (optional)" aria-label="Rejection reason"
                                           class="w-36 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700 placeholder-slate-400 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold">
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-outline text-red-600 border-red-200 hover:bg-red-50">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-3 text-xs text-slate-500">
        <i class="bi bi-info-circle mr-1"></i> Approving sends the request to superadmin/TFRB for final approval before the operator becomes an active member.
    </div>
</div>
