/**
 * assets/js/live_search.js
 * Real-Time Product Live Search via Fetch API
 * ─────────────────────────────────────────────
 * Listens on #productSearch input, debounces, calls /api/products.php?q=,
 * and re-renders the product table body without a page reload.
 */

'use strict';

// ── Debounce Utility ──────────────────────────────────────────────────────────
/**
 * Returns a debounced version of fn that fires after `wait` ms of silence.
 */
function debounce(fn, wait = 280) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

// ── Stock Level Badge ─────────────────────────────────────────────────────────
function stockBadge(product) {
    const isLow = product.low_stock;
    const ratio = product.reorder_level > 0
        ? Math.min(1, product.stock_level / product.reorder_level)
        : 1;
    const pct   = Math.round(ratio * 100);

    const barColor = isLow
        ? (pct <= 25 ? '#ef4444' : '#f59e0b')
        : '#22c55e';

    return `
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="flex:1;height:5px;background:var(--border-color);border-radius:99px;min-width:60px;">
                <div style="width:${pct}%;height:100%;background:${barColor};border-radius:99px;transition:width 0.5s;"></div>
            </div>
            <span style="font-weight:600;min-width:28px;text-align:right;">${product.stock_level}</span>
            ${isLow ? '<span class="badge badge-danger" style="animation:pulse-badge 2s infinite;font-size:10px;padding:2px 6px;">LOW</span>' : ''}
        </div>
    `;
}

// ── Row Renderer ──────────────────────────────────────────────────────────────
/**
 * Build a table row HTML string for a product object.
 * isAdmin is a boolean passed from the view via a data attribute on the table.
 */
function buildRow(product, isAdmin) {
    const price    = parseFloat(product.unit_price).toFixed(2);
    const rowClass = product.low_stock ? 'style="background:rgba(239,68,68,0.03)"' : '';

    const editBtn = `
        <a href="/Collaborative_project/views/products.php?action=edit&id=${product.id}"
           class="btn btn-ghost btn-sm" title="Edit product">✏️</a>
    `;

    const deleteBtn = isAdmin
        ? `<button class="btn btn-ghost btn-sm delete-product-btn"
                   data-id="${product.id}"
                   data-name="${escHtml(product.name)}"
                   title="Delete product">🗑️</button>`
        : '';

    return `
        <tr ${rowClass} data-product-id="${product.id}">
            <td>
                <div style="font-weight:600;color:var(--text-primary);">${escHtml(product.name)}</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${escHtml(product.category || '—')}</div>
            </td>
            <td><span class="mono">${escHtml(product.sku)}</span></td>
            <td>${escHtml(product.supplier_name)}</td>
            <td>${stockBadge(product)}</td>
            <td style="color:var(--text-muted);">${product.reorder_level}</td>
            <td style="font-weight:600;">$${price}</td>
            <td>
                <div style="display:flex;gap:4px;align-items:center;">
                    ${editBtn}
                    ${deleteBtn}
                    <button class="btn btn-ghost btn-sm stock-quick-update"
                            data-id="${product.id}"
                            data-name="${escHtml(product.name)}"
                            data-stock="${product.stock_level}"
                            title="Quick stock update">📦</button>
                </div>
            </td>
        </tr>
    `;
}

