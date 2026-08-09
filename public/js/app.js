(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initSidebar();
        initModalTriggers();
        initDropdowns();
        initPasswordToggles();
        initComplaintCards();
        initNotificationCards();
        initNotificationLive();
        initOperatorModals();
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

    function initComplaintCards() {
        var headers = document.querySelectorAll('[data-complaint-toggle]');
        headers.forEach(function (header) {
            function toggle() {
                var card = header.closest('[data-complaint-card]');
                if (!card) return;
                var details = card.querySelector('[data-complaint-details]');
                var chevron = header.querySelector('[data-complaint-chevron]');
                var collapsed = details.classList.toggle('hidden');
                if (chevron) {
                    chevron.classList.toggle('rotate-180', collapsed);
                }
                header.setAttribute('aria-expanded', String(!collapsed));
            }
            header.addEventListener('click', function (e) {
                if (e.target.closest('[data-complaint-check]')) return;
                toggle();
            });
            header.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });

        var checkboxes = document.querySelectorAll('[data-complaint-check]');
        var selectAll = document.querySelector('[data-complaint-select-all]');
        var bulkForm = document.getElementById('bulkReviewForm');
        var bulkIds = document.getElementById('bulkReviewIds');
        var bulkBtn = document.querySelector('[data-bulk-review]');
        var bulkCount = document.querySelector('[data-bulk-count]');

        function updateBulk() {
            var checked = document.querySelectorAll('[data-complaint-check]:checked');
            var total = document.querySelectorAll('[data-complaint-check]').length;
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
                var checked = document.querySelectorAll('[data-complaint-check]:checked');
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
            if (!e.target.closest('[data-tw-dropdown]')) {
                closeAllDropdowns();
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('[data-tw-dropdown-menu]').forEach(function (menu) {
            menu.classList.remove('open');
        });
    }
})();
