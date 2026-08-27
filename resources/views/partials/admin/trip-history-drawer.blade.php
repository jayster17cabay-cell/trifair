{{--
    Reusable right-side sliding drawer. On the reports page it shows an
    operator's trip history. Rows that should open this drawer must be
    clickable elements carrying:
    - data-open-trips
    - data-trips-url   (AJAX endpoint returning { html: ... })
    - data-name, data-plate, data-count, data-avg
    The drawer is populated entirely from those attributes by
    initTripHistoryDrawer() in public/js/app.js, so other pages can reuse it
    (e.g. an operator's trip history from the Operators page).
--}}
<div class="tw-drawer-overlay" data-trip-drawer-overlay></div>
<aside class="tw-drawer" data-trip-drawer role="dialog" aria-modal="true" aria-labelledby="tripDrawerTitle" aria-hidden="true">
    <div class="tw-drawer-head">
        <div class="flex min-w-0 items-center gap-3">
            <div class="min-w-0">
                <div id="tripDrawerTitle" class="truncate text-sm font-bold text-slate-800" data-trip-drawer-name>Operator</div>
                <div class="mt-0.5 truncate text-xs text-slate-500" data-trip-drawer-subtitle></div>
            </div>
        </div>
        <button type="button" class="tw-drawer-close" data-trip-drawer-close aria-label="Close trip history">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="tw-drawer-body" data-trip-drawer-list>
        <div class="flex items-center justify-center gap-2 py-10 text-xs text-slate-500">
            <div class="tw-loading-spinner"></div> Loading trips...
        </div>
    </div>
</aside>
