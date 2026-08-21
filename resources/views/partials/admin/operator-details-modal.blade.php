{{--
    Reusable operator details modal. Requires: $routePrefix (unused here but kept
    for parity with the row component). Single generic backdrop populated by
    initOperatorModals() in public/js/app.js from each row's data-operator payload.
    Uses the existing .tw-modal / data-tw-modal-close / backdrop-click pattern.
--}}
<div id="operatorDetailsModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="opModalName">
        <div class="tw-modal-head">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div id="opModalAvatar" class="tw-avatar tw-avatar-md shrink-0 bg-navy-700"></div>
                <div class="min-w-0 flex-1">
                    <div id="opModalName" class="truncate text-base font-bold text-slate-900">Operator</div>
                    <div id="opModalEmail" class="truncate text-xs text-slate-500">&nbsp;</div>
                </div>
                <span id="opModalStatus" class="tw-badge tw-badge-gray shrink-0"></span>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="tw-modal-body">
            <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-diagram-3 mr-1 text-navy-500"></i>TODA</div>
                    <div id="opModalToda" class="text-sm font-semibold text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-telephone mr-1 text-emerald-600"></i>Contact</div>
                    <div id="opModalContact" class="text-sm text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-bounding-box mr-1 text-blue-600"></i>Plate #</div>
                    <div id="opModalPlate" class="text-sm font-semibold text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-grid-1x2 mr-1 text-violet-600"></i>Body #</div>
                    <div id="opModalBody" class="text-sm font-semibold text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-card-text mr-1 text-amber-600"></i>License #</div>
                    <div id="opModalLicense" class="text-sm font-semibold text-slate-700">—</div>
                </div>
                <div>
                    <div class="tw-stat-label mb-1"><i class="bi bi-palette mr-1 text-pink-600"></i>Tricycle Color</div>
                    <div id="opModalColor" class="text-sm text-slate-700">—</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="tw-stat-label mb-1"><i class="bi bi-geo-alt mr-1 text-red-500"></i>Address</div>
                    <div id="opModalAddress" class="text-sm text-slate-700">—</div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4">
            <a id="opModalEdit" href="#" class="tw-btn tw-btn-sm tw-btn-gold">
                <i class="bi bi-pencil"></i>Edit Operator
            </a>
            <a id="opModalQr" href="#" class="tw-btn tw-btn-sm tw-btn-outline">
                <i class="bi bi-qr-code"></i>View QR Code
            </a>
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost ml-auto" data-tw-modal-close>
                Close
            </button>
        </div>
    </div>
</div>
