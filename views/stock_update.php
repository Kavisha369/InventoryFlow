<?php
/**
 * views/stock_update.php
 * Stock Level Update — Accessible to Both Admin & Staff
 * ──────────────────────────────────────────────────────
 * POST handler also serves AJAX requests from live_search.js quick-update modal.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/StockController.php';

AuthController::requireAuth();   // Both admin and staff can access this

// ── AJAX POST Handler ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update') {
        header('Content-Type: application/json');

        $productId = (int)($_POST['product_id'] ?? 0);
        $newStock  = isset($_POST['new_stock']) ? (int)$_POST['new_stock'] : null;
        $note      = trim($_POST['note'] ?? 'Stock updated via admin panel');

        if ($productId <= 0 || $newStock === null || $newStock < 0) {
            echo json_encode(['success' => false, 'error' => ERR_INVALID_INPUT]);
            exit;
        }

        $ok = StockController::updateStock($productId, $newStock, $note);

        if ($ok) {
            // Run low-stock check after update — may auto-create draft PO
            ProductController::checkAndTriggerDraftPO($productId);
            echo json_encode(['success' => true, 'new_stock' => $newStock]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Stock update failed. Check product ID and permissions.']);
        }
        exit;
    }
}

// ── Page Data ─────────────────────────────────────────────────────────────────
$products    = ProductController::getAll();
$lowStock    = ProductController::getLowStock();
$pageTitle   = 'Stock Update';
$activePage  = 'stock';
$flash       = '';
$flashType   = 'success';

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header animate-fade-in">
    <div>
        <div class="page-title">Stock Update</div>
        <div class="page-subtitle">Update stock levels for any product. All changes are logged.</div>
    </div>
</div>

<!-- Quick Update Panel -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;" class="animate-fade-in">

    <!-- Product List with Inline Update -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">📦 All Products</span>
            <div class="search-bar-wrap" style="max-width:260px;">
                <span class="search-icon">🔍</span>
                <input type="search" id="stockFilterInput" class="form-control search-input"
                       placeholder="Filter products…" autocomplete="off">
            </div>
        </div>
        <div class="card-body no-pad">
            <div class="table-wrap">
                <table class="data-table" id="stockTable" aria-label="Products for stock update">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>New Stock</th>
                            <th>Note</th>
                            <th>Save</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                    <?php foreach ($products as $p):
                        $isLow  = ((int)$p['stock_level'] < (int)$p['reorder_level']);
                        $rowBg  = $isLow ? 'style="background:rgba(239,68,68,0.03);"' : '';
                    ?>
                    <tr <?= $rowBg ?> data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if ($isLow): ?>
                            <span class="badge badge-danger" style="font-size:9px;animation:pulse-badge 2s infinite;">LOW STOCK</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="mono"><?= htmlspecialchars($p['sku']) ?></span></td>
                        <td>
                            <span id="current-<?= $p['id'] ?>" style="font-weight:700;<?= $isLow ? 'color:var(--brand-danger);' : '' ?>">
                                <?= $p['stock_level'] ?>
                            </span>
                        </td>
                        <td><?= $p['reorder_level'] ?></td>
                        <td>
                            <input type="number"
                                   id="new-stock-<?= $p['id'] ?>"
                                   class="form-control stock-input"
                                   data-product-id="<?= $p['id'] ?>"
                                   data-product-name="<?= htmlspecialchars($p['name']) ?>"
                                   value="<?= $p['stock_level'] ?>"
                                   min="0" max="99999"
                                   style="width:90px;padding:6px 8px;">
                        </td>
                        <td>
                            <input type="text"
                                   id="note-<?= $p['id'] ?>"
                                   class="form-control"
                                   placeholder="Reason…"
                                   style="width:130px;padding:6px 8px;font-size:12px;">
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm save-stock-btn"
                                    data-product-id="<?= $p['id'] ?>">
                                💾
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Summary -->
    <div class="alert-panel">
        <div class="alert-panel-header">
            <div class="alert-panel-title">⚠️ Needs Attention</div>
            <?php if (count($lowStock) > 0): ?>
            <div class="alert-panel-count"><?= count($lowStock) ?></div>
            <?php endif; ?>
        </div>
        <?php if (empty($lowStock)): ?>
            <div class="empty-state" style="padding:var(--space-8) var(--space-4);">
                <div class="empty-icon">✅</div>
                <div class="empty-title">All good!</div>
                <div class="empty-desc">No low-stock items.</div>
            </div>
        <?php else: ?>
        <?php foreach ($lowStock as $item):
            $shortage = $item['reorder_level'] - $item['stock_level'];
        ?>
        <div class="alert-item">
            <div class="alert-item-icon">⚠️</div>
            <div class="alert-item-info">
                <div class="alert-item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="alert-item-stock">
                    Stock: <strong style="color:var(--brand-danger);"><?= $item['stock_level'] ?></strong>
                    | Need: +<?= $shortage ?>
                </div>
            </div>
            <button class="btn btn-primary btn-sm"
                    onclick="
                        document.getElementById('new-stock-<?= $item['id'] ?>').value = <?= $item['reorder_level'] * REORDER_MULTIPLIER ?>;
                        document.getElementById('new-stock-<?= $item['id'] ?>').focus();
                        document.getElementById('note-<?= $item['id'] ?>').value = 'Restocked to reorder level';
                    ">
                Fix
            </button>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- History Toggle (last 20 changes) -->
<div class="card mt-4 animate-fade-in">
    <div class="card-header">
        <span class="card-title">📜 Recent Stock Changes</span>
        <button class="btn btn-ghost btn-sm" id="toggleHistory">Show</button>
    </div>
    <div id="historySection" style="display:none;">
        <?php
        // Fetch recent stock history across all products for this tenant
        $tid = AuthController::tenantId();
        require_once __DIR__ . '/../config/db.php';
        $history = DB::query(
            'SELECT sh.*, p.name AS product_name, p.sku, u.username
             FROM stock_history sh
             JOIN products p ON p.id = sh.product_id
             LEFT JOIN users u ON u.id = sh.changed_by
             WHERE sh.tenant_id = :tid
             ORDER BY sh.recorded_at DESC LIMIT 20',
            [':tid' => $tid]
        )->fetchAll();
        ?>
        <div class="table-wrap">
        <table class="data-table" aria-label="Stock history">
            <thead><tr><th>Product</th><th>SKU</th><th>Before</th><th>After</th><th>Δ Change</th><th>Note</th><th>By</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h):
                $delta = (int)$h['new_stock'] - (int)$h['old_stock'];
                $deltaColor = $delta > 0 ? 'var(--brand-success)' : ($delta < 0 ? 'var(--brand-danger)' : 'var(--text-muted)');
            ?>
            <tr>
                <td><?= htmlspecialchars($h['product_name']) ?></td>
                <td><span class="mono"><?= htmlspecialchars($h['sku']) ?></span></td>
                <td><?= $h['old_stock'] ?></td>
                <td style="font-weight:600;"><?= $h['new_stock'] ?></td>
                <td style="font-weight:700;color:<?= $deltaColor ?>;">
                    <?= $delta > 0 ? '+' : '' ?><?= $delta ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($h['change_note'] ?? '') ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($h['username'] ?? 'system') ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= date('M d, H:i', strtotime($h['recorded_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Filter input ─────────────────────────────────────────────────────────
    const filterInput = document.getElementById('stockFilterInput');
    filterInput.addEventListener('input', () => {
        const q = filterInput.value.toLowerCase();
        document.querySelectorAll('#stockTableBody tr').forEach(row => {
            row.style.display = row.dataset.name.includes(q) ? '' : 'none';
        });
    });

    // ── Save stock buttons ────────────────────────────────────────────────────
    document.querySelectorAll('.save-stock-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const pid      = btn.dataset.productId;
            const input    = document.getElementById(`new-stock-${pid}`);
            const noteEl   = document.getElementById(`note-${pid}`);
            const newStock = parseInt(input.value, 10);
            const note     = noteEl.value.trim() || 'Manual stock update';

            if (isNaN(newStock) || newStock < 0) {
                Toast.show('Enter a valid stock level.', 'warning');
                return;
            }

            btn.disabled    = true;
            btn.textContent = '…';

            try {
                const res  = await fetch('/Collaborative_project/views/stock_update.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update&product_id=${pid}&new_stock=${newStock}&note=${encodeURIComponent(note)}`,
                });
                const json = await res.json();

                if (json.success) {
                    Toast.show(`Stock updated to ${newStock}.`, 'success');
                    const curEl = document.getElementById(`current-${pid}`);
                    if (curEl) {
                        curEl.textContent = newStock;
                        // Highlight briefly
                        curEl.style.transition = 'color 0.3s';
                        curEl.style.color = '#22c55e';
                        setTimeout(() => curEl.style.color = '', 2000);
                    }
                    noteEl.value = '';
                    LowStockBadge.refresh();
                } else {
                    Toast.show(json.error || 'Update failed.', 'danger');
                }
            } catch (e) {
                Toast.show('Network error.', 'danger');
            } finally {
                btn.disabled    = false;
                btn.textContent = '💾';
            }
        });
    });

    // ── History toggle ────────────────────────────────────────────────────────
    const toggleBtn     = document.getElementById('toggleHistory');
    const historySection = document.getElementById('historySection');
    toggleBtn.addEventListener('click', () => {
        const open = historySection.style.display === 'block';
        historySection.style.display = open ? 'none' : 'block';
        toggleBtn.textContent = open ? 'Show' : 'Hide';
    });

});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
