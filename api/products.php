<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
AuthController::startSession();

// Support public storefront view without requiring authentication
$isPublic = isset($_GET['public']) && $_GET['public'] === '1';
$tenantId = null;

if ($isPublic) {
    $tenantId = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
} else {
    AuthController::requireAuth();
}

header('Content-Type: application/json; charset=utf-8');
try {
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $products = ProductController::getAll($search, $tenantId);
    $products = array_map(static function(array $p): array { $p['low_stock']=((int)$p['stock_level']<(int)$p['reorder_level']); return $p; }, $products);
    echo json_encode(['success'=>true,'count'=>count($products),'data'=>$products], JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
} catch (Throwable $e) { http_response_code(500); error_log('[API/products] '.$e->getMessage()); echo json_encode(['success'=>false,'error'=>ERR_SERVER]); }
