(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initSidebar();
        initModalTriggers();
        initDropdowns();
        initPasswordToggles();
    });

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
