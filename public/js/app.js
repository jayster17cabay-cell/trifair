(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initSidebar();
        initModalTriggers();
        initDropdowns();
        initExportForms();
        initExportScopeFilters();
        initPasswordToggles();
        initProofInputs();
        initCollapsibleCardLists();
        initNotificationCards();
        initNotificationLive();
        initOperatorModals();
        initFranchiseModals();
        initSuspendModals();
        initOfficerModals();
        initTripHistoryDrawer();
        initOperatorMenu();
        initPresidentModals();
        initSubmitGuards();
    });

    function initSubmitGuards() {
        // Prevent accidental double submissions. The first submit is left
        // untouched (so HTML5 validation, inline confirm() handlers and the
        // submit button's own value all keep working); only a repeat submit
        // issued before the browser navigates away is swallowed.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            // If an earlier handler (e.g. confirm()) already cancelled this
            // submit, leave the form armed so the user can retry.
            if (e.defaultPrevented) return;
            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET') return;
            if (form.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }
            form.dataset.submitting = '1';
        });

        // Re-arm forms restored from the back/forward cache so a returning
        // user is never stuck with a locked form.
        window.addEventListener('pageshow', function () {
            var forms = document.querySelectorAll('form[data-submitting]');
            for (var i = 0; i < forms.length; i++) forms[i].removeAttribute('data-submitting');
        });
    }

    function initOperatorModals() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-operator-view]');
            if (!btn) return;
            var op;
            try {
                op = JSON.parse(btn.getAttribute('data-operator-view'));
            } catch (err) {
                return;
            }
            if (!op) return;
            fillOperatorModal(op);
            openModal('operatorDetailsModal');
        });
    }

    function escHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function initOfficerModals() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-officer-view]');
            if (!btn) return;
            var officer;
            try {
                officer = JSON.parse(btn.getAttribute('data-officer-view'));
            } catch (err) {
                return;
            }
            if (!officer) return;
            fillOfficerModal(officer);
            openModal('officerDetailsModal');
        });
    }

    function fillOfficerModal(officer) {
        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) {
                el.textContent = value == null || value === '' ? '—' : value;
            }
        }

        setText('ofModalName', officer.name);
        setText('ofModalEmail', officer.email);
        setText('ofModalPhone', officer.phone);
        setText('ofModalJoined', officer.joined);

        var avatar = document.getElementById('ofModalAvatar');
        if (avatar) {
            avatar.className = 'tw-avatar tw-avatar-md shrink-0 ' + (officer.avatarBg || 'bg-navy-700');
            avatar.textContent = (officer.name || '?').charAt(0).toUpperCase();
        }

        var status = document.getElementById('ofModalStatus');
        if (status) {
            status.className = 'tw-badge shrink-0 ' + (officer.statusClass || 'tw-badge-gray');
            status.innerHTML = '<i class="bi ' + escHtml(officer.statusIcon || 'bi-circle') + '"></i>' + escHtml(officer.statusLabel || '');
        }

        var verified = document.getElementById('ofModalVerified');
        if (verified) {
            if (officer.verified) {
                verified.innerHTML = '<span class="inline-flex items-center gap-1.5 text-sm text-emerald-700"><i class="bi bi-patch-check-fill"></i> Email address verified</span>';
            } else {
                verified.innerHTML = '<span class="inline-flex items-center gap-1.5 text-sm text-amber-700"><i class="bi bi-clock-fill"></i> Awaiting email verification</span>';
            }
        }
    }

    function fillOperatorModal(op) {
        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) {
                el.textContent = value == null || value === '' ? '—' : value;
            }
        }

        setText('opModalName', op.name);
        setText('opModalEmail', op.email);
        setText('opModalToda', op.toda);
        setText('opModalPlate', op.plate);
        setText('opModalBody', op.body);
        setText('opModalColor', op.color);
        setText('opModalAddress', op.address);

        var status = document.getElementById('opModalStatus');
        if (status) {
            status.className = 'tw-badge shrink-0 ' + (op.statusClass || 'tw-badge-gray');
            status.innerHTML = '<i class="bi ' + escHtml(op.statusIcon || 'bi-circle') + '"></i>' + escHtml(op.statusLabel || '');
        }

        var contact = document.getElementById('opModalContact');
        if (contact) {
            if (op.contact) {
                contact.innerHTML = '<a href="tel:' + escHtml(op.contact) + '" class="font-semibold text-navy-600 hover:underline">' + escHtml(op.contact) + '</a>';
            } else {
                contact.textContent = '—';
            }
        }

        var edit = document.getElementById('opModalEdit');
        if (edit) {
            edit.setAttribute('href', op.editUrl || '#');
        }
        var qr = document.getElementById('opModalQr');
        if (qr) {
            qr.setAttribute('href', op.qrUrl || '#');
        }

        var franchise = document.getElementById('opModalFranchise');
        if (franchise) {
            if (op.franchiseNo) {
                var line = 'No. <span class="font-mono font-semibold">' + escHtml(op.franchiseNo) + '</span>';
                if (op.franchiseIssued) {
                    line += ' · from ' + escHtml(op.franchiseIssued);
                }
                if (op.franchiseValidUntil) {
                    line += ' → until ' + escHtml(op.franchiseValidUntil);
                } else {
                    line += ' · no expiry';
                }
                if (op.franchiseStatus === 'expired') {
                    line += ' <span class="tw-badge tw-badge-red">Expired</span>';
                } else if (op.franchiseExpiringSoon) {
                    line += ' <span class="tw-badge tw-badge-amber">Expiring Soon</span>';
                } else {
                    line += ' <span class="tw-badge tw-badge-green">Valid</span>';
                }
                franchise.innerHTML = line;
            } else {
                franchise.textContent = 'No franchise yet';
            }
        }

        var renewBtn = document.getElementById('opModalRenewBtn');
        if (renewBtn) {
            if (op.canIssueFranchise && op.hasTerm) {
                renewBtn.classList.remove('hidden');
                renewBtn.setAttribute('data-franchise-open', JSON.stringify(op));
            } else {
                renewBtn.classList.add('hidden');
            }
        }

        showSuspensionControls(op);

        var suspensionWrap = document.getElementById('opModalSuspensionWrap');
        var suspension = document.getElementById('opModalSuspension');
        if (suspensionWrap && suspension) {
            if (op.isSuspended) {
                var lines = '<span class="tw-badge tw-badge-amber"><i class="bi bi-slash-circle-fill"></i>Suspended</span>';
                if (op.suspensionUntil) {
                    lines += ' <span class="text-slate-500">until ' + escHtml(op.suspensionUntil) + '</span>';
                }
                if (op.suspensionFrom) {
                    lines += ' <span class="text-xs text-slate-400">(from ' + escHtml(op.suspensionFrom) + ')</span>';
                }
                lines += '<div class="mt-1.5 text-slate-700">' + escHtml(op.suspensionReason || '—') + '</div>';
                if (op.suspensionBy) {
                    lines += '<div class="mt-0.5 text-xs text-slate-400">By ' + escHtml(op.suspensionBy) + '</div>';
                }
                suspension.innerHTML = lines;
                suspensionWrap.classList.remove('hidden');
            } else {
                suspension.textContent = '—';
                suspensionWrap.classList.add('hidden');
            }
        }
    }

    function showSuspensionControls(op) {
        var suspendBtn = document.getElementById('opModalSuspendBtn');
        var endForm = document.getElementById('opModalEndSuspendForm');
        if (!suspendBtn && !endForm) return;

        if (op.isSuspended) {
            if (suspendBtn) suspendBtn.classList.add('hidden');
            if (endForm) {
                if (op.endSuspensionUrl) {
                    endForm.setAttribute('action', op.endSuspensionUrl);
                    endForm.classList.remove('hidden');
                } else {
                    endForm.classList.add('hidden');
                }
            }
        } else if (op.canSuspend && op.suspendUrl) {
            if (endForm) endForm.classList.add('hidden');
            if (suspendBtn) {
                suspendBtn.classList.remove('hidden');
                suspendBtn.setAttribute('data-suspend-open', JSON.stringify(op));
            }
        } else {
            if (suspendBtn) suspendBtn.classList.add('hidden');
            if (endForm) endForm.classList.add('hidden');
        }
    }

    function initFranchiseModals() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-franchise-open]');
            if (!btn) return;
            var op;
            try {
                op = JSON.parse(btn.getAttribute('data-franchise-open'));
            } catch (err) {
                return;
            }
            if (!op || !op.issueUrl) return;
            var mode = btn.getAttribute('data-franchise-mode') || (op.hasTerm ? 'edit' : 'issue');
            fillFranchiseModal(op, mode);
            closeModal(document.querySelector('#operatorDetailsModal .tw-modal'));
            openModal('operatorFranchiseModal');
        });
    }

    function initSuspendModals() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-suspend-open]');
            if (!btn) return;
            var op;
            try {
                op = JSON.parse(btn.getAttribute('data-suspend-open'));
            } catch (err) {
                return;
            }
            if (!op || !op.suspendUrl) return;

            var form = document.getElementById('suspendForm');
            if (form) {
                form.setAttribute('action', op.suspendUrl);
            }
            var subtitle = document.getElementById('suspendModalSubtitle');
            if (subtitle) {
                subtitle.textContent = (op.name || '') + (op.toda ? ' · ' + op.toda : '');
            }

            closeModal(document.querySelector('#operatorDetailsModal .tw-modal'));
            openModal('operatorSuspendModal');
        });
    }

    function fillFranchiseModal(op, mode) {
        mode = mode || (op.hasTerm ? 'edit' : 'issue');

        var httpMethod = 'POST';
        var action = op.issueUrl;
        var title = 'Issue Franchise';
        var submitLabel = 'Issue Franchise';
        var showNotice = false;
        var prefill = false;

        if (mode === 'renew') {
            action = op.renewUrl;
            title = 'Renew Franchise';
            submitLabel = 'Renew Franchise';
            showNotice = true;
        } else if (mode === 'edit') {
            action = op.updateUrl || op.issueUrl;
            httpMethod = 'PUT';
            title = 'Edit Franchise';
            submitLabel = 'Update Franchise';
            prefill = true;
        }

        var form = document.getElementById('franchiseForm');
        if (form) {
            form.setAttribute('action', action || '#');
        }
        var methodEl = document.getElementById('franchiseMethod');
        if (methodEl) {
            methodEl.value = httpMethod;
        }

        var setText = function (id, value) {
            var el = document.getElementById(id);
            if (el) {
                el.textContent = value == null || value === '' ? '' : value;
            }
        };
        setText('franchiseModalTitle', title);
        setText('franchiseModalSubtitle', op.name || '');

        var notice = document.getElementById('franchiseRenewNotice');
        if (notice) {
            notice.classList.toggle('hidden', !showNotice);
        }

        var submit = document.getElementById('franchiseSubmit');
        if (submit) {
            submit.innerHTML = '<i class="bi bi-check-lg"></i>';
            submit.setAttribute('title', submitLabel);
            submit.setAttribute('aria-label', submitLabel);
        }

        var keepNumber = (mode === 'edit' || mode === 'renew');
        var no = document.getElementById('franchiseNo');
        if (no) no.value = keepNumber ? (op.franchiseNo || '') : '';
        var issued = document.getElementById('franchiseIssuedAt');
        if (issued) issued.value = prefill ? (op.franchiseIssuedRaw || '') : '';
        var until = document.getElementById('franchiseValidUntil');
        if (until) until.value = prefill ? (op.franchiseValidUntilRaw || '') : '';
    }

    function initCollapsibleCardLists() {
        initCollapsibleCardList('complaint');
        initCollapsibleCardList('rating');
    }

    function initCollapsibleCardList(prefix) {
        var attr = function (name) {
            return '[data-' + prefix + '-' + name + ']';
        };

        var headers = document.querySelectorAll(attr('toggle'));
        headers.forEach(function (header) {
            function toggle() {
                var card = header.closest(attr('card'));
                if (!card) return;
                var details = card.querySelector(attr('details'));
                var chevron = header.querySelector(attr('chevron'));
                var collapsed = details.classList.toggle('hidden');
                if (chevron) {
                    chevron.classList.toggle('rotate-180', collapsed);
                }
                header.setAttribute('aria-expanded', String(!collapsed));
            }
            header.addEventListener('click', function (e) {
                if (e.target.closest(attr('check'))) return;
                toggle();
            });
            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });

        var checkboxes = document.querySelectorAll(attr('check'));
        var selectAll = document.querySelector(attr('select-all'));
        var bulkForm = document.getElementById(prefix + 'BulkReviewForm');
        var bulkIds = document.getElementById(prefix + 'BulkReviewIds');
        var bulkBtn = document.querySelector('[data-' + prefix + '-bulk-review]');
        var bulkCount = document.querySelector('[data-' + prefix + '-bulk-count]');

        function updateBulk() {
            var checked = document.querySelectorAll(attr('check') + ':checked');
            var total = document.querySelectorAll(attr('check')).length;
            var count = checked.length;
            if (selectAll) {
                selectAll.checked = count > 0 && count === total;
                selectAll.indeterminate = count > 0 && count < total;
            }
            if (bulkBtn) {
                bulkBtn.disabled = count === 0;
            }
            if (bulkCount) {
                bulkCount.textContent = count;
            }
        }

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', updateBulk);
        });
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                updateBulk();
            });
        }
        if (bulkForm && bulkIds) {
            bulkForm.addEventListener('submit', function (e) {
                var checked = document.querySelectorAll(attr('check') + ':checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    return;
                }
                bulkIds.value = JSON.stringify(Array.prototype.map.call(checked, function (c) {
                    return c.value;
                }));
            });
        }

        updateBulk();
    }

    function initNotificationCards() {
        document.querySelectorAll('[data-notification-card]').forEach(function (card) {
            var header = card.querySelector('[data-notification-toggle]');
            var details = card.querySelector('[data-notification-details]');
            var chevron = card.querySelector('[data-notification-chevron]');
            if (!header || !details) return;
            var expanded = false;

            function setExpanded(open) {
                expanded = open;
                details.classList.toggle('hidden', !open);
                if (chevron) {
                    chevron.classList.toggle('rotate-180', open);
                }
                header.setAttribute('aria-expanded', String(open));
                if (open) {
                    markNotificationRead(card);
                }
            }

            header.addEventListener('click', function (e) {
                if (e.target.closest('a')) return;
                setExpanded(!expanded);
            });
            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    setExpanded(!expanded);
                }
            });
        });
    }

    function markNotificationRead(card) {
        var id = card.getAttribute('data-notification-id');
        if (!id || card.classList.contains('notification-read')) return;
        card.classList.add('notification-read');

        card.classList.remove('bg-blue-50/40');
        var header = card.querySelector('[data-notification-toggle]');
        if (header) {
            header.classList.add('hover:bg-slate-50/70');
        }
        var dot = card.querySelector('[data-notification-dot]');
        if (dot) {
            dot.classList.add('invisible');
        }

        var token = document.querySelector('meta[name="csrf-token"]');
        fetch('/notifications/' + encodeURIComponent(id) + '/read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : '',
            },
            body: '{}',
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && typeof data.unread_count === 'number') {
                    updateUnreadBadges(data.unread_count);
                }
            })
            .catch(function () {});
    }

    function updateUnreadBadges(count) {
        var show = count > 0;
        var badges = document.querySelectorAll('[data-unread-badge]');
        for (var i = 0; i < badges.length; i++) {
            badges[i].textContent = count;
            badges[i].style.display = show ? '' : 'none';
        }
    }

    function initNotificationLive() {
        var listEl = document.getElementById('notificationList');
        if (!listEl) return;
        var currentSig = null;

        function render(data) {
            if (data.signature && data.signature !== currentSig) {
                currentSig = data.signature;
                if (data.html) {
                    listEl.innerHTML = data.html;
                    initNotificationCards();
                }
            }
            if (data.counts) {
                Object.keys(data.counts).forEach(function (key) {
                    var el = document.querySelector('[data-notif-count="' + key + '"]');
                    if (el) el.textContent = data.counts[key];
                });
            }
            if (typeof data.unreadCount === 'number') {
                updateUnreadBadges(data.unreadCount);
            }
            if (typeof data.hasItems === 'boolean') {
                var form = document.getElementById('markAllReadForm');
                if (form) form.style.display = data.hasItems ? '' : 'none';
            }
        }

        function poll() {
            fetch(window.location.href, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error('poll failed'); return r.json(); })
                .then(render)
                .catch(function () {});
        }

        setTimeout(poll, 4000);
        setInterval(poll, 30000);
    }

    function initPasswordToggles() {
        document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.querySelector(btn.getAttribute('data-pw-toggle'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    }

    function initProofInputs() {
        var MAX = 5;
        document.querySelectorAll('input[data-proof-input]').forEach(function (input) {
            var form = input.closest('form');
            var list = form ? form.querySelector('[data-proof-files]') : null;
            input.addEventListener('change', function () {
                if (!list) return;
                var files = Array.prototype.slice.call(input.files || []);
                if (files.length === 0) {
                    list.textContent = '';
                    return;
                }
                var shown = files.slice(0, MAX);
                var names = shown.map(function (f) { return f.name; }).join(', ');
                var extra = files.length - shown.length;
                var note = names + (extra > 0 ? ' (+' + extra + ' more — only ' + MAX + ' files can be uploaded)' : '');
                list.textContent = '';
                var span = document.createElement('span');
                span.className = 'inline-flex items-center gap-1 text-emerald-600';
                span.innerHTML = '<i class="bi bi-paperclip"></i>';
                span.appendChild(document.createTextNode(' ' + note));
                if (extra > 0) span.className = 'inline-flex items-center gap-1 text-amber-600';
                list.appendChild(span);
            });
        });
    }

    function initAlerts() {
        document.querySelectorAll('[data-tw-dismiss]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sel = btn.getAttribute('data-tw-dismiss');
                var el = (sel ? document.querySelector(sel) : null) || btn.closest('.tw-alert');
                if (el) {
                    el.remove();
                }
            });
        });
    }

    function initSidebar() {
        var toggle = document.getElementById('sidebarToggle');
        var overlay = document.getElementById('sidebarOverlay');
        var closeBtn = document.getElementById('sidebarClose');
        var sidebar = document.querySelector('[data-tw-sidebar]');

        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('-translate-x-full');
                if (overlay) {
                    overlay.classList.toggle('hidden');
                }
            });
        }
        if (closeBtn && sidebar) {
            closeBtn.addEventListener('click', function () {
                sidebar.classList.add('-translate-x-full');
                if (overlay) {
                    overlay.classList.add('hidden');
                }
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function () {
                if (sidebar) {
                    sidebar.classList.add('-translate-x-full');
                }
                overlay.classList.add('hidden');
            });
        }
    }

    function initModalTriggers() {
        document.querySelectorAll('[data-tw-modal-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(btn.getAttribute('data-tw-modal-open'));
            });
        });
        document.querySelectorAll('[data-tw-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(btn.closest('.tw-modal'));
            });
        });
        document.querySelectorAll('.tw-modal-backdrop').forEach(function (backdrop) {
            var downX = 0, downY = 0, downOnBackdrop = false;
            backdrop.addEventListener('mousedown', function (e) {
                downOnBackdrop = e.target === backdrop;
                downX = e.clientX;
                downY = e.clientY;
            });
            backdrop.addEventListener('click', function (e) {
                if (!downOnBackdrop || e.target !== backdrop) return;
                if (Math.abs(e.clientX - downX) > 5 || Math.abs(e.clientY - downY) > 5) return;
                closeModal(backdrop.querySelector('.tw-modal'));
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.tw-modal-backdrop:not(.hidden)').forEach(function (backdrop) {
                    closeModal(backdrop.querySelector('.tw-modal'));
                });
            }
        });
    }

    function openModal(id) {
        var backdrop = document.getElementById(id) || document.querySelector('[data-tw-modal="' + id + '"]');
        if (!backdrop) {
            return;
        }
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        var backdrop = modal ? modal.closest('.tw-modal-backdrop') : null;
        if (backdrop) {
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function setDropdownState(btn, open) {
        var menu = document.getElementById(btn.getAttribute('data-tw-dropdown'));
        if (menu) {
            menu.classList.toggle('open', open);
        }
        btn.setAttribute('aria-expanded', String(open));
    }

    function initDropdowns() {
        // Event delegation: the operators live-search replaces the <tbody>
        // (and its [data-tw-dropdown] buttons) on every keystroke, so the
        // toggle is bound here on the document instead of per-button at init.
        // This keeps the grouped actions (Edit/Approve/Assign/Archive…) working
        // on freshly re-rendered rows.
        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-tw-dropdown]') : null;
            if (!btn) {
                if (!e.target.closest('[data-tw-dropdown-menu]')) {
                    closeAllDropdowns();
                }
                return;
            }
            e.stopPropagation();
            var menu = document.getElementById(btn.getAttribute('data-tw-dropdown'));
            if (!menu) return;
            var willOpen = !menu.classList.contains('open');
            closeAllDropdowns();
            setDropdownState(btn, willOpen);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var openBtn = document.querySelector('[data-tw-dropdown][aria-expanded="true"]');
            if (openBtn) {
                closeAllDropdowns();
                openBtn.focus();
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('[data-tw-dropdown-menu]').forEach(function (menu) {
            menu.classList.remove('open');
        });
        document.querySelectorAll('[data-tw-dropdown]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    function initExportForms() {
        document.querySelectorAll('[data-export-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var format = form.querySelector('input[name="format"]:checked');
                form.target = (format && format.value === 'pdf') ? '_blank' : '';

                var btn = form.querySelector('[data-export-submit]');
                var label = form.querySelector('[data-export-text]');
                if (!btn) return;

                var original = label ? label.textContent : '';
                btn.disabled = true;
                if (label) label.textContent = 'Preparing…';

                window.setTimeout(function () {
                    btn.disabled = false;
                    if (label) label.textContent = original;
                }, 4000);
            });
        });
    }

    function initExportScopeFilters() {
        // In an export panel that offers both a TODA and an Operator selector,
        // keep the Operator list scoped to the chosen TODA: picking a TODA shows
        // only its members, and "All TODAs" brings every operator back.
        document.querySelectorAll('[data-export-form]').forEach(function (form) {
            var toda = form.querySelector('select[name="toda_id"]');
            var operator = form.querySelector('select[name="operator_id"]');
            if (!toda || !operator) return;

            var all = Array.prototype.map.call(operator.options, function (o) {
                return { value: o.value, label: o.textContent, toda: o.getAttribute('data-toda') || '' };
            });
            if (!all.some(function (o) { return o.toda !== ''; })) return;

            function todaLabel() {
                return toda.selectedIndex >= 0 ? toda.options[toda.selectedIndex].textContent : 'All TODAs';
            }

            function rebuild() {
                var selected = toda.value;
                var prev = operator.value;
                var label = todaLabel();
                operator.innerHTML = '';
                all.forEach(function (o) {
                    if (o.value !== '' && selected && o.toda !== selected) return;
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = (o.value === '')
                        ? (selected ? label + ' — All members' : 'All operators')
                        : o.label;
                    opt.setAttribute('data-toda', o.toda);
                    if (o.value === prev) opt.selected = true;
                    operator.appendChild(opt);
                });
            }

            toda.addEventListener('change', rebuild);
            rebuild();
        });
    }

    function initOperatorMenu() {
        var toggle = document.querySelector('[data-op-menu-toggle]');
        var menu = document.querySelector('[data-op-menu]');
        var overlay = document.querySelector('[data-op-menu-overlay]');
        if (!toggle || !menu || !overlay) return;

        function setOpen(open) {
            menu.classList.toggle('open', open);
            overlay.classList.toggle('open', open);
            menu.setAttribute('aria-hidden', String(!open));
            toggle.setAttribute('aria-expanded', String(open));
            document.body.style.overflow = open ? 'hidden' : '';
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!menu.classList.contains('open'));
        });

        overlay.addEventListener('click', function () {
            setOpen(false);
        });

        menu.addEventListener('click', function (e) {
            if (e.target.closest('a[role="menuitem"], button[role="menuitem"]')) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('open')) {
                setOpen(false);
            }
        });
    }

    function initPresidentModals() {
        var backdrop = document.getElementById('presidentMemberModal');
        if (!backdrop) return;
        var body = document.getElementById('presidentMemberModalBody');

        function loadMemberDetail(url) {
            if (!url) return;
            if (body) body.innerHTML = '<div class="flex items-center justify-center gap-2 p-10 text-slate-400"><i class="bi bi-arrow-repeat animate-spin"></i> Loading…</div>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (body && data.html) body.innerHTML = data.html;
                })
                .catch(function () {
                    if (body) body.innerHTML = '<div class="p-8 text-center text-sm text-red-500">Unable to load member details.</div>';
                });
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-president-member]');
            if (!btn) return;
            var id = btn.getAttribute('data-president-member');
            var url = btn.getAttribute('data-url');
            if (!id || !url) return;

            openModal('presidentMemberModal');
            loadMemberDetail(url);
        });

        // The member-detail endpoint returns JSON, so pagination inside the modal
        // is loaded in place instead of navigating to the raw JSON response.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('#presidentMemberModal [data-president-pagination] a[href]');
            if (!link) return;
            e.preventDefault();
            loadMemberDetail(link.getAttribute('href'));
        });

        // The member-detail partial is injected via AJAX after page load, so its
        // data-tw-modal-close button needs a delegated handler to close the modal.
        document.addEventListener('click', function (e) {
            var closeBtn = e.target.closest('#presidentMemberModal [data-tw-modal-close]');
            if (!closeBtn) return;
            closeModal(closeBtn.closest('.tw-modal'));
        });
    }

    function initTripHistoryDrawer() {
        var drawer = document.querySelector('[data-trip-drawer]');
        var overlay = document.querySelector('[data-trip-drawer-overlay]');
        if (!drawer || !overlay) return;

        var nameEl = drawer.querySelector('[data-trip-drawer-name]');
        var subEl = drawer.querySelector('[data-trip-drawer-subtitle]');
        var listEl = drawer.querySelector('[data-trip-drawer-list]');
        var closeBtn = drawer.querySelector('[data-trip-drawer-close]');

        var currentRow = null;
        var cache = {};

        function starsHtml(avg) {
            var filled = Math.round(Number(avg) || 0);
            var html = '';
            for (var i = 1; i <= 5; i++) {
                html += '<i class="bi ' + (i <= filled ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300') + '" style="font-size:0.6rem;"></i>';
            }
            return html;
        }

        function setRowActive(row, active) {
            if (!row) return;
            var chevron = row.querySelector('[data-row-chevron]');
            if (chevron) chevron.classList.toggle('rotate-180', active);
        }

        function closeDrawer() {
            if (!drawer.classList.contains('open')) return;
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (currentRow) {
                setRowActive(currentRow, false);
                currentRow = null;
            }
        }

        function openDrawer(row) {
            if (currentRow === row && drawer.classList.contains('open')) {
                closeDrawer();
                return;
            }

            var url = row.getAttribute('data-trips-url');
            var name = row.getAttribute('data-name') || 'Unknown';
            var plate = row.getAttribute('data-plate');
            var count = row.getAttribute('data-count') || '0';
            var avg = row.getAttribute('data-avg') || '0.0';

            nameEl.textContent = name;
            var subtitle = (plate ? plate + ' \u00b7 ' : '') + count + ' trip' + (count === '1' ? '' : 's') + ' \u00b7 ' + starsHtml(avg) + ' ' + avg + ' avg';
            subEl.innerHTML = subtitle;

            if (currentRow) setRowActive(currentRow, false);
            setRowActive(row, true);
            currentRow = row;

            if (cache[url]) {
                listEl.innerHTML = cache[url];
            } else {
                listEl.innerHTML = '<div class="flex items-center justify-center gap-2 py-10 text-xs text-slate-500"><div class="tw-loading-spinner"></div> Loading trips...</div>';
                fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { if (!r.ok) throw new Error('load failed'); return r.json(); })
                    .then(function (data) {
                        var html = data.html || '<p class="py-8 text-center text-xs text-slate-500">No trips recorded yet.</p>';
                        cache[url] = html;
                        if (currentRow && currentRow.getAttribute('data-trips-url') === url) {
                            listEl.innerHTML = html;
                        }
                    })
                    .catch(function () {
                        listEl.innerHTML = '<p class="py-8 text-center text-xs text-slate-500">Failed to load trips.</p>';
                    });
            }

            drawer.classList.add('open');
            overlay.classList.add('open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        document.addEventListener('click', function (e) {
            var row = e.target.closest('[data-open-trips]');
            if (row) {
                e.preventDefault();
                openDrawer(row);
                return;
            }
            if (overlay.contains(e.target)) {
                closeDrawer();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDrawer);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-trip-show-all]');
            if (!btn) return;
            // Trips are grouped per TODA; only reveal this group's hidden rows.
            var scope = btn.closest('[data-trip-group]') || listEl;
            scope.querySelectorAll('[data-trip-more]').forEach(function (el) {
                el.classList.remove('hidden');
            });
            btn.classList.add('hidden');
        });
    }
})();
