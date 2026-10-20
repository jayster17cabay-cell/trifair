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
    } elseif ($operator->status === 'suspended') {
        $statusLabel = 'Suspended'; $statusClass = 'tw-badge-amber'; $statusIcon = 'bi-slash-circle-fill';
    } else {
        $statusLabel = 'Inactive'; $statusClass = 'tw-badge-gray'; $statusIcon = 'bi-pause-circle-fill';
    }

    $accountActive = (bool) ($operator->user->is_active ?? true);
    // A TODA president is still an operator (they keep their Operator row), so
    // they appear here by name too. They are managed via the Presidents section.
    $isPresident = ($operator->user->role ?? null) === 'operator_president';

    $operatorName = $operator->user->name ?? 'Unknown';
    $todaName = $operator->toda ? $operator->toda->name : null;

    // Every TODA this operator belongs to: the home TODA plus each active
    // membership (a driver may belong to up to three TODAs). De-duplicated and
    // sorted so the TODA column can show them all and the assign-as-president
    // action can offer the right choices.
    $operatorTodaList = collect();
    if ($operator->toda) {
        $operatorTodaList->push($operator->toda);
    }
    foreach ($operator->todas as $membershipToda) {
        if (($membershipToda->pivot->status ?? null) === \App\Models\OperatorToda::STATUS_ACTIVE
            && !$operatorTodaList->contains('id', $membershipToda->id)) {
            $operatorTodaList->push($membershipToda);
        }
    }
    $operatorTodaList = $operatorTodaList->sortBy('name')->values();

    // TODAs this operator already presides over, and those still free to take
    // the presidency (no president yet). When every TODA is already presided
    // over by this operator there is nothing to assign.
    $presidedTodas = $operatorTodaList->filter(fn ($t) => $t->president && (int) optional($t->president)->operator_id === (int) $operator->id);
    $assignableTodas = $operatorTodaList->filter(fn ($t) => !$t->president)->values();
    $allTodasPresided = $operatorTodaList->isNotEmpty() && $presidedTodas->count() === $operatorTodaList->count();

    $currentTerm = $operator->currentTerm;
    $franchiseNo = $currentTerm->franchise_no ?? null;
    $franchiseStatus = $operator->franchiseStatus();
    $franchiseExpiringSoon = $operator->isFranchiseExpiringSoon();
    $canIssueFranchise = !$operator->isArchived() && in_array($operator->status, ['active', 'inactive'], true);

    // Live (non-lapsed) TODA suspension, if the president has issued one.
    $suspension = $operator->currentSuspension;
    $isSuspended = $operator->status === 'suspended' && $suspension !== null && !$suspension->isExpired();

    // Superadmin/TFRB officers may suspend (and lift) any operator — including a
    // TODA president, who cannot suspend themselves.
    $canSuspend = !$operator->isArchived() && !in_array($operator->status, ['pending', 'rejected'], true);

    $viewData = [
        'id' => $operator->id,
        'name' => $operatorName,
        'email' => $operator->user->email ?? '',
        'toda' => $todaName,
        'contact' => $operator->contact_number,
        'plate' => $operator->plate_number,
        'body' => $operator->body_number,
        'color' => $operator->motorcycle_model,
        'address' => $operator->address,
        'statusLabel' => $statusLabel,
        'statusClass' => $statusClass,
        'statusIcon' => $statusIcon,
        'accountActive' => $accountActive,
        'isSuspended' => $isSuspended,
        'canSuspend' => $canSuspend,
        'suspendUrl' => route($routePrefix . '.operators.suspend', $operator),
        'endSuspensionUrl' => route($routePrefix . '.operators.suspend.end', $operator),
        'suspensionReason' => $isSuspended ? $suspension->reason : null,
        'suspensionFrom' => $isSuspended ? $suspension->suspended_at->format('M d, Y') : null,
        'suspensionUntil' => $isSuspended ? $suspension->suspended_until->format('M d, Y') : null,
        'suspensionBy' => ($isSuspended && $suspension->suspender) ? $suspension->suspender->name : null,
        'todas' => $operatorTodaList->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
        'editUrl' => route($routePrefix . '.operators.edit', $operator),
        'qrUrl' => route($routePrefix . '.operators.qrcode', $operator),
        'franchiseNo' => $franchiseNo,
        'franchiseIssued' => optional($currentTerm ? $currentTerm->issued_at : null)->format('M d, Y'),
        'franchiseValidUntil' => ($currentTerm && $currentTerm->valid_until) ? $currentTerm->valid_until->format('M d, Y') : null,
        'franchiseIssuedRaw' => ($currentTerm && $currentTerm->issued_at) ? $currentTerm->issued_at->toDateString() : null,
        'franchiseValidUntilRaw' => ($currentTerm && $currentTerm->valid_until) ? $currentTerm->valid_until->toDateString() : null,
        'franchiseStatus' => $franchiseStatus,
        'franchiseExpiringSoon' => $franchiseExpiringSoon,
        'hasTerm' => (bool) $currentTerm,
        'canIssueFranchise' => $canIssueFranchise,
        'issueUrl' => route($routePrefix . '.operators.franchise.issue', $operator),
        'updateUrl' => route($routePrefix . '.operators.franchise.update', $operator),
        'renewUrl' => route($routePrefix . '.operators.franchise.renew', $operator),
    ];
