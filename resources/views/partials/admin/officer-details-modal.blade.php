{{--
    Reusable TFRB officer details modal. Single generic backdrop populated by
    initOfficerModals() in public/js/app.js from each row's data-officer payload.
    Uses the existing .tw-modal / data-tw-modal-close / backdrop-click pattern.
--}}
<div id="officerDetailsModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal tw-modal-sm" role="dialog" aria-modal="true" aria-labelledby="ofModalName">
        <div class="tw-modal-head">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div id="ofModalAvatar" class="tw-avatar tw-avatar-md shrink-0 bg-navy-700"></div>
                <div class="min-w-0 flex-1">
                    <div id="ofModalName" class="truncate text-base font-bold text-slate-900">TFRB Officer</div>
                    <div id="ofModalEmail" class="truncate text-xs text-slate-500">&nbsp;</div>
                </div>
                <span id="ofModalStatus" class="tw-badge tw-badge-gray shrink-0"></span>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="tw-modal-body">
            <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-telephone mr-1 text-emerald-600"></i>Phone</div>
                    <div id="ofModalPhone" class="text-sm text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-calendar-check mr-1 text-blue-600"></i>Joined</div>
                    <div id="ofModalJoined" class="text-sm font-semibold text-slate-700">—</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="tw-stat-label mb-1"><i class="bi bi-shield-check mr-1 text-navy-600"></i>Verification</div>
                    <div id="ofModalVerified" class="text-sm text-slate-700">—</div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" data-tw-modal-close>
                Close
            </button>
        </div>
    </div>
</div>
