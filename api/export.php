<?php
/**
 * api/export.php
 * Export Engine — Print-Ready Purchase Order / Invoice
 * ──────────────────────────────────────────────────────
 * GET /api/export.php?order_id=N
 *
 * Renders a fully styled, print-optimised HTML page for a purchase order.
 * The frontend calls window.open(this URL) and the user hits Ctrl+P or the
 * Print button inside the rendered page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

AuthController::startSession();
AuthController::requireAuth();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    http_response_code(400);
    die('Invalid order ID.');
}

$order = OrderController::getById($orderId);
if (!$order) {
    http_response_code(404);
    die(ERR_NOT_FOUND);
}

// ── Compute totals ────────────────────────────────────────────────────────────
$lineTotal = 0;
foreach ($order['items'] as $item) {
    $lineTotal += $item['quantity'] * $item['unit_cost'];
}
$tax      = round($lineTotal * 0.10, 2);   // 10% tax example
$grandTotal = round($lineTotal + $tax, 2);

$statusBadge = [
    'draft'     => '#94a3b8',
    'pending'   => '#f59e0b',
    'ordered'   => '#6366f1',
    'received'  => '#22c55e',
    'cancelled' => '#ef4444',
];
$statusColor = $statusBadge[$order['order_status']] ?? '#94a3b8';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #<?= $order['id'] ?> — <?= htmlspecialchars(APP_NAME) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #f8fafc;
            padding: 40px;
        }
        .po-wrapper {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            overflow: hidden;
        }
        /* Header */
        .po-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #fff;
            padding: 32px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .po-brand { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .po-brand span { color: #6366f1; }
        .po-meta { text-align: right; font-size: 12px; opacity: 0.85; line-height: 1.7; }
        .po-meta strong { font-size: 20px; font-weight: 700; opacity: 1; display: block; }
        /* Status */
        .po-status-bar {
            background: #f1f5f9;
            padding: 12px 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fff;
            background: <?= $statusColor ?>;
        }
        /* Parties */
        .po-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .po-party {
            padding: 24px 40px;
        }
        .po-party:first-child {
            border-right: 1px solid #e2e8f0;
        }
        .po-party h4 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .po-party p { line-height: 1.8; color: #334155; }
        .po-party .company { font-weight: 700; font-size: 15px; color: #0f172a; }
        /* Line Items Table */
        .po-table-wrap { padding: 24px 40px; }
        .po-table-wrap h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 14px;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        thead th:last-child { text-align: right; }
        tbody td {
            padding: 12px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        tbody td:last-child { text-align: right; font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }
        .sku-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
        }
        /* Totals */
        .po-totals {
            padding: 0 40px 24px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 280px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #475569;
        }
        .totals-row:last-child {
            border-bottom: none;
            background: #1e293b;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
        }
        /* Footer */
        .po-footer {
            background: #f8fafc;
            padding: 20px 40px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #94a3b8;
        }
        .print-btn {
            display: inline-block;
            padding: 10px 24px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #4f46e5; }
        @media print {
            body { background: #fff; padding: 0; }
            .po-wrapper { box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
<div class="po-wrapper">

    <!-- Header -->
    <div class="po-header">
        <div>
            <div class="po-brand">Inven<span>Track</span> Pro</div>
            <div style="margin-top:6px; font-size:12px; opacity:0.7;">
                <?= htmlspecialchars(EXPORT_COMPANY_ADDRESS) ?><br>
                <?= htmlspecialchars(EXPORT_COMPANY_PHONE) ?> &nbsp;·&nbsp; <?= htmlspecialchars(EXPORT_COMPANY_EMAIL) ?>
            </div>
        </div>
        <div class="po-meta">
            <strong>PURCHASE ORDER</strong>
            PO #<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?><br>
            Issued: <?= date('M d, Y', strtotime($order['created_at'])) ?><br>
            Updated: <?= date('M d, Y', strtotime($order['updated_at'])) ?>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="po-status-bar">
        Status: <span class="status-pill"><?= htmlspecialchars(strtoupper($order['order_status'])) ?></span>
        <?php if (!empty($order['notes'])): ?>
            &nbsp;·&nbsp; <span style="color:#64748b;font-size:12px;"><?= htmlspecialchars($order['notes']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Parties -->
    <div class="po-parties">
        <div class="po-party">
            <h4>Bill From (Buyer)</h4>
            <p class="company"><?= htmlspecialchars($_SESSION[SESSION_COMPANY] ?? APP_NAME) ?></p>
            <p><?= htmlspecialchars(EXPORT_COMPANY_ADDRESS) ?></p>
            <p><?= htmlspecialchars(EXPORT_COMPANY_EMAIL) ?></p>
            <p><?= htmlspecialchars(EXPORT_COMPANY_PHONE) ?></p>
        </div>
        <div class="po-party">
            <h4>Bill To (Supplier)</h4>
            <p class="company"><?= htmlspecialchars($order['supplier_name']) ?></p>
            <?php if (!empty($order['supplier_address'])): ?>
            <p><?= htmlspecialchars($order['supplier_address']) ?></p>
            <?php endif; ?>
            <p><?= htmlspecialchars($order['supplier_email']) ?></p>
            <?php if (!empty($order['supplier_phone'])): ?>
            <p><?= htmlspecialchars($order['supplier_phone']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Line Items -->
    <div class="po-table-wrap">
        <h4>Order Line Items</h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($order['items'] as $i => $item): ?>
                <tr>
                    <td style="color:#94a3b8;"><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><span class="sku-badge"><?= htmlspecialchars($item['sku']) ?></span></td>
                    <td><?= number_format($item['quantity']) ?></td>
                    <td>$<?= number_format($item['unit_cost'], 2) ?></td>
                    <td>$<?= number_format($item['quantity'] * $item['unit_cost'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="po-totals">
        <div class="totals-box">
            <div class="totals-row"><span>Subtotal</span><span>$<?= number_format($lineTotal, 2) ?></span></div>
            <div class="totals-row"><span>Tax (10%)</span><span>$<?= number_format($tax, 2) ?></span></div>
            <div class="totals-row"><span>TOTAL DUE</span><span>$<?= number_format($grandTotal, 2) ?></span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="po-footer">
        <span>Generated by <?= APP_NAME ?> v<?= APP_VERSION ?> &nbsp;·&nbsp; <?= date('Y-m-d H:i') ?></span>
        <button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>
    </div>

</div>
</body>
</html>
