<div id="complaintResolutionModal" class="tw-modal-backdrop hidden" data-complaint-resolution-modal>
    <div class="tw-modal" role="dialog" aria-modal="true" aria-labelledby="complaintResolutionTitle">
        <div class="tw-modal-head">
            <h3 id="complaintResolutionTitle" class="text-base font-bold text-slate-800" data-resolution-title>Resolve Complaint</h3>
            <button type="button" class="tw-modal-close" data-resolution-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="complaintResolutionForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="tw-modal-body">
                <p class="mb-3 text-sm leading-relaxed text-slate-500" data-resolution-help></p>
                <label class="tw-label" for="resolutionNote">Resolution details</label>
                <textarea id="resolutionNote" name="resolution_note" rows="4" required maxlength="2000" class="tw-textarea" placeholder="Describe the resolution..."></textarea>
                <div class="mt-3 hidden" data-resolution-proof-wrap>
                    <label class="tw-label" for="resolutionProofs">Resolution proof (optional)</label>
                    <input type="file" id="resolutionProofs" name="resolution_proofs[]" multiple accept="image/*,video/*,.pdf,.doc,.docx" class="tw-input">
                    <p class="mt-1 text-xs text-slate-400">Attach evidence that the complaint was resolved (up to 5 files).</p>
                </div>
                <div class="mt-3 hidden" data-resolution-checklist-wrap>
                    <label class="tw-label">Panel follow-up checklist (optional)</label>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="grid gap-1 sm:grid-cols-2">
                            @foreach (\App\Models\Rating::PANEL_ESCALATION_CHECKLIST as $checklistItem)
                                <label class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 transition hover:bg-white">
                                    <input type="checkbox" name="escalation_checklist[]" value="{{ $checklistItem['key'] }}" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-navy-600 focus:ring-navy-500">
                                    <span class="text-xs leading-snug text-slate-700">{{ $checklistItem['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" class="tw-btn tw-btn-sm tw-btn-outline" data-resolution-close>Cancel</button>
                <button type="submit" class="tw-btn tw-btn-sm tw-btn-navy" data-resolution-submit>Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.querySelector('[data-complaint-resolution-modal]');
        if (!modal) return;
        var form = document.getElementById('complaintResolutionForm');
        var title = modal.querySelector('[data-resolution-title]');
        var help = modal.querySelector('[data-resolution-help]');
        var label = modal.querySelector('label[for="resolutionNote"]');
        var note = document.getElementById('resolutionNote');
        var proofWrap = modal.querySelector('[data-resolution-proof-wrap]');
        var checklistWrap = modal.querySelector('[data-resolution-checklist-wrap]');
        var proofInput = document.getElementById('resolutionProofs');
        var submit = modal.querySelector('[data-resolution-submit]');
        var lastTrigger;

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest ? event.target.closest('[data-complaint-resolution]') : null;
            if (trigger) {
                event.preventDefault();
                var solved = trigger.getAttribute('data-mode') === 'solve';
                lastTrigger = trigger;
                form.action = trigger.getAttribute('data-action');
                title.textContent = solved ? 'Mark Complaint Solved' : 'Reject Complaint';
                label.textContent = solved ? 'Resolution details' : 'Reason for rejection';
                help.textContent = solved ? 'Explain how complaint ' + trigger.getAttribute('data-ref') + ' was resolved.' : 'Explain why complaint ' + trigger.getAttribute('data-ref') + ' is being rejected.';
                proofWrap.classList.toggle('hidden', !solved);
                checklistWrap.classList.toggle('hidden', !solved);
                note.value = '';
                if (proofInput) proofInput.value = '';
                submit.textContent = solved ? 'Mark Solved' : 'Reject';
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                note.focus();
            }
            if (event.target.closest && event.target.closest('[data-resolution-close]')) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                if (lastTrigger) lastTrigger.focus();
            }
        });
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<i class="bi bi-arrow-repeat animate-spin" aria-hidden="true"></i> Saving...';
        });
    })();
</script>
