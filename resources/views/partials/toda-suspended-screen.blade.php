{{--
    The entire dashboard for a TODA the account is suspended in. The member can
    still open the TODA — this is all they see. It deliberately carries no KPIs,
    ratings or member data, only the suspension itself and a way out.
    Requires: $operator (App\Models\Operator|null),
              $toda (App\Models\Toda|null — the TODA being opened).
--}}
@php
    $suspTodaId = $toda ? (int) $toda->id : null;

    // A named TODA is checked directly; the combined "All TODAs" view has no
    // TODA of its own, so it falls back to whatever live suspension exists and
    // names the TODA that actually issued it.
    $suspension = !$operator
        ? null
        : ($suspTodaId ? $operator->currentSuspensionForToda($suspTodaId) : $operator->currentSuspension);

    $suspTodaName = $toda
        ? $toda->name
        : (($suspension && $suspension->toda_id) ? optional($suspension->toda)->name : null);

    $suspAvailableTodas = collect();
    $suspUser = auth()->user();
    if ($suspUser && ($suspUser->isOperator() || $suspUser->isOperatorPresident())) {
        $suspAvailableTodas = \App\Support\TodaContext::availableTodas($suspUser);
    }
@endphp
<div class="mx-auto w-full max-w-xl py-6">
    <div class="tw-card overflow-hidden">
        <div class="px-5 py-6 text-center" style="background-image: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl text-4xl text-white shadow-md"
                 style="background-image: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="bi bi-slash-circle-fill"></i>
            </div>

            @if ($suspTodaName)
                <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.2em] text-gold-dark">
                    <i class="bi bi-building mr-1"></i>{{ $suspTodaName }}
                </p>
            @endif
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">You are suspended</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
                @if ($suspTodaName)
                    Your membership in <strong>{{ $suspTodaName }}</strong> is currently suspended, so this
                    dashboard and your ratings here are unavailable.
                @else
                    Your TODA membership is currently suspended, so this dashboard and your ratings
                    here are unavailable.
                @endif
            </p>
        </div>

        <div class="space-y-3 px-5 py-5">
            @if ($suspension && $suspension->reason)
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Reason</div>
                    <div class="mt-0.5 text-sm text-slate-700">{{ $suspension->reason }}</div>
                </div>
            @endif

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="bi bi-info-circle-fill mr-1"></i>
                @if ($suspension && $suspension->suspended_until)
                    This suspension runs until <strong>{{ $suspension->suspended_until->format('M d, Y') }}</strong>.
                @else
                    Please contact your TODA president or the TFRB Office to have this reviewed.
                @endif
            </div>

            <p class="text-center text-xs text-slate-400">
                <i class="bi bi-shield-check mr-1"></i>
                Contact your TODA president or the TFRB Office for any question about this suspension.
            </p>

            @if ($suspAvailableTodas->count() > 1)
                <a href="{{ route('toda.select') }}" class="tw-btn tw-btn-outline w-full">
                    <i class="bi bi-arrow-left-right mr-1"></i> Change TODA
                </a>
            @endif
        </div>
    </div>
</div>