// ── HTML Escape ───────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Main Search Controller ────────────────────────────────────────────────────
const LiveSearch = (() => {
    const INPUT     = document.getElementById('productSearch');
    const TBODY     = document.getElementById('productTableBody');
    const COUNT_EL  = document.getElementById('productCount');
    const SPINNER   = document.querySelector('.search-spinner');

    if (!INPUT || !TBODY) return { init: () => {} };   // not on the products page

    const isAdmin = TBODY.dataset.isAdmin === '1';

    // Track active fetch to cancel stale responses
    let activeFetch = null;

    /**
     * Fetch products from the API and re-render the table body.
     */
    async function fetchProducts(query = '') {
        if (SPINNER) SPINNER.style.display = 'block';

        // Abort previous fetch if still pending
        if (activeFetch) activeFetch.abort();
        const controller = new AbortController();
        activeFetch = controller;

        try {
            const url = `/Collaborative_project/api/products.php${query ? '?q=' + encodeURIComponent(query) : ''}`;
            const res = await fetch(url, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();

            if (!json.success) throw new Error(json.error || 'Unknown error');

            renderTable(json.data);
            if (COUNT_EL) COUNT_EL.textContent = `${json.count} product${json.count !== 1 ? 's' : ''}`;

        } catch (err) {
            if (err.name === 'AbortError') return;   // stale request — ignore
            TBODY.innerHTML = `
                <tr><td colspan="7" class="text-center" style="padding:40px;color:var(--text-muted);">
                    ⚠️ Failed to load products. Please refresh.
                </td></tr>
            `;
        } finally {
            if (SPINNER) SPINNER.style.display = 'none';
            activeFetch = null;
        }
    }

    /**
     * Render rows from data array into TBODY.
     */
    function renderTable(products) {
        if (products.length === 0) {
            TBODY.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <div class="empty-title">No products found</div>
                        <div class="empty-desc">Try adjusting your search term</div>
                    </div>
                </td></tr>
            `;
            return;
        }

        TBODY.innerHTML = products.map(p => buildRow(p, isAdmin)).join('');

        // Stagger row animations
        TBODY.querySelectorAll('tr').forEach((row, i) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(6px)';
            row.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            requestAnimationFrame(() => {
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, i * 30);
            });
        });

        // Attach event listeners for new rows
        attachRowEvents();
    }

    /**
     * Attach delete and quick-update event handlers to newly rendered rows.
     */
    function attachRowEvents() {
        // Quick stock update buttons
        TBODY.querySelectorAll('.stock-quick-update').forEach(btn => {
            btn.addEventListener('click', () => openQuickStockModal(btn));
        });

        // Delete buttons (admin only)
        TBODY.querySelectorAll('.delete-product-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id   = btn.dataset.id;
                const name = btn.dataset.name;

                const ok = await confirmDialog(
                    `Delete <strong>${name}</strong>? This action cannot be undone.`,
                    'Delete Product'
                );
                if (!ok) return;

                try {
                    const res  = await fetch('/Collaborative_project/views/products.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete&product_id=${id}`,
                    });
                    const json = await res.json();
                    if (json.success) {
                        Toast.show(`Product "${name}" deleted.`, 'success');
                        fetchProducts(INPUT.value);
                    } else {
                        Toast.show(json.error || 'Delete failed.', 'danger');
                    }
                } catch (e) {
                    Toast.show('Network error.', 'danger');
                }
            });
        });
    }

    /**
     * Open the quick stock update inline modal.
     */
    function openQuickStockModal(btn) {
        const productId = btn.dataset.id;
        const name      = btn.dataset.name;
        const current   = btn.dataset.stock;

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal" style="max-width:380px;" role="dialog" aria-modal="true" aria-label="Update Stock">
                <div class="modal-header">
                    <span class="modal-title">📦 Update Stock</span>
                    <button class="modal-close" id="qsClose">✕</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                        <strong>${name}</strong><br>
                        Current stock: <strong>${current}</strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="qsNewStock">New Stock Level <span class="required">*</span></label>
                        <input type="number" id="qsNewStock" class="form-control"
                               value="${current}" min="0" max="99999" autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="qsNote">Change Note</label>
                        <input type="text" id="qsNote" class="form-control"
                               placeholder="e.g. Manual adjustment, received delivery…">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="qsCancel">Cancel</button>
                    <button class="btn btn-primary"   id="qsSave">💾 Save</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.getElementById('qsNewStock').select();

        const close = () => overlay.remove();

        overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
        document.getElementById('qsClose').addEventListener('click', close);
        document.getElementById('qsCancel').addEventListener('click', close);

        document.getElementById('qsSave').addEventListener('click', async () => {
            const newStock = parseInt(document.getElementById('qsNewStock').value, 10);
            const note     = document.getElementById('qsNote').value.trim();

            if (isNaN(newStock) || newStock < 0) {
                Toast.show('Enter a valid stock level (≥ 0).', 'warning');
                return;
            }

            try {
                const res  = await fetch('/Collaborative_project/views/stock_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update&product_id=${productId}&new_stock=${newStock}&note=${encodeURIComponent(note)}`,
                });
                const json = await res.json();

                if (json.success) {
                    Toast.show(`Stock updated to ${newStock}.`, 'success');
                    close();
                    fetchProducts(INPUT.value);
                    LowStockBadge.refresh();
                } else {
                    Toast.show(json.error || 'Update failed.', 'danger');
                }
            } catch (e) {
                Toast.show('Network error.', 'danger');
            }
        });
    }

    function init() {
        // Initial load
        fetchProducts();

        // Debounced search
        INPUT.addEventListener('input', debounce(e => {
            fetchProducts(e.target.value.trim());
        }, 280));

        // Clear on Escape
        INPUT.addEventListener('keydown', e => {
            if (e.key === 'Escape') { INPUT.value = ''; fetchProducts(); }
        });
    }

    return { init, fetchProducts };
})();

document.addEventListener('DOMContentLoaded', () => LiveSearch.init());

// Expose for external refresh calls (e.g., after adding a product)
window.LiveSearch = LiveSearch;
