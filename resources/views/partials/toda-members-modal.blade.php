{{--
    Shared TODA members modal. Requires:
    - $membersUrl  base URL for the members AJAX (e.g. url('/superadmin/toda'))
    - $addMemberUrl  URL of the operator create flow (e.g. route('superadmin.operators.create'))
    Server-renders member rows via partials.toda-member-list (XSS-safe).
--}}

<div id="todaModal" class="tw-modal-backdrop hidden"
     data-members-url="{{ rtrim($membersUrl, '/') }}">
    <div class="tw-modal tw-modal-sm tw-expand-panel" role="dialog" aria-modal="true" aria-labelledby="todaModalTitle">
        <div class="tw-modal-head">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-navy-600 text-white">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div id="todaModalTitle" class="truncate text-base font-bold text-slate-900">TODA</div>
                    <div id="todaModalCount" class="text-xs text-slate-500">Loading members…</div>
                </div>
            </div>
            <button type="button" class="tw-modal-close" data-tw-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="tw-modal-body">
            <div id="todaMembersList" class="max-h-[260px] overflow-y-auto pr-1">
                <div class="py-8 text-center text-slate-400">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-navy-600"></div>
                    <div class="mt-2 text-sm">Loading members...</div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3">
            <span class="text-xs text-slate-400">Click outside to close</span>
            <button type="button" class="tw-btn tw-btn-sm tw-btn-ghost" data-tw-modal-close>Close</button>
        </div>
    </div>
</div>

<script>
    function showTodaMembers(todaId, todaName) {
        var modal = document.getElementById('todaModal');
        if (!modal) return;
        var title = document.getElementById('todaModalTitle');
        var count = document.getElementById('todaModalCount');
        var list = document.getElementById('todaMembersList');

        if (title) title.textContent = todaName;
        if (count) count.textContent = 'Loading members…';
        if (list) {
            list.innerHTML = '<div class="py-8 text-center text-slate-400">' +
                '<div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-navy-600"></div>' +
                '<div class="mt-2 text-sm">Loading members...</div></div>';
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        var base = modal.getAttribute('data-members-url') || '';
        fetch(base + '/' + encodeURIComponent(todaId) + '/members', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) {
                if (!r.ok) throw new Error('failed');
                return r.json();
            })
            .then(function (data) {
                if (list) list.innerHTML = data.html || '';
                if (count) count.textContent = data.count === 1 ? '1 member' : (data.count || 0) + ' members';
            })
            .catch(function () {
                if (list) {
                    list.innerHTML = '<div class="py-8 text-center text-red-600">' +
                        '<i class="bi bi-exclamation-circle text-2xl"></i>' +
                        '<div class="mt-2 text-sm">Failed to load members</div></div>';
                }
                if (count) count.textContent = 'Failed to load';
            });
    }

    function closeTodaModal() {
        var modal = document.getElementById('todaModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-member-more]');
        if (!btn) return;
        var item = btn.closest('[data-member-item]');
        if (!item) return;
        var actions = item.querySelector('[data-member-actions]');
        if (!actions) return;
        var opening = actions.classList.contains('hidden');
        actions.classList.toggle('hidden', !opening);
        btn.setAttribute('aria-expanded', String(opening));
    });
</script>
