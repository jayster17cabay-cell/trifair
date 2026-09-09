@once
@php
    // Context operator (from the QR/rate page). Null on the landing page.
    $sosOperatorId = $sosOperatorId ?? null;
@endphp
{{-- ============ PASSENGER EMERGENCY (SOS) BUTTON ============ --}}
<style>
    .sos-fab {
        position: fixed;
        right: 18px;
        bottom: calc(18px + var(--safe-bottom, 0px));
        z-index: 9999;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        border-radius: 999px;
        padding: 8px 18px 8px 10px;
        background: #dc2626;
        color: #fff;
        font-weight: 800;
        font-size: 0.85rem;
        letter-spacing: 0.02em;
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.45);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.2s ease;
        font-family: inherit;
    }
    .sos-fab:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(220, 38, 38, 0.55); }
    .sos-fab:active { transform: translateY(0) scale(0.98); }
    .sos-fab.sos-fab-cooling { opacity: 0.75; cursor: default; }
    .sos-pulse {
        position: relative;
    }
    .sos-pulse::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 999px;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55);
        animation: sos-pulse-anim 2s infinite;
    }
    @keyframes sos-pulse-anim {
        0%   { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55); }
        70%  { box-shadow: 0 0 0 14px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .sos-dot {
        display: inline-flex;
        width: 30px;
        height: 30px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #fff;
        color: #dc2626;
        font-weight: 900;
        font-size: 0.72rem;
        letter-spacing: 0.02em;
    }
    .sos-overlay {
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(3px);
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 0;
    }
    .sos-sheet {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        background: #fff;
        border-radius: 22px 22px 0 0;
        padding: 20px 18px calc(24px + var(--safe-bottom, 0px));
        box-shadow: 0 -10px 40px rgba(15, 23, 42, 0.25);
        animation: sos-sheet-up 0.22s ease-out;
    }
    @keyframes sos-sheet-up {
        from { transform: translateY(48px); opacity: 0.6; }
        to   { transform: translateY(0); opacity: 1; }
    }
    .sos-cat {
        display: flex;
        align-items: center;
        gap: 14px;
        width: 100%;
        text-align: left;
        border-radius: 16px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        padding: 14px 16px;
        cursor: pointer;
        transition: transform 0.12s ease, border-color 0.12s ease, box-shadow 0.12s ease;
        font-family: inherit;
    }
    .sos-cat:hover, .sos-cat:focus-visible { border-color: currentColor; box-shadow: 0 6px 18px rgba(15,23,42,0.08); transform: translateY(-1px); }
    .sos-cat:active { transform: scale(0.985); }
    .sos-cat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; flex-shrink: 0; }
    .sos-cat-label { font-weight: 800; font-size: 0.95rem; }
    .sos-cat-sub { font-size: 0.72rem; color: #64748b; margin-top: 1px; }
    .sos-cat-chevron { margin-left: auto; color: #cbd5e1; font-size: 1rem; }
    .sos-hold-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 6px 0 2px; }
    .sos-hold-label { font-weight: 700; font-size: 0.85rem; color: #334155; }
    .sos-hold-btn {
        position: relative;
        width: 168px;
        height: 168px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(145deg, #dc2626, #b91c1c);
        color: #fff;
        font-weight: 900;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        cursor: pointer;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
        box-shadow: 0 12px 32px rgba(220, 38, 38, 0.4);
        font-family: inherit;
    }
    .sos-hold-btn:disabled { opacity: 0.8; cursor: default; }
    .sos-hold-ring {
        position: absolute;
        inset: -8px;
        width: calc(100% + 16px);
        height: calc(100% + 16px);
        border-radius: 999px;
        pointer-events: none;
    }
    .sos-hold-ring circle { fill: none; stroke-width: 6; }
    .sos-hold-ring .track { stroke: rgba(255,255,255,0.18); }
    .sos-hold-ring .fill { stroke: #fff; stroke-linecap: round; transform: rotate(-90deg); transform-origin: 50% 50%; transition: stroke-dashoffset 0.05s linear; }
    .sos-hold-pct { position: absolute; inset: auto 0 18px; text-align: center; font-size: 0.7rem; font-weight: 700; opacity: 0.9; }
    .sos-note { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; font-size: 0.85rem; font-family: inherit; resize: none; }
    .sos-note:focus { outline: none; border-color: #64748b; }
    .sos-back { background: none; border: none; color: #64748b; font-weight: 700; font-size: 0.8rem; cursor: pointer; padding: 4px 8px; font-family: inherit; }
    .sos-success-icon {
        width: 96px; height: 96px; border-radius: 999px;
        background: #dcfce7; color: #16a34a;
        display: flex; align-items: center; justify-content: center;
        font-size: 3rem; margin: 0 auto;
        animation: sos-pop 0.35s ease-out;
    }
    @keyframes sos-pop { 0% { transform: scale(0.5); opacity: 0; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
    .sos-error { color: #dc2626; font-weight: 700; font-size: 0.8rem; }
    [hidden] { display: none !important; }
</style>

<button type="button" class="sos-fab sos-pulse" id="sosFab" aria-label="Emergency SOS">
    <span class="sos-dot">SOS</span><span id="sosFabLabel">Emergency</span>
</button>

<div class="sos-overlay" id="sosOverlay" hidden>
    <div class="sos-sheet" role="dialog" aria-label="Emergency alert">
        {{-- Step 1: category picker --}}
        <div id="sosStepCat">
            <div class="mb-1 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800">Emergency Alert</h3>
                    <p class="text-xs text-slate-500">What is happening? Select the closest option.</p>
                </div>
                <button type="button" class="sos-back" id="sosClose">Close</button>
            </div>
            <div class="mt-3 grid gap-2.5" id="sosCatList"></div>
            <p class="mt-3 text-center text-[0.68rem] text-slate-400"><i class="bi bi-shield-fill-check"></i> Your location and ride details are sent to TFRB responders.</p>
        </div>

        {{-- Step 2: long-press confirm --}}
        <div id="sosStepHold" hidden>
            <div class="mb-1 flex items-center justify-between">
                <button type="button" class="sos-back" id="sosBack"><i class="bi bi-arrow-left"></i> Back</button>
                <div><h3 class="text-right text-lg font-extrabold text-slate-800" id="sosHoldCategory"></h3></div>
            </div>
            <p class="text-center text-xs text-slate-500">Hold the button for 3 seconds to send. Release early to cancel.</p>
            <div class="sos-hold-wrap">
                <button type="button" class="sos-hold-btn" id="sosHoldBtn">
                    <svg class="sos-hold-ring" viewBox="0 0 100 100" aria-hidden="true">
                        <circle class="track" cx="50" cy="50" r="46"></circle>
                        <circle class="fill" id="sosHoldRing" cx="50" cy="50" r="46"></circle>
                    </svg>
                    <span>Hold to Send<br>Emergency Alert</span>
                    <span class="sos-hold-pct" id="sosHoldPct">0%</span>
                </button>
            </div>
            <textarea class="sos-note" id="sosNote" rows="2" maxlength="280" placeholder="Optional note (e.g. describe what's happening)"></textarea>
        </div>

        {{-- Step 3: result --}}
        <div id="sosStepDone" hidden>
            <div class="py-10">
                <div class="sos-success-icon"><i class="bi bi-check-lg"></i></div>
                <h3 class="mt-4 text-center text-xl font-extrabold text-emerald-600">Emergency alert sent</h3>
                <p class="mt-1 text-center text-sm text-slate-500">TFRB responders have been notified. Stay safe.</p>
                <div class="mx-auto mt-4 max-w-[280px] rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                    <div class="flex justify-between py-0.5"><span>Category</span><strong id="sosDoneCategory"></strong></div>
                    <div class="flex justify-between py-0.5"><span>Time</span><strong id="sosDoneTime"></strong></div>
                    <div class="flex justify-between py-0.5"><span>Location</span><strong id="sosDoneLocation">Captured</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.__triFairSosInit) return;
    window.__triFairSosInit = true;

    window.__triFairSosCsrf = "{{ csrf_token() }}";

    var CONFIRM_HOLD_DURATION_MS = 3000;
    var COOLDOWN_MS = 30000;
    var GEO_TIMEOUT_MS = 8000;

    var CATEGORIES = [
        { key: 'general',          label: 'General Emergency', sub: 'Life-threatening or urgent help', color: '#dc2626', icon: 'bi-exclamation-triangle-fill' },
        { key: 'accident',         label: 'Accident',          sub: 'Collision or crash on the road',  color: '#ea580c', icon: 'bi-truck-front' },
        { key: 'theft_harassment', label: 'Theft / Harassment', sub: 'Threatened or victimized',        color: '#7f1d1d', icon: 'bi-shield-fill' },
        { key: 'other',            label: 'Other',              sub: 'Anything else — add a note',      color: '#475569', icon: 'bi-three-dots' }
    ];
    var OPERATOR_ID = {{ $sosOperatorId ?? 'null' }};
    var RING_C = 2 * Math.PI * 46;

    var fab = document.getElementById('sosFab');
    var overlay = document.getElementById('sosOverlay');
    var stepCat = document.getElementById('sosStepCat');
    var stepHold = document.getElementById('sosStepHold');
    var stepDone = document.getElementById('sosStepDone');
    var catList = document.getElementById('sosCatList');
    var holdBtn = document.getElementById('sosHoldBtn');
    var holdRing = document.getElementById('sosHoldRing');
    var holdPct = document.getElementById('sosHoldPct');
    var noteEl = document.getElementById('sosNote');
    var closeBtn = document.getElementById('sosClose');
    var backBtn = document.getElementById('sosBack');

    var selected = null;
    var holdActive = false;
    var holdStart = 0;
    var holdRaf = null;
    var holding = false;
    var coolingUntil = 0;
    holdRing.style.strokeDasharray = RING_C;
    setHold(0);

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : window.__triFairSosCsrf || '';
    }

    function cookie(name) {
        var parts = document.cookie.split('; ');
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i].indexOf('=');
            if (parts[i].substring(0, p) === name) return decodeURIComponent(parts[i].substring(p + 1));
        }
        return '';
    }
    function ensurePid() {
        var id = cookie('tf_pid');
        if (!id) {
            id = '';
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for (var i = 0; i < 40; i++) id += chars.charAt(Math.floor(Math.random() * chars.length));
            document.cookie = 'tf_pid=' + id + '; path=/; max-age=31536000; SameSite=Lax';
        }
        return id;
    }

    function setHold(pct) {
        holdRing.style.strokeDashoffset = RING_C - (RING_C * pct / 100);
        holdPct.textContent = Math.round(pct) + '%';
    }

    function openSheet() {
        selected = null;
        showStepCat();
        overlay.hidden = false;
    }
    function closeSheet() {
        if (holding) cancelHold();
        overlay.hidden = true;
    }
    function showStepCat() { stepCat.hidden = false; stepHold.hidden = true; stepDone.hidden = true; buildCategories(); }
    function showStepHold() { stepCat.hidden = true; stepHold.hidden = false; stepDone.hidden = true; setHold(0); noteEl.value = ''; }
    function showStepDone() { stepCat.hidden = true; stepHold.hidden = true; stepDone.hidden = false; }

    function buildCategories() {
        catList.innerHTML = '';
        CATEGORIES.forEach(function (c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sos-cat';
            btn.style.setProperty('color', '#1e293b');
            btn.innerHTML =
                '<span class="sos-cat-icon" style="background:' + c.color + '"><i class="bi ' + c.icon + '"></i></span>' +
                '<span><span class="sos-cat-label">' + c.label + '</span><span class="sos-cat-sub" style="display:block">' + c.sub + '</span></span>' +
                '<i class="bi bi-chevron-right sos-cat-chevron"></i>';
            btn.addEventListener('click', function () {
                selected = c;
                document.getElementById('sosHoldCategory').textContent = c.label;
                noteEl.placeholder = c.key === 'other' ? 'Briefly describe the situation (optional)' : (c.key === 'theft_harassment' ? 'Optional: describe what happened or the person' : 'Optional note');
                showStepHold();
            });
            catList.appendChild(btn);
        });
    }

    function cancelHold() {
        holding = false;
        holdActive = false;
        if (holdRaf) { cancelAnimationFrame(holdRaf); holdRaf = null; }
        rebuildHoldInner();
    }

    function rebuildHoldInner() {
        holdBtn.innerHTML =
            '<svg class="sos-hold-ring" viewBox="0 0 100 100" aria-hidden="true"><circle class="track" cx="50" cy="50" r="46"></circle><circle class="fill" id="sosHoldRing" cx="50" cy="50" r="46"></circle></svg>' +
            '<span>Hold to Send<br>Emergency Alert</span>' +
            '<span class="sos-hold-pct" id="sosHoldPct">0%</span>';
        holdRing = document.getElementById('sosHoldRing');
        holdPct = document.getElementById('sosHoldPct');
        holdRing.style.strokeDasharray = RING_C;
        setHold(0);
    }

    function beginHold(e) {
        e.preventDefault();
        if (!selected || coolingUntil > Date.now()) return;
        if (holding) return;
        holding = true;
        holdActive = true;
        holdStart = performance.now();
        function tick(ts) {
            if (!holdActive) return;
            var pct = Math.min(100, ((ts - holdStart) / CONFIRM_HOLD_DURATION_MS) * 100);
            setHold(pct);
            if (pct >= 100) {
                holdActive = false;
                holding = false;
                setHold(100);
                var el = document.getElementById('sosHoldRing');
                if (el) el.style.strokeDashoffset = 0;
                completeSend();
                return;
            }
            holdRaf = requestAnimationFrame(tick);
        }
        holdRaf = requestAnimationFrame(tick);
    }
    function endHold() { if (holding) cancelHold(); }

    function getLocation() {
        return new Promise(function (resolve) {
            if (!('geolocation' in navigator)) return resolve(null);
            var done = false;
            var timer = setTimeout(function () { if (!done) { done = true; resolve(null); } }, GEO_TIMEOUT_MS);
            navigator.geolocation.getCurrentPosition(
                function (pos) { if (!done) { done = true; clearTimeout(timer); resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }); } },
                function () { if (!done) { done = true; clearTimeout(timer); resolve(null); } },
                { enableHighAccuracy: true, timeout: GEO_TIMEOUT_MS, maximumAge: 30000 }
            );
        });
    }

    function completeSend() {
        var payload = {
            _token: csrf(),
            category: selected.key,
            passenger_id: ensurePid(),
            operator_id: OPERATOR_ID,
            passenger_name: document.getElementById('passenger_name') ? document.getElementById('passenger_name').value : '',
            passenger_contact: document.getElementById('passenger_contact') ? document.getElementById('passenger_contact').value : '',
            note: noteEl.value
        };
        getLocation().then(function (loc) {
            if (loc) { payload.location_lat = loc.lat; payload.location_lng = loc.lng; }
            return postSos(payload).then(function (ok) {
                if (ok) {
                    document.getElementById('sosDoneCategory').textContent = selected.label;
                    document.getElementById('sosDoneTime').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('sosDoneLocation').textContent = loc ? 'Captured' : 'Unavailable';
                    showStepDone();
                    coolingUntil = Date.now() + COOLDOWN_MS;
                    fab.classList.add('sos-fab-cooling');
                    document.getElementById('sosFabLabel').textContent = 'Alert Sent';
                    fab.disabled = true;
                    setTimeout(function () {
                        fab.disabled = false;
                        fab.classList.remove('sos-fab-cooling');
                        document.getElementById('sosFabLabel').textContent = 'Emergency';
                    }, COOLDOWN_MS);
                    if (navigator.vibrate) { try { navigator.vibrate([120, 60, 120]); } catch (err) {} }
                } else {
                    var err = document.createElement('p');
                    err.className = 'sos-error';
                    err.textContent = 'Could not send alert. Please check your connection and try again.';
                    stepHold.appendChild(err);
                    setHold(0);
                    setTimeout(function () { if (err.parentNode) err.parentNode.removeChild(err); }, 6000);
                }
            });
        });
    }

    function postSos(data) {
        return fetch('/sos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf()
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        }).then(function (r) { return r.ok; }).catch(function () { return false; });
    }

    fab.addEventListener('click', function () { if (coolingUntil <= Date.now()) openSheet(); });
    closeBtn.addEventListener('click', closeSheet);
    backBtn.addEventListener('click', showStepCat);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeSheet(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });

    holdBtn.addEventListener('pointerdown', beginHold);
    holdBtn.addEventListener('pointerup', endHold);
    holdBtn.addEventListener('pointercancel', endHold);
    holdBtn.addEventListener('pointerleave', endHold);
    holdBtn.addEventListener('contextmenu', function (e) { e.preventDefault(); });
})();
</script>
@endonce