{{-- Shared TODA members modal (XSS-safe). Requires: $membersUrl (e.g. url('/superadmin/toda')) --}}

<div id="todaModal" class="tw-modal-backdrop hidden" onclick="if(event.target===this)closeTodaModal()">
    <div class="tw-modal">
        <div class="tw-modal-head">
            <h4 id="todaModalTitle" class="text-base font-bold text-slate-900">Members</h4>
            <button type="button" class="tw-modal-close" onclick="closeTodaModal()" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="tw-modal-body max-h-[60vh] overflow-y-auto" id="todaModalBody">
            <div class="py-8 text-center text-slate-500">
                <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-navy-600"></div>
                <div class="mt-2 text-sm">Loading members...</div>
            </div>
        </div>
    </div>
</div>

<script>
function esc(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

function showTodaMembers(todaId, todaName) {
    var modal = document.getElementById('todaModal');
    var title = document.getElementById('todaModalTitle');
    var body = document.getElementById('todaModalBody');
    title.textContent = todaName + ' \u2014 Members';
    body.innerHTML = '<div class="py-8 text-center text-slate-400"><div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-slate-300 border-t-navy-600"></div><div class="mt-2 text-sm">Loading members...</div></div>';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    var url = '{{ $membersUrl }}'.replace(/\/+$/, '') + '/' + encodeURIComponent(todaId) + '/members';
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.members || data.members.length === 0) {
                body.innerHTML = '<div class="py-8 text-center text-slate-400"><i class="bi bi-people text-2xl"></i><div class="mt-2 text-sm">No members yet</div></div>';
                return;
            }
            var html = '';
            data.members.forEach(function(m, i) {
                var initial = esc((m.name || '').charAt(0).toUpperCase());
                var statusBadge = m.status === 'active'
                    ? '<span class="tw-badge tw-badge-green"><i class="bi bi-check-circle-fill"></i> Active</span>'
                    : '<span class="tw-badge tw-badge-amber"><i class="bi bi-pause-circle-fill"></i> Inactive</span>';
                var metaHtml = '';
                if (m.plate_number) metaHtml += '<div class="text-xs">Plate: ' + esc(m.plate_number) + '</div>';
                if (m.body_number) metaHtml += '<div class="text-xs text-slate-400">Body: #' + esc(m.body_number) + '</div>';
                html += '<div class="flex items-center gap-3 py-2.5"' + (i > 0 ? ' style="border-top: 1px solid #f1f5f9;"' : '') + '>' +
                    '<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-navy-600 to-navy-500 text-sm font-extrabold text-white">' + initial + '</div>' +
                    '<div class="min-w-0 flex-1">' +
                        '<div class="text-sm font-bold text-slate-800">' + esc(m.name) + '</div>' +
                        (metaHtml ? '<div class="text-[0.72rem] text-slate-600">' + metaHtml + '</div>' : '') +
                    '</div>' +
                    '<div class="shrink-0">' + statusBadge + '</div>' +
                '</div>';
            });
            body.innerHTML = html;
        })
        .catch(function() {
            body.innerHTML = '<div class="py-8 text-center text-red-600"><i class="bi bi-exclamation-circle text-2xl"></i><div class="mt-2 text-sm">Failed to load members</div></div>';
        });
}

function closeTodaModal() {
    var el = document.getElementById('todaModal');
    if (el) { el.classList.add('hidden'); document.body.style.overflow = ''; }
}
</script>
