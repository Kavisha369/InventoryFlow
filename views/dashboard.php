<?php
/**
 * views/dashboard.php
 * Main Dashboard — KPI Cards, Chart, Low-Stock Alerts, Recent Orders
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

AuthController::requireAuth();

// Run low-stock automation check on every dashboard load
$newDraftCount = ProductController::runLowStockCheck();

$kpis       = DashboardController::getKPIs();
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/layout/header.php';
?>

<!-- Flash message if new draft POs were auto-created -->
<?php if ($newDraftCount > 0): ?>
<div class="alert alert-warning animate-fade-in" data-auto-dismiss="6000" role="alert">
    🤖 <strong><?= $newDraftCount ?> new draft purchase order<?= $newDraftCount > 1 ? 's' : '' ?></strong>
    auto-generated for low-stock products.
    <a href="/Collaborative_project/views/orders.php" style="color:inherit;font-weight:700;">View Orders →</a>
</div>
<?php endif; ?>

<!-- ── Page Header ──────────────────────────────────────────────────────── -->
<div class="page-header animate-fade-in">
    <div>
        <div class="page-title">Good <?= (date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening')) ?>, <?= htmlspecialchars(explode(' ', $_SESSION[SESSION_USERNAME])[0]) ?> 👋</div>
        <div class="page-subtitle">Here's what's happening across your inventory today.</div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="/Collaborative_project/views/products.php?action=new" class="btn btn-primary">
            ➕ Add Product
        </a>
        <a href="/Collaborative_project/views/orders.php" class="btn btn-secondary">
            📋 View Orders
        </a>
    </div>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="dashboard-grid animate-fade-in">

    <!-- Total Products -->
    <div class="kpi-card kpi-primary">
        <div class="kpi-header">
            <div class="kpi-label">Total Products</div>
            <div class="kpi-icon">📦</div>
        </div>
        <div class="kpi-value"><?= number_format($kpis['total_products']) ?></div>
        <div class="kpi-sub">Active SKUs in inventory</div>
    </div>

    <!-- Suppliers -->
    <div class="kpi-card kpi-success">
        <div class="kpi-header">
            <div class="kpi-label">Suppliers</div>
            <div class="kpi-icon">🏭</div>
        </div>
        <div class="kpi-value"><?= number_format($kpis['total_suppliers']) ?></div>
        <div class="kpi-sub">Active vendor relationships</div>
    </div>

    <!-- Open Orders -->
    <div class="kpi-card kpi-warning">
        <div class="kpi-header">
            <div class="kpi-label">Open Orders</div>
            <div class="kpi-icon">📋</div>
        </div>
        <div class="kpi-value"><?= number_format($kpis['pending_orders']) ?></div>
        <div class="kpi-sub">Draft, pending & ordered POs</div>
    </div>

    <!-- Low Stock Alert -->
    <div class="kpi-card kpi-danger">
        <div class="kpi-header">
            <div class="kpi-label">Low Stock</div>
            <div class="kpi-icon">⚠️</div>
        </div>
        <div class="kpi-value" style="color:var(--brand-danger);"><?= number_format($kpis['low_stock_count']) ?></div>
        <div class="kpi-sub">
            Items below reorder level
            <?php if ($kpis['low_stock_count'] > 0): ?>
                <span class="badge badge-danger" style="margin-left:6px;animation:pulse-badge 2s infinite;">ALERT</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Inventory Value Banner ────────────────────────────────────────────── -->
<div style="background:linear-gradient(135deg,#1e293b,#334155);border-radius:var(--radius-lg);padding:var(--space-5) var(--space-6);margin-bottom:var(--space-5);display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-md);" class="animate-fade-in">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(148,163,184,.7);margin-bottom:4px;">Total Inventory Value</div>
        <div style="font-size:32px;font-weight:800;color:#fff;letter-spacing:-1px;">
            $<?= number_format($kpis['inventory_value'], 2) ?>
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:12px;color:rgba(148,163,184,.6);">Across all active products</div>
        <div style="font-size:12px;color:rgba(148,163,184,.6);"><?= date('M d, Y H:i') ?></div>
    </div>
</div>

<!-- ── Chart + Low-Stock Panel Row ──────────────────────────────────────── -->
<div class="dashboard-row animate-fade-in">

    <!-- Stock Value Trend Chart -->
    <div class="chart-panel">
        <div class="chart-header">
            <div class="chart-title">📈 Stock Value Trend</div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <!-- Period selectors -->
                <div class="chart-controls" role="group" aria-label="Chart period">
                    <button class="chart-btn active" data-days="7"  id="chart7d">7D</button>
                    <button class="chart-btn"        data-days="30" id="chart30d">30D</button>
                    <button class="chart-btn"        data-days="60" id="chart60d">60D</button>
                </div>
                <!-- Dataset toggle -->
                <div class="chart-controls" role="group" aria-label="Chart dataset" style="margin-left:8px;">
                    <button class="chart-btn active" data-dataset="0">💰 Value</button>
                    <button class="chart-btn"        data-dataset="1">📦 Units</button>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="chart-legend">
            <div class="legend-item">
                <div class="legend-dot" style="background:#6366f1;"></div>
                <span>Stock Value (USD)</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#22d3ee;"></div>
                <span>Total Units</span>
            </div>
        </div>

        <!-- Canvas -->
        <div class="chart-canvas-wrap" style="position:relative;">
            <div class="chart-loading" style="display:flex;padding:60px 0;gap:10px;justify-content:center;align-items:center;color:var(--text-muted);font-size:13px;">
                <div class="spinner"></div> Loading chart data…
            </div>
            <canvas id="stockChart" height="240" aria-label="Stock value trend chart" role="img"></canvas>
        </div>
    </div>

    <!-- Low-Stock Alert Panel -->
    <div class="alert-panel">
        <div class="alert-panel-header">
            <div class="alert-panel-title">
                ⚠️ Low Stock Alerts
            </div>
            <?php if ($kpis['low_stock_count'] > 0): ?>
            <div class="alert-panel-count" aria-label="<?= $kpis['low_stock_count'] ?> alerts">
                <?= $kpis['low_stock_count'] ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($kpis['low_stock_items'])): ?>
            <div class="empty-state" style="padding:var(--space-8) var(--space-4);">
                <div class="empty-icon">✅</div>
                <div class="empty-title">All stocked up!</div>
                <div class="empty-desc">No products below reorder level.</div>
            </div>
        <?php else: ?>
            <?php foreach ($kpis['low_stock_items'] as $item):
                $ratio    = $item['reorder_level'] > 0 ? $item['stock_level'] / $item['reorder_level'] : 0;
                $pct      = min(100, round($ratio * 100));
                $urgency  = $ratio <= 0.25 ? 'critical' : 'warning';
                $barColor = $urgency === 'critical' ? 'critical' : 'warning';
            ?>
            <div class="alert-item <?= $urgency ?>">
                <div class="alert-item-icon">
                    <?= $urgency === 'critical' ? '🔴' : '🟡' ?>
                </div>
                <div class="alert-item-info">
                    <div class="alert-item-name" title="<?= htmlspecialchars($item['name']) ?>">
                        <?= htmlspecialchars($item['name']) ?>
                    </div>
                    <div class="alert-item-stock">
                        Stock: <strong><?= $item['stock_level'] ?></strong> /
                        Reorder: <?= $item['reorder_level'] ?>
                    </div>
                </div>
                <div class="stock-bar-wrap">
                    <div class="stock-bar">
                        <div class="stock-bar-fill <?= $barColor ?>" style="width:<?= $pct ?>%;"></div>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);text-align:right;margin-top:2px;"><?= $pct ?>%</div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="padding:var(--space-3) var(--space-5);">
                <a href="/Collaborative_project/views/orders.php" class="btn btn-danger btn-sm w-full" style="justify-content:center;">
                    View Draft POs →
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Recent Orders Table ───────────────────────────────────────────────── -->
<div class="orders-table-wrap animate-fade-in">
    <div class="card-header">
        <span class="card-title">🕒 Recent Purchase Orders</span>
        <a href="/Collaborative_project/views/orders.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-wrap">
        <table class="data-table" aria-label="Recent purchase orders">
            <thead>
                <tr>
                    <th>PO #</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total Cost</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $recentOrders = array_slice($kpis['recent_orders'], 0, 8);
            if (empty($recentOrders)):
            ?>
                <tr><td colspan="7">
                    <div class="empty-state"><div class="empty-icon">📋</div><div class="empty-title">No orders yet</div></div>
                </td></tr>
            <?php else: ?>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><span class="mono">#<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                    <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                    <td>
                        <span class="status-pill status-<?= htmlspecialchars($order['order_status']) ?>">
                            <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                        </span>
                    </td>
                    <td><?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></td>
                    <td style="font-weight:600;">$<?= number_format((float)$order['total_cost'], 2) ?></td>
                    <td style="color:var(--text-muted);"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="/Collaborative_project/views/orders.php?id=<?= $order['id'] ?>"
                               class="btn btn-ghost btn-sm" title="View order">👁️</a>
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

<?php require_once __DIR__ . '/layout/footer.php'; ?>
