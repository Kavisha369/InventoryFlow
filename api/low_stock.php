<?php
/**
 * api/low_stock.php
 * JSON API — Low-Stock Alert Feed
 * ────────────────────────────────
 * GET /api/low_stock.php
 *
 * Returns all products where stock_level < reorder_level.
 * Used by the pulsing alert badge and sidebar notification panel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

AuthController::startSession();
AuthController::requireAuth();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    $items = ProductController::getLowStock();

    // Enrich with shortage quantity and urgency tier
    $enriched = array_map(static function (array $p): array {
        $shortage = (int)$p['reorder_level'] - (int)$p['stock_level'];
        $ratio    = $p['reorder_level'] > 0
                    ? $p['stock_level'] / $p['reorder_level']
                    : 0;

        $p['shortage']   = $shortage;
        $p['urgency']    = $ratio <= 0.25 ? 'critical' : 'warning';
        return $p;
    }, $items);

    echo json_encode([
        'success' => true,
        'count'   => count($enriched),
        'data'    => $enriched,
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('[API/low_stock] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => ERR_SERVER]);
}
