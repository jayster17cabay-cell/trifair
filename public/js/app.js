(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initSidebar();
        initModalTriggers();
        initDropdowns();
        initPasswordToggles();
        initCollapsibleCardLists();
        initNotificationCards();
        initNotificationLive();
        initOperatorModals();
        initOfficerModals();
        initTripHistoryDrawer();
        initOperatorMenu();
    });

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
        setText('opModalLicense', op.license);
        setText('opModalColor', op.color);
        setText('opModalAddress', op.address);

        var avatar = document.getElementById('opModalAvatar');
        if (avatar) {
            avatar.className = 'tw-avatar tw-avatar-md shrink-0 ' + (op.avatarBg || 'bg-navy-700');
            avatar.textContent = (op.name || '?').charAt(0).toUpperCase();
        }

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
        var bell = document.getElementById('unreadBellBadge');
        var side = document.getElementById('unreadSideBadge');
        if (bell) bell.style.display = show ? '' : 'none';
        if (side) side.style.display = show ? '' : 'none';
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
            if (typeof data.invalidCount === 'number') {
                var inv = document.querySelector('[data-notif-count="invalid"]');
                if (inv) inv.textContent = data.invalidCount;
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
            backdrop.addEventListener('mousedown', function (e) {
                if (e.target === backdrop) {
                    closeModal(backdrop.querySelector('.tw-modal'));
                }
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

    function initDropdowns() {
        document.querySelectorAll('[data-tw-dropdown]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var menu = document.getElementById(btn.getAttribute('data-tw-dropdown'));
                if (!menu) return;
                var wasOpen = menu.classList.contains('open');
                closeAllDropdowns();
                if (!wasOpen) {
                    menu.classList.add('open');
                }
            });
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-tw-dropdown]') && !e.target.closest('[data-tw-dropdown-menu]')) {
                closeAllDropdowns();
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('[data-tw-dropdown-menu]').forEach(function (menu) {
            menu.classList.remove('open');
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

    function initTripHistoryDrawer() {
        var drawer = document.querySelector('[data-trip-drawer]');
        var overlay = document.querySelector('[data-trip-drawer-overlay]');
        if (!drawer || !overlay) return;

        var nameEl = drawer.querySelector('[data-trip-drawer-name]');
        var subEl = drawer.querySelector('[data-trip-drawer-subtitle]');
        var avatarEl = drawer.querySelector('[data-trip-drawer-avatar]');
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
            var bg = row.getAttribute('data-avatar-bg') || 'bg-navy-700';
            var letter = row.getAttribute('data-avatar-letter') || '?';

            nameEl.textContent = name;
            avatarEl.className = 'tw-avatar tw-avatar-md shrink-0 text-white ' + bg;
            avatarEl.textContent = letter;
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
            listEl.querySelectorAll('[data-trip-more]').forEach(function (el) {
                el.classList.remove('hidden');
            });
            btn.classList.add('hidden');
        });
    }
})();
