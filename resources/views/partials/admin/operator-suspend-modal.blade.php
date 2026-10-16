{{--
    Operator suspension modal. Opened by [data-suspend-open] triggers (from the
    operator details modal footer) and populated by initSuspendModals() in
    public/js/app.js. Posts to the operator suspend route for the active surface
    (superadmin or tfrb-officer).
--}}
<div id="operatorSuspendModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="suspendModalTitle">
        <div class="tw-modal-head">
            <div class="min-w-0 flex-1">
                <div id="suspendModalTitle" class="truncate text-base font-bold text-slate-900">{{ $suspendTitle ?? 'Suspend Operator' }}</div>
                <div id="suspendModalSubtitle" class="truncate text-xs text-slate-500">&nbsp;</div>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="suspendForm" method="POST" action="#">
            @csrf
            <div class="tw-modal-body">
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <i class="bi bi-exclamation-triangle-fill mr-1"></i>
                    Suspending blocks this account from signing in and from being rated until the end date.
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="suspendFormReason" class="tw-stat-label mb-1 block">Resolution / Reason</label>
                        <textarea id="suspendFormReason" name="reason" rows="3" required maxlength="1000"
                                  class="tw-input" placeholder="State the resolution or reason for the suspension…"></textarea>
                    </div>
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <label for="suspendFormStart" class="tw-stat-label mb-1 block">Start date</label>
                        <input type="date" id="suspendFormStart" name="suspended_at" class="tw-input" required value="{{ now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="suspendFormEnd" class="tw-stat-label mb-1 block">End date</label>
                        <input type="date" id="suspendFormEnd" name="suspended_until" class="tw-input" required value="{{ now()->addDays(7)->toDateString() }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4">
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-danger" style="background-color:#dc2626;color:#fff;">
                    <i class="bi bi-slash-circle"></i>Suspend
                </button>
                <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost ml-auto" data-tw-modal-close>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
