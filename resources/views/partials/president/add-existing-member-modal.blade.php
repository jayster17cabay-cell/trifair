{{--
    Add Existing Operator modal (president). Searches already-registered
    operators and adds the SAME account to the president's TODA — no duplicate
    account is created. The membership lands as pending for superadmin/TFRB
    final approval. Opened by [data-tw-modal-open="presidentAddExistingModal"].
--}}
<div id="presidentAddExistingModal" class="tw-modal-backdrop hidden">
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="addExistingModalTitle">
        <div class="tw-modal-head">
            <div class="min-w-0 flex-1">
                <div id="addExistingModalTitle" class="truncate text-base font-bold text-slate-900">Add Existing Operator</div>
                <div class="truncate text-xs text-slate-500">Search a registered operator — same account, same body number. Final approval by superadmin/TFRB.</div>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="tw-modal-body">
            <div class="tw-input-group mb-3">
                <span class="tw-input-group-icon"><i class="bi bi-search"></i></span>
                <input type="search" id="existingOperatorSearch" class="tw-input" placeholder="Search name, body number, plate, or email…" aria-label="Search existing operators">
            </div>
            <div id="existingOperatorResults" class="rounded-lg border border-slate-100">
                <div class="px-4 py-8 text-center text-sm text-slate-400">Start typing to search operators.</div>
            </div>
        </div>

        <div class="flex items-center border-t border-slate-100 px-6 py-4">
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost ml-auto" data-tw-modal-close>Close</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('existingOperatorSearch');
        var results = document.getElementById('existingOperatorResults');
        if (!input || !results) return;

        var url = @json(route('president.operators.search'));
        var timer = null;
        var current = null;

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 1) {
                results.innerHTML = '<div class="px-4 py-8 text-center text-sm text-slate-400">Start typing to search operators.</div>';
                return;
            }
            timer = setTimeout(function () {
                if (current) { current.abort(); }
                current = new AbortController();
                fetch(url + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: current.signal
                })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { results.innerHTML = d.html; })
                    .catch(function () { /* aborted or network error */ });
            }, 300);
        });
    })();
</script>
