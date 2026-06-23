<?php
/**
 * views/orders.php
 * Purchase Order Management — View, Status Update, Export
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

AuthController::requireAuth();

$isAdmin = AuthController::isAdmin();
$flash   = '';
$flashType = 'success';
$viewOrder = null;

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update_status') {
        $orderId = (int)$_POST['order_id'];
        $status  = $_POST['order_status'] ?? '';
        $rows    = OrderController::updateStatus($orderId, $status);
        header('Location: /Collaborative_project/views/orders.php?flash=status_updated');
        exit;
    }

    if ($postAction === 'delete_order') {
        header('Content-Type: application/json');
        OrderController::delete((int)$_POST['order_id']);
        echo json_encode(['success' => true]);
        exit;
    }
}

if (isset($_GET['flash'])) {
    $msgs  = ['status_updated' => 'Order status updated.'];
    $flash = $msgs[$_GET['flash']] ?? '';
}

// ── View single order detail
$detailId = (int)($_GET['id'] ?? 0);
if ($detailId > 0) {
    $viewOrder = OrderController::getById($detailId);
}

// ── Filter by status
$filterStatus = isset($_GET['status']) && in_array($_GET['status'], ORDER_STATUSES) ? $_GET['status'] : null;
$orders       = OrderController::getAll($filterStatus);

$pageTitle  = 'Purchase Orders';
$activePage = 'orders';

require_once __DIR__ . '/layout/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> animate-fade-in" data-auto-dismiss="5000" role="alert"><?= $flash ?></div>
<?php endif; ?>

<div class="page-header animate-fade-in">
    <div>
        <div class="page-title">Purchase Orders</div>
        <div class="page-subtitle"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found</div>
    </div>
</div>

<!-- Status Filter Tabs -->
<div style="display:flex;gap:8px;margin-bottom:var(--space-5);flex-wrap:wrap;" role="tablist" aria-label="Filter orders by status">
    <a href="/Collaborative_project/views/orders.php"
       class="btn <?= $filterStatus === null ? 'btn-primary' : 'btn-secondary' ?> btn-sm" role="tab">All</a>
    <?php foreach (ORDER_STATUSES as $s): ?>
    <a href="/Collaborative_project/views/orders.php?status=<?= $s ?>"
       class="btn <?= $filterStatus === $s ? 'btn-primary' : 'btn-secondary' ?> btn-sm status-<?= $s ?>" role="tab">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Order Detail Panel (shown when ?id= provided) -->
<?php if ($viewOrder): ?>
<div class="card mb-4 animate-fade-in">
    <div class="card-header">
        <span class="card-title">
            📋 Order #<?= str_pad((string)$viewOrder['id'], 5, '0', STR_PAD_LEFT) ?>
            &nbsp;<span class="status-pill status-<?= $viewOrder['order_status'] ?>"><?= ucfirst($viewOrder['order_status']) ?></span>
        </span>
        <div style="display:flex;gap:8px;">
            <a href="/Collaborative_project/api/export.php?order_id=<?= $viewOrder['id'] ?>"
               target="_blank" class="btn btn-secondary btn-sm">🖨️ Export PO</a>
            <a href="/Collaborative_project/views/orders.php" class="btn btn-ghost btn-sm">✕ Close</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-5);">
            <div>
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:6px;">Supplier</div>
                <div style="font-weight:700;"><?= htmlspecialchars($viewOrder['supplier_name']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($viewOrder['supplier_email']) ?></div>
            </div>
            <div>
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:6px;">Notes</div>
                <div style="font-size:13px;color:var(--text-secondary);"><?= htmlspecialchars($viewOrder['notes'] ?: '—') ?></div>
            </div>
        </div>
        <table class="data-table" aria-label="Order line items">
            <thead>
                <tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Cost</th><th>Line Total</th></tr>
            </thead>
            <tbody>
            <?php foreach ($viewOrder['items'] as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><span class="mono"><?= htmlspecialchars($item['sku']) ?></span></td>
                <td><?= number_format($item['quantity']) ?></td>
                <td>$<?= number_format($item['unit_cost'], 2) ?></td>
                <td style="font-weight:600;">$<?= number_format($item['quantity'] * $item['unit_cost'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="display:flex;justify-content:flex-end;margin-top:var(--space-4);">
            <div style="background:var(--bg-elevated);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:var(--space-4) var(--space-5);min-width:200px;">
                <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:var(--text-primary);">
                    <span>Total Cost</span>
                    <span>$<?= number_format($viewOrder['total_cost'], 2) ?></span>
                </div>
            </div>
        </div>
        <?php if ($isAdmin && $viewOrder['order_status'] !== ORDER_STATUS_RECEIVED && $viewOrder['order_status'] !== ORDER_STATUS_CANCELLED): ?>
        <div style="margin-top:var(--space-5);padding-top:var(--space-5);border-top:1px solid var(--border-color);">
            <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="hidden" name="action"   value="update_status">
                <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                <label class="form-label" style="margin:0;" for="order_status_sel">Update Status:</label>
                <select id="order_status_sel" name="order_status" class="form-control" style="width:auto;">
                    <?php foreach (ORDER_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $viewOrder['order_status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">✅ Update</button>
                <?php if ($viewOrder['order_status'] === ORDER_STATUS_DRAFT): ?>
                <button type="button" class="btn btn-danger btn-sm" id="deleteOrderBtn">🗑️ Delete Draft</button>
                <?php endif; ?>
            </form>
        </div>
        <script>
        document.getElementById('deleteOrderBtn')?.addEventListener('click', async () => {
            const ok = await confirmDialog('Delete this draft order? This cannot be undone.', 'Delete Order');
            if (!ok) return;
            const res  = await fetch('/Collaborative_project/views/orders.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete_order&order_id=<?= $viewOrder['id'] ?>',
            });
            const json = await res.json();
            if (json.success) { window.location.href = '/Collaborative_project/views/orders.php'; }
        });
        </script>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card animate-fade-in">
    <div class="card-body no-pad">
        <div class="table-wrap">
            <table class="data-table" aria-label="Purchase orders table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total Cost</th>
                        <th>Notes</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <div class="empty-title">No orders found</div>
                        <div class="empty-desc">Orders are auto-created when stock drops below reorder level.</div>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr <?= (int)$order['id'] === $detailId ? 'style="background:rgba(99,102,241,0.06);"' : '' ?>>
                    <td><span class="mono">#<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                    <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                    <td>
                        <span class="status-pill status-<?= $order['order_status'] ?>">
                            <?= ucfirst($order['order_status']) ?>
                        </span>
                    </td>
                    <td><?= $order['item_count'] ?></td>
                    <td style="font-weight:600;">$<?= number_format((float)$order['total_cost'], 2) ?></td>
                    <td style="font-size:12px;color:var(--text-muted);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($order['notes'] ?: '—') ?>
                    </td>
                    <td style="color:var(--text-muted);"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="/Collaborative_project/views/orders.php?id=<?= $order['id'] ?>"
                               class="btn btn-ghost btn-sm" title="View details">👁️</a>
                            <a href="/Collaborative_project/api/export.php?order_id=<?= $order['id'] ?>"
                               target="_blank"
                               class="btn btn-ghost btn-sm" title="Export PO">🖨️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
