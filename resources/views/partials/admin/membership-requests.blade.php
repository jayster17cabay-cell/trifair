{{--
    TODA Membership Requests — final superadmin/TFRB approval queue. Rows are
    operators a TODA president already approved (or added directly); approving
    activates the membership so the operator becomes rateable under that TODA.
    Requires: $membershipRequests (OperatorToda collection, status=pending_admin),
              $routePrefix (superadmin|tfrb-officer).
--}}
<div class="tw-card mb-6 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3" style="background-image: linear-gradient(135deg, #071e42 0%, #0A2A5E 100%);">
        <h3 class="tw-card-title text-sm !text-white">
            <i class="bi bi-diagram-3-fill mr-1 text-gold"></i> TODA Membership Requests
            <span class="tw-badge tw-badge-amber ml-2">{{ $membershipRequests->count() }}</span>
        </h3>
        <p class="hidden text-xs text-slate-300 sm:inline">Awaiting final approval</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Operator</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Body #</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">TODA</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Requested</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Decision</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($membershipRequests as $request)
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
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $request->toda->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ $request->created_at?->format('M d, Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route($routePrefix . '.operators.memberships.approve', $request) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="tw-btn tw-btn-sm tw-btn-gold">
                                        <i class="bi bi-check2"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route($routePrefix . '.operators.memberships.reject', $request) }}" class="flex items-center gap-1">
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
        <i class="bi bi-info-circle mr-1"></i> Approving activates the membership; rejecting closes the request.
    </div>
</div>
