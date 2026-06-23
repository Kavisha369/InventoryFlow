<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/SupplierController.php';
require_once __DIR__ . '/../controllers/OrderController.php';
class DashboardController {
    public static function getKPIs(): array {
        return [
            'total_products'  => ProductController::countAll(),
            'total_suppliers' => SupplierController::countAll(),
            'pending_orders'  => OrderController::countPending(),
            'low_stock_count' => ProductController::countLowStock(),
            'inventory_value' => ProductController::totalInventoryValue(),
            'low_stock_items' => ProductController::getLowStock(),
            'recent_orders'   => OrderController::getAll(),
        ];
    }
}
