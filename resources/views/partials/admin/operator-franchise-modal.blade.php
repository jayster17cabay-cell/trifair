{{--
    Franchise issue/renew modal. Opened by [data-franchise-open] triggers and
    populated by initFranchiseModals() in public/js/app.js. The same form posts
    to either the issue or renew route depending on whether the operator already
    has a franchise term.
--}}
<div id="operatorFranchiseModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="franchiseModalTitle">
        <div class="tw-modal-head">
            <div class="min-w-0 flex-1">
                <div id="franchiseModalTitle" class="truncate text-base font-bold text-slate-900">Franchise</div>
                <div id="franchiseModalSubtitle" class="truncate text-xs text-slate-500">&nbsp;</div>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="franchiseForm" method="POST" action="#">
            @csrf
            <input type="hidden" name="_method" id="franchiseMethod" value="POST">
            <div class="tw-modal-body">
                <div id="franchiseRenewNotice" class="mb-4 hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <i class="bi bi-info-circle-fill mr-1"></i>
                    Renewing will close the current term and start a new one. The operator's ratings, average and complaints will reset to zero; old records stay in the All&nbsp;Time view.
                </div>
                <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="franchiseNo" class="tw-stat-label mb-1 block">Franchise No.</label>
                        <input type="text" id="franchiseNo" name="franchise_no" class="tw-input" maxlength="100" placeholder="e.g. FR-0001">
                    </div>
                    <div>
                        <label for="franchiseIssuedAt" class="tw-stat-label mb-1 block">Issued On</label>
                        <input type="date" id="franchiseIssuedAt" name="issued_at" class="tw-input">
                    </div>
                    <div>
                        <label for="franchiseValidUntil" class="tw-stat-label mb-1 block">Valid Until</label>
                        <input type="date" id="franchiseValidUntil" name="valid_until" class="tw-input">
                        <p class="mt-1 text-xs text-slate-500">Leave blank for no expiry.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4">
                <button type="submit" id="franchiseSubmit" class="tw-btn tw-btn-sm tw-btn-gold" title="Confirm" aria-label="Confirm franchise">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost ml-auto" data-tw-modal-close>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
