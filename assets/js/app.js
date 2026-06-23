/**
 * assets/js/app.js
 * Global UI Initialisation — Theme, Sidebar, Notifications
 * ──────────────────────────────────────────────────────────
 * Runs on every page after DOM content loaded.
 */

'use strict';

// ── Theme Management ──────────────────────────────────────────────────────────
const ThemeManager = (() => {
    const KEY      = 'inventrack_theme';
    const ROOT     = document.documentElement;
    const TOGGLE   = document.getElementById('themeToggle');

    /** Apply a theme to <html data-theme="..."> */
    function apply(theme) {
        ROOT.setAttribute('data-theme', theme);
        if (TOGGLE) {
            TOGGLE.textContent = theme === 'dark' ? '☀️' : '🌙';
            TOGGLE.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`);
        }
        localStorage.setItem(KEY, theme);
    }

    /** Read saved preference; default to system preference */
    function init() {
        const saved  = localStorage.getItem(KEY);
        const prefer = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        apply(saved || prefer);

        if (TOGGLE) {
            TOGGLE.addEventListener('click', () => {
                const current = ROOT.getAttribute('data-theme') || 'light';
                apply(current === 'dark' ? 'light' : 'dark');
            });
        }
    }

    return { init, apply };
})();

// ── Sidebar Toggle ────────────────────────────────────────────────────────────
const Sidebar = (() => {
    const SHELL       = document.querySelector('.app-shell');
    const TOGGLE_BTN  = document.getElementById('sidebarToggle');
    const KEY         = 'inventrack_sidebar_collapsed';

    function setCollapsed(collapsed) {
        if (!SHELL) return;
        SHELL.classList.toggle('collapsed', collapsed);
        localStorage.setItem(KEY, collapsed ? '1' : '0');
    }

    function init() {
        // Restore saved state
        const saved = localStorage.getItem(KEY) === '1';
        setCollapsed(saved);

        if (TOGGLE_BTN) {
            TOGGLE_BTN.addEventListener('click', () => {
                const isCollapsed = SHELL.classList.contains('collapsed');
                setCollapsed(!isCollapsed);
            });
        }
    }

    return { init };
})();

// ── Active Nav Link ───────────────────────────────────────────────────────────
function highlightActiveNav() {
    const path     = window.location.pathname.replace(/\\/g, '/');
    const navItems = document.querySelectorAll('.nav-item');

    navItems.forEach(item => {
        const href = (item.getAttribute('href') || '').replace(/\\/g, '/');
        if (href && path.includes(href.split('/').pop().replace('.php', ''))) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// ── Low-Stock Badge Updater ───────────────────────────────────────────────────
const LowStockBadge = (() => {
    const INTERVAL_MS = 30_000;   // refresh every 30 seconds
    let   timer;

    async function refresh() {
        try {
            const res  = await fetch('/Collaborative_project/api/low_stock.php');
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;

            const count = json.count;

            // Update all nav badges with id "lowStockBadge"
            document.querySelectorAll('[data-badge="low-stock"]').forEach(el => {
                el.textContent = count;
                el.style.display = count > 0 ? '' : 'none';
            });

        } catch (e) {
            // Silently fail if API unreachable
        }
    }

    function start() {
        refresh();
        timer = setInterval(refresh, INTERVAL_MS);
    }

    return { start, refresh };
})();

// ── Toast Notification System ─────────────────────────────────────────────────
const Toast = (() => {
    let container;

    function ensureContainer() {
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            Object.assign(container.style, {
                position: 'fixed',
                bottom: '24px',
                right: '24px',
                zIndex: '999',
                display: 'flex',
                flexDirection: 'column',
                gap: '10px',
                pointerEvents: 'none',
            });
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Show a toast notification.
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} type
     * @param {number} duration  ms before auto-dismiss (0 = manual)
     */
    function show(message, type = 'info', duration = 4000) {
        const c     = ensureContainer();
        const toast = document.createElement('div');

        const iconMap = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
        const colorMap = {
            success: '#22c55e',
            danger:  '#ef4444',
            warning: '#f59e0b',
            info:    '#6366f1',
        };

        Object.assign(toast.style, {
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '12px 18px',
            borderRadius: '10px',
            background: 'var(--bg-surface)',
            border: `1px solid ${colorMap[type]}33`,
            boxShadow: '0 8px 24px rgba(0,0,0,0.15)',
            fontSize: '13px',
            fontFamily: 'var(--font-sans)',
            color: 'var(--text-primary)',
            maxWidth: '320px',
            pointerEvents: 'all',
            animation: 'fadeInUp 0.3s ease',
            borderLeft: `4px solid ${colorMap[type]}`,
            transition: 'opacity 0.3s ease, transform 0.3s ease',
        });

        toast.innerHTML = `<span>${iconMap[type]}</span><span>${message}</span>`;
        c.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    }

    return { show };
})();

// ── Confirm Dialog ────────────────────────────────────────────────────────────
/**
 * Async confirm dialog using native dialog element for zero dependencies.
 * @param {string} message
 * @param {string} [confirmText]
 * @returns {Promise<boolean>}
 */
function confirmDialog(message, confirmText = 'Confirm') {
    return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal" style="max-width:400px;" role="alertdialog" aria-modal="true">
                <div class="modal-header">
                    <span class="modal-title">⚠️ Confirm Action</span>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);font-size:14px;">${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="cdCancel">Cancel</button>
                    <button class="btn btn-danger"    id="cdConfirm">${confirmText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.getElementById('cdConfirm').focus();

        overlay.addEventListener('click', e => {
            if (e.target === overlay) { overlay.remove(); resolve(false); }
        });
        document.getElementById('cdCancel').addEventListener('click',  () => { overlay.remove(); resolve(false); });
        document.getElementById('cdConfirm').addEventListener('click', () => { overlay.remove(); resolve(true); });
    });
}

// ── Auto-dismiss Flash Messages ───────────────────────────────────────────────
function autoDismissAlerts() {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss, 10) || 5000;
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
            alert.style.opacity = '0';
            alert.style.maxHeight = '0';
            alert.style.marginBottom = '0';
            alert.style.padding = '0';
            setTimeout(() => alert.remove(), 400);
        }, delay);
    });
}

// ── Format Currency ───────────────────────────────────────────────────────────
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

// ── Format Date ───────────────────────────────────────────────────────────────
function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    Sidebar.init();
    highlightActiveNav();
    LowStockBadge.start();
    autoDismissAlerts();
});

// Expose globally for inline use in views
window.Toast          = Toast;
window.confirmDialog  = confirmDialog;
window.formatCurrency = formatCurrency;
window.formatDate     = formatDate;