@endphp

<tr class="tw-tr-hover even:bg-slate-50/60 {{ $accountActive ? '' : 'opacity-60' }}">
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
        <div class="space-y-1">
            @forelse ($operatorTodaList as $todaEntry)
                @php
                    $entryPresident = $todaEntry->president;
                    $entryIsThisOperator = $entryPresident && (int) optional($entryPresident)->operator_id === (int) $operator->id;
                @endphp
                <span class="tw-badge tw-badge-navy">
                    <i class="bi bi-diagram-3"></i>{{ $todaEntry->name }}
                    @if ($entryIsThisOperator)
                        <i class="bi bi-award-fill text-gold" title="President of this TODA"></i>
                    @endif
                </span>
            @empty
                <span class="tw-badge tw-badge-gray">Unassigned</span>
            @endforelse
        </div>
    </td>
    <td class="tw-td hidden text-sm text-slate-500 md:table-cell">{{ $operator->contact_number ?? '—' }}</td>
    <td class="tw-td hidden lg:table-cell">
        @if ($franchiseNo)
            <div class="space-y-1">
                <div class="font-mono text-xs font-semibold text-slate-700">{{ $franchiseNo }}</div>
                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                    @if ($franchiseStatus === 'expired')
                        <span class="tw-badge tw-badge-red"><i class="bi bi-exclamation-triangle-fill"></i>Expired</span>
                    @elseif ($franchiseExpiringSoon)
                        <span class="tw-badge tw-badge-amber"><i class="bi bi-exclamation-triangle-fill"></i>Expiring Soon</span>
                    @else
                        <span class="tw-badge tw-badge-green"><i class="bi bi-patch-check-fill"></i>Valid</span>
                    @endif
                    @if ($currentTerm && $currentTerm->issued_at)
                        <span class="text-[11px] font-medium text-slate-500">from {{ $currentTerm->issued_at->format('M d, Y') }}</span>
                    @endif
                    @if ($currentTerm && $currentTerm->valid_until)
                        <span class="text-[11px] font-medium text-slate-500">until {{ $currentTerm->valid_until->format('M d, Y') }}</span>
                    @else
                        <span class="text-[11px] font-medium text-slate-400">no expiry</span>
                    @endif
                </div>
            </div>
        @else
            <span class="tw-badge tw-badge-gray"><i class="bi bi-dash-circle"></i>None</span>
        @endif
    </td>
    <td class="tw-td">
        <div class="flex flex-wrap items-center gap-1">
            <span class="tw-badge {{ $statusClass }}"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
            @if ($isPresident)
                <span class="tw-badge tw-badge-navy" title="TODA President — also an operator"><i class="bi bi-award-fill"></i>President</span>
            @endif
        </div>
    </td>
    <td class="tw-td">
        <div class="flex items-center justify-end gap-1.5">
            {{-- View details --}}
            <button type="button" class="tw-btn tw-btn-sm tw-btn-outline-navy"
                    title="View details" aria-label="View details of {{ $operatorName }}"
                    data-operator-view='@json($viewData)'>
                <i class="bi bi-eye"></i>
            </button>

            {{-- Franchise (issue / edit) --}}
            @if ($canIssueFranchise)
                <button type="button" class="tw-btn tw-btn-sm tw-btn-outline-gold"
                        title="{{ $currentTerm ? 'Edit franchise' : 'Issue franchise' }}"
                        aria-label="{{ $currentTerm ? 'Edit franchise for ' : 'Issue franchise for ' }}{{ $operatorName }}"
                        data-franchise-open='@json($viewData)'
                        data-franchise-mode="{{ $currentTerm ? 'edit' : 'issue' }}">
                    <i class="bi {{ $currentTerm ? 'bi-pencil-square' : 'bi-patch-check' }}"></i>
                </button>
            @endif

            {{-- More actions --}}
            <div class="relative inline-block text-left">
                <button type="button"
                        class="tw-btn tw-btn-sm tw-btn-outline"
                        title="More actions" aria-label="More actions for {{ $operatorName }}"
                        aria-haspopup="true" aria-expanded="false"
                        data-tw-dropdown="operatorActions{{ $operator->id }}">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <div id="operatorActions{{ $operator->id }}" data-tw-dropdown-menu
                     class="tw-dropdown right-0 top-full z-50 mt-1 min-w-[13rem] py-1.5">
                    @if ($operator->isArchived())
                        <div class="tw-dropdown-label">Archived</div>
                        <form action="{{ route($routePrefix . '.operators.restore', $operator) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-dropdown-item" onclick="return confirm(@js('Restore ' . $operatorName . '?'))">
                                <i class="bi bi-arrow-counterclockwise"></i>Restore operator
                            </button>
                        </form>
                    @elseif (in_array($operator->status, ['pending', 'rejected'], true))
                        <div class="tw-dropdown-label">Review registration</div>
                        <form action="{{ route($routePrefix . '.operators.approve', $operator) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-dropdown-item" onclick="return confirm(@js('Approve ' . $operatorName . '?'))">
                                <i class="bi bi-check-circle"></i>Approve
                            </button>
                        </form>
                        <form action="{{ route($routePrefix . '.operators.reject', $operator) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-dropdown-item tw-dropdown-danger" onclick="return confirm(@js('Reject and remove ' . $operatorName . '?'))">
                                <i class="bi bi-x-circle"></i>Reject
                            </button>
                        </form>
                    @else
                        @php
                            $suspendPayload = ['name' => $operatorName, 'toda' => $todaName, 'suspendUrl' => route($routePrefix . '.operators.suspend', $operator)];
                        @endphp

                        <div class="tw-dropdown-label">Manage</div>
                        <a href="{{ route($routePrefix . '.operators.edit', $operator) }}" class="tw-dropdown-item">
                            <i class="bi bi-pencil"></i>Edit details
                        </a>
                        @unless ($allTodasPresided)
                            @if ($assignableTodas->isEmpty())
                                @php
                                    $blockingPresident = $operatorTodaList->first(fn ($t) => $t->president && (int) optional($t->president)->operator_id !== (int) $operator->id);
                                    $blockingName = $blockingPresident ? optional(optional(optional($blockingPresident->president)->operator)->user)->name : null;
                                    $assignPrompt = $blockingName
                                        ? 'This TODA already has a president (' . $blockingName . '). Remove them as President first before assigning a new one.'
                                        : 'This operator has no TODA available to preside over.';
                                @endphp
                                <button type="button" class="tw-dropdown-item" title="{{ $assignPrompt }}" aria-label="President already assigned" disabled style="opacity:.45;cursor:not-allowed">
                                    <i class="bi bi-award"></i>Assign as President
                                </button>
                            @elseif ($operatorTodaList->count() === 1)
                                <form action="{{ route($routePrefix . '.operators.assignPresident', $operator) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="toda_id" value="{{ $assignableTodas->first()->id }}">
                                    <button type="submit" class="tw-dropdown-item" onclick="return confirm(@js('Assign ' . $operatorName . ' as President of ' . $assignableTodas->first()->name . '? They will oversee all members of that TODA.'))">
                                        <i class="bi bi-award"></i>Assign as President
                                    </button>
                                </form>
                            @else
                                <div class="tw-dropdown-label">Assign as President of…</div>
                                @foreach ($assignableTodas as $assignToda)
                                    <form action="{{ route($routePrefix . '.operators.assignPresident', $operator) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="toda_id" value="{{ $assignToda->id }}">
                                        <button type="submit" class="tw-dropdown-item" onclick="return confirm(@js('Assign ' . $operatorName . ' as President of ' . $assignToda->name . '? They will oversee all members of that TODA.'))">
                                            <i class="bi bi-award"></i>{{ $assignToda->name }}
                                        </button>
                                    </form>
                                @endforeach
                            @endif
                        @endunless

                        <div class="tw-dropdown-divider"></div>
                        <div class="tw-dropdown-label">Account</div>
                        <form action="{{ route($routePrefix . '.operators.resetPassword', $operator) }}" method="POST" onsubmit="return confirm(@js('Reset the password of ' . $operatorName . '? A temporary password will be e-mailed to them.'))">
                            @csrf
                            <button type="submit" class="tw-dropdown-item">
                                <i class="bi bi-key"></i>Reset password
                            </button>
                        </form>
                        @if ($operator->status === 'suspended')
                            <form action="{{ route($routePrefix . '.operators.suspend.end', $operator) }}" method="POST" onsubmit="return confirm(@js('End ' . $operatorName . '\'s suspension early?'))">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="tw-dropdown-item">
                                    <i class="bi bi-play-circle"></i>End suspension
                                </button>
                            </form>
                        @elseif ($canSuspend)
                            <button type="button" class="tw-dropdown-item tw-dropdown-danger"
                                    data-suspend-open='@json($suspendPayload)'>
                                <i class="bi bi-slash-circle"></i>Suspend account
                            </button>
                        @endif

                        <div class="tw-dropdown-divider"></div>
                        <form action="{{ route($routePrefix . '.operators.archive', $operator) }}" method="POST" onsubmit="return confirm(@js('Archive ' . $operatorName . '? They will be hidden from active lists but keep their rating history.'))">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tw-dropdown-item tw-dropdown-danger">
                                <i class="bi bi-archive"></i>Archive
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </td>
</tr>
