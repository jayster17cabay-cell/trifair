{{--
    Status screen shown when an operator/president's franchise has lapsed.
    Requires: $operator (App\Models\Operator|null),
              $toda (App\Models\Toda|null).
--}}
<div class="mx-auto w-full max-w-xl py-6">
    <div class="tw-card overflow-hidden">
        <div class="px-5 py-6 text-center" style="background-image: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl text-4xl text-white shadow-md"
                 style="background-image: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                <i class="bi bi-calendar-x-fill"></i>
            </div>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Your franchise has expired</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
                Your franchise has expired. Please contact the TFRB Office to renew it.
            </p>
        </div>

        <div class="space-y-3 px-5 py-5">
            @if ($operator && $operator->currentTerm && $operator->currentTerm->valid_until)
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Franchise</div>
                    <div class="mt-0.5 text-sm text-slate-700">
                        {{ $operator->currentTerm->franchise_no ?? 'No number' }}
                        &middot; expired {{ $operator->currentTerm->valid_until->format('M d, Y') }}
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="bi bi-info-circle-fill mr-1"></i>
                Please contact the TFRB Office to have your franchise renewed. You will be able to access your dashboard again once it is renewed.
            </div>

            <p class="text-center text-xs text-slate-400">
                <i class="bi bi-shield-check mr-1"></i>
                Contact the TFRB Office for any question about your franchise.
            </p>
        </div>
    </div>
</div>