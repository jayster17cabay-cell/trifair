{{--
    DashboardQuickActions component — 2-column grid of quick-action cards.
    Requires: $operator (for the QR print link).
--}}
<div class="op-qa-grid">
    @if ($operator && $operator->qr_code)
        <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" target="_blank" rel="noopener" class="op-qa-card">
            <span class="op-qa-icon op-qa-icon-green"><i class="bi bi-printer-fill"></i></span>
            <span class="op-qa-label">Print QR</span>
        </a>
    @endif

    <button type="button" data-tw-modal-open="howToUseModal" class="op-qa-card">
        <span class="op-qa-icon op-qa-icon-blue"><i class="bi bi-question-circle-fill"></i></span>
        <span class="op-qa-label">How to use</span>
    </button>
</div>
