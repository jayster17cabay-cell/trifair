{{--
    QrCodeCard component — "Your QR code" card with print/save actions.
    Requires: $operator.
--}}
<div class="op-card">
    <div class="op-card-head">
        <h3 class="op-card-title"><i class="bi bi-qr-code mr-1.5 text-navy-600"></i> Your QR code</h3>
    </div>

    @if ($operator && $operator->qr_code)
        <div class="op-qr-panel">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" alt="QR Code" class="op-qr-img">
        </div>

        <div class="op-qr-actions">
            <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" class="tw-btn tw-btn-gold tw-btn-sm flex-1">
                <i class="bi bi-printer"></i> Print
            </a>
            <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('rate.operator', $operator->qr_code)) }}" download="trifair-qr-{{ $operator->qr_code }}.png" class="tw-btn tw-btn-outline tw-btn-sm flex-1">
                <i class="bi bi-download"></i> Save
            </a>
        </div>

        <div class="op-info-banner">
            <i class="bi bi-info-circle-fill"></i>
            <span>Print and display inside your motorcycle</span>
        </div>
    @else
        <div class="py-2 text-center">
            <i class="bi bi-qr-code text-4xl text-slate-300"></i>
            <p class="mt-3 text-sm text-slate-500">No QR code assigned yet.</p>
            <p class="text-xs text-slate-600">Contact your TFRB Officer to get one.</p>
        </div>
    @endif
</div>
